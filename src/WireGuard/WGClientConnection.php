<?php

/*
 * eduVPN - End-user friendly VPN.
 *
 * Copyright: 2016-2019, The Commons Conservancy eduVPN Programme
 * SPDX-License-Identifier: AGPL-3.0+
 */

namespace LC\Portal\WireGuard;

/** @psalm-suppress MissingConstructor */
class WGClientConnection
{
    /** @var string */
    public $publicKey;

    /** @var string */
    public $name;

    /** @var array<string> */
    public $allowedIPs;
}
