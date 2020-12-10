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
use LC\Portal\WireGuard\Manager\WGManager;

class WireGuardApiModule implements ServiceModuleInterface
{
    const PREFIX = '/wg/';

    /** @var \LC\Portal\WireGuard\Manager\WGManager */
    private $wgManager;

    public function __construct(WGManager $wgManager)
    {
        $this->wgManager = $wgManager;
    }

    /**
     * @return void
     */
    public function init(Service $service)
    {
        $service->get(
            self::PREFIX.'available',
            /**
             * @return Response
             */
            function (Request $request, array $hookData) {
                $response = new Response(200, 'text/plain');
                $response->setBody('y');

                return $response;
            }
        );

        $service->post(
            self::PREFIX.'create_config',
            /**
             * @return Response
             */
            function (Request $request, array $hookData) {
                /** @var \LC\Portal\OAuth\VpnAccessTokenInfo $accessTokenInfo */
                $accessTokenInfo = $hookData['auth'];
                $userId = $accessTokenInfo->getUserId();
                $clientId = $accessTokenInfo->getClientId();
                $displayName = $clientId;
                $publicKey = $request->requirePostParameter('publicKey');

                $wgConfigFile = $this->wgManager->addConfig($userId, $displayName, $clientId, $publicKey);

                $response = new Response(200, 'text/plain');
                $response->setBody($wgConfigFile);

                return $response;
            }
        );
    }
}
