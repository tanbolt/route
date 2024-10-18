<?php
namespace Tanbolt\Route;

class Router
{
    const REGEX_DELIMITER = '#';

    /**
     * 正则表达式中的特殊字符
     */
    const SEPARATORS = '/,;.:-_~+*=@|';

    const FILTER_GRAPH = 0;
    const FILTER_ALPHA = 5;
    const FILTER_DIGIT = 15;
    const FILTER_ALNUM = 20;
    const FILTER_IP = 30;
    const FILTER_EMAIL = 25;
    const FILTER_ZH = 40;
    const FILTER_ZH_NICK = 45;
    const FILTER_REGEXP = 50;

    /**
     * 是否大小写敏感
     * @var bool
     */
    private $caseSensitive = false;

    /**
     * method
     * @var array|null
     */
    private $method = null;

    /**
     * scheme
     * @var array|null
     */
    private $scheme = null;

    /**
     * domain
     * @var array|null
     */
    private $host = null;

    /**
     * port
     * @var array|null
     */
    private $port = null;

    /**
     * pathInfo
     * @var string
     */
    private $uri = null;

    /**
     * callback
     * @var null
     */
    private $callback = null;

    /**
     * anything 变量容器
     * @var string[]
     */
    private $anything = [];

    /**
     * 校验规则容器
     * @var array
     */
    private $filters = [];

    /**
     * uri 正则匹配数组
     * @var array
     */
    private $matches = null;

    /**
     * 解析后的正则表达式
     * @var string
     */
    private $compiled = null;

    /**
     * prefix
     * @var string
     */
    private $prefix = null;

    /**
     * 路由中包含的变量
     * @var array|null
     */
    private $variables = null;

    /**
     * preg 结果
     * @var array|null
     */
    private $tokens = null;

    /**
     * uri 匹配且验证过的 结果
     * @var array
     */
    private $parameters = [];

    /**
     * 创建 Router 对象
     * @param ?string $uri
     * @param mixed $callback
     * @param array $with
     */
    public function __construct(string $uri = null, $callback = null, array $with = [])
    {
        if (empty($uri)) {
            $uri = '/';
        } elseif ('/' !== $uri[0]) {
            $uri = '/'.$uri;
        }
        $this->withUri($uri);
        if ($callback) {
            $this->setCallback($callback);
        }
        if (isset($with['caseSensitive'])) {
            $this->caseSensitive($with['caseSensitive']);
        }
        if (isset($with['method'])) {
            $this->withMethod($with['method']);
        }
        if (isset($with['scheme'])) {
            $this->withScheme($with['scheme']);
        }
        if (isset($with['host'])) {
            $this->withHost($with['host']);
        }
        if (isset($with['port'])) {
            $this->withPort($with['port']);
        }
    }

    /**
     * 设置是否大小写敏感
     * @param bool $enable
     * @return $this
     */
    public function caseSensitive(bool $enable = true)
    {
        $this->caseSensitive = $enable;
        return $this;
    }

    /**
     * 判断当前 router 是否大小写敏感
     * @return bool
     */
    public function isCaseSensitive()
    {
        return $this->caseSensitive;
    }

    /**
     * 设置匹配的请求方式，如 GET,POST...
     * @param array|string[] $method
     * @return $this
     */
    public function withMethod(...$method)
    {
        $this->method = static::tobeArray($method, true);
        return $this;
    }

    /**
     * 获取当前匹配的请求方式
     * @return ?array
     */
    public function method()
    {
        return $this->method;
    }

    /**
     * 设置匹配的 scheme，如 http,https...
     * @param array|string[] $scheme
     * @return $this
     */
    public function withScheme(...$scheme)
    {
        $this->scheme = static::tobeArray($scheme, false);
        return $this;
    }

    /**
     * 获取当前匹配的 scheme
     * @return ?array
     */
    public function scheme()
    {
        return $this->scheme;
    }

    /**
     * 设置匹配的 host, 如 foo.com,bar.com...
     * @param array|string[] $host
     * @return $this
     */
    public function withHost(...$host)
    {
        $this->host = static::tobeArray($host, false);
        return $this;
    }

    /**
     * 获取当前匹配的 host
     * @return ?array
     */
    public function host()
    {
        return $this->host;
    }

