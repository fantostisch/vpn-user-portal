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
        $result = $this->httpClient->get($this->baseUrl.'/user/'.$username.'/config', []);
        $responseCode = $result->getCode();
        $responseString = $result->getBody();
        if (200 !== $responseCode) {
            throw new RuntimeException('Unexpected response code from WireGuard Daemon: "'.$responseCode.'". Response: '.$responseString);
        }

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
        $result = $this->httpClient->post($this->baseUrl.'/user/'.$username.'/config', [], ['name' => $name]);
        $responseCode = $result->getCode();
        $responseString = $result->getBody();
        if (200 !== $responseCode) {
            throw new RuntimeException('Unexpected response code from WireGuard Daemon: "'.$responseCode.'". Response: '.$responseString);
        }

        return json_decode($responseString, false);
    }
}
