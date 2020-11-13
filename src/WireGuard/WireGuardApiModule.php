<?php

/*
 * eduVPN - End-user friendly VPN.
 *
 * Copyright: 2016-2019, The Commons Conservancy eduVPN Programme
 * SPDX-License-Identifier: AGPL-3.0+
 */

namespace LC\Portal\WireGuard;

use LC\Common\Http\Request;
use LC\Common\Http\Response;
use LC\Common\Http\Service;
use LC\Common\Http\ServiceModuleInterface;

class WireGuardApiModule implements ServiceModuleInterface
{
    /** @var \LC\Portal\WireGuard\WGEnabledConfig */
    private $wgConfig;

    /**
     * @param \LC\Portal\WireGuard\WGEnabledConfig $wgConfig
     */
    public function __construct($wgConfig)
    {
        $this->wgConfig = $wgConfig;
    }

    /**
     * @return void
     */
    public function init(Service $service)
    {
        $service->get(
            '/wireguard_enabled',
            /**
             * @return Response
             */
            function (Request $request, array $hookData) {
                $response = new Response(200, 'text/plain');
                $response->setBody('y');

                return $response;
            }
        );
    }
}
