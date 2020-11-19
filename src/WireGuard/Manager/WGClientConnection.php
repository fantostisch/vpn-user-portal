<?php

/*
 * eduVPN - End-user friendly VPN.
 *
 * Copyright: 2016-2019, The Commons Conservancy eduVPN Programme
 * SPDX-License-Identifier: AGPL-3.0+
 */

namespace LC\Portal\WireGuard\Manager;

use LC\Portal\WireGuard\Daemon\WGDaemonClientConnection;
use LC\Portal\WireGuard\Storage\WGStorageClientConfig;

/**
 * @psalm-immutable
 */
class WGClientConnection
{
    /** @var string */
    public $publicKey;

    /** @var string */
    public $name;

    /** @var string|null */
    public $clientId;

    /** @var array<string> */
    public $allowedIPs;

    /**
     * @param string        $publicKey
     * @param string        $name
     * @param string|null   $clientId
     * @param array<string> $allowedIPs
     */
    public function __construct($publicKey, $name, $clientId, array $allowedIPs)
    {
        $this->publicKey = $publicKey;
        $this->name = $name;
        $this->clientId = $clientId;
        $this->allowedIPs = $allowedIPs;
    }

    /**
     * @return WGClientConnection
     */
    public static function from(WGDaemonClientConnection $daemonConnection, WGStorageClientConfig $storageConfig)
    {
        return new self($daemonConnection->publicKey, $storageConfig->name, $storageConfig->clientId, $daemonConnection->allowedIPs);
    }
}
