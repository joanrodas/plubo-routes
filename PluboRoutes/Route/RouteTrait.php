<?php
namespace PluboRoutes\Route;

/**
 * Common route functions.
 *
 */
trait RouteTrait {

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
   * Get the path to be matched.
   *
   * @return string
   */
  public function getPath() {
    return $this->path;
  }

  /**
   * Get the config parameters of the route.
   *
   * @return array
   */
  public function getConfig() {
    return $this->config;
  }

  /**
   * Set the matches of the route.
   *
   * @param array
   */
  public function addArg($arg) {
    $this->args[] = $arg;
  }

  /**
   * Get the matches of the route.
   *
   * @return array
   */
  public function getArgs() {
    return $this->args;
  }

  public function serialize() {
    return serialize( array($this->path, $this->args) );
  }

  public function unserialize($data) {
    $data = unserialize($data);
    $this->path = $data['path'];
    $this->args = $data['args'];
  }

}
