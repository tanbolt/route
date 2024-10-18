<?php
namespace Tanbolt\Route;

class Route implements RouteInterface
{
    /**
     * 默认设置: 可接受的 HTTP 规则
     * @var array
     */
    public static $httpMethods = ['ANY', 'GET', 'HEAD', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'];

    /**
     * 默认设置: 大小写是否敏感
     * @var bool
     */
    public static $caseSensitive = false;

    /**
     * router 收集容器
     * @var Router[]
     */
    protected $collection = [];

    /**
     * 当前已匹配路由
     * @var Router
     */
    protected $currentRouter;

    /**
     * 规则 group 前缀
     * @var string
     */
    private $groupPrefix = null;

    /**
     * CaseSensitive
     * @var bool
     */
    private $groupCaseSensitive = null;

    /**
     * scheme
     * @var array|null
     */
    private $groupScheme = null;

    /**
     * domain
     * @var array|null
     */
    private $groupHost = null;

    /**
     * port
     * @var array|null
     */
    private $groupPort = null;

    /**
     * 生成一个 Router
     * @param ?string $uri url path
     * @param mixed $callback 回调
     * @param array $with 其他限制，支持 caseSensitive, method, scheme, host, port
     * @return Router
     */
    public static function makeRouter(string $uri = null, $callback = null, array $with = [])
    {
        return new Router($uri, $callback, $with);
    }

    /**
     * 获取所有已设置的 router 规则
     * @return Router[]
     */
    public function collection()
    {
        return $this->collection;
    }

    /**
     * @inheritdoc
     */
    public function add(Router $router)
    {
        if (!in_array($router, $this->collection)) {
            $this->collection[] = $router;
        }
        return $this;
    }

    /**
     * @inheritdoc
     */
    public function group(string $prefix, callable $callback)
    {
        $prefix = trim($prefix);
        if (empty($prefix)) {
            $prefix = '/';
        } else {
            $prefix = '/' . trim($prefix, '/');
        }
        $this->groupPrefix = $prefix;
        call_user_func($callback, $this);
        $this->groupPrefix = null;
        $this->groupCaseSensitive = null;
        $this->groupScheme = null;
        $this->groupHost = null;
        $this->groupPort = null;
        return $this;
    }

    /**
     * 批量设置大小写是否敏感, 只能用于 group 回调函数中
     * @param ?bool $case
     * @return $this
     */
    public function groupCaseSensitive(?bool $case = true)
    {
        if (null !== $this->groupPrefix) {
            $this->groupCaseSensitive = $case;
        }
        return $this;
    }

    /**
     * 批量设置匹配 scheme，如 http,https..., 只能用于 group 回调函数中
     * @param string[] $scheme
     * @return $this
     */
    public function groupScheme(...$scheme)
    {
        if (null !== $this->groupPrefix) {
            $this->groupScheme = $scheme;
        }
        return $this;
    }

    /**
     * 批量设置匹配 host, 如 foo.com,bar.com..., 只能用于 group 回调函数中
     * @param string[] $host
     * @return $this
     */
    public function groupHost(...$host)
    {
        if (null !== $this->groupPrefix) {
            $this->groupHost = $host;
        }
        return $this;
    }

    /**
     * 批量设置匹配 port，如 80, 443..., 只能用于 group 回调函数中
     * @param string[]|int[] $port
     * @return $this
     */
    public function groupPort(...$port)
    {
        if (null !== $this->groupPrefix) {
            $this->groupPort = $port;
        }
        return $this;
    }

    /**
     * 添加一条 HEAD 任何请求方式规则
     * @param string $uri
     * @param mixed $callback
     * @return Router
     */
    public function head(string $uri, $callback)
    {
        return $this->http(__FUNCTION__, $uri, $callback);
    }

    /**
     * 添加一条 PUT 任何请求方式规则
     * @param string $uri
     * @param mixed $callback
     * @return Router
     */
    public function put(string $uri, $callback)
    {
        return $this->http(__FUNCTION__, $uri, $callback);
    }

    /**
     * 添加一条 PATCH 任何请求方式规则
     * @param string $uri
     * @param mixed $callback
     * @return Router
     */
    public function patch(string $uri, $callback)
    {
        return $this->http(__FUNCTION__, $uri, $callback);
    }

    /**
     * 添加一条 DELETE 任何请求方式规则
     * @param string $uri
     * @param mixed $callback
     * @return Router
     */
    public function delete(string $uri, $callback)
    {
        return $this->http(__FUNCTION__, $uri, $callback);
    }

    /**
     * @inheritdoc
     */
    public function get(string $uri, $callback)
    {
        return $this->http(__FUNCTION__, $uri, $callback);
    }

    /**
     * @inheritdoc
     */
    public function post(string $uri, $callback)
    {
        return $this->http(__FUNCTION__, $uri, $callback);
    }

    /**
     * @inheritdoc
     */
    public function any(string $uri, $callback)
    {
        return $this->http(__FUNCTION__, $uri, $callback);
    }

    /**
     * @inheritdoc
     */
    public function http($method, string $uri, $callback)
    {
        $methods = static::preparedMethods($method);
        $this->collection[] = $router = $this->preparedRouter($methods, $uri, $callback);
        return $router;
    }

    /**
     * 格式化 methods
     * @param string|array $method
     * @return ?array
     */
    protected static function preparedMethods($method)
    {
        $methods = [];
        if (is_array($method)) {
            foreach ($method as $v) {
                if (in_array( $v = strtoupper($v), static::$httpMethods) ) {
                    $methods[] = $v;
                }
            }
        } elseif(is_string($method) && in_array($method = strtoupper($method), static::$httpMethods) ) {
            $methods[] = $method;
        }
        if (in_array('ANY', $methods)) {
            $methods = null;
        }
        return $methods;
    }

    /**
     * 创建 router
     * @param ?array $methods
     * @param string $uri
     * @param mixed $callback
     * @return Router
     */
    protected function preparedRouter(?array $methods, string $uri, $callback)
    {
        if (null !== $methods && (!is_array($methods) || !count($methods))) {
            throw new RouterException('$method not support');
        }
        $uri = '/' . trim($uri, '/');
        if (null !== $this->groupPrefix) {
            $uri = $this->groupPrefix.$uri;
            $with = [
                'method' => $methods,
                'caseSensitive' => null === $this->groupCaseSensitive ? static::$caseSensitive : $this->groupCaseSensitive,
                'scheme' => $this->groupScheme,
                'host' => $this->groupHost,
                'port' => $this->groupPort,
            ];
        } else {
            $with = [
                'method' => $methods,
                'caseSensitive' => static::$caseSensitive,
            ];
        }
        return static::makeRouter($uri, $callback, $with);
    }

    /**
     * 由请求参数获取匹配路由
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
    ) {
        $this->currentRouter = null;
        foreach ($this->collection as $router) {
            if ($router->match($pathInfo, $method, $host, $scheme, $port)) {
                return $this->currentRouter = $router;
            }
        }
        return false;
    }

    /**
     * @inheritdoc
     */
    public function setRouter(Router $router)
    {
        $this->currentRouter = $router;
        return $this;
    }

    /**
     * @inheritdoc
     */
    public function forgetRouter()
    {
        $this->currentRouter = null;
        return $this;
    }

    /**
     * @inheritdoc
     */
    public function router()
    {
        return $this->currentRouter;
    }

    /**
     * 清除当前 router
     * @return $this
     */
    public function __destruct()
    {
        $this->currentRouter = null;
        return $this;
    }
}
