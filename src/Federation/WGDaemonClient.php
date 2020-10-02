<?php

/*
 * eduVPN - End-user friendly VPN.
 *
 * Copyright: 2016-2019, The Commons Conservancy eduVPN Programme
 * SPDX-License-Identifier: AGPL-3.0+
 */

namespace LC\Portal\Federation;

use RuntimeException;

class CreateResponse
{
    /** @var string */
    public $ip;

    /** @var string */
    public $clientPrivateKey;

    /** @var string */
    public $serverPublicKey;
}

class WGClientConfig
{
    /** @var string */
    public $name;

    /** @var string */
    public $info;

    /** @var string */
    public $ip;

    /** @var string */
    public $modified;
}

class WGDaemonClient
{
    /** @var resource */
    private $curlChannel;

    /** @var string */
    private $baseUri;

    /**
     * @param string $baseUri
     */
    public function __construct($baseUri)
    {
        if (false === $this->curlChannel = curl_init()) {
            throw new RuntimeException('unable to create cURL channel');
        }
        $this->baseUri = $baseUri;
    }

    public function __destruct()
    {
        curl_close($this->curlChannel);
    }

    /**
     * @param string $username
     *
     * @return array<string, WGClientConfig>
     */
    public function getConfigs($username)
    {
        $result = $this->get('/user/' . $username . '/config');
        $responseCode = $result[0];
        $responseString = $result[1];
        if (200 !== $responseCode) {
            throw new RuntimeException('Unexpected response code from WireGuard Daemon: "' . $responseCode . '". Response: ' . $responseString);
        }
        /** @var array<string, WGClientConfig> */
        return (array)json_decode($responseString, false); //todo: handle case when json can not be decoded?
    }

    /**
     * @param string $username
     * @param string $name
     * @param string $info
     *
     * @return CreateResponse
     */
    public function creatConfig($username, $name, $info)
    {
        $result = $this->postJson('/user/' . $username . '/config', json_encode(['name' => $name, 'info' => $info]));
        $responseCode = $result[0];
        $responseString = $result[1];
        if (200 !== $responseCode) {
            throw new RuntimeException('Unexpected response code from WireGuard Daemon: "' . $responseCode . '". Response: ' . $responseString);
        }
        return json_decode($responseString, false);
    }

    /**
     * @param string $requestUri
     *
     * @return array{0: int, 1: string}
     */
    public function get($requestUri)
    {
        return $this->exec(
            [
                CURLOPT_URL => $this->baseUri . $requestUri,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => "",
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
            ]
        );
    }

    /**
     * @param string $requestUri
     * @param string $jsonString
     *
     * @return array{0: int, 1: string}
     */
    private function postJson($requestUri, $jsonString)
    {
        return $this->exec(
            [
                CURLOPT_URL => $this->baseUri . $requestUri,
                CURLOPT_POSTFIELDS => $jsonString,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_CUSTOMREQUEST => 'POST',
                CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
            ]
        );
    }

    /**
     * @return array{0: int, 1: string}
     */
    private function exec(array $curlOptions)
    {
        // reset all cURL options
        $this->curlReset();

        $defaultCurlOptions = [
            CURLOPT_HEADER => false,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_PROTOCOLS => CURLPROTO_HTTP | CURLPROTO_HTTPS,
        ];

        if (false === curl_setopt_array($this->curlChannel, $curlOptions + $defaultCurlOptions)) {
            throw new RuntimeException('unable to set cURL options');
        }

        $responseData = curl_exec($this->curlChannel);
        if (!\is_string($responseData)) {
            $curlError = curl_error($this->curlChannel);
            throw new RuntimeException(sprintf('failure performing the HTTP request: "%s"', $curlError));
        }

        return [
            (int)curl_getinfo($this->curlChannel, CURLINFO_HTTP_CODE),
            $responseData,
        ];
    }

    /**
     * @return void
     */
    private function curlReset()
    {
        // requires PHP >= 5.5 for curl_reset
        if (\function_exists('curl_reset')) {
            curl_reset($this->curlChannel);

            return;
        }

        // reset the request method to GET, that is enough to allow for
        // multiple requests using the same cURL channel
        if (false === curl_setopt($this->curlChannel, CURLOPT_HTTPGET, true)) {
            throw new RuntimeException('unable to set cURL options');
        }
    }
}