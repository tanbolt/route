<?php

use Tanbolt\Route\Router;
use PHPUnit\Framework\TestCase;

class RouterTest extends TestCase
{
    public function testInstance()
    {
        $router = new Router();
        static::assertEquals('/', $router->uri());
        static::assertNull($router->callback());
        static::assertNull($router->host());
        static::assertNull( $router->method());
        static::assertNull($router->scheme());
        static::assertNull($router->port());

        $router = new Router('/', 'call', [
            'host' => 'foo.com',
            'method' => ['GET','POST'],
            'scheme' => 'http',
            'port' => [80, 90]
        ]);
        static::assertEquals('/', $router->uri());
        static::assertEquals('call', $router->callback());
        static::assertEquals(['foo.com'], $router->host());
        static::assertEquals(['GET', 'POST'], $router->method());
        static::assertEquals(['http'], $router->scheme());
        static::assertEquals(['80', '90'], $router->port());
    }

    public function testCaseSensitive()
    {
        $router = new Router();
        static::assertFalse($router->isCaseSensitive());
        static::assertSame($router, $router->caseSensitive(true));
        static::assertTrue($router->isCaseSensitive());
        static::assertSame($router, $router->caseSensitive(false));
        static::assertFalse($router->isCaseSensitive());
    }

    public function testMethodMethod()
    {
        $router = new Router();
        static::assertNull($router->method());
        static::assertSame($router, $router->withMethod('GET'));
        static::assertEquals(['GET'], $router->method());
        static::assertSame($router, $router->withMethod(['get', 'POST']));
        static::assertEquals(['GET', 'POST'], $router->method());
        static::assertSame($router, $router->withMethod('PUT', 'GET', 'POST'));
        static::assertEquals(['PUT', 'GET', 'POST'], $router->method());
        static::assertSame($router, $router->withMethod(null));
        static::assertNull($router->method());
    }

    public function testSchemaMethod()
    {
        $router = new Router();
        static::assertNull($router->scheme());
        static::assertSame($router, $router->withScheme('HTTP'));
        static::assertEquals(['http'], $router->scheme());
        static::assertSame($router, $router->withScheme(['http', 'Https']));
        static::assertEquals(['http', 'https'], $router->scheme());
        static::assertSame($router, $router->withScheme('ftp', 'http', 'Ssh'));
        static::assertEquals(['ftp', 'http', 'ssh'], $router->scheme());
        static::assertSame($router, $router->withScheme(null));
        static::assertNull($router->scheme());
    }

    public function testHostMethod()
    {
        $router = new Router();
        static::assertNull($router->host());
        static::assertSame($router, $router->withHost('foo.com'));
        static::assertEquals(['foo.com'], $router->host());
        static::assertSame($router, $router->withHost(['Foo.com', 'bar.com']));
        static::assertEquals(['foo.com', 'bar.com'], $router->host());
        static::assertSame($router, $router->withHost('foo.com', 'Biz.com', 'bar.cc'));
        static::assertEquals(['foo.com', 'biz.com', 'bar.cc'], $router->host());
        static::assertSame($router, $router->withHost(null));
        static::assertNull($router->host());
    }

    public function testPortMethod()
    {
        $router = new Router();
        static::assertNull($router->port());
        static::assertSame($router, $router->withPort(80));
        static::assertEquals(['80'], $router->port());
        static::assertSame($router, $router->withPort([80, '8080']));
        static::assertEquals(['80', '8080'], $router->port());
        static::assertSame($router, $router->withPort('80', 443, '8080'));
        static::assertEquals(['80', '443', '8080'], $router->port());
        static::assertSame($router, $router->withPort(null));
        static::assertNull($router->port());
    }

    public function testUriMethod()
    {
        $router = new Router();
        static::assertEquals('/', $router->uri());
        static::assertSame($router, $router->withUri('/{foo}'));
        static::assertEquals('/{foo}', $router->uri());

        $router->withUri('/{foo}/bar/');
        static::assertEquals('/{foo}/bar', $router->uri());

        $router->withUri('{bar}/biz');
        static::assertEquals('/{bar}/biz', $router->uri());

        $router->withUri('{biz}/foo/');
        static::assertEquals('/{biz}/foo', $router->uri());
    }

    public function testCallbackMethod()
    {
        $router = new Router();
        static::assertNull($router->callback());
        static::assertSame($router, $router->setCallback('foo'));
        static::assertEquals('foo', $router->callback());

        $function = function() {
        };

        $router->setCallback($function);
        static::assertEquals($function, $router->callback());

        $router->setCallback(['class', 'method']);
        static::assertEquals(['class', 'method'], $router->callback());
    }