    /**
     * 设置匹配的 port，如 80, 443...
     * @param array|string[]|int[] $port
     * @return $this
     */
    public function withPort(...$port)
    {
        $this->port = static::tobeArray($port, false);
        return $this;
    }

    /**
     * 获取当前匹配的 port
     * @return ?array
     */
    public function port()
    {
        return $this->port;
    }

    /**
     * 设置匹配的 uri
     * @param string $uri
     * @return $this
     */
    public function withUri(string $uri)
    {
        $this->uri = '/'.trim($uri, '/');
        $this->matches = $this->compiled = null;
        return $this;
    }

    /**
     * 获取当前匹配的 uri
     * @return string
     */
    public function uri()
    {
        return $this->uri;
    }

    /**
     * 设置匹配规则的回调函数
     * @param mixed $callback
     * @return $this
     */
    public function setCallback($callback)
    {
        $this->callback = $callback;
        return $this;
    }

    /**
     * 获取匹配规则的回调函数
     * @return mixed
     */
    public function callback()
    {
        return $this->callback;
    }

    /**
     * 匹配设置: 任意字符 包括 /
     * @param string $key
     * @return $this
     */
    public function isAnything(string $key)
    {
        if (empty($key)) {
            throw new RouterException('Router filter key is empty.');
        }
        $this->compiled = null;
        $this->anything[] = $key;
        return $this;
    }

    /**
     * 匹配设置: 可打印字符串，空格除外
     * @param string $key
     * @return $this
     */
    public function isGraph(string $key)
    {
        return $this->addFilter($key, static::FILTER_GRAPH);
    }

    /**
     * 匹配设置: 纯字母
     * @param string $key
     * @param ?bool $case True: 必须为大写；False: 全为小写；null: 大小写皆可
     * @return $this
     */
    public function isAlpha(string $key, bool $case = null)
    {
        return $this->addFilter($key, static::FILTER_ALPHA, $case);
    }

    /**
     * 匹配设置: 纯数字
     * @param string $key
     * @return $this
     */
    public function isDigit(string $key)
    {
        return $this->addFilter($key, static::FILTER_DIGIT);
    }

    /**
     * 匹配设置: 字母数字
     * @param string $key
     * @param bool $allowUnderlined 是否允许下划线
     * @return $this
     */
    public function isAlnum(string $key, bool $allowUnderlined = true)
    {
        return $this->addFilter($key, static::FILTER_ALNUM, $allowUnderlined);
    }

    /**
     * 匹配设置: IP地址
     * @param string $key
     * @param bool $allowIpv6 是否允许IPV6
     * @param bool $allowSpecial 是否允许特殊IP [如 192.168, 255.255, 0.0]
     * @return $this
     */
    public function isIp(string $key, bool $allowIpv6 = false, bool $allowSpecial = false)
    {
        return $this->addFilter($key, static::FILTER_IP, [$allowIpv6, $allowSpecial]);
    }

    /**
     * 匹配设置: email
     * @param string $key
     * @return $this
     */
    public function isEmail(string $key)
    {
        return $this->addFilter($key, static::FILTER_EMAIL);
    }

    /**
     * 匹配设置: 汉字
     * @param string $key
     * @return $this
     */
    public function isZh(string $key)
    {
        return $this->addFilter($key, static::FILTER_ZH);
    }

    /**
     * 匹配设置: 昵称 (汉字, 字母, 数字, 下划线)
     * @param string $key
     * @return $this
     */
    public function isZhNick(string $key)
    {
        return $this->addFilter($key, static::FILTER_ZH_NICK);
    }

    /**
     * 匹配设置: 自定义正则
     * @param string $key
     * @param string $reg
     * @return $this
     */
    public function isRegex(string $key, string $reg)
    {
        return $this->addFilter($key, static::FILTER_REGEXP, static::sanitizeRegex($key, $reg));
    }

    /**
     * 添加限制条件
     * @param string $key
     * @param int $filter
     * @param mixed $option
     * @return $this
     */
    private function addFilter(string $key, int $filter, $option = null)
    {
        if (empty($key)) {
            throw new RouterException('Router filter key is empty.');
        }
        if (!array_key_exists($key, $this->filters)) {
            $this->filters[$key] = [];
        }
        $this->filters[$key][] = [$filter, $option];
        return $this;
    }

