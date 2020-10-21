<?php

/*
 * eduVPN - End-user friendly VPN.
 *
 * Copyright: 2016-2019, The Commons Conservancy eduVPN Programme
 * SPDX-License-Identifier: AGPL-3.0+
 */

namespace LC\Portal\WireGuard;

use LC\Common\Http\BeforeHookInterface;
use LC\Common\Http\Request;
use LC\Common\TplInterface;

/**
 * Augments the "template" with information about whether WireGuard is enabled.
 * Decides if the WireGuard menu items are shown.
 */
class WireGuardHook implements BeforeHookInterface
{
    /** @var bool */
    private $wireguardEnabled;

    /** @var \LC\Common\TplInterface */
    private $tpl;

    /**
     * @param bool $wireguardEnabled
     */
    public function __construct($wireguardEnabled, TplInterface &$tpl)
    {
        $this->wireguardEnabled = $wireguardEnabled;
        $this->tpl = $tpl;
    }

    /**
     * @return bool
     */
    public function executeBefore(Request $request, array $hookData)
    {
        $this->tpl->addDefault(['wireguardEnabled' => $this->wireguardEnabled]);

        return $this->wireguardEnabled;
    }
}