    public function testMatchBasic()
    {
        $router = new Router('/foo/bar');

        static::assertSame($router, $router->match('/Foo/bar/'));
        static::assertEquals([], $router->getParameters());
        static::assertEquals([], $router->getMatches());

        static::assertFalse($router->match('/bar/foo'));
        $router->caseSensitive(true);
        static::assertFalse($router->match('/Foo/bar/'));
    }

    public function testMatchVar()
    {
        $router = new Router('/foo/{bar}/{biz}');
        static::assertFalse($router->match('/bar/foo'));
        static::assertFalse($router->match('/foo/bar'));
        static::assertFalse($router->match('/foo/bar/'));
        static::assertEquals([], $router->getParameters());
        static::assertEquals([], $router->getMatches());

        static::assertSame($router, $router->match('/Foo/Key/val'));
        static::assertEquals(['bar' => 'Key', 'biz' => 'val'], $router->getParameters());
        static::assertEquals(['bar' => 'Key', 'biz' => 'val'], $router->getMatches());
        static::assertEquals('Key', $router->getParameters('bar'));
        static::assertEquals('val', $router->getParameters('biz'));
        static::assertNull($router->getParameters('none'));
        static::assertEquals('def', $router->getParameters('none', 'def'));
        static::assertSame($router, $router->setParameters('bar', 'bar'));
        static::assertEquals('bar', $router->getParameters('bar'));

        $router->caseSensitive(true);
        static::assertFalse($router->match('/Foo/Key/val'));
    }

    public function testMatchVarCanPass()
    {
        $router = new Router('/foo/{bar}/{biz?}');
        static::assertFalse($router->match('/bar/foo'));
        static::assertFalse($router->match('/foo/'));
        static::assertEquals([], $router->getParameters());
        static::assertEquals([], $router->getMatches());

        $router = new Router('/foo/{bar}/{biz?}');
        static::assertSame($router, $router->match('/Foo/Key/val'));
        static::assertEquals(['bar' => 'Key', 'biz' => 'val'], $router->getParameters());
        static::assertEquals(['bar' => 'Key', 'biz' => 'val'], $router->getMatches());
        static::assertEquals('Key', $router->getParameters('bar'));
        static::assertEquals('val', $router->getParameters('biz'));
        static::assertNull($router->getParameters('none'));
        static::assertEquals('def', $router->getParameters('none', 'def'));
        static::assertSame($router, $router->setParameters('bar', 'bar'));
        static::assertEquals('bar', $router->getParameters('bar'));

        $router = new Router('/foo/{bar}/{biz?}');
        static::assertSame($router, $router->match('/Foo/Key'));
        static::assertEquals(['bar' => 'Key', 'biz' => null], $router->getParameters());
        static::assertEquals(['bar' => 'Key'], $router->getMatches());
        static::assertEquals('Key', $router->getParameters('bar'));
        static::assertNull($router->getParameters('biz'));
        static::assertNull($router->getParameters('none'));
        static::assertEquals('def', $router->getParameters('none', 'def'));
        static::assertSame($router, $router->setParameters('bar', 'bar'));
        static::assertEquals('bar', $router->getParameters('bar'));

        $router = new Router('/pre/{foo}/{bar?}/{biz?}');
        static::assertSame($router, $router->match('/Pre/Key'));
        static::assertEquals(['foo' => 'Key', 'bar' => null, 'biz' => null], $router->getParameters());
        static::assertEquals(['foo' => 'Key'], $router->getMatches());
        static::assertEquals('Key', $router->getParameters('foo'));
        static::assertNull($router->getParameters('bar'));
        static::assertNull($router->getParameters('biz'));

        $router = new Router('/pre/{foo}/{bar?}/{biz?}');
        static::assertSame($router, $router->match('/Pre/Key/val/'));
        static::assertEquals(['foo' => 'Key', 'bar' => 'val', 'biz' => null], $router->getParameters());
        static::assertEquals(['foo' => 'Key', 'bar' => 'val'], $router->getMatches());
        static::assertEquals('Key', $router->getParameters('foo'));
        static::assertEquals('val', $router->getParameters('bar'));
        static::assertNull($router->getParameters('biz'));

        $router = new Router('/pre/{foo}/{bar?}/{biz?}');
        static::assertSame($router, $router->match('/Pre/Key/val/4'));
        static::assertEquals(['foo' => 'Key', 'bar' => 'val', 'biz' => '4'], $router->getParameters());
        static::assertEquals(['foo' => 'Key', 'bar' => 'val', 'biz' => '4'], $router->getMatches());
        static::assertEquals('Key', $router->getParameters('foo'));
        static::assertEquals('val', $router->getParameters('bar'));
        static::assertEquals('4', $router->getParameters('biz'));
    }


