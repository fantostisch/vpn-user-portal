<?php

/*
 * eduVPN - End-user friendly VPN.
 *
 * Copyright: 2016-2019, The Commons Conservancy eduVPN Programme
 * SPDX-License-Identifier: AGPL-3.0+
 */

namespace LC\Portal\WireGuard;

use LC\Common\Config;
use LC\Common\Exception\ConfigException;

/**
 * @psalm-immutable
 */
class CreateResponse
{
    /** @var string */
    public $ip;

    /** @var string */
    public $clientPrivateKey;

    /** @var string */
    public $serverPublicKey;

    /**
     * @param string $ip
     * @param string $clientPrivateKey
     * @param string $serverPublicKey
     */
    public function __construct($ip, $clientPrivateKey, $serverPublicKey)
    {
        $this->ip = $ip;
        $this->clientPrivateKey = $clientPrivateKey;
        $this->serverPublicKey = $serverPublicKey;
    }

    /**
     * @param array $array
     *
     * @return CreateResponse|non-empty-array<ValidationError>
     *
     * //todo: this @psalm-suppress should be moved above the return statement,
     * but php-cs-fixer does not like "/**" and psalm does not accept "/*"
     * @psalm-suppress PossiblyUndefinedVariable
     */
    public static function fromArray($array)
    {
        $c = new Config($array);

        $validationErrors = [];

        try {
            $ip = $c->requireString('ip');
        } catch (ConfigException $e) {
            array_push($validationErrors, $e);
        }

        try {
            $clientPrivateKey = $c->requireString('clientPrivateKey');
        } catch (ConfigException $e) {
            array_push($validationErrors, $e);
        }

        try {
            $serverPublicKey = $c->requireString('serverPublicKey');
        } catch (ConfigException $e) {
            array_push($validationErrors, $e);
        }

        if (!empty($validationErrors)) {
            return array_map('\LC\Portal\WireGuard\ValidationError::fromConfigException', $validationErrors);
        }

        return new self($ip, $clientPrivateKey, $serverPublicKey);
    }
}
