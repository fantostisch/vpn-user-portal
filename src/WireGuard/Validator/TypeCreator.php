<?php

/*
 * eduVPN - End-user friendly VPN.
 *
 * Copyright: 2016-2019, The Commons Conservancy eduVPN Programme
 * SPDX-License-Identifier: AGPL-3.0+
 */

namespace LC\Portal\WireGuard\Validator;

use LC\Common\Http\Exception\HttpException;
use ReflectionClass;
use ReflectionMethod;

class TypeCreator
{
    /**
     * @param string $typeName
     * @param mixed  $value
     * @param string $errorMessage
     *
     * @throws HttpException
     *
     * @return mixed
     */
    public static function createTypeThrowIfError($typeName, $value, $errorMessage)
    {
        $result = self::createType($typeName, $value);

        if (!ValidationError::isValid($result)) {
            throw new HttpException((new ValidationError($errorMessage, $result))->__toString(), 500);
        }

        return $result;
    }

    /**
     * @param string $typeName
     * @param mixed  $data
     *
     * @throws HttpException
     *
     * @return mixed|array<ValidationError>
     */
    public static function createType($typeName, $data)
    {
        // Union types, return first type that works.
        $types = array_map('trim', explode('|', $typeName));
        if (\count($types) >= 2) {
            $validationErrors = [];
            foreach ($types as $type) {
                $result = self::createType($type, $data);
                if (ValidationError::isValid($result)) {
                    return $result;
                }
                array_push($validationErrors, new ValidationError('Type was not a "'.$type.'".', $result));
            }

            return [new ValidationError('Type was not any of "'.$typeName.'".', $validationErrors)];
        }

        // String literal
        if ('"' === substr($typeName, 0, 1) && '"' === substr($typeName, \strlen($typeName) - 1, 1)) {
            if (!\is_string($data)) {
                return [new ValidationError('Expected string literal "'.$typeName.'" but no string provided: "'.$data.'".')];
            }
            if ($typeName !== '"'.$data.'"') {
                return [new ValidationError('String "'.$data.'" was not the expected: "'.$typeName.'".')];
            }

            return $data;
        }

        // Number literal
        if (is_numeric($typeName)) {
            // is_numeric also accepts strings, which we do not want
            if (\is_string($data)) {
                return [new ValidationError('Number literal expected: "'.$typeName.'" but string provided: "'.$data.'".')];
            }
            if (!is_numeric($data)) {
                return [new ValidationError('Number literal expected: "'.$typeName.'" but no number provided: "'.$data.'".')];
            }
            if ((float) $typeName !== (float) $data) {
                return [new ValidationError('Number "'.$data.'" was not the expected: "'.$typeName.'".')];
            }

            return $data;
        }

        // Handle array
        $arrayTypePrefix = 'array<';
        if (substr($typeName, 0, \strlen($arrayTypePrefix)) === $arrayTypePrefix) {
            if ('>' !== substr($typeName, -1)) {
                throw new HttpException('Invalid type: "array<" specified, but missing ">".', 500);
            }
            if (!\is_array($data)) {
                return [new ValidationError('Expected: "'.$typeName.'", but no array provided.')];
            }
            $arrayTypeStrings = substr($typeName, \strlen($arrayTypePrefix), -1);
            $arrayTypes = explode(',', $arrayTypeStrings);
            switch (\count($arrayTypes)) {
                case 1:
                    $keyType = 'int';
                    $valueType = $arrayTypes[0];
                    break;
                case 2:
                    $keyType = $arrayTypes[0];
                    $valueType = $arrayTypes[1];
                    break;
                default:
                    throw new HttpException('Array type provided with '.\count($arrayTypes).' inner types: '.$typeName.'.', 500);
            }

            $validArrayKeyTypes = ['string', 'int', 'float', 'bool'];
            if (!\in_array($keyType, $validArrayKeyTypes, true)) {
                throw new HttpException('Invalid array key type: '.$keyType.'. Allowed types: '.implode(', ', $validArrayKeyTypes).'.', 500);
            }

            $resultArray = [];
            $validationErrors = [];
            foreach ($data as $keyData => $valueData) {
                $key = self::createType($keyType, $keyData);
                if (!ValidationError::isValid($key)) {
                    array_push($validationErrors, new ValidationError('Invalid array key, expected: '.$keyType.'.', $key));
                }
                $value = self::createType($valueType, $valueData);
                if (!ValidationError::isValid($value)) {
                    array_push($validationErrors, new ValidationError('Invalid value for key "'.$key.'", expected: "'.$valueType.'".', $value));
                }

                if (empty($validationErrors)) {
                    $resultArray[$key] = $value;
                }
            }
            if (!empty($validationErrors)) {
                return $validationErrors;
            }

            return $resultArray;
        }

        // Handle built-in types using their \is_{type} function.
        $typesWithValidators = [
            'string', 'int', 'float', 'double', 'bool', 'null',
        ];
        if (\in_array($typeName, $typesWithValidators, true)) {
            $validatorName = '\is_'.$typeName;
            if (!$validatorName($data)) {
                return [new ValidationError(var_export($data, true).' was not a '.$typeName.'.')];
            }

            return $data;
        }

        // Handle classes
        try {
            /** @psalm-suppress ArgumentTypeCoercion */
            $class = new ReflectionClass($typeName);
        } catch (\ReflectionException $e) {
            throw new HttpException('Unknown type: "'.$typeName.'".', 500);
        }
        if (!\is_array($data)) {
            return [new ValidationError('Could not create "'.$class->getName()
                .' because the value provided was not an array: '.var_export($data, true).'.')];
        }

        return self::classFromArray($class, $data);
    }

