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
class EnableWireGuardHook implements BeforeHookInterface
{
    /** @var \LC\Common\TplInterface */
    private $tpl;

    public function __construct(TplInterface &$tpl)
    {
        $this->tpl = $tpl;
    }

    /**
     * @return void
     */
    public function executeBefore(Request $request, array $hookData)
    {
        $this->tpl->addDefault(['wireguardEnabled' => true]);
    }
}
