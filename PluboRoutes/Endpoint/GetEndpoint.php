<?php
namespace PluboRoutes\Endpoint;

/**
 * An Endpoint describes a route and its parameters.
 *
 */
final class GetEndpoint implements EndpointInterface
{
    use EndpointTrait;

    /**
     * Constructor.
     *
     * @param string $namespace
     * @param string $path
     * @param callable $config
     */
    public function __construct(string $namespace, string $path, callable $config, callable $permission_callback = null)
    {
        $this->namespace = $namespace;
        $this->path = $path;
        $this->config = $config;
        $this->permission_callback = $permission_callback ?? '__return_true';
        $this->args = array();
    }

    public function getMethod()
    {
        return \WP_REST_Server::READABLE;
    }
}
