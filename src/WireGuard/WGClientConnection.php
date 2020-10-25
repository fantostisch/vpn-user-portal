<?php

/*
 * eduVPN - End-user friendly VPN.
 *
 * Copyright: 2016-2019, The Commons Conservancy eduVPN Programme
 * SPDX-License-Identifier: AGPL-3.0+
 */

namespace LC\Portal\WireGuard;

/**
 * @psalm-immutable
 */
class WGClientConnection
{
    /** @var string */
    public $publicKey;

    /** @var string */
    public $name;

    /** @var array<string> */
    public $allowedIPs;

    /**
     * @param string        $publicKey
     * @param string        $name
     * @param array<string> $allowedIPs
     */
    public function __construct($publicKey, $name, array $allowedIPs)
    {
        $this->publicKey = $publicKey;
        $this->name = $name;
        $this->allowedIPs = $allowedIPs;
    }
}
