<?php

use Tanbolt\Route\Route;
use Tanbolt\Route\Router;
use PHPUnit\Framework\TestCase;

class RouteTest extends TestCase
{
    public function testAddRouter()
    {
        $route = new Route();
        $router = new Router();
        static::assertCount(0, $route->collection());
        static::assertSame($route, $route->add($router));
        static::assertCount(1, $route->collection());
        static::assertSame($route, $route->add($router));
        static::assertCount(1, $route->collection());
        static::assertSame($router, $route->collection()[0]);
    }

    public function testAddHttpRouter()
    {
        $route = new Route();
        static::assertCount(0, $route->collection());

        $route->get('/foo', 'call');
        static::assertCount(1, $route->collection());
        $router = $route->collection()[0];
        static::assertInstanceOf('\Tanbolt\Route\Router', $router);
        static::assertEquals('/foo', $router->uri());
        static::assertEquals(['GET'], $router->method());
        static::assertEquals('call', $router->callback());
        static::assertFalse($router->isCaseSensitive());
        static::assertNull($router->scheme());
        static::assertNull($router->host());
        static::assertNull($router->port());


        $route->post('/{bar2}', 'call2')->withScheme('http')->caseSensitive(true);
        static::assertCount(2, $route->collection());
        $router = $route->collection()[1];
        static::assertInstanceOf('\Tanbolt\Route\Router', $router);
        static::assertEquals('/{bar2}', $router->uri());
        static::assertEquals(['POST'], $router->method());
        static::assertEquals('call2', $router->callback());
        static::assertTrue($router->isCaseSensitive());
        static::assertEquals(['http'], $router->scheme());
        static::assertNull($router->host());
        static::assertNull($router->port());


        $route->head('/{bar3}', 'call3')->withScheme('http', 'https');
        static::assertCount(3, $route->collection());
        $router = $route->collection()[2];
        static::assertInstanceOf('\Tanbolt\Route\Router', $router);
        static::assertEquals('/{bar3}', $router->uri());
        static::assertEquals(['HEAD'], $router->method());
        static::assertEquals('call3', $router->callback());
        static::assertEquals(['http', 'https'], $router->scheme());
        static::assertNull($router->host());
        static::assertNull($router->port());


        $route->put('/{bar4}', 'call4')->withScheme('http', 'https')->withHost('foo.com');
        static::assertCount(4, $route->collection());
        $router = $route->collection()[3];
        static::assertInstanceOf('\Tanbolt\Route\Router', $router);
        static::assertEquals('/{bar4}', $router->uri());
        static::assertEquals(['PUT'], $router->method());
        static::assertEquals('call4', $router->callback());
        static::assertEquals(['http', 'https'], $router->scheme());
        static::assertEquals(['foo.com'], $router->host());
        static::assertNull($router->port());


        $route->patch('/{bar5}', 'call5')->withScheme('http', 'https')->withHost(['foo.com', 'bar.com']);
        static::assertCount(5, $route->collection());
        $router = $route->collection()[4];
        static::assertInstanceOf('\Tanbolt\Route\Router', $router);
        static::assertEquals('/{bar5}', $router->uri());
        static::assertEquals(['PATCH'], $router->method());
        static::assertEquals('call5', $router->callback());
        static::assertEquals(['http', 'https'], $router->scheme());
        static::assertEquals(['foo.com', 'bar.com'], $router->host());
        static::assertNull($router->port());


        $route->delete('/{bar6}', 'call6')->withScheme('http', 'https')->withHost(['foo.com', 'bar.com'])->withPort(80);
        static::assertCount(6, $route->collection());
        $router = $route->collection()[5];
        static::assertInstanceOf('\Tanbolt\Route\Router', $router);
        static::assertEquals('/{bar6}', $router->uri());
        static::assertEquals(['DELETE'], $router->method());
        static::assertEquals('call6', $router->callback());
        static::assertEquals(['http', 'https'], $router->scheme());
        static::assertEquals(['foo.com', 'bar.com'], $router->host());
        static::assertEquals(['80'], $router->port());


        $route->any('/{bar7}', 'call7')->withScheme('http', 'https')->withHost(['foo.com', 'bar.com'])->withPort(80, 8080);
        static::assertCount(7, $route->collection());
        $router = $route->collection()[6];
        static::assertInstanceOf('\Tanbolt\Route\Router', $router);
        static::assertEquals('/{bar7}', $router->uri());
        static::assertNull($router->method());
        static::assertEquals('call7', $router->callback());
        static::assertEquals(['http', 'https'], $router->scheme());
        static::assertEquals(['foo.com', 'bar.com'], $router->host());
        static::assertEquals(['80', '8080'], $router->port());


        $route->http(['get','post'], '/{bar8}', 'call8')
            ->withScheme('http', 'https')->withHost(['foo.com', 'bar.com'])->withPort(80, 8080);
        static::assertCount(8, $route->collection());
        $router = $route->collection()[7];
        static::assertInstanceOf('\Tanbolt\Route\Router', $router);
        static::assertEquals('/{bar8}', $router->uri());
        static::assertEquals(['GET', 'POST'], $router->method());
        static::assertEquals('call8', $router->callback());
        static::assertEquals(['http', 'https'], $router->scheme());
        static::assertEquals(['foo.com', 'bar.com'], $router->host());
        static::assertEquals(['80', '8080'], $router->port());


        $route->http(['any','get','post'], '/{bar8}', 'call8')
            ->withScheme('http', 'https')->withHost(['foo.com', 'bar.com'])->withPort(80, 8080);
        static::assertCount(9, $route->collection());
        $router = $route->collection()[8];
        static::assertInstanceOf('\Tanbolt\Route\Router', $router);
        static::assertEquals('/{bar8}', $router->uri());
        static::assertNull($router->method());
        static::assertEquals('call8', $router->callback());
        static::assertEquals(['http', 'https'], $router->scheme());
        static::assertEquals(['foo.com', 'bar.com'], $router->host());
        static::assertEquals(['80', '8080'], $router->port());
    }

