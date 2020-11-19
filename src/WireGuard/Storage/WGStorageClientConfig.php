<?php

/*
 * eduVPN - End-user friendly VPN.
 *
 * Copyright: 2016-2019, The Commons Conservancy eduVPN Programme
 * SPDX-License-Identifier: AGPL-3.0+
 */

namespace LC\Portal\WireGuard\Storage;

/**
 * @psalm-immutable
 */
class WGStorageClientConfig
{
    /** @var string */
    public $publicKey;

    /** @var string */
    public $name;

    /** @var string|null */
    public $clientId;

    /**
     * @param string      $public_key
     * @param string      $display_name
     * @param string|null $client_id
     */
    public function __construct($public_key, $display_name, $client_id)
    {
        $this->publicKey = $public_key;
        $this->name = $display_name;
        $this->clientId = $client_id;
    }
}
