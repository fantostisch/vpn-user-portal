<?php

/*
 * eduVPN - End-user friendly VPN.
 *
 * Copyright: 2016-2019, The Commons Conservancy eduVPN Programme
 * SPDX-License-Identifier: AGPL-3.0+
 */

namespace LC\Portal\WireGuard\Manager;

use LC\Common\Http\Exception\HttpException;
use LC\Common\Http\InputValidation;
use LC\Common\HttpClient\ServerClient;
use LC\Portal\Storage;
use LC\Portal\Tpl;
use LC\Portal\WireGuard\Daemon\NoIPAvailableException;
use LC\Portal\WireGuard\Daemon\WGDaemonClient;
use LC\Portal\WireGuard\Storage\WGStorageClientConfig;
use LC\Portal\WireGuard\Validator\TypeCreator;
use LC\Portal\WireGuard\Validator\ValidationError;

class WGManager
{
    /** @var WGEnabledConfig */
    private $config;

    /** @var Storage */
    private $storage;

    /** @var WGDaemonClient */
    private $daemonClient;

    /** @var Tpl */
    private $tpl;

    /** @var \LC\Common\HttpClient\ServerClient */
    private $serverClient;

    /**
     * @param string $baseDir
     */
    public function __construct(WGEnabledConfig $portalConfig, Storage $storage, WGDaemonClient $daemonClient, $baseDir, ServerClient $serverClient)
    {
        $this->config = $portalConfig;
        $this->storage = $storage;
        $this->daemonClient = $daemonClient;
        $this->tpl = new Tpl([sprintf('%s/views', $baseDir)], [], sprintf('%s/web', $baseDir));
        $this->serverClient = $serverClient;
    }

    /**
     * @return WGEnabledConfig
     */
    public function getPortalConfig()
    {
        return $this->config;
    }

    /**
     * @param string $userId
     * @param bool   $all    if false, only return configs created in the portal
     *
     * @throws HttpException
     *
     * @return array<WGClientConfig>
     */
    public function getConfigs($userId, $all = true)
    {
        $daemonConfigs = $this->daemonClient->getConfigs($userId);
        $storageConfigs = $this->storage->getWGConfigs($userId);

        $configs = [];
        foreach ($daemonConfigs as $publicKey => $daemonConfig) {
            if (\array_key_exists($publicKey, $storageConfigs)) {
                $storageConfig = $storageConfigs[$publicKey];
            } else {
                // Storage is not in sync with daemon
                $storageConfig = new WGStorageClientConfig($publicKey, 'unknown', null);
            }
            $filter = !$all && null !== $storageConfig->clientId;
            if (!($filter)) {
                $configs[$publicKey] = WGClientConfig::from($daemonConfig, $storageConfig);
            }
        }

        return $configs;
    }

    /**
     * @param string      $userId
     * @param string      $displayName
     * @param string|null $clientId
     * @param string|null $publicKey
     *
     * @throws \LC\Common\Http\Exception\InputValidationException
     *
     * @return string
     */
    public function addConfig($userId, $displayName, $clientId, $publicKey = null)
    {
        $displayName = InputValidation::displayName($displayName);
        $clientId = null === $clientId ? null : InputValidation::clientId($clientId);
        try {
            if (\is_string($publicKey)) {
                $response = $this->daemonClient->createConfig($userId, $publicKey);
                $ip = $response->ip;
                $clientPrivateKey = null;
                $serverPublicKey = $response->serverPublicKey;
            } else {
                $kpResponse = $this->daemonClient->createConfig($userId);
                $ip = $kpResponse->ip;
                $publicKey = $kpResponse->clientPublicKey;
                $clientPrivateKey = $kpResponse->clientPrivateKey;
                $serverPublicKey = $kpResponse->serverPublicKey;
            }
        } catch (NoIPAvailableException $_) {
            if (!$this->removeUnusedConfigs()) {
                $message = 'No IP addresses are available.';
                throw new HttpException($message, 500);
            }

            return $this->addConfig($userId, $displayName, $clientId, $publicKey);
        }

        try {
            $this->storage->addWGConfig($userId, $publicKey, $displayName, $clientId);
        } catch (\PDOException $e) {
            $this->daemonClient->deleteConfig($userId, $publicKey);
            throw $e;
        }
        $wgConfigFile = $this->tpl->render(
            'WGConfigurationFile',
            [
                'hostName' => $this->config->hostName,
                'port' => $this->config->port,
                'clientIp' => $ip,
                'serverPublicKey' => $serverPublicKey,
                'clientPrivateKey' => $clientPrivateKey,
                'dnsServers' => $this->config->dns,
            ]
        );

        return $wgConfigFile;
    }