    public function groupRouterTest(Route $route)
    {
        $routers = $route->collection();
        static::assertCount(6, $routers);

        static::assertEquals('/foo/bar', $routers[0]->uri());
        static::assertEquals('call1', $routers[0]->callback());
        static::assertTrue($routers[0]->isCaseSensitive());
        static::assertEquals(['GET'], $routers[0]->method());
        static::assertEquals(['http'], $routers[0]->scheme());
        static::assertEquals(['foo.com'], $routers[0]->host());
        static::assertNull($routers[0]->port());

        static::assertEquals('/foo/biz', $routers[1]->uri());
        static::assertEquals('call2', $routers[1]->callback());
        static::assertFalse($routers[1]->isCaseSensitive());
        static::assertEquals(['POST'], $routers[1]->method());
        static::assertEquals(['http'], $routers[1]->scheme());
        static::assertEquals(['foo.com'], $routers[1]->host());
        static::assertEquals(['80'], $routers[1]->port());

        static::assertEquals('/foo/{bar}', $routers[2]->uri());
        static::assertEquals('call3', $routers[2]->callback());
        static::assertFalse($routers[2]->isCaseSensitive());
        static::assertEquals(['PUT'], $routers[2]->method());
        static::assertEquals(['https'], $routers[2]->scheme());
        static::assertEquals(['foo.com'], $routers[2]->host());
        static::assertNull($routers[2]->port());

        static::assertEquals('/foo/{biz}', $routers[3]->uri());
        static::assertEquals('call4', $routers[3]->callback());
        static::assertTrue($routers[3]->isCaseSensitive());
        static::assertEquals(['DELETE'], $routers[3]->method());
        static::assertEquals(['http','https'], $routers[3]->scheme());
        static::assertEquals(['bar.com'], $routers[3]->host());
        static::assertNull($routers[3]->port());

        static::assertEquals('/foo/bar/{biz}', $routers[4]->uri());
        static::assertEquals('call5', $routers[4]->callback());
        static::assertFalse($routers[4]->isCaseSensitive());
        static::assertNull($routers[4]->method());
        static::assertNull($routers[4]->scheme());
        static::assertNull($routers[4]->host());
        static::assertNull($routers[4]->port());

        static::assertEquals('/bar/biz', $routers[5]->uri());
        static::assertEquals('call6', $routers[5]->callback());
        static::assertFalse($routers[5]->isCaseSensitive());
        static::assertEquals(['GET'], $routers[0]->method());
        static::assertNull($routers[5]->scheme());
        static::assertNull($routers[5]->host());
        static::assertNull($routers[5]->port());
    }

