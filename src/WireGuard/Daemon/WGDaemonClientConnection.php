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
class WGDaemonClientConnection
{
    /** @var string */
    public $publicKey;

    /** @var array<string> */
    public $allowedIPs;

    /**
     * @param string        $publicKey
     * @param array<string> $allowedIPs
     */
    public function __construct($publicKey, array $allowedIPs)
    {
        $this->publicKey = $publicKey;
        $this->allowedIPs = $allowedIPs;
    }
}