    public function testMathWithParameter()
    {
        $router = new Router('/foo', null, [
            'method' => 'GET'
        ]);
        static::assertFalse($router->match('/foo'));
        static::assertFalse($router->match('/foo', 'POST'));
        static::assertSame($router, $router->match('/foo', 'GET'));
        static::assertSame($router, $router->match('/foo', 'get'));


        $router = new Router('/foo', null, [
            'host' => 'foo.com'
        ]);
        static::assertFalse($router->match('/foo'));
        static::assertFalse($router->match('/foo', null, 'bar.com'));
        static::assertSame($router, $router->match('/foo', null, 'foo.com'));
        static::assertSame($router, $router->match('/foo', null, 'Foo.com'));


        $router = new Router('/foo', null, [
            'scheme' => ['http', 'https']
        ]);
        static::assertFalse($router->match('/foo'));
        static::assertFalse($router->match('/foo', null, null, 'ftp'));
        static::assertSame($router, $router->match('/foo', null, null, 'http'));
        static::assertSame($router, $router->match('/foo', null, null, 'https'));


        $router = new Router('/foo', null, [
            'port' => [80, '8080']
        ]);
        static::assertFalse($router->match('/foo'));
        static::assertFalse($router->match('/foo', null, null, null, '443'));
        static::assertSame($router, $router->match('/foo', null, null, null, '80'));
        static::assertSame($router, $router->match('/foo', null, null, null, 8080));

        $router = new Router('/foo', null, [
            'method' => 'GET',
            'scheme' => ['http', 'https'],
            'host' => 'foo.com',
            'port' => '80',
        ]);
        static::assertFalse($router->match('/foo'));
        static::assertFalse($router->match('/foo', 'GET'));
        static::assertFalse($router->match('/foo', 'GET', 'foo.com'));
        static::assertFalse($router->match('/foo', 'GET', 'foo.com', 'http'));
        static::assertSame($router, $router->match('/foo', 'GET', 'foo.com', 'http', 80));
        static::assertSame($router, $router->match('/Foo', 'get', 'Foo.com', 'Http', 80));

        $router = new Router('/foo');
        static::assertSame($router, $router->match('/Foo'));
        static::assertSame($router, $router->match('/Foo', 'get'));
        static::assertSame($router, $router->match('/Foo', 'get', 'Foo.com'));
        static::assertSame($router, $router->match('/Foo', 'get', 'Foo.com', 'Http'));
        static::assertSame($router, $router->match('/Foo', 'get', 'Foo.com', 'Http', 80));
    }


    public function testMatchAnything()
    {
        $router = new Router('/pre/{foo}');
        static::assertFalse($router->match('/Pre'));
        static::assertFalse($router->match('/Pre/foo/bar'));

        $router->isAnything('foo');
        static::assertFalse($router->match('/Pre'));
        static::assertFalse($router->match('/Pre/'));

        static::assertSame($router, $router->match('/Pre/foo/bar'));
        static::assertEquals(['foo' => 'foo/bar'], $router->getParameters());
        static::assertEquals(['foo' => 'foo/bar'], $router->getMatches());

        static::assertSame($router, $router->match('/Pre/foo/bar/biz'));
        static::assertEquals(['foo' => 'foo/bar/biz'], $router->getParameters());
        static::assertEquals(['foo' => 'foo/bar/biz'], $router->getMatches());

        $router->withUri('/pre/{foo?}');

        static::assertSame($router, $router->match('/Pre'));
        static::assertEquals(['foo' => null], $router->getParameters());
        static::assertEquals([], $router->getMatches());

        static::assertSame($router, $router->match('/Pre/'));
        static::assertEquals(['foo' => null], $router->getParameters());
        static::assertEquals([], $router->getMatches());

        static::assertSame($router, $router->match('/Pre/foo/bar'));
        static::assertEquals(['foo' => 'foo/bar'], $router->getParameters());
        static::assertEquals(['foo' => 'foo/bar'], $router->getMatches());

        static::assertSame($router, $router->match('/Pre/foo/bar/biz'));
        static::assertEquals(['foo' => 'foo/bar/biz'], $router->getParameters());
        static::assertEquals(['foo' => 'foo/bar/biz'], $router->getMatches());
    }

