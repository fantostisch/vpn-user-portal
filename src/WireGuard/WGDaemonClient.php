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
     * @param string $username
     *
     * @return array<string, WGClientConfig>
     */
    public function getConfigs($username)
    {
        $result = $this->httpClient->get($this->baseUrl.'/config', ['user_id' => $username]);
        $responseCode = $result->getCode();
        $responseString = $result->getBody();
        $this->assertResponseCode([200], $responseCode, $responseString);

        /* @var array<string, WGClientConfig> */
        return (array) json_decode($responseString, false); //todo: handle case when json can not be decoded?
    }

    /**
     * @param string $username
     * @param string $name
     *
     * @return CreateResponse
     */
    public function creatConfig($username, $name)
    {
        $result = $this->httpClient->post($this->baseUrl.'/config', ['user_id' => $username], ['name' => $name]);
        $responseCode = $result->getCode();
        $responseString = $result->getBody();
        $this->assertResponseCode([200], $responseCode, $responseString);

        return json_decode($responseString, false);
    }

    /**
     * @param string $username
     * @param string $publicKey
     *
     * @return void
     */
    public function deleteConfig($username, $publicKey)
    {
        $result = $this->httpClient->delete($this->baseUrl.'/config', ['user_id' => $username, 'public_key' => $publicKey], []);
        $responseCode = $result->getCode();
        $responseString = $result->getBody();
        $this->assertResponseCode([200, 409], $responseCode, $responseString);
    }

    /**
     * @param string $username
     *
     * @return void
     */
    public function disableUser($username)
    {
        $result = $this->httpClient->post($this->baseUrl.'/disable_user', ['user_id' => $username], []);
        $responseCode = $result->getCode();
        $responseString = $result->getBody();
        $this->assertResponseCode([200], $responseCode, $responseString);
    }

    /**
     * @param string $username
     *
     * @return void
     */
    public function enableUser($username)
    {
        $result = $this->httpClient->post($this->baseUrl.'/enable_user', ['user_id' => $username], []);
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
