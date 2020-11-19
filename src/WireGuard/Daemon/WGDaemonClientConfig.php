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
class WGDaemonClientConfig
{
    /** @var string */
    public $ip;

    /** @var string */
    public $modified;

    /**
     * @param string $ip
     * @param string $modified
     */
    public function __construct($ip, $modified)
    {
        $this->ip = $ip;
        $this->modified = $modified;
    }
}
