<?php
namespace Tanbolt\Route;

interface RouteInterface
{
    /**
     * 添加一条路由规则
     * @param Router $router
     * @return static
     */
    public function add(Router $router);

    /**
     * 设置一组有共同属性的路由规则
     * @param string $prefix
     * @param callable $callback
     * @return static
     */
    public function group(string $prefix, callable $callback);

    /**
     * 添加一条 GET 任何请求方式规则
     * @param string $uri
     * @param mixed $callback
     * @return Router
     */
    public function get(string $uri, $callback);

    /**
     * 添加一条 POST 任何请求方式规则
     * @param string $uri
     * @param mixed $callback
     * @return Router
     */
    public function post(string $uri, $callback);

    /**
     * 添加一条 任何请求方式规则
     * @param string $uri
     * @param mixed $callback
     * @return Router
     */
    public function any(string $uri, $callback);

    /**
     * 添加一条 Http 请求规则
     * @param array|string $method 请求方式，如 'GET' , ['get', 'post']
     * @param string $uri uri匹配规则
     * @param mixed $callback 回调函数
     * @return Router
     */
    public function http($method, string $uri, $callback);

    /**
     * 获取路由
     * @param string $pathInfo
     * @param ?string $method
     * @param ?string $host
     * @param ?string $scheme
     * @param ?string $port
     * @return bool|Router
     */
    public function dispatch(
        string $pathInfo,
        string $method = null,
        string $host = null,
        string $scheme = null,
        string $port = null
    );

    /**
     * 设置当前 router
     * @param Router $router
     * @return static
     */
    public function setRouter(Router $router);

    /**
     * 取消当前 router
     * @return static
     */
    public function forgetRouter();

    /**
     * 获取当前 router
     * @return Router
     */
    public function router();
}