    /**
     * 使用 $filter 规则验证 $value
     * @param string $value 待验证值
     * @param int $filter 验证规则
     * @param mixed $option 验证规则所需的额外参数
     * @return bool
     */
    public function checkFilter(string $value, int $filter, $option = null)
    {
        switch ($filter) {
            case static::FILTER_GRAPH:
                return ctype_graph($value);
            case static::FILTER_ALPHA:
                return null === $option ? ctype_alpha($value) : ($option ? ctype_upper($value) : ctype_lower($value));
            case static::FILTER_DIGIT:
                return ctype_digit($value);
            case static::FILTER_ALNUM:
                return $option ? preg_match('#^[a-zA-Z0-9_]+$#', $value) : ctype_alnum($value);
            case static::FILTER_IP:
                $allowIpv6 = isset($option[0]) && $option[0];
                $allowSpecial = isset($option[1]) && $option[1];
                if ($allowIpv6) {
                    return $allowSpecial ? filter_var($value, FILTER_VALIDATE_IP)
                        : filter_var($value, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE|FILTER_FLAG_NO_RES_RANGE);
                }
                return $allowSpecial ? filter_var($value, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)
                    : filter_var($value, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4|FILTER_FLAG_NO_PRIV_RANGE|FILTER_FLAG_NO_RES_RANGE);
            case static::FILTER_EMAIL:
                return filter_var($value, FILTER_VALIDATE_EMAIL);
            case static::FILTER_ZH:
                return preg_match(static::makeRegexp('\p{Han}+', 'u'), $value);
            case static::FILTER_ZH_NICK:
                return preg_match(static::makeRegexp('[\p{Han}a-zA-Z0-9_]+', 'u'), $value);
            case static::FILTER_REGEXP:
                return preg_match(static::makeRegexp($option, 'su'.($this->isCaseSensitive() ? '' : 'i')), $value);
        }
        return true;
    }

    /**
     * 获取当前路由正则表达式
     * @return string
     */
    public function regex()
    {
        if ($this->compiled) {
            return $this->compiled;
        }
        return $this->preparedRegex();
    }

    /**
     * 通过参数匹配路由， 若匹配成功， 会自动设置 $parameters, 匹配失败返回 false
     * @param string $pathInfo
     * @param ?string $method
     * @param ?string $host
     * @param ?string $scheme
     * @param ?string $port
     * @return $this|false
     */
    public function match(
        string $pathInfo,
        string $method = null,
        string $host = null,
        string $scheme = null,
        string $port = null
    ) {
        if (is_array($methods = $this->method()) && !in_array(strtoupper($method), $methods)) {
            return false;
        }
        if (is_array($schemes = $this->scheme()) && !in_array(strtolower($scheme), $schemes)) {
            return false;
        }
        if (is_array($hosts = $this->host()) && !in_array(strtolower($host), $hosts)) {
            return false;
        }
        if (is_array($ports = $this->port()) && !in_array(strtolower($port), $ports)) {
            return false;
        }
        if (!is_array($parameter = $this->matchParameters($pathInfo))) {
            return false;
        }
        $this->parameters = $parameter;
        return $this;
    }

    /**
     * 获取 match 匹配的 parameters 结果
     * @param ?string $key
     * @param null $default
     * @return array|string
     */
    public function getParameters(string $key = null, $default = null)
    {
        if (null === $key) {
            return $this->parameters;
        }
        return array_key_exists($key, $this->parameters) ? $this->parameters[$key] : $default;
    }

    /**
     * 重置 parameters 指定项的值
     * @param string $key
     * @param mixed $val
     * @return $this
     */
    public function setParameters(string $key, $val)
    {
        $this->parameters[$key] = $val;
        return $this;
    }

    /**
     * 仅获取有匹配结果的数组
     * @return array
     */
    public function getMatches()
    {
        $matches = [];
        foreach ($this->parameters as $key => $parameter) {
            if (!empty($parameter)) {
                $matches[$key] = $parameter;
            }
        }
        return $matches;
    }

