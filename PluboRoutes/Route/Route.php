<?php

namespace PluboRoutes\Route;

/**
 * A Route describes a route and its parameters.
 *
 */
final class Route implements RouteInterface
{
    use RouteTrait;

    /**
     * The template that the route wants to load or a callable.
     *
     * @var string\callable
     */
    private $template;

    /**
     * Constructor.
     *
     * @param string $path
     * @param string|callable $template
     * @param array $config
     */
    public function __construct(string $path, $template, array $config = [])
    {
        $this->path = $path;
        $this->template = $template;
        $this->config = $config;
        $this->args = [];
    }

    /**
     * Get the action to be executed when this route is matched.
     *
     * @return string
     */
    public function getAction()
    {
        return "plubo/route_{$this->getName()}";
    }

    /**
     * Check if the action is a callable.
     *
     * @return boolean
     */
    public function hasCallback()
    {
        return false;
    }

    /**
     * Check if the template is a callable.
     *
     * @return boolean
     */
    public function hasTemplateCallback()
    {
        return is_callable($this->template);
    }

    /**
     * Get the template to be loaded when this route is matched.
     *
     * @return string
     */
    public function getTemplate()
    {
        $template_name = $this->template;
        if($this->hasTemplateCallback()) return $template_name;
        
        $custom_directory = $this->config['template_path'] ?? '';

        // Check if a custom directory is provided
        if ($custom_directory) {
            $customTemplate = trailingslashit($custom_directory) . $template_name;
            if (is_readable($customTemplate)) {
                return $customTemplate;
            }
        }

        // Check if the template exists in the theme
        $themeTemplate = locate_template(apply_filters('plubo/template', $template_name));

        return $themeTemplate ?: $template_name;
    }

    /**
     * Check if route has basic auth.
     *
     * @return boolean
     */
    public function hasBasicAuth()
    {
        $basic_auth = $this->config['basic_auth'] ?? [];
        return is_array($basic_auth) && !empty($basic_auth);
    }

    /**
     * Get basic auth.
     *
     * @return array
     */
    public function getBasicAuth()
    {
        $basic_auth = $this->config['basic_auth'] ?? [];
        return $basic_auth;
    }

    /**
     * Renders the html.
     *
     * @return boolean
     */
    public function isRender()
    {
        $render = $this->config['render'] ?? false;
        return filter_var(($render != false), FILTER_VALIDATE_BOOLEAN);
    }

    /**
     * Get the status.
     *
     * @return int
     */
    public function getStatus()
    {
        $status = $this->config['status'] ?? 200;
        return (int)$status;
    }
}