    public function testGroupRouter()
    {
        $route = new Route();
        $route->group('/foo/', function(Route $route) {
            $route->groupCaseSensitive(true)->groupScheme('http')->groupHost('foo.com');
            $route->get('/bar', 'call1');
            $route->post('/biz/', 'call2')->caseSensitive(false)->withPort(80);
            $route->put('/{bar}', 'call3')->caseSensitive(false)->withScheme('https');
            $route->delete('/{biz}', 'call4')->withHost('bar.com')->withScheme('http', 'https');
            $route->any('/bar/{biz}', 'call5')->withScheme(null)->withHost(null)->caseSensitive(false);
        });
        $route->get('/bar/biz', 'call6');
        $this->groupRouterTest($route);


        $route = new Route();
        $route->group('/foo/', function() use ($route) {
            $route->groupCaseSensitive(true)->groupScheme('http')->groupHost('foo.com');
            $route->get('/bar', 'call1');
            $route->post('/biz/', 'call2')->caseSensitive(false)->withPort(80);
            $route->put('/{bar}', 'call3')->caseSensitive(false)->withScheme('https');
            $route->delete('/{biz}', 'call4')->withHost('bar.com')->withScheme('http', 'https');
            $route->any('/bar/{biz}', 'call5')->withScheme(null)->withHost(null)->caseSensitive(false);
        });
        $route->get('/bar/biz', 'call6');
        $this->groupRouterTest($route);
    }

    public function testDispatch()
    {
        $route = new Route();
        $route->get('/foo', 'call1')->withHost('foo.com');
        $route->get('/foo', 'call1');
        $route->get('/foo/{bar}', 'call3');
        $route->post('/bar', 'call2')->withMethod(['post', 'put']);

        $router = Route::makeRouter();

        static::assertFalse($route->dispatch('/foo', 'POST'));
        static::assertNull($route->router());

        static::assertEquals($route->collection()[0], $route->dispatch('/foo', 'get', 'foo.com'));
        static::assertEquals($route->collection()[0], $route->router());

        static::assertEquals($route->collection()[1], $route->dispatch('/foo', 'get'));
        static::assertEquals($route->collection()[1], $route->router());

        // set forget
        static::assertSame($route, $route->setRouter($router));
        static::assertEquals($router, $route->router());
        static::assertSame($route, $route->forgetRouter());
        static::assertNull($route->router());

        static::assertEquals($route->collection()[2], $route->dispatch('/foo/name', 'get', 'foo.com'));
        static::assertEquals($route->collection()[2], $route->router());
        static::assertEquals(['bar' => 'name'], $route->router()->getMatches());

        static::assertEquals($route->collection()[2], $route->dispatch('/foo/val', 'get', 'foo.com'));
        static::assertEquals($route->collection()[2], $route->router());
        static::assertEquals(['bar' => 'val'], $route->router()->getMatches());

        static::assertFalse($route->dispatch('/bar', 'GET'));
        static::assertNull($route->router());

        static::assertEquals($route->collection()[3], $route->dispatch('/bar', 'POST'));
        static::assertEquals($route->collection()[3], $route->router());
        static::assertSame($route, $route->forgetRouter());
        static::assertEquals($route->collection()[3], $route->dispatch('/bar', 'put'));
        static::assertEquals($route->collection()[3], $route->router());
    }

}
