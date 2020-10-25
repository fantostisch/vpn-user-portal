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
class WGClientConfig
{
    /** @var string */
    public $name;

    /** @var string */
    public $ip;

    /** @var string */
    public $modified;

    /**
     * @param string $name
     * @param string $ip
     * @param string $modified
     */
    public function __construct($name, $ip, $modified)
    {
        $this->name = $name;
        $this->ip = $ip;
        $this->modified = $modified;
    }
}
