<?php

/*
 * eduVPN - End-user friendly VPN.
 *
 * Copyright: 2016-2019, The Commons Conservancy eduVPN Programme
 * SPDX-License-Identifier: AGPL-3.0+
 */

namespace LC\Portal\WireGuard;

use LC\Common\Exception\ConfigException;

/**
 * @psalm-immutable
 */
class ValidationError
{
    /** @var string */
    public $message;

    /**
     * @param string $message
     */
    public function __construct($message)
    {
        $this->message = $message;
    }

    /**
     * @param ConfigException $ce
     *
     * @return ValidationError
     */
    public static function fromConfigException($ce)
    {
        return new self($ce->getMessage());
    }
}
