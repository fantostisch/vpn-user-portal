<?php

/*
 * eduVPN - End-user friendly VPN.
 *
 * Copyright: 2016-2019, The Commons Conservancy eduVPN Programme
 * SPDX-License-Identifier: AGPL-3.0+
 */

namespace LC\Portal\WireGuard;

class WGClientConfigGenerator
{
    /**
     * @param string        $hostName
     * @param int           $port
     * @param string        $clientIp
     * @param string        $serverPublicKey
     * @param string        $clientPrivateKey
     * @param array<string> $dnsServers
     *
     * @return string
     */
    public static function get($hostName, $port, $clientIp, $serverPublicKey, $clientPrivateKey, array $dnsServers)
    {
        $clientConfig = [
            '[Interface]',
            'PrivateKey = '.$clientPrivateKey,
            'DNS = '.implode(',', $dnsServers),
            'Address = '.$clientIp,
            '',
            '[Peer]',
            'PublicKey = '.$serverPublicKey,
            'AllowedIPs = 0.0.0.0/0',
            'Endpoint = '.$hostName.':'.$port,
            PHP_EOL,
        ];

        return implode(PHP_EOL, $clientConfig);
    }
}
