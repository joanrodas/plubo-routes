<?php
namespace PluboRoutes\Endpoint;

/**
 * An Endpoint describes a route and its parameters.
 *
 */
abstract class Endpoint implements EndpointInterface
{
    /**
     * The endpoint namespace.
     *
     * @var string
     */
    private $namespace;

    /**
     * The URL path that the endpoint needs to match.
     *
     * @var string
     */
    private $path;

    /**
     * The configuration of the endpoint.
     *
     * @var array
     */
    private $config;

    /**
     * The permission callback of the endpoint.
     *
     * @var array
     */
    private $permission_callback;

    /**
     * The method of the endpoint.
     *
     * @var string
     */
    protected $method;

    /**
     * Constructor.
     *
     * @param string $namespace
     * @param string $path
     * @param callable $config
     * @param string $method
     */
    public function __construct(string $namespace, string $path, callable $config, callable $permission_callback = null)
    {
        $this->namespace = $namespace;
        $this->path = $path;
        $this->config = $config;
        $this->permission_callback = $permission_callback ?? '__return_true';
        $this->args = [];
    }

    /**
     * Get the namespace of the endpoint.
     *
     * @return string
     */
    public function getNamespace()
    {
        return $this->namespace;
    }

    /**
     * Get the path to be matched.
     *
     * @return string
     */
    public function getPath()
    {
        return $this->path;
    }

    /**
     * Get the config parameters of the endpoint.
     *
     * @return array
     */
    public function getConfig()
    {
        return $this->config;
    }

    /**
     * Get the method of the endpoint.
     *
     * @return string
     */
    public function getMethod()
    {
        return $this->method;
    }

    /**
     * Get the endpoint permission callback.
     *
     * @return callable
     */
    public function getPermissionCallback()
    {
        return $this->permission_callback;
    }

    /**
     * Serialize the endpoint.
     *
     * @return string
     */
    public function __serialize()
    {
        return [
            'path' => $this->path,
            'method' => $this->method
        ];
    }

    /**
     * Unserialize the endpoint.
     *
     * @param array
     */
    public function __unserialize($data)
    {
        $this->path = $data['path'];
        $this->method = $data['method'];
    }
}
