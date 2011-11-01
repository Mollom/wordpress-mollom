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
   * Mapping of configuration names to Wordpress variables.
   *
   * @see Mollom::loadConfiguration()
   */
  private $configuration_map = array(
    'publicKey' => 'mollom_public_key',
    'privateKey' => 'mollom_private_key',
    'servers' => 'mollom_servers',
  );

  /**
   * Implements Mollom::loadConfiguration().
   */
  public function loadConfiguration($name) {
    $name = $this->configuration_map[$name];
    return get_option($name);
  }

  /**
   * Implements Mollom::saveConfiguration().
   */
  public function saveConfiguration($name, $value) {
    $name = $this->configuration_map[$name];
    return update_option($name, $value);
  }

  /**
   * Implements Mollom::deleteConfiguration().
   */
  public function deleteConfiguration($name) {
    $name = $this->configuration_map[$name];
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
		if ($method == 'GET') {
			$function = 'wp_remote_get';
		} elseif ($method == 'POST') {
			$function = 'wp_remote_post';			
		}

		$data = array(
			'body' => $query,
		  'headers' => $headers,
		);

		$res = $function($server . '/' . $path, $data);
		var_dump($server . '/' . $path);
	var_dump($res['response']);
    $response = (object) array(
      'code' => $res['response']['code'],
      'message' => isset($res['response']['message']) ? $res['response']['message'] : NULL,
      'headers' => isset($res['headers']) ? $res['headers'] : array(),
      'body' => $res['body'],
    );

    return $response;
	}
	
}