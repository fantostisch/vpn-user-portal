<?php

/*
 * eduVPN - End-user friendly VPN.
 *
 * Copyright: 2016-2019, The Commons Conservancy eduVPN Programme
 * SPDX-License-Identifier: AGPL-3.0+
 */

namespace LC\Portal\WireGuard;

use LC\Common\Http\HtmlResponse;
use LC\Common\Http\RedirectResponse;
use LC\Common\Http\Request;
use LC\Common\Http\Service;
use LC\Common\Http\ServiceModuleInterface;
use LC\Common\TplInterface;
use LC\Portal\WireGuard\Manager\WGManager;

class WireGuardPortalModule implements ServiceModuleInterface
{
    /** @var \LC\Common\TplInterface */
    private $tpl;

    /** @var \LC\Portal\WireGuard\Manager\WGManager */
    private $wgManager;

    public function __construct(TplInterface $tpl, WGManager $wgManager)
    {
        $this->tpl = $tpl;
        $this->wgManager = $wgManager;
    }

    /**
     * @return void
     */
    public function init(Service $service)
    {
        $service->get(
            '/WGConfigurations',
            /**
             * @return \LC\Common\Http\Response
             */
            function (Request $request, array $hookData) {
                /** @var \LC\Common\Http\UserInfo */
                $userInfo = $hookData['auth'];
                $userId = $userInfo->getUserId();
                $wgConfigs = $this->wgManager->getConfigs($userId);

                return new HtmlResponse(
                    $this->tpl->render(
                        'vpnPortalWGConfigurations',
                        [
                            'wgConfigs' => $wgConfigs,
                        ]
                    )
                );
            }
        );

        $service->post(
            '/WGConfigurations',
            /**
             * @return \LC\Common\Http\Response
             */
            function (Request $request, array $hookData) {
                /** @var \LC\Common\Http\UserInfo */
                $userInfo = $hookData['auth'];
                $userId = $userInfo->getUserId();
                $displayName = $request->requirePostParameter('displayName');

                $wgConfigFile = $this->wgManager->addConfig($userId, $displayName, null);
                $wgConfigFileName = sprintf('%s_%s_%s.conf', $this->wgManager->getPortalConfig()->hostName, date('Ymd'), $displayName);
                $wgConfigs = $this->wgManager->getConfigs($userId);

                return new HtmlResponse(
                    $this->tpl->render(
                        'vpnPortalWGConfigurations',
                        [
                            'wgConfigs' => $wgConfigs,
                            'wgConfigFileName' => $wgConfigFileName,
                            'wgConfigFile' => rawurlencode($wgConfigFile),
                            'newConfigName' => $displayName,
                        ]
                    )
                );
            }
        );

        $service->post(
            '/deleteWGConfig',
            /**
             * @return \LC\Common\Http\Response
             */
            function (Request $request, array $hookData) {
                /** @var \LC\Common\Http\UserInfo */
                $userInfo = $hookData['auth'];
                $userId = $userInfo->getUserId();
                $publicKey = $request->requirePostParameter('publicKey');

                $this->wgManager->deleteConfig($userId, $publicKey);

                return new RedirectResponse($request->getRootUri().'WGConfigurations', 302);
            }
        );
    }
}