    /**
     * @param string      $userId
     * @param string      $publicKey
     * @param string|null $clientId
     *
     * @throws HttpException
     *
     * @return void
     */
    public function deleteConfig($userId, $publicKey, $clientId)
    {
        if (null !== $clientId) {
            $storageConfig = $this->storage->getWGConfig($userId, $publicKey);
            if (null !== $storageConfig) {
                if ($storageConfig->clientId !== $clientId) {
                    // How did a client that did not create this config know that this config exists?
                    return;
                }
            }
        }

        $this->daemonClient->deleteConfig($userId, $publicKey);
        $this->storage->deleteWGConfig($userId, $publicKey);
    }

    /**
     * @psalm-type userID=string
     *
     * @throws HttpException
     *
     * @return array<userID, array<WGClientConnection>>
     */
    public function getClientConnections()
    {
        $daemonUserConnections = $this->daemonClient->getClientConnections();

        $connections = [];
        foreach ($daemonUserConnections as $userId => $daemonConnections) {
            $connections[$userId] = array_map(function ($daemonConnection) use ($userId) {
                $storageConfig = $this->storage->getWGConfig($userId, $daemonConnection->publicKey);
                if (null === $storageConfig) {
                    // Storage is not in sync with daemon
                    $storageConfig = new WGStorageClientConfig($daemonConnection->publicKey, 'unknown', null);
                }

                return WGClientConnection::from($daemonConnection, $storageConfig);
            }, $daemonConnections);
        }

        return $connections;
    }

    /**
     * @param string $userId
     *
     * @throws HttpException
     *
     * @return void
     */
    public function disableUser($userId)
    {
        $this->daemonClient->disableUser($userId);
    }

    /**
     * @param string $userId
     *
     * @throws HttpException
     *
     * @return void
     */
    public function enableUser($userId)
    {
        $this->daemonClient->enableUser($userId);
    }

    /**
     * First remove all configs not created in the portal of all disabled users. If no configs are removed, remove all
     * configs of all disabled users. If no configs are removed, remove all configs not created in te portal that have
     * not been used in 1 day (todo). Clients should have send a disconnect call which should have removed these configs,
     * but this call might not always reach the api.
     *
     * @throws HttpException
     *
     * @return bool if we removed at least 1 config
     */
    private function removeUnusedConfigs()
    {
        // Prevent exceptions from leaking info about other users.
        try {
            $users = $this->serverClient->getRequireArray('user_list');

            /** @var array<VPNServerAPIUser>|array<ValidationError> $users */
            $users = TypeCreator::createType("array<LC\Portal\WireGuard\Manager\VPNServerAPIUser>", $users);
            if (!ValidationError::isValid($users)) {
                throw new HttpException('Could not parse users from API', 500);
            }

            $removed = false;
            /** @var array<array{userId: string, publicKey: string}> $portalConfigsDisabledUsers */
            $portalConfigsDisabledUsers = [];
            /** @var array<VPNServerAPIUser> $users */
            foreach ($users as $user) {
                if ($user->disabled) {
                    $configs = $this->getConfigs($user->userId, true);
                    foreach ($configs as $publicKey => $config) {
                        if (null === $config->clientId) {
                            if (!$removed) {
                                array_push($portalConfigsDisabledUsers, [
                                    'userId' => $user->userId,
                                    'publicKey' => $publicKey,
                                ]);
                            }
                        } else {
                            $this->deleteConfig($user->userId, $publicKey, $config->clientId);
                            $removed = true;
                        }
                    }
                }
            }
            if ($removed) {
                return true;
            }

            foreach ($portalConfigsDisabledUsers as $userIdAndPublicKey) {
                $this->deleteConfig($userIdAndPublicKey['userId'], $userIdAndPublicKey['publicKey'], null);
                $removed = true;
            }
            if ($removed) {
                return true;
            }

            //todo: remove configs not created in the portal that have not been used in 1 day.

            return false;
        } catch (\Exception $ex) {
            throw new HttpException('Error removing unused WireGuard configurations.', 500);
        }
    }
}
