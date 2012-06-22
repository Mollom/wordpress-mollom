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
  public $configuration_map = array(
    'publicKey' => 'mollom_public_key',
    'privateKey' => 'mollom_private_key',
  );

  /**
   * Implements Mollom::loadConfiguration().
   */
  public function loadConfiguration($name) {
    return get_option($this->configuration_map[$name]);
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
   * Overrides Mollom::writeLog().
   *
   * @todo Implement this
   */
  function writeLog() {
    foreach ($this->log as $key => $entry) {
      // @todo: write log away
    }

    // Purge the logs
    $this->purgeLog();
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

    if (is_wp_error($result)) {
      // A WP_Error means a network error by default.
      $code = self::NETWORK_ERROR;
      // Try to extract error code from error message, if any.
      $code_in_message = (int) $result->get_error_message();
      if ($code_in_message > 0) {
        $code = $code_in_message;
      }
      $response = (object) array(
        'code' => $code,
        'message' => $result->get_error_message(),
        'headers' => array(),
        'body' => NULL,
      );
    } else {
      $response = (object) array(
        'code' => $result['response']['code'],
        'message' => $result['response']['message'],
        'headers' => $result['headers'],
        'body' => $result['body'],
      );
    }

    return $response;
  }

}

class MollomWordpressTest extends MollomWordpress {
  /**
   * Overrides Mollom::$server.
   */
  public $server = 'dev.mollom.com';

  /**
   * Flag indicating whether to verify and automatically create testing API keys upon class instantiation.
   *
   * @var bool
   */
  public $createKeys;

  /**
   * Mapping of configuration names to Wordpress variables.
   *
   * We are overriding the configuration map of the base class because we don't
   * want to unintentionally change or replace the production keys.
   *
   * @see Mollom::loadConfiguration()
   */
  public $configuration_map = array(
    'publicKey' => 'mollom_developer_mode_public_key',
    'privateKey' => 'mollom_developer_mode_private_key',
  );

  /**
   * Overrides Mollom::__construct().
   *
   * This class accounts for multiple scenarios:
   * - Straight low-level requests against the testing API from a custom script,
   *   caring for API keys on its own.
   * - Whenever the testing mode is enabled (either through the module's
   *   settings page or by changing the mollom_testing_mode system variable),
   *   the client requires valid testing API keys to perform any calls. Testing
   *   API keys are different to production API keys, need to be created first,
   *   and may vanish at any time (whenever the testing API server is
   *   redeployed). Since they are different, the class stores them in different
   *   system variables. Since they can vanish at any time, the class verifies
   *   the keys upon every instantiation, and automatically creates new testing
   *   API keys if necessary.
   * - Some automated unit tests attempt to verify that authentication errors
   *   are handled correctly by the class' error handling. The automatic
   *   creation and recovery of testing API keys would break those assertions,
   *   so said tests can disable the behavior by preemptively setting
   *   $createKeys or the 'mollom_testing_create_keys' system variable to FALSE,
   *   and manually create testing API keys (once).
   */
  function __construct() {
    parent::__construct();

    // Any Mollom API request requires valid API keys, or no API calls can be
    // executed. Verify that testing API keys exist and are still valid.
    if (!isset($this->createKeys)) {
      $this->createKeys = (bool) get_option('mollom_testing_create_keys', TRUE);
    }
    // If valid client API keys are expected, verify API keys whenever this
    // class is instantiated.
    if ($this->createKeys) {
      $this->checkKeys();
    }
  }

  /**
   * Checks whether current API keys are valid and creates new keys if they are not.
   */
  public function checkKeys() {
    // Verifying keys may return an authentication error, from which we will
    // automatically recover below, so do not write the request log (yet).
    $this->writeLog = FALSE;
    if (!empty($this->publicKey) && !empty($this->privateKey)) {
      $result = $this->verifyKeys();
    }
    else {
      $result = self::AUTH_ERROR;
    }
    $this->writeLog = TRUE;

    // If current keys are invalid, create and save new testing API keys.
    if ($result === self::AUTH_ERROR) {
      if ($this->createKeys()) {
        $this->saveKeys();
      }
    }
  }

  /**
   * Creates new testing API keys.
   *
   * @todo Move site properties into $data argument (Drupal-specific values),
   *   rename to createTestingSite(), and move into Mollom class?
   */
  public function createKeys() {
    // Do not attempt to create API keys repeatedly.
    $this->createKeys = FALSE;

    // Without any API keys, the client does not even attempt to perform a
    // request. Set dummy API keys to overcome that sanity check.
    $this->publicKey = 'public';
    $this->privateKey = 'private';

    // Skip authorization for creating testing API keys.
    $oAuthStrategy = $this->oAuthStrategy;
    $this->oAuthStrategy = '';
    $result = $this->createSite(array(
      'url' => get_option('siteurl', ''),
      'email' => get_option('site_mail', 'mollom-drupal-test@example.com'),
    ));
    $this->oAuthStrategy = $oAuthStrategy;

    // Set class properties.
    if (is_array($result) && !empty($result['publicKey']) && !empty($result['privateKey'])) {
      $this->publicKey = $result['publicKey'];
      $this->privateKey = $result['privateKey'];
      return TRUE;
    }
    else {
      unset($this->publicKey, $this->privateKey);
      return FALSE;
    }
  }

  /**
   * Saves API keys to local configuration store.
   */
  public function saveKeys() {
    $this->saveConfiguration('publicKey', $this->publicKey);
    $this->saveConfiguration('privateKey', $this->privateKey);
  }
}