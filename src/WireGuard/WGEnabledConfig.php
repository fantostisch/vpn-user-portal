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
}
