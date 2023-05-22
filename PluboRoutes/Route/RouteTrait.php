<?php
namespace PluboRoutes\Route;

/**
 * Common route functions.
 *
 */
trait RouteTrait
{

    /**
     * The URL path that the route needs to match.
     *
     * @var string
     */
    private $path;

    /**
     * The optional config of the route.
     *
     * @var array
     */
    private $config;

    /**
     * The matches of the route.
     *
     * @var array
     */
    private $args;

    /**
     * Get the name of the route.
     *
     * @return string
     */
    public function getName()
    {
        return $this->config['name'] ?? md5($this->path);
    }

    /**
     * Get the title of the route.
     *
     * @return string
     */
    public function getTitle()
    {
        return $this->config['title'] ?? '';
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
     * Get the config parameters of the route.
     *
     * @return array
     */
    public function getConfig()
    {
        return $this->config;
    }

    /**
     * Set the matches of the route.
     *
     * @param array
     */
    public function addArg($arg)
    {
        $this->args[] = $arg;
    }

    /**
     * Get the matches of the route.
     *
     * @return array
     */
    public function getArgs()
    {
        return $this->args;
    }

    /**
     * Get extra query vars.
     *
     * @return array
     */
    public function getExtraVars()
    {
        $query_vars = $this->config['extra_vars'] ?? [];
        return $query_vars;
    }

    /**
     * Serialize the route.
     *
     * @return string
     */
    public function __serialize()
    {
        return [
            'path' => $this->path,
            'extra_vars' => $this->getExtraVars()
        ];
    }

    /**
     * Unserialize the route.
     *
     * @param array
     */
    public function __unserialize($data)
    {
        $this->path = $data['path'];
        $this->config['extra_vars'] = $data['extra_vars'];
    }
}
