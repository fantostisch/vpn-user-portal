<?php

/*
 * eduVPN - End-user friendly VPN.
 *
 * Copyright: 2016-2019, The Commons Conservancy eduVPN Programme
 * SPDX-License-Identifier: AGPL-3.0+
 */

namespace LC\Portal\WireGuard\Manager;

/**
 * @psalm-immutable
 */
class VPNServerAPIUser
{
    /** @var string */
    public $userId;

    /** @var bool */
    public $disabled;

    /**
     * VPNServerAPIUser constructor.
     *
     * @param string $user_id
     * @param bool   $is_disabled
     */
    public function __construct($user_id, $is_disabled)
    {
        $this->userId = $user_id;
        $this->disabled = $is_disabled;
    }
}
