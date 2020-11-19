<?php

/*
 * eduVPN - End-user friendly VPN.
 *
 * Copyright: 2016-2019, The Commons Conservancy eduVPN Programme
 * SPDX-License-Identifier: AGPL-3.0+
 */

namespace LC\Portal;

use DateInterval;
use DateTime;
use fkooman\OAuth\Server\StorageInterface;
use fkooman\SAML\SP\Web\Exception\HttpException;
use fkooman\SqliteMigrate\Migration;
use LC\Common\Http\CredentialValidatorInterface;
use LC\Common\Http\UserInfo;
use LC\Portal\WireGuard\Storage\WGStorageClientConfig;
use LC\Portal\WireGuard\Validator\TypeCreator;
use PDO;

class Storage implements CredentialValidatorInterface, StorageInterface
{
    const CURRENT_SCHEMA_VERSION = '2020111701';

    /** @var \PDO */
    private $db;

    /** @var \DateTime */
    private $dateTime;

    /** @var \fkooman\SqliteMigrate\Migration */
    private $migration;

    /** @var \DateInterval */
    private $sessionExpiry;

    /**
     * @param string $schemaDir
     */
    public function __construct(PDO $db, $schemaDir, DateInterval $sessionExpiry)
    {
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        if ('sqlite' === $db->getAttribute(PDO::ATTR_DRIVER_NAME)) {
            $db->exec('PRAGMA foreign_keys = ON');
        }
        $this->db = $db;
        $this->migration = new Migration($db, $schemaDir, self::CURRENT_SCHEMA_VERSION);
        $this->sessionExpiry = $sessionExpiry;
        $this->dateTime = new DateTime();
    }

    /**
     * @return void
     */
    public function setDateTime(DateTime $dateTime)
    {
        $this->dateTime = $dateTime;
    }

    /**
     * @param string $authUser
     * @param string $authPass
     *
     * @return false|UserInfo
     */
    public function isValid($authUser, $authPass)
    {
        $stmt = $this->db->prepare(
            'SELECT
                password_hash
             FROM users
             WHERE
                user_id = :user_id'
        );

        $stmt->bindValue(':user_id', $authUser, PDO::PARAM_STR);
        $stmt->execute();
        $dbHash = $stmt->fetchColumn(0);
        $isVerified = password_verify($authPass, $dbHash);
        if ($isVerified) {
            return new UserInfo($authUser, []);
        }

        return false;
    }

    /**
     * @param string $userId
     * @param string $userPass
     *
     * @return void
     */
    public function add($userId, $userPass)
    {
        if ($this->userExists($userId)) {
            $this->updatePassword($userId, $userPass);

            return;
        }

        $stmt = $this->db->prepare(
            'INSERT INTO
                users (user_id, password_hash, created_at)
            VALUES
                (:user_id, :password_hash, :created_at)'
        );

        $passwordHash = password_hash($userPass, PASSWORD_DEFAULT);
        $stmt->bindValue(':user_id', $userId, PDO::PARAM_STR);
        $stmt->bindValue(':password_hash', $passwordHash, PDO::PARAM_STR);
        $stmt->bindValue(':created_at', $this->dateTime->format(DateTime::ATOM), PDO::PARAM_STR);
        $stmt->execute();
    }

    /**
     * @param string $authUser
     *
     * @return bool
     */
    public function userExists($authUser)
    {
        $stmt = $this->db->prepare(
            'SELECT
                COUNT(*)
             FROM users
             WHERE
                user_id = :user_id'
        );

        $stmt->bindValue(':user_id', $authUser, PDO::PARAM_STR);
        $stmt->execute();

        return 1 === (int) $stmt->fetchColumn();
    }

    /**
     * @param string $userId
     * @param string $newUserPass
     *
     * @return bool
     */
    public function updatePassword($userId, $newUserPass)
    {
        $stmt = $this->db->prepare(
            'UPDATE
                users
             SET
                password_hash = :password_hash
             WHERE
                user_id = :user_id'
        );

        $passwordHash = password_hash($newUserPass, PASSWORD_DEFAULT);
        $stmt->bindValue(':user_id', $userId, PDO::PARAM_STR);
        $stmt->bindValue(':password_hash', $passwordHash, PDO::PARAM_STR);
        $stmt->execute();

        return 1 === $stmt->rowCount();
    }

    /**
     * @param string $authKey
     *
     * @return bool
     */
    public function hasAuthorization($authKey)
    {
        $stmt = $this->db->prepare(
            'SELECT
                auth_time
             FROM authorizations
             WHERE
                auth_key = :auth_key'
        );

        $stmt->bindValue(':auth_key', $authKey, PDO::PARAM_STR);
        $stmt->execute();

        if (false === $authTime = $stmt->fetchColumn()) {
            // auth_key does not exist (anymore)
            return false;
        }

        $authTimeDateTime = new DateTime($authTime);
        $expiresAt = date_add($authTimeDateTime, $this->sessionExpiry);

        return $expiresAt > $this->dateTime;
    }

    /**
     * @param string $userId
     * @param string $clientId
     * @param string $scope
     * @param string $authKey
     *
     * @return void
     */
    public function storeAuthorization($userId, $clientId, $scope, $authKey)
    {
        // the "authorizations" table has the UNIQUE constraint on the
        // "auth_key" column, thus preventing multiple entries with the same
        // "auth_key" to make absolutely sure "auth_keys" cannot be replayed
        $stmt = $this->db->prepare(
            'INSERT INTO authorizations (
                auth_key,
                user_id,
                client_id,
                scope,
                auth_time
             ) 
             VALUES(
                :auth_key,
                :user_id, 
                :client_id,
                :scope,
                :auth_time
             )'
        );

