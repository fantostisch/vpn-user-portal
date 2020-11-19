<?php

/*
 * eduVPN - End-user friendly VPN.
 *
 * Copyright: 2016-2019, The Commons Conservancy eduVPN Programme
 * SPDX-License-Identifier: AGPL-3.0+
 */

namespace LC\Portal\WireGuard\Manager;

use LC\Portal\WireGuard\Daemon\WGDaemonClientConfig;
use LC\Portal\WireGuard\Storage\WGStorageClientConfig;

/**
 * @psalm-immutable
 */
class WGClientConfig
{
    /** @var string */
    public $name;

    /** @var string */
    public $ip;

    /** @var string */
    public $modified;

    /** @var string|null */
    public $clientId;

    /**
     * @param string      $name
     * @param string      $ip
     * @param string      $modified
     * @param string|null $clientId
     */
    public function __construct($name, $ip, $modified, $clientId)
    {
        $this->name = $name;
        $this->ip = $ip;
        $this->modified = $modified;
        $this->clientId = $clientId;
    }

    /**
     * @return WGClientConfig
     */
    public static function from(WGDaemonClientConfig $daemonData, WGStorageClientConfig $storageData)
    {
        return new self($storageData->name, $daemonData->ip, $daemonData->modified, $storageData->clientId);
    }
}
