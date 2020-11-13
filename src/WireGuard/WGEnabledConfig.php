<?php

/*
 * eduVPN - End-user friendly VPN.
 *
 * Copyright: 2016-2019, The Commons Conservancy eduVPN Programme
 * SPDX-License-Identifier: AGPL-3.0+
 */

namespace LC\Portal\WireGuard;

use LC\Common\Config;
use LC\Common\Exception\ConfigException;
use LC\Common\HttpClient\CurlHttpClient;

/**
 * @psalm-immutable
 */
class WGEnabledConfig
{
    /** @var \LC\Portal\WireGuard\WGDaemonClient */
    public $wgDaemonClient;

    /** @var string */
    public $wgHostName;

    /** @var int */
    public $wgPort;

    /** @var array<string> */
    public $dns;

    /**
     * @param string        $wgHostName
     * @param int           $wgPort
     * @param array<string> $dns
     */
    public function __construct(WGDaemonClient $wgDaemonClient, $wgHostName, $wgPort, array $dns)
    {
        $this->wgDaemonClient = $wgDaemonClient;
        $this->wgHostName = $wgHostName;
        $this->wgPort = $wgPort;
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

        $wgHttpClient = new CurlHttpClient();
        $wgDaemonClient = new WGDaemonClient($wgHttpClient, $wgProvidedConfig->requireString('daemonUri', 'http://localhost:8080'));

        $dnsArray = $wgProvidedConfig->requireArray('dns');
        foreach ($dnsArray as $dns) {
            if (!\is_string($dns)) {
                throw new ConfigException('DNS provided for WireGuard "'.$dns.'" was not a string.');
            }
        }

        return new self(
            $wgDaemonClient,
            $wgProvidedConfig->requireString('hostName'),
            $wgProvidedConfig->requireInt('port', 51820),
            $dnsArray
        );
    }
}
