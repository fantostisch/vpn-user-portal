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
class WGClientConnection
{
    /** @var string */
    public $publicKey;

    /** @var string */
    public $name;

    /** @var array<string> */
    public $allowedIPs;

    /**
     * @param string        $publicKey
     * @param string        $name
     * @param array<string> $allowedIPs
     */
    public function __construct($publicKey, $name, array $allowedIPs)
    {
        $this->publicKey = $publicKey;
        $this->name = $name;
        $this->allowedIPs = $allowedIPs;
    }

    /**
     * @param array $array
     *
     * @return WGClientConnection|non-empty-array<ValidationError>
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
            $publicKey = $c->requireString('publicKey');
        } catch (ConfigException $e) {
            array_push($validationErrors, $e);
        }

        try {
            $name = $c->requireString('name');
        } catch (ConfigException $e) {
            array_push($validationErrors, $e);
        }

        try {
            $allowedIPs = $c->requireArray('allowedIPs');
            foreach ($allowedIPs as $ip) {
                if (!\is_string($ip)) {
                    array_push($validationErrors, new ConfigException('ip "'.$ip.'" not of type string'));
                }
            }
        } catch (ConfigException $e) {
            array_push($validationErrors, $e);
        }

        if (!empty($validationErrors)) {
            return array_map('\LC\Portal\WireGuard\ValidationError::fromConfigException', $validationErrors);
        }

        return new self($publicKey, $name, $allowedIPs);
    }
}
