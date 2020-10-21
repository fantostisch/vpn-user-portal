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
     * @param string $hostName
     * @param int    $port
     * @param string $clientIp
     * @param string $serverPublicKey
     * @param string $clientPrivateKey
     *
     * @return string
     */
    public static function get($hostName, $port, $clientIp, $serverPublicKey, $clientPrivateKey)
    {
        $clientConfig = [
            '[Interface]',
            'PrivateKey = '.$clientPrivateKey,
            'DNS = 9.9.9.9', //todo: do not hardcode dns
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