    /**
     * @template T of object
     *
     * @param ReflectionClass<T> $class
     *
     * @throws HttpException
     *
     * @return T|array<ValidationError>
     */
    private static function classFromArray($class, array $array)
    {
        $constructor = $class->getConstructor();
        if (null === $constructor) {
            throw new HttpException('Class '.$class->getName().' does not have a constructor.', 500);
        }
        $constructorParameters = $constructor->getParameters();

        $params = [];
        $validationErrors = [];
        foreach ($constructorParameters as $parameter) {
            $parameterName = $parameter->name;
            if (\array_key_exists($parameterName, $array)) {
                $parameterValue = $array[$parameterName];
            } else {
                try {
                    $parameterValue = $parameter->getDefaultValue();
                } catch (\ReflectionException $e) {
                    $errorMessage = 'Required parameter "'.$parameterName.'" not provided for constructor of class '.$class->getName().'.';
                    array_push($validationErrors, new ValidationError($errorMessage));
                    continue;
                }
            }
            $parameterClass = $parameter->getClass();
            if (null === $parameterClass) {
                $parameterType = self::getFunctionParameterTypeFromDoc($constructor, $parameterName);
                if (null === $parameterType) {
                    $message = 'Type not found for constructor parameter "'.$parameterName.'" in class "'.$class->getName().'".';
                    throw new HttpException($message, 500);
                }
                $argument = self::createType($parameterType, $parameterValue);
            } else {
                $parameterType = $parameterClass->getName();
                $argument = self::classFromArray($parameterClass, $parameterValue);
            }
            if (!ValidationError::isValid($argument)) {
                $errorMessage = 'Invalid argument for parameter "'.$parameterName.'", expected "'.$parameterType
                    .'" for constructor of class "'.$class->getName().'".';
                array_push($validationErrors, new ValidationError($errorMessage, $argument));
                continue;
            }
            $params[$parameter->getPosition()] = $argument;
        }

        if (!empty($validationErrors)) {
            return $validationErrors;
        }

        return $class->newInstanceArgs($params);
    }

    /**
     * @param ReflectionMethod $reflectionMethod
     * @param string           $parameterName
     *
     * @return string|null
     */
    private static function getFunctionParameterTypeFromDoc($reflectionMethod, $parameterName)
    {
        $comments = $reflectionMethod->getDocComment();
        $commentLines = explode("\n", $comments);
        $commentLinesUnionTrimmed = array_map(function ($line) {
            return implode('|', array_map('trim', explode('|', $line)));
        }, $commentLines);
        $commentLinesSplitWords = array_map(function ($line) {
            return array_values(array_filter(explode(' ', $line), function ($w) {
                return '' !== ($w);
            }));
        }, $commentLinesUnionTrimmed);
        foreach ($commentLinesSplitWords as $word) {
            if (\count($word) >= 4) {
                if ('*' === $word[0] && '@param' === $word[1] && $word[3] === '$'.$parameterName) {
                    return $word[2];
                }
            }
        }

        return null;
    }
}