    /**
     * match 获取 $parameters
     * @param string $pathInfo
     * @return array|bool
     */
    protected function matchParameters(string $pathInfo)
    {
        // 格式化 pathInfo
        $pathInfo = '/'.trim($pathInfo, '/');
        $variables = $this->preparedMatches();
        if (!count($variables)) {
            if ($this->uri === $pathInfo || (!$this->isCaseSensitive() && strtolower($this->uri) === strtolower($pathInfo))) {
                return [];
            }
            return false;
        }
        $compiled = $this->regex();
        if (!empty($this->prefix) &&
            (($this->isCaseSensitive() && strpos($pathInfo, $this->prefix) !== 0) ||
                (!$this->isCaseSensitive() && stripos($pathInfo, $this->prefix) !== 0))
        ) {
            return false;
        }
        if (!preg_match($compiled, $pathInfo, $matches)) {
            return false;
        }
        array_shift($matches);

        $matches = array_intersect_key($matches, array_flip($this->variables));
        $matches = array_filter($matches, function ($value) {
            return is_string($value) && strlen($value) > 0;
        });

        $parameters = [];
        foreach ($this->tokens as $token) {
            if (!is_array($token)) {
                continue;
            }
            $key = $token[2];
            if (array_key_exists($key, $matches)) {
                if (!$this->checkParameters($key, $matches[$key])) {
                    return false;
                }
                $parameters[$key] = $matches[$key];
            } else {
                if (!$token[3]) {
                    return false;
                }
                $parameters[$key] = null;
            }
        }
        return $parameters;
    }

    /**
     * 为生成正则表达式准备数据
     * @return string
     */
    protected function preparedRegex()
    {
        $pos = 0;
        $variables = [];
        $tokens = [];
        $uri = $this->uri;
        foreach ($this->preparedMatches() as $match) {
            // {bar?}
            if ($canPass = '?' === substr($match[0][0], -2, 1)) {
                $varName = substr($match[0][0], 1, -2);
            } else {
                $varName = substr($match[0][0], 1, -1);
            }
            if (is_numeric($varName)) {
                throw new RouterException(sprintf(
                    'Variable name "%s" cannot be numeric in route pattern "%s". Please use a different name.',
                    $varName, $uri
                ));
            }
            if (in_array($varName, $variables)) {
                throw new RouterException(sprintf(
                    'Route pattern "%s" cannot reference variable name "%s" more than once.',
                    $uri, $varName
                ));
            }
            $precedingText = substr($uri, $pos, $match[0][1] - $pos);
            $precedingChar = strlen($precedingText) > 0 ? substr($precedingText, -1) : '';
            $isSeparator = '' !== $precedingChar && false !== strpos(static::SEPARATORS, $precedingChar);
            $pos = $match[0][1] + strlen($match[0][0]);

            if ($isSeparator && strlen($precedingText) > 1) {
                $tokens[] = substr($precedingText, 0, -1);
            } elseif (!$isSeparator && strlen($precedingText) > 0) {
                $tokens[] = $precedingText;
            }

            $nextSeparator = '';
            if ('' != $followingPattern = (string) substr($uri, $pos)) {
                $pattern = preg_replace('#{\w+\??}#', '', $followingPattern);
                if (isset($pattern[0]) && false !== strpos(static::SEPARATORS, $pattern[0]) ) {
                    $nextSeparator = $pattern[0];
                }
            }
            if (in_array($varName, $this->anything)) {
                $regexp = '(.*)?';
            } else {
                // 自动模式
                $regexp = sprintf(
                    '[^%s%s]+',
                    preg_quote('/', static::REGEX_DELIMITER),
                    '/' !== $nextSeparator && '' !== $nextSeparator ? preg_quote($nextSeparator, static::REGEX_DELIMITER) : ''
                );
                if (empty($followingPattern) || (!empty($nextSeparator) && !preg_match('#^{\w+\??}#', $followingPattern))) {
                    $regexp .= '+';
                }
            }
            $tokens[] = [$isSeparator ? $precedingChar : '', $regexp, $varName, $canPass];
            $variables[] = $varName;
        }
        if ($pos < strlen($uri)) {
            $tokens[] = substr($uri, $pos);
        }
        $firstOptional = PHP_INT_MAX;
        for ($i = count($tokens) - 1; $i >= 0; --$i) {
            $token = $tokens[$i];
            if (is_array($token) && $token[3]) {
                $firstOptional = $i;
            } else {
                break;
            }
        }
        $regexp = '';
        $caseSensitive = $this->isCaseSensitive();
        for ($i = 0, $nbToken = count($tokens); $i < $nbToken; ++$i) {
            $regexp .= static::preparedRegexUnit($tokens, $i, $firstOptional, $caseSensitive);
        }
        $this->tokens = $tokens;
        $this->variables = $variables;
        $this->prefix = !is_array($tokens[0]) ? $tokens[0] : '';
        return $this->compiled = static::makeRegexp($regexp, 's'.($caseSensitive ? '' : 'i'));
    }

