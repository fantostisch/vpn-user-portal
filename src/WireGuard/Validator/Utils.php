<?php

/*
 * eduVPN - End-user friendly VPN.
 *
 * Copyright: 2016-2019, The Commons Conservancy eduVPN Programme
 * SPDX-License-Identifier: AGPL-3.0+
 */

namespace LC\Portal\WireGuard\Validator;

class Utils
{
    /**
     * @psalm-pure
     *
     * @param string $string
     * @param int    $depth
     *
     * @return string
     */
    public static function indent($string, $depth = 1)
    {
        $indention = str_repeat('    ', $depth);

        return $indention.implode("\n".$indention, explode("\n", $string));
    }
}
