<?php

/*
 * eduVPN - End-user friendly VPN.
 *
 * Copyright: 2016-2019, The Commons Conservancy eduVPN Programme
 * SPDX-License-Identifier: AGPL-3.0+
 */

namespace LC\Portal\WireGuard\Manager;

use LC\Common\Config;
use LC\Common\Exception\ConfigException;
use LC\Portal\WireGuard\Validator\TypeCreator;

/**
 * @psalm-immutable
 */
class WGEnabledConfig
{
    /** @var string */
    public $hostName;

    /** @var int */
    public $port;

    /** @var string */
    public $daemonUri;

    /** @var array<string> */
    public $dns;

    /**
     * @param string        $hostName
     * @param int           $port
     * @param string        $daemonUri
     * @param array<string> $dns
     */
    public function __construct($hostName, array $dns, $port = 51820, $daemonUri = 'http://localhost:8080')
    {
        $this->hostName = $hostName;
        $this->port = $port;
        $this->daemonUri = $daemonUri;
        $this->dns = $dns;
    }

    /**
     * @throws ConfigException
     *
     * @return false|WGEnabledConfig
     */
    public static function fromConfig(Config $config)
    {
        $wgProvidedConfig = $config->s('WireGuard');

        if (!(true === $wgProvidedConfig->optionalBool('enabled'))) {
            return false;
        }

        return TypeCreator::createTypeThrowIfError(
            "\LC\Portal\WireGuard\Manager\WGEnabledConfig",
            $wgProvidedConfig->toArray(),
            'Could not parse WireGuard configuration.');
    }
}
