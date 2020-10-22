<?php

/*
 * eduVPN - End-user friendly VPN.
 *
 * Copyright: 2016-2019, The Commons Conservancy eduVPN Programme
 * SPDX-License-Identifier: AGPL-3.0+
 */

namespace LC\Portal\WireGuard;

use LC\Common\Http\Exception\HttpException;
use LC\Common\HttpClient\HttpClientInterface;
use LC\Common\Json;
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
     * @throws HttpException
     *
     * @return array<string, WGClientConfig>
     *
     * @psalm-suppress InvalidReturnType
     * @psalm-suppress InvalidReturnStatement
     */
    public function getConfigs($userId)
    {
        $result = $this->httpClient->get($this->baseUrl.'/configs', ['user_id' => $userId]);
        $responseCode = $result->getCode();
        $responseString = $result->getBody();
        $this->assertResponseCode([200], $responseCode, $responseString);

        $decodedJson = Json::decode($responseString);

        /** @var array<string,WGClientConfig|non-empty-array<ValidationError>> $result */
        $result = array_map(function ($v) {
            if (!\is_array($v)) {
                return [new ValidationError('WGClientConfig expected but got: '.$v)];
            }

            return WGClientConfig::fromArray($v);
        }, $decodedJson);

        $this->assertNoErrors($result, 'Invalid configurations received from WG Daemon');

        return $result;
    }

    /**
     * @param string $userId
     * @param string $name
     *
     * @return CreateResponse
     *
     * @psalm-suppress InvalidReturnType
     * @psalm-suppress InvalidReturnStatement
     */
    public function createConfig($userId, $name)
    {
        $result = $this->httpClient->post($this->baseUrl.'/create_config_and_key_pair', [], ['user_id' => $userId, 'name' => $name]);
        $responseCode = $result->getCode();
        $responseString = $result->getBody();
        $this->assertResponseCode([200], $responseCode, $responseString);

        $decodedJson = Json::decode($responseString);

        /** @var CreateResponse|non-empty-array<ValidationError> $result */
        $result = CreateResponse::fromArray($decodedJson);

        $this->assertNoErrors($result, 'Invalid response from WG Daemon');

        return $result;
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
     *
     * @psalm-suppress InvalidReturnStatement
     * @psalm-suppress InvalidReturnType
     */
    public function getClientConnections()
    {
        $result = $this->httpClient->get($this->baseUrl.'/client_connections', []);
        $responseCode = $result->getCode();
        $responseString = $result->getBody();
        $this->assertResponseCode([200], $responseCode, $responseString);

        $decodedJson = Json::decode($responseString);

        /** @var array<string,array<string,array<WGClientConnection>|non-empty-array<ValidationError>>> $result */
        $result = array_map(function ($v) {
            if (!\is_array($v)) {
                return [new ValidationError('Array of WGClientConnection expected but got: '.$v)];
            }

            return array_map(function ($v2) {
                if (!\is_array($v2)) {
                    return [new ValidationError('WGClientConnection expected but got: '.$v2)];
                }

                return WGClientConnection::fromArray($v2);
            }, $v);
        }, $decodedJson);

        $this->assertNoErrors($result, 'Invalid connections received from WG Daemon');

        return $result;
    }

    /**
     * @param array<int> $expected
     * @param int        $responseCode
     * @param string     $responseString
     *
     * @return void
     */
    private static function assertResponseCode($expected, $responseCode, $responseString)
    {
        if (!\in_array($responseCode, $expected, true)) {
            throw new RuntimeException('Unexpected response code from WireGuard Daemon: "'.$responseCode.'". Response: '.$responseString);
        }
    }

    /**
     * @param mixed  $t
     * @param string $message
     *
     * @throws HttpException
     *
     * @return void
     */
    private static function assertNoErrors($t, $message)
    {
        $success = array_walk_recursive(
            $t,
            /**
             * @param mixed $v
             * @param mixed $_
             */
            function ($v, $_) use ($t, $message) {
                if ($v instanceof ValidationError) {
                    throw new HttpException($message.': '.json_encode($t, JSON_PRETTY_PRINT), 500);
                }
            }
        );
        if (!$success) {
            throw new HttpException('Unable to check for errors.', 500);
        }
    }
}
