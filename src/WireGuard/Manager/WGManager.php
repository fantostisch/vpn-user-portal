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
use LC\Common\TplInterface;
use LC\Portal\Storage;
use LC\Portal\WireGuard\Daemon\WGDaemonClient;
use LC\Portal\WireGuard\Storage\WGStorageClientConfig;

class WGManager
{
    /** @var WGEnabledConfig */
    private $config;

    /** @var Storage */
    private $storage;

    /** @var WGDaemonClient */
    private $daemonClient;

    public function __construct(WGEnabledConfig $portalConfig, Storage $storage, WGDaemonClient $daemonClient)
    {
        $this->config = $portalConfig;
        $this->storage = $storage;
        $this->daemonClient = $daemonClient;
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
     *
     * @throws HttpException
     *
     * @return array<WGClientConfig>
     */
    public function getConfigs($userId)
    {
        $daemonConfigs = $this->daemonClient->getConfigs($userId);
        $storageConfigs = $this->storage->getWGConfigs($userId);

        $configs = [];
        foreach ($daemonConfigs as $publicKey => $daemonConfig) {
            if (\array_key_exists($publicKey, $storageConfigs)) {
                $storageConfig = $storageConfigs[$publicKey];
            } else {
                // Storage is not in sync with daemon
                $storageConfig = new WGStorageClientConfig($publicKey, 'unknown', 'unknown');
            }
            $configs[$publicKey] = WGClientConfig::from($daemonConfig, $storageConfig);
        }

        return $configs;
    }

    /**
     * @param string $userId
     * @param string $displayName
     *
     * @throws \LC\Common\Http\Exception\InputValidationException
     *
     * @return string
     */
    public function addConfig($userId, $displayName, TplInterface $tpl)
    {
        $validatedDisplayName = InputValidation::displayName($displayName);
        $createResponse = $this->daemonClient->createConfig($userId);
        try {
            $this->storage->addWGConfig($userId, $createResponse->clientPublicKey, $validatedDisplayName, null);
        } catch (\PDOException $e) {
            $this->daemonClient->deleteConfig($userId, $createResponse->clientPublicKey);
            throw $e;
        }
        $wgConfigFile = $tpl->render(
            'vpnPortalWGConfigurationFile',
            [
                'hostName' => $this->config->hostName,
                'port' => $this->config->port,
                'clientIp' => $createResponse->ip,
                'serverPublicKey' => $createResponse->serverPublicKey,
                'clientPrivateKey' => $createResponse->clientPrivateKey,
                'dnsServers' => $this->config->dns,
            ]
        );

        return $wgConfigFile;
    }

    /**
     * @param string $userId
     * @param string $publicKey
     *
     * @throws HttpException
     *
     * @return void
     */
    public function deleteConfig($userId, $publicKey)
    {
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
                    $storageConfig = new WGStorageClientConfig($daemonConnection->publicKey, 'unknown', 'unknown');
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
}