    /**
     * @dataProvider filterData
     * @param $filter
     * @param $option
     * @param $rightBar
     * @param array $errorBar
     */
    public function testMatchFilterSystem($filter, $option, $rightBar, array $errorBar)
    {
        $call = ['bar'];
        if (is_array($option)) {
            $call = array_merge($call, $option);
        } elseif ($option !== null) {
            $call[] = $option;
        }

        $router = new Router('/pre/{foo}/{bar}');
        static::assertSame($router, $router->match('/Pre/Key/val'));
        static::assertEquals(['foo' => 'Key', 'bar' => 'val'], $router->getParameters());
        static::assertEquals(['foo' => 'Key', 'bar' => 'val'], $router->getMatches());

        $router = new Router('/pre/{foo}/{bar}');
        call_user_func_array([$router, $filter], $call);
        static::assertSame($router, $router->match('/Pre/Key/'.$rightBar));
        static::assertEquals(['foo' => 'Key', 'bar' => $rightBar], $router->getParameters());
        static::assertEquals(['foo' => 'Key', 'bar' => $rightBar], $router->getMatches());

        foreach ($errorBar as $bar) {
            $router = new Router('/pre/{foo}/{bar}');
            call_user_func_array([$router, $filter], $call);
            static::assertFalse($router->match('/Pre/Key/'.$bar));
            static::assertEquals([], $router->getParameters());
            static::assertEquals([], $router->getMatches());
        }

        $router = new Router('/pre/{foo}/{bar?}');
        call_user_func_array([$router, $filter], $call);
        static::assertSame($router, $router->match('/Pre/Key/'.$rightBar));
        static::assertEquals(['foo' => 'Key', 'bar' => $rightBar], $router->getParameters());
        static::assertEquals(['foo' => 'Key', 'bar' => $rightBar], $router->getMatches());

        foreach ($errorBar as $bar) {
            $router = new Router('/pre/{foo}/{bar?}');
            call_user_func_array([$router, $filter], $call);
            static::assertFalse($router->match('/Pre/Key/'.$bar));
            static::assertEquals([], $router->getParameters());
            static::assertEquals([], $router->getMatches());
        }

        $router = new Router('/pre/{foo}/{bar?}');
        $router->isDigit('bar');
        static::assertSame($router, $router->match('/Pre/Key'));
        static::assertEquals(['foo' => 'Key', 'bar' => null], $router->getParameters());
        static::assertEquals(['foo' => 'Key'], $router->getMatches());
    }

    public function filterData()
    {
        return [
            [
                'isGraph', null, 'abc', ['a b', 'c  d']
            ],

            [
                'isAlpha', null, 'abcEFG', ['a2', 'd_']
            ],

            [
                'isAlpha', true, 'ABC', ['abc', 'Ea', '2D']
            ],

            [
                'isAlpha', false, 'abc', ['ABC', 'Ea', '2D']
            ],

            [
                'isDigit', null, 4, ['val', '4a']
            ],

            [
                'isAlnum', false, 'abc123', ['abc_123', '777*', '==哈哈']
            ],

            [
                'isAlnum', true, 'abc_123', ['abc+123', '777*', '==哈哈']
            ],

            [
                'isIp', null, '120.40.44.171', [
                    '192.168.1.1',
                    '255.255.255.255',
                    '24a6:57:c:36cf:0000:5efe:109.205.140.116',
                    'fe80:4:6c:8c74:0000:5efe:109.205.140.116',
                ]
            ],

            [
                'isIp', [true], '120.40.44.171', [
                    '192.168.1.1',
                    '255.255.255.255',
                    'fe80:4:6c:8c74:0000:5efe:109.205.140.116',
                ]
            ],

            [
                'isIp', [true], '24a6:57:c:36cf:0000:5efe:109.205.140.116', [
                    '192.168.1.1',
                    '255.255.255.255',
                    'fe80:4:6c:8c74:0000:5efe:109.205.140.116',
                ]
            ],

            [
                'isIp', [true, true], '192.168.1.1', ['22.x']
            ],

            [
                'isIp', [true, true], '255.255.255.255', ['22.x']
            ],

            [
                'isIp', [true, true], 'fe80:4:6c:8c74:0000:5efe:109.205.140.116', ['22.x']
            ],

            [
                'isEmail', null, 'foo@bar.com', ['foo', 'foo@', 'foo@bar']
            ],

            [
                'isZh', null, '中', ['foo', '汉h', '你2']
            ],

            [
                'isZhNick', null, '中_222Foo', ['fo*o', '汉h^']
            ],


            [
                'isRegex', '\d+', '2222', ['fo*o', '汉h^', '22ddd']
            ],
        ];
    }
}