        $stmt->bindValue(':auth_key', $authKey, PDO::PARAM_STR);
        $stmt->bindValue(':user_id', $userId, PDO::PARAM_STR);
        $stmt->bindValue(':client_id', $clientId, PDO::PARAM_STR);
        $stmt->bindValue(':scope', $scope, PDO::PARAM_STR);
        $stmt->bindValue(':auth_time', $this->dateTime->format(DateTime::ATOM), PDO::PARAM_STR);
        $stmt->execute();
    }

    /**
     * @param string $userId
     *
     * @return array<array>
     */
    public function getAuthorizations($userId)
    {
        $stmt = $this->db->prepare(
            'SELECT
                auth_key,
                client_id,
                scope,
                auth_time
             FROM authorizations
             WHERE
                user_id = :user_id'
        );

        $stmt->bindValue(':user_id', $userId, PDO::PARAM_STR);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * @param string $authKey
     *
     * @return void
     */
    public function deleteAuthorization($authKey)
    {
        $stmt = $this->db->prepare(
            'DELETE FROM
                authorizations
             WHERE
                auth_key = :auth_key'
        );

        $stmt->bindValue(':auth_key', $authKey, PDO::PARAM_STR);
        $stmt->execute();
    }

    /**
     * @psalm-type publicKey=string
     *
     * @param string $userId
     *
     * @return array<publicKey, WGStorageClientConfig>
     */
    public function getWGConfigs($userId)
    {
        $stmt = $this->db->prepare(
            <<< 'SQL'
    SELECT
        user_id, 
        public_key, 
        display_name, 
        client_id
    FROM 
        wireguard_configs
    WHERE 
        user_id = :user_id
SQL
        );
        $stmt->bindValue(':user_id', $userId, PDO::PARAM_STR);
        $stmt->execute();

        /** @var array<WGStorageClientConfig> $storageConfigs */
        $storageConfigs = TypeCreator::createTypeThrowIfError(
            "array<\LC\Portal\WireGuard\Storage\WGStorageClientConfig>",
            $stmt->fetchAll(PDO::FETCH_ASSOC),
            'Invalid data in database! Could not convert data to client configurations.'
        );

        $configs = [];
        foreach ($storageConfigs as $storageConfig) {
            $configs[$storageConfig->publicKey] = $storageConfig;
        }

        return $configs;
    }

    /**
     * @param string $userId
     * @param string $publicKey
     *
     * @return WGStorageClientConfig|null
     */
    public function getWGConfig($userId, $publicKey)
    {
        $stmt = $this->db->prepare(
            <<< 'SQL'
    SELECT
        user_id,
        public_key,
        display_name,
        client_id
    FROM
        wireguard_configs
    WHERE
        user_id = :user_id
    AND
        public_key = :public_key
SQL
        );
        $stmt->bindValue(':user_id', $userId, PDO::PARAM_STR);
        $stmt->bindValue(':public_key', $publicKey, PDO::PARAM_STR);
        $stmt->execute();

        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $resultCount = \count($result);
        switch ($resultCount) {
            case 0:
                return null;
            case 1:
                break;
            default:
                throw new HttpException(500, 'Invalid data in database! '.$resultCount.' results, only 1 expected.');
        }

        return TypeCreator::createTypeThrowIfError(
            "\LC\Portal\WireGuard\Storage\WGStorageClientConfig",
            $result[0],
            'Invalid data in database! Could not convert data to client configuration.'
        );
    }

    /**
     * @param string      $userId
     * @param string      $publicKey
     * @param string      $displayName
     * @param string|null $clientId
     *
     * @return void
     */
    public function addWGConfig($userId, $publicKey, $displayName, $clientId)
    {
        $stmt = $this->db->prepare(
            <<< 'SQL'
    INSERT INTO wireguard_configs
        (user_id, public_key, display_name, client_id)
    VALUES
        (:user_id, :public_key, :display_name, :client_id)
SQL
        );
        $stmt->bindValue(':user_id', $userId, PDO::PARAM_STR);
        $stmt->bindValue(':public_key', $publicKey, PDO::PARAM_STR);
        $stmt->bindValue(':display_name', $displayName, PDO::PARAM_STR);
        $stmt->bindValue(':client_id', $clientId, PDO::PARAM_STR | PDO::PARAM_NULL);
        $stmt->execute();
    }

    /**
     * //todo: /delete_user in the api does not delete the WireGuard configurations.
     *
     * @param string $userId
     * @param string $publicKey
     *
     * @return void
     */
    public function deleteWGConfig($userId, $publicKey)
    {
        $stmt = $this->db->prepare(
            <<< 'SQL'
    DELETE FROM
        wireguard_configs
    WHERE
        user_id = :user_id
    AND
        public_key = :public_key
SQL
        );
        $stmt->bindValue(':user_id', $userId, PDO::PARAM_STR);
        $stmt->bindValue(':public_key', $publicKey, PDO::PARAM_STR);
        $stmt->execute();
    }

    /**
     * @return void
     */
    public function init()
    {
        $this->migration->init();
    }

    /**
     * @return void
     */
    public function update()
    {
        $this->migration->run();
    }
}
