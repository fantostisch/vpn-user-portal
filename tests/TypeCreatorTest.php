<?php

/*
 * eduVPN - End-user friendly VPN.
 *
 * Copyright: 2016-2019, The Commons Conservancy eduVPN Programme
 * SPDX-License-Identifier: AGPL-3.0+
 */

namespace LC\Portal\Tests;

use LC\Portal\WireGuard\Daemon\WGDaemonError;
use LC\Portal\WireGuard\Manager\WGClientConnection;
use LC\Portal\WireGuard\Validator\TypeCreator;
use LC\Portal\WireGuard\Validator\ValidationError;
use PHPUnit\Framework\TestCase;

class Foo
{
    /** @var WGClientConnection */
    public $wgClientConnection;

    /** @var array<string> */
    public $stringArray;

    /** @var array<WGClientConnection> */
    public $connections;

    /** @var string */
    public $optionalString;

    /**
     * @param WGClientConnection                                     $wgClientConnection Connection
     * @param array<string>                                          $stringArray
     * @param array<\LC\Portal\WireGuard\Manager\WGClientConnection> $connections        Connections
     * @param string                                                 $optionalString
     */
    public function __construct(WGClientConnection $wgClientConnection, array $stringArray, array $connections, $optionalString = 'defaultValue')
    {
        $this->wgClientConnection = $wgClientConnection;
        $this->stringArray = $stringArray;
        $this->connections = $connections;
        $this->optionalString = $optionalString;
    }
}

class TypeCreatorTest extends TestCase
{
    public function testValidData()
    {
        $wgConnection1 = new WGClientConnection('publicKey1===', 'My Config 1', 'clientId1', ['ip1', 'ip1']);
        $wgConnection2 = new WGClientConnection('publicKey2===', 'My config 2', 'clientId2', ['ip2', 'ip2']);
        $wgConnection3 = new WGClientConnection('publicKey3===', 'My config 3', 'clientId3', ['ip3', 'ip3']);

        $tests = [
            ['string' => 'test'],
            ['"StringLiteral"' => 'StringLiteral'],
            ['"3"' => '3'],
            ['3' => 3],
            ['3.14' => 3.14],
            ['int' => 4],
            ['float' => 3.1],
            ['double' => 3.1],
            ['bool' => true],
            ['bool' => false],
            ['null' => null],
            ['3 | "String Literal 1" | "String Literal 2"' => 'String Literal 2'],
            ['null|string' => null],
            ['array<string>' => ['1', '2', '3', 'foo']],
            ['array<int>' => [1, 2, 3]],
            ['array<string,string>' => ['k1' => 'v1', 'k2' => 'v2', 'k3' => 'v3']],
            ['\LC\Portal\WireGuard\Manager\WGClientConnection' => $wgConnection1],
            ['\LC\Portal\WireGuard\Daemon\WGDaemonError' => new WGDaemonError('user_already_disabled', 'Error description.')],
            ['array<\LC\Portal\WireGuard\Manager\WGClientConnection>' => [$wgConnection1, $wgConnection2, $wgConnection3]],
            ['array<string,array<\LC\Portal\WireGuard\Manager\WGClientConnection>>' => ['k1' => [$wgConnection1], 'k2' => [$wgConnection2, $wgConnection3], 'k3' => []]],
            ['\LC\Portal\Tests\Foo' => new Foo($wgConnection1, ['one', 'two', 'three'], [$wgConnection1, $wgConnection2, $wgConnection3])],
            ['\LC\Portal\Tests\Foo' => new Foo($wgConnection1, ['one', 'two', 'three'], [$wgConnection1, $wgConnection2, $wgConnection3], 'not the default value')],
        ];

        foreach ($tests as $test) {
            foreach ($test as $type => $expected) {
                $actual = TypeCreator::createType($type, json_decode(json_encode($expected), true));
                if (!ValidationError::isValid($actual)) {
                    $message = (new ValidationError('Could not create: '.$type, $actual))->__toString();
                } else {
                    $message = 'No validation errors for type '.$type;
                }
                $this->assertEquals($expected, $actual, $message);
            }
        }
    }

    public function testMissingConstructorArgument()
    {
        $type = '\LC\Portal\WireGuard\Manager\WGClientConnection';
        $data = ['publicKey' => 'publicKey1===', 'ip' => ['ip1', 'ip2']];
        $expected = [
            new ValidationError('Required parameter "name" not provided for constructor of class LC\Portal\WireGuard\Manager\WGClientConnection.'),
            new ValidationError('Required parameter "clientId" not provided for constructor of class LC\Portal\WireGuard\Manager\WGClientConnection.'),
            new ValidationError('Required parameter "allowedIPs" not provided for constructor of class LC\Portal\WireGuard\Manager\WGClientConnection.'),
        ];
        $actual = TypeCreator::createType($type, json_decode(json_encode($data), true));
        $this->assertEquals($expected, $actual);
    }

    public function testConstructWithoutArgumentList()
    {
        $type = '\LC\Portal\WireGuard\Manager\WGClientConnection';
        $data = 'yeeh';
        $expected = [new ValidationError('Could not create "LC\Portal\WireGuard\Manager\WGClientConnection because the value provided was not an array: \'yeeh\'.')];
        $actual = TypeCreator::createType($type, json_decode(json_encode($data), true));
        $this->assertEquals($expected, $actual);
    }

    public function testInvalidTypeForUnion()
    {
        $type = '6.4 | "Sting Literal 1" | "String Literal 2" | "String Literal 3"';
        $data = 'String Literal 4';
        $actual = TypeCreator::createType($type, json_decode(json_encode($data), true));
        $valid = ValidationError::isValid($actual);
        if (!$valid) {
            $message = (new ValidationError('Could not create: '.$type, $actual))->__toString();
        } else {
            $message = 'No validation errors for type '.$type;
        }
        $this->assertSame($valid, false, $message);
    }
}
