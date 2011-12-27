<?php

/**
 * @file
 * Mollom client class for Wordpress.
 */

/**
 * Wordpress Mollom client implementation.
 */
class MollomWordpress extends Mollom {

  public $configuration_map = array(
    'publicKey' => 'mollom_public_key',
    'privateKey' => 'mollom_private_key',
  );

  /**
   * Implements Mollom::loadConfiguration().
   */
  public function loadConfiguration($name) {
    $name = (isset($this->configuration_map[$name])) ? $this->configuration_map[$name] : $name;
    return get_option($name);
  }

  /**
   * Implements Mollom::saveConfiguration().
   */
  public function saveConfiguration($name, $value) {
    return update_option($name, $value);
  }

  /**
   * Implements Mollom::deleteConfiguration().
   */
  public function deleteConfiguration($name) {
    return delete_option($name);
  }

  /**
   * Implements Mollom::getClientInformation().
   */
  public function getClientInformation() {
    global $wp_version;

    $data = array(
      'platformName' => 'Wordpress',
      'platformVersion' => $wp_version,
      'clientName' => 'WP Mollom',
      'clientVersion' => MOLLOM_PLUGIN_VERSION,
    );

    return $data;
  }

  /**
   * Implements Mollom::request().
   */
  protected function request($method, $server, $path, $query = NULL, array $headers = array()) {
    $function = ($method == 'GET' ? 'wp_remote_get' : 'wp_remote_post');
    $data = array(
      'headers' => $headers,
      'body' => $query,
    );
    $result = $function($server . '/' . $path, $data);

    $response = (object) array(
      'code' => $result['response']['code'],
      'message' => isset($result['response']['message']) ? $result['response']['message'] : NULL,
      'headers' => isset($result['headers']) ? $result['headers'] : array(),
      'body' => $result['body'],
    );
    return $response;
  }

}
