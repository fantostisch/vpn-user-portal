<?php

/*
 * eduVPN - End-user friendly VPN.
 *
 * Copyright: 2016-2019, The Commons Conservancy eduVPN Programme
 * SPDX-License-Identifier: AGPL-3.0+
 */

namespace LC\Portal\WireGuard;

use LC\Common\Http\HtmlResponse;
use LC\Common\Http\InputValidation;
use LC\Common\Http\RedirectResponse;
use LC\Common\Http\Request;
use LC\Common\Http\Service;
use LC\Common\Http\ServiceModuleInterface;
use LC\Common\TplInterface;

class WireGuardPortalModule implements ServiceModuleInterface
{
    /** @var \LC\Common\TplInterface */
    private $tpl;

    /** @var \LC\Portal\WireGuard\WGEnabledConfig */
    private $wgConfig;

    /**
     * @param \LC\Portal\WireGuard\WGEnabledConfig $wgConfig
     */
    public function __construct(TplInterface $tpl, $wgConfig)
    {
        $this->tpl = $tpl;
        $this->wgConfig = $wgConfig;
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
                $wgConfigs = $this->wgConfig->wgDaemonClient->getConfigs($userId);

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

                $displayName = InputValidation::displayName($request->requirePostParameter('displayName'));
                $userId = $userInfo->getUserId();

                $createResponse = $this->wgConfig->wgDaemonClient->createConfig($userId, $displayName);
                $wgConfigFile = $this->tpl->render('vpnPortalWGConfigurationFile',
                    [
                        'hostName' => $this->wgConfig->wgHostName,
                        'port' => $this->wgConfig->wgPort,
                        'clientIp' => $createResponse->ip,
                        'serverPublicKey' => $createResponse->serverPublicKey,
                        'clientPrivateKey' => $createResponse->clientPrivateKey,
                        'dnsServers' => $this->wgConfig->dns,
                    ]
                );
                $wgConfigFileName = sprintf('%s_%s_%s.conf', $this->wgConfig->wgHostName, date('Ymd'), $displayName);

                $wgConfigs = $this->wgConfig->wgDaemonClient->getConfigs($userId);

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

                $publicKey = $request->requirePostParameter('publicKey');
                $this->wgConfig->wgDaemonClient->deleteConfig($userInfo->getUserId(), $publicKey);

                return new RedirectResponse($request->getRootUri().'WGConfigurations', 302);
            }
        );
    }
}
