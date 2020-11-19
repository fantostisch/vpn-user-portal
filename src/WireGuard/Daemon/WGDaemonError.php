<?php

/*
 * eduVPN - End-user friendly VPN.
 *
 * Copyright: 2016-2019, The Commons Conservancy eduVPN Programme
 * SPDX-License-Identifier: AGPL-3.0+
 */

namespace LC\Portal\WireGuard\Daemon;

/**
 * @psalm-immutable
 */
class WGDaemonError
{
    /** @var "config_not_found" | "user_already_enabled" | "user_already_disabled" */
    public $errorType;

    /** @var string */
    public $errorDescription;

    /**
     * @param "config_not_found" | "user_already_enabled" | "user_already_disabled" $errorType
     * @param string                                                                $errorDescription
     */
    public function __construct($errorType, $errorDescription)
    {
        $this->errorType = $errorType;
        $this->errorDescription = $errorDescription;
    }
}
