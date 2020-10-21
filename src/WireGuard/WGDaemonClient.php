<?php

/*
 * eduVPN - End-user friendly VPN.
 *
 * Copyright: 2016-2019, The Commons Conservancy eduVPN Programme
 * SPDX-License-Identifier: AGPL-3.0+
 */

namespace LC\Portal\WireGuard;

use LC\Common\HttpClient\HttpClientInterface;
use RuntimeException;

class WGDaemonClient
{
    /** @var HttpClientInterface */
    private $httpClient;

    /** @var string */
    private $baseUrl;

    /**
     * @param string $baseUrl
     */
    public function __construct(HttpClientInterface $httpClient, $baseUrl)
    {
        $this->baseUrl = $baseUrl;
        $this->httpClient = $httpClient;
    }

    /**
     * @param string $userId
     *
     * @return array<string, WGClientConfig>
     */
    public function getConfigs($userId)
    {
        $result = $this->httpClient->get($this->baseUrl.'/configs', ['user_id' => $userId]);
        $responseCode = $result->getCode();
        $responseString = $result->getBody();
        $this->assertResponseCode([200], $responseCode, $responseString);

        /* @var array<string, WGClientConfig> */
        return (array) json_decode($responseString, false); //todo: handle case when json can not be decoded?
    }

    /**
     * @param string $userId
     * @param string $name
     *
     * @return CreateResponse
     */
    public function createConfig($userId, $name)
    {
        $result = $this->httpClient->post($this->baseUrl.'/create_config_and_key_pair', [], ['user_id' => $userId, 'name' => $name]);
        $responseCode = $result->getCode();
        $responseString = $result->getBody();
        $this->assertResponseCode([200], $responseCode, $responseString);

        return json_decode($responseString, false);
    }

    /**
     * @param string $userId
     * @param string $publicKey
     *
     * @return void
     */
    public function deleteConfig($userId, $publicKey)
    {
        $result = $this->httpClient->post($this->baseUrl.'/delete_config', ['user_id' => $userId, 'public_key' => $publicKey], []);
        $responseCode = $result->getCode();
        $responseString = $result->getBody();
        $this->assertResponseCode([200, 409], $responseCode, $responseString);
    }

    /**
     * @param string $userId
     *
     * @return void
     */
    public function disableUser($userId)
    {
        $result = $this->httpClient->post($this->baseUrl.'/disable_user', ['user_id' => $userId], []);
        $responseCode = $result->getCode();
        $responseString = $result->getBody();
        $this->assertResponseCode([200], $responseCode, $responseString);
    }

    /**
     * @param string $userId
     *
     * @return void
     */
    public function enableUser($userId)
    {
        $result = $this->httpClient->post($this->baseUrl.'/enable_user', ['user_id' => $userId], []);
        $responseCode = $result->getCode();
        $responseString = $result->getBody();
        $this->assertResponseCode([200, 419], $responseCode, $responseString);
    }

    /**
     * @psalm-type userID=string
     *
     * @return array<userID, array<WGClientConnection>>
     */
    public function getClientConnections()
    {
        $result = $this->httpClient->get($this->baseUrl.'/client_connections', []);
        $responseCode = $result->getCode();
        $responseString = $result->getBody();
        $this->assertResponseCode([200], $responseCode, $responseString);

        return (array) json_decode($responseString, false);
    }

    /**
     * @param array<int> $expected
     * @param int        $responseCode
     * @param string     $responseString
     *
     * @return void
     */
    private function assertResponseCode($expected, $responseCode, $responseString)
    {
        if (!\in_array($responseCode, $expected, true)) {
            throw new RuntimeException('Unexpected response code from WireGuard Daemon: "'.$responseCode.'". Response: '.$responseString);
        }
    }
}
