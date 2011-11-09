<?php

/**
 * @file
 * Mollom client class for Wordpress.
 */

/**
 * Wordpress Mollom client implementation.
 */
class MollomWordpress extends Mollom {
  /**
   * Implements Mollom::loadConfiguration().
   */
  public function loadConfiguration($name) {
    return get_option('mollom_' . $name);
  }

  /**
   * Implements Mollom::saveConfiguration().
   */
  public function saveConfiguration($name, $value) {
    return update_option('mollom_' . $name, $value);
  }

  /**
   * Implements Mollom::deleteConfiguration().
   */
  public function deleteConfiguration($name) {
    return delete_option('mollom_' . $name);
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
    echo "<pre>"; var_dump($server . '/' . $path); echo "</pre>\n";
    echo "<pre>"; var_dump($result); echo "</pre>\n";

    $response = (object) array(
      'code' => $result['response']['code'],
      'message' => isset($result['response']['message']) ? $result['response']['message'] : NULL,
      'headers' => isset($result['headers']) ? $result['headers'] : array(),
      'body' => $result['body'],
    );
    return $response;
  }

}
