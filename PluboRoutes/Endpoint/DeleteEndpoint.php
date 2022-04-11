<?php
namespace PluboRoutes\Endpoint;

/**
 * An endpoint with DELETE method.
 *
 */
final class DeleteEndpoint extends Endpoint
{
    /**
     * Constructor.
     *
     * @param string $namespace
     * @param string $path
     * @param callable $config
     */
    public function __construct(string $namespace, string $path, callable $config, callable $permission_callback = null)
    {
        parent::__construct($namespace, $path, $config, $permission_callback);
        $this->method = \WP_REST_Server::DELETABLE;
    }
}
