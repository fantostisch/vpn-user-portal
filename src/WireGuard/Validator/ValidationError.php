<?php

/*
 * eduVPN - End-user friendly VPN.
 *
 * Copyright: 2016-2019, The Commons Conservancy eduVPN Programme
 * SPDX-License-Identifier: AGPL-3.0+
 */

namespace LC\Portal\WireGuard\Validator;

/**
 * @psalm-immutable
 */
class ValidationError
{
    /** @var string */
    public $message;

    /**
     * Underlying validation errors.
     *
     * @var array<ValidationError>
     */
    public $err;

    /**
     * @param string                 $message
     * @param array<ValidationError> $err
     */
    public function __construct($message, array $err = [])
    {
        $this->message = $message;
        $this->err = $err;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        if (empty($this->err)) {
            return $this->message;
        }

        $indentValidationError =
            /**
             * @return string
             */
            function (self $ve) {
                return Utils::indent($ve->__toString());
            };

        return $this->message."\n"
            .'Caused by:'."\n"
            .implode(
                "\n".
                'and'."\n",
                array_map($indentValidationError, $this->err)
            );
    }

    /**
     * Checks if $t is not an array<ValidationError>.
     *
     * @template T
     *
     * @param T|array<ValidationError> $t
     *
     * @return bool
     * @psalm-assert-if-false array<ValidationError> $t
     * @psalm-return  (T is array<ValidationError> ? false : true)
     */
    public static function isValid($t)
    {
        if (\is_array($t) && !empty($t) && array_values($t)[0] instanceof self) {
            return false;
        }

        return true;
    }
}
