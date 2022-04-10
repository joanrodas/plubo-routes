<?php
namespace PluboRoutes\Endpoint;

/**
 * Common endpoint functions.
 *
 */
trait EndpointTrait
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
     * The matches of the endpoint.
     *
     * @var array
     */
    private $args;

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
    public function serialize()
    {
        return serialize(array($this->path, $this->args));
    }

    /**
     * Unserialize the endpoint.
     *
     * @param array
     */
    public function unserialize($data)
    {
        $data = unserialize($data);
        $this->path = $data['path'];
        $this->args = $data['args'];
    }
}
