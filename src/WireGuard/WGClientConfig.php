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
class WGClientConfig
{
    /** @var string */
    public $name;

    /** @var string */
    public $ip;

    /** @var string */
    public $modified;

    /**
     * @param string $name
     * @param string $ip
     * @param string $modified
     */
    public function __construct($name, $ip, $modified)
    {
        $this->name = $name;
        $this->ip = $ip;
        $this->modified = $modified;
    }

    /**
     * @param array $array
     *
     * @return WGClientConfig|non-empty-array<ValidationError>
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
            $name = $c->requireString('name');
        } catch (ConfigException $e) {
            array_push($validationErrors, $e);
        }

        try {
            $ip = $c->requireString('ip');
        } catch (ConfigException $e) {
            array_push($validationErrors, $e);
        }

        try {
            $modified = $c->requireString('modified');
        } catch (ConfigException $e) {
            array_push($validationErrors, $e);
        }

        if (!empty($validationErrors)) {
            return array_map('\LC\Portal\WireGuard\ValidationError::fromConfigException', $validationErrors);
        }

        return new self($name, $ip, $modified);
    }
}
