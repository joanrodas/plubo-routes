<?php
namespace PluboRoutes\Endpoint;

/**
 * An Endpoint with PUT method.
 *
 */
final class PutEndpoint extends Endpoint
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
        $this->method = 'PUT';
    }
}
