<?php
/**
 *  Copyright (C) 2016 SURFnet.
 *
 *  This program is free software: you can redistribute it and/or modify
 *  it under the terms of the GNU Affero General Public License as
 *  published by the Free Software Foundation, either version 3 of the
 *  License, or (at your option) any later version.
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU Affero General Public License for more details.
 *
 *  You should have received a copy of the GNU Affero General Public License
 *  along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace SURFnet\VPN\Portal;

use DateTime;
use DateTimeZone;
use SURFnet\VPN\Common\Http\ApiResponse;
use SURFnet\VPN\Common\Http\InputValidation;
use SURFnet\VPN\Common\Http\Request;
use SURFnet\VPN\Common\Http\Response;
use SURFnet\VPN\Common\Http\Service;
use SURFnet\VPN\Common\Http\ServiceModuleInterface;
use SURFnet\VPN\Common\HttpClient\ServerClient;
use SURFnet\VPN\Common\ProfileConfig;

class VpnApiModule implements ServiceModuleInterface
{
    /** @var \SURFnet\VPN\Common\HttpClient\ServerClient */
    private $serverClient;

    /** @var bool */
    private $shuffleHosts;

    public function __construct(ServerClient $serverClient)
    {
        $this->serverClient = $serverClient;
        $this->shuffleHosts = true;
    }

    public function setShuffleHosts($shuffleHosts)
    {
        $this->shuffleHosts = (bool) $shuffleHosts;
    }

    public function init(Service $service)
    {
        $service->get(
            '/profile_list',
            function (Request $request, array $hookData) {
                $userId = $hookData['auth'];

                $profileList = $this->serverClient->getProfileList();
                $userGroups = $this->serverClient->getUserGroups(['user_id' => $userId]);

                $userProfileList = [];
                foreach ($profileList as $profileId => $profileData) {
                    $profileConfig = new ProfileConfig($profileData);
                    if ($profileConfig->v('enableAcl')) {
                        // is the user member of the aclGroupList?
                        if (!self::isMember($userGroups, $profileConfig->v('aclGroupList'))) {
                            continue;
                        }
                    }

                    $userProfileList[] = [
                        'profile_id' => $profileId,
                        'display_name' => $profileConfig->v('displayName'),
                        'two_factor' => $profileConfig->v('twoFactor'),
                    ];
                }

                return new ApiResponse('profile_list', $userProfileList);
            }
        );

        $service->post(
            '/create_config',
            function (Request $request, array $hookData) {
                $userId = $hookData['auth'];

                $displayName = InputValidation::displayName($request->getPostParameter('display_name'));
                $profileId = InputValidation::profileId($request->getPostParameter('profile_id'));

                return $this->getConfig($request->getServerName(), $profileId, $userId, $displayName);
            }
        );

        $service->get(
            '/user_messages',
            function (Request $request, array $hookData) {
                $userId = $hookData['auth'];

                $msgList = [];
                $userMessages = $this->serverClient->getUserMessages($userId);
                foreach ($userMessages as $userMessage) {
                    $dateTime = new DateTime($userMessage['date_time']);
                    $dateTime->setTimeZone(new DateTimeZone('UTC'));

                    $msgList[] = [
                        // no support yet for 'motd' type in application API
                        'type' => $userMessage['type'],
                        'date' => $dateTime->format('Y-m-d\TH:i:s\Z'),
                        'content' => $userMessage['message'],
                    ];
                }

                return new ApiResponse(
                    'user_messages',
                    $msgList
                );
            }
        );

        $service->get(
            '/system_messages',
            function (Request $request, array $hookData) {
                $msgList = [];

                $motdMessages = $this->serverClient->getSystemMessages('motd');
                foreach ($motdMessages as $motdMessage) {
                    $dateTime = new DateTime($motdMessage['date_time']);
                    $dateTime->setTimeZone(new DateTimeZone('UTC'));

                    $msgList[] = [
                        // no support yet for 'motd' type in application API
                        'type' => 'notification',
                        'date' => $dateTime->format('Y-m-d\TH:i:s\Z'),
                        'content' => $motdMessage['message'],
                    ];
                }

                return new ApiResponse(
                    'system_messages',
                    $msgList
                );
            }
        );
    }

    private function getConfig($serverName, $profileId, $userId, $displayName)
    {
        // create a certificate
        $clientCertificate = $this->serverClient->postAddClientCertificate(['user_id' => $userId, 'display_name' => $displayName]);

        // obtain information about this profile to be able to construct
        // a client configuration file
        $profileList = $this->serverClient->getProfileList();
        $profileData = $profileList[$profileId];

        $clientConfig = ClientConfig::get($profileData, $clientCertificate, $this->shuffleHosts);
        $clientConfig = str_replace("\n", "\r\n", $clientConfig);

        $response = new Response(200, 'application/x-openvpn-profile');
        $response->setBody($clientConfig);

        return $response;
    }

    private static function isMember(array $userGroups, array $aclGroupList)
    {
        // if any of the groups in userGroups is part of aclGroupList return
        // true, otherwise false
        foreach ($userGroups as $userGroup) {
            if (in_array($userGroup['id'], $aclGroupList)) {
                return true;
            }
        }

        return false;
    }
}
