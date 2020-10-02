<?php

/*
 * eduVPN - End-user friendly VPN.
 *
 * Copyright: 2016-2019, The Commons Conservancy eduVPN Programme
 * SPDX-License-Identifier: AGPL-3.0+
 */

namespace LC\Portal;

class WGClientConfigGenerator
{
    /**
     * @param string $hostName
     * @param string $clientIp
     * @param string $serverPublicKey
     * @param string $clientPrivateKey
     * @return string
     */
    public static function get($hostName, $clientIp, $serverPublicKey, $clientPrivateKey)
    {
        $clientConfig = [
            '[Interface]',
            'PrivateKey = ' . $clientPrivateKey,
            'DNS = 8.8.8.8', //todo
            'Address = ' . $clientIp,
            '',
            '[Peer]',
            'PublicKey = ' . $serverPublicKey,
            'AllowedIPs = 0.0.0.0/0',
            'Endpoint = ' . $hostName . ':51820',
            PHP_EOL,
        ];

        return implode(PHP_EOL, $clientConfig);
    }
}