    /**
     * 获取 uri 正则匹配数组
     * @return array
     */
    protected function preparedMatches()
    {
        if ($this->matches) {
            return $this->matches;
        }
        preg_match_all('#{\w+\??}#', $this->uri, $matches, PREG_OFFSET_CAPTURE | PREG_SET_ORDER);
        return $this->matches = (array) $matches;
    }

    /**
     * 检测当前路由的匹配规则
     * @param string $key
     * @param string $value
     * @return bool
     */
    protected function checkParameters(string $key, string $value)
    {
        if (isset($this->filters[$key]) && count((array) $this->filters[$key])) {
            foreach ($this->filters[$key] as $filter) {
                if ($this->checkFilter($value, $filter[0], $filter[1])) {
                    return true;
                }
            }
            return false;
        }
        return true;
    }

    /**
     * 准备每个变量的正则表达式
     * @see https://github.com/symfony/routing/blob/master/RouteCompiler.php#L253
     * @author Fabien Potencier <fabien@symfony.com>
     * @author Tobias Schultze <http://tobion.de>
     * @param array $tokens
     * @param int $index
     * @param int $firstOptional
     * @param bool $caseSensitive
     * @return string
     */
    protected static function preparedRegexUnit(array $tokens, int $index, int $firstOptional, bool $caseSensitive)
    {
        $token = $tokens[$index];
        if (is_array($token)) {
            // Variable tokens
            if (0 === $index && 0 === $firstOptional) {
                // When the only token is an optional variable token, the separator is required
                return sprintf('%s(?P<%s>%s)?', preg_quote($token[0], static::REGEX_DELIMITER), $token[2], $token[1]);
            } else {
                $regexp = sprintf('%s(?P<%s>%s)', preg_quote($token[0], static::REGEX_DELIMITER), $token[2], $token[1]);
                if ($index >= $firstOptional) {
                    // Enclose each optional token in a subpattern to make it optional.
                    // "?:" means it is non-capturing, i.e. the portion of the subject string that
                    // matched the optional subpattern is not passed back.
                    $regexp = "(?:$regexp";
                    $nbTokens = count($tokens);
                    if ($nbTokens - 1 == $index) {
                        // Close the optional subpatterns
                        $regexp .= str_repeat(')?', $nbTokens - $firstOptional - (0 === $firstOptional ? 1 : 0));
                    }
                }
                return $regexp;
            }
        } else {
            $token = $caseSensitive ? $token : strtolower($token);
            return preg_quote($token, static::REGEX_DELIMITER);
        }
    }

    /**
     * 生成正则表达式
     * @param string $regexp
     * @param null $extra
     * @return string
     */
    protected static function makeRegexp(string $regexp, $extra = null)
    {
        return static::REGEX_DELIMITER.'^'.$regexp.'$'.static::REGEX_DELIMITER.($extra ?: '');
    }

    /**
     * 去除表达式首尾断言符
     * @param string $key
     * @param string $regex
     * @return string
     */
    protected static function sanitizeRegex(string $key, string $regex)
    {
        if ('' !== $regex) {
            if ('^' === $regex[0]) {
                $regex = (string) substr($regex, 1);
            }
            if ('$' === substr($regex, -1)) {
                $regex = substr($regex, 0, -1);
            }
        }
        if ('' === $regex) {
            throw new RouterException(sprintf('Router regex for "%s" cannot be empty.', $key));
        }
        return $regex;
    }

    /**
     * flatten array
     * @param array $array 原始数组
     * @param ?bool $case 是否强制大小写
     * @return array
     */
    protected static function tobeArray(array $array, bool $case = null)
    {
        $return = [];
        array_walk_recursive($array, function ($x) use (&$return, $case) {
            if (!empty($x)) {
                $x = (string) $x;
                if (null !== $case) {
                    $x = $case ? strtoupper($x) : strtolower($x);
                }
                $return[] = $x;
            }
        });
        return count($return) ? $return : null;
    }
}
