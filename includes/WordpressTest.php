<?php

/**
 * @file
 * Mollom testing client class for Wordpress.
 */

/**
 * Wordpress Mollom testing client implementation.
 */
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
  public $createKeys = TRUE;

  /**
   * Mapping of configuration names to Wordpress variables.
   *
   * We are overriding the configuration map of the base class because we don't
   * want to unintentionally change or replace the production keys.
   *
   * @see Mollom::loadConfiguration()
   */
  public $configuration_map = array(
    'publicKey' => 'mollom_testing_public_key',
    'privateKey' => 'mollom_testing_private_key',
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
    // Load and set publicKey and privateKey configuration values.
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
      'url' => site_url(),
      'email' => get_option('site_mail', 'mollom-wordpress-test@example.com'),
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

