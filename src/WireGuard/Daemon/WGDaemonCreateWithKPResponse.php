<?php

/*
 * eduVPN - End-user friendly VPN.
 *
 * Copyright: 2016-2019, The Commons Conservancy eduVPN Programme
 * SPDX-License-Identifier: AGPL-3.0+
 */

namespace LC\Portal\WireGuard\Daemon;

/**
 * @psalm-immutable
 */
class WGDaemonCreateWithKPResponse
{
    /** @var string */
    public $ip;

    /** @var string */
    public $clientPrivateKey;

    /** @var string */
    public $clientPublicKey;

    /** @var string */
    public $serverPublicKey;

    /**
     * @param string $ip
     * @param string $clientPrivateKey
     * @param string $clientPublicKey
     * @param string $serverPublicKey
     */
    public function __construct($ip, $clientPrivateKey, $clientPublicKey, $serverPublicKey)
    {
        $this->ip = $ip;
        $this->clientPrivateKey = $clientPrivateKey;
        $this->clientPublicKey = $clientPublicKey;
        $this->serverPublicKey = $serverPublicKey;
    }
}
