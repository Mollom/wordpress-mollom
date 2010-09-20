<?php
/**
 * This file contains a small PHP framework which allows you to connect with the Mollom API.
 *
 * See {@link http://www.mollom.com Mollom} for more information and up to date
 * documentation on how to use the API.
 *
 * @author Matthias Vandermaesen <matthias@colada.be>
 * @version 0.01
 * @package Mollom
 */

/**
 * defines the API version
 */
define( 'MOLLOM_API_VERSION', '1.0' );

/**
 * Defines the error codes thrown by the API
 */
define( 'MOLLOM_ERROR'   , 1000 );
define( 'MOLLOM_REFRESH' , 1100 );
define( 'MOLLOM_REDIRECT', 1200 );

/**
 * Defines the analysis codes thrown by the API
 */
define( 'MOLLOM_ANALYSIS_HAM'     , 1);
define( 'MOLLOM_ANALYSIS_SPAM'    , 2);
define( 'MOLLOM_ANALYSIS_UNSURE'  , 3);

/**
 * Interface which needs to be implemented by any XML-RPC class before an instance
 * of that class can be passed to {@link Mollom::setRPCClient}
 *
 * @package Mollom
 */
interface MollomRPCClient {
  public function query();
  public function setServer($server, $path = false, $port = 80);
  public function getResponse();
  public function isError();
  public function getErrorCode();
  public function getErrorMessage();
}

/**
 * This class abstracts the Mollom API and makes it available as a host of public
 * functions. The Mollom API is XML-RPC based. So the class will not run unless you pass
 * it an instance of class which implements the XML-RPC protocol and adheres to the 
 * MollomRPCClient interface.
 * You'll need to signup with Mollom and receive a public/private key before you get access
 * to the API.
 * This class only makes the API available. Correctly implementing a fully fledged client for
 * your application, still requires the developer to follow the {@link http://mollom.com/api documentation}
 * thoroughly.
 *
 * @package Mollom
 */
class Mollom {
  private $public_key;
  private $private_key;
  private $mollom_rpcclient;
  private $mollom_servers;
  private $mollom_errors;
  private $mollom_response;
  private $session_id;
  private $reverse_proxy_addressses;
  
  /**
   * Constructor for the Mollom class
   *
   * @param String public_key The Public Key registered with the Mollom account
   * @param String private_key The Private Key (HMAC-SHA1) registered with the Mollom account
   * @param MollomRPCClient rpc_client An instance of a class which implements the XML-RPC protocol
   */
  function __construct($public_key = NULL, $private_key = NULL, MollomRPCClient $rpc_client = NULL) {
    $this->public_key = $public_key;
    $this->private_key = $private_key;
    $this->mollom_errors = FALSE;
    $this->mollom_servers = NULL;
    $this->mollom_servers_refreshed = FALSE;
    $this->mollom_response = NULL;
    $this->session_id = NULL;
    $this->reverse_proxy_addressses = NULL;
    $this->mollom_rpcclient = $rpc_client;
  }

  /**
   * Sets an instance of a class which implements the XML-RPC protocol
   *
   * @param MollomRPCClient rpc_client
   */
  public function setRPCClient(MollomRPCClient $rpc_client) {
    $this->mollom_rpcclient = $rpc_client;
  }

  /**
   * Sets the public key which the application manager receives after registering with Mollom
   *
   * @param String public_key The public key provided by Mollom
   */
  public function setPublicKey($public_key = NULL) {
	  $this->public_key = $public_key;
  }

  /**
   * Sets the private key which the application manager receives after registering with Mollom
   *
   * @param String private_key The private key provided by Mollom
   */
  public function setPrivateKey($private_key = NULL) {
	  $this->pirvate_key = $private_key;
  }

  /**
   * Return all Mollom or XML RPC related errors
   *
   * @return array All errors thrown by this instance.
   */
  public function getErrors() {
    return $this->mollom_errors;
  }

  /**
   * Mollom uses a fallback system to avoid overload of it's servers. Upon a request to Mollom,
   * the client will receive a list of ip's pointing to a range of reachable servers. This list
   * should be cached. Use this function to retrieve the server list for caching in your application.
   * You'll get a cue from {@link checkServerListRefreshed()} if the server list was refreshed.
   *
   * @return array An array of reachable Mollom servers
   */
  public function getServerList() {
	  return $this->mollom_servers;
  }

  /**
   * Mollom's server fallback system relies on a periodic refreshed list of reachable servers which
   * the client application should cache. Use this function to use the cached version of the server list.
   * Set the cached server list before you make any API calls to Mollom as these will trigger a refresh
   * if the list when it's non existent.
   *
   * @return array An array of servers which Mollom will try to connect with.
   */
  public function setServerList($servers = NULL) {
    return $this->mollom_servers = $servers;
  }

  /**
   * If the serverlist was refreshed, the client will raise a flag through this function. Applications
   * should check this flag. If set to TRUE, the server list should be retrieved through getServerList
   * and cached.
   *
   * @return boolean TRUE if a refresh occurred, FALSE if not.
   */
  public function checkServerListRefreshed() {
    return $this->mollom_servers_refreshed;
  }

  /**
   * The client tries to detect the ip address of the author and send it with an API call to Mollom. If
   * the host of the client is behind a reverse proxy, the address of the proxy might inadvertently be
   * sent to Mollom. Setting the reverse proxy addresses will avoid this.
   *
   * @param array $addresses An array containing the ip addresses of reverse proxies
   */
  public function setReverseProxyAddresses($addresses) {
    $this->reverse_proxy_addresses = $addresses;
  }

  /**
   * Implements mollom.verifyKey.
   *
   * @return array|boolean Either an array with the response or FALSE if the query failed
   */
  public function verifyKey() {
    if ($this->query('mollom.verifyKey')) {
	    return $this->mollom_response;
	  }
		
  	return FALSE;
  }

  /**
   * Implements mollom.detectLanguage
   *
   * @param String $text Text on which to run language detection
   * @return array|boolean Either an array containing pairs of language and confidence values or FALSE if the query failed
   */
  public function detectLanguage($text = NULL) {
  	$data = array('text' => $text);

	  if ($languages = $this->query('mollom.detectLanguage', $data)) {
      return $languages;
    }
    
    return FALSE;
  }

  /**
   * Implements mollom.addBlacklistText
   * 
   * @param String $text Up to 100 characters of text to be blacklisted on your site, in lowercase, with all leading and trailing spaces trimmed
   * @param String $context The information to search for the text, either "everything" for the entire post, "links" for link URLs and link titles only or "author" for all author related information
   * @param String $reason 	One of the following reasons the text is blacklisted: "spam", "profanity", "low-quality", "unwanted"
   * @return boolean Always returns TRUE unless the query failed in which case FALSE was returned.
   */
  public function addBlacklistText($text = NULL, $context = 'everything', $reason = 'spam') {
  	$data = array(
	    'text'    => $text,
	    'context'   => $context,
	    'reason'  => $reason,
	  );

  	if ($result = $this->query('mollom.addBlacklistText', $data)) {
	    return TRUE;
	  }

    return FALSE;	
  }

  /**
   * Implements mollom.removeBlacklistText
   *
   * @param String $text Up to 100 characters of text to be blacklisted on your site, in lowercase, with all leading and trailing spaces trimmed
   * @param String $context The information to search for the text, either "everything" for the entire post, "links" for link URLs and link titles only or "author" for all author related information
   * @param String $reason 	One of the following reasons the text is blacklisted: "spam", "profanity", "low-quality", "unwanted"
   * @return boolean Always returns TRUE unless the query failed in which case FALSE was returned.
   */   
  public function removeBlacklistText($text = NULL) {
	  $data = array(
	    'text' => $text,
	  );
	
	  if ($result = $this->query('mollom.removeBlacklistText', $data)) {
	    return TRUE;
	  }
	
	  return FALSE;
  }

  /**
  * Get a list of blacklisted text strings
  *
  * Get the list of text strings which the administrator has blacklisted with Mollom for 
  * the current domain.
  *
  * @return boolean|array Either an array with the response or FALSE if the query failed
  */
  public function listBlacklistText() {
	  if ($blacklist = $this->query('mollom.listBlacklistText')) {
  	  return $blacklist;
    }

    return FALSE;
  }

  /**
   * Implements mollom.addBlacklistURL
   *
   * @param String $url A URL to be added to your site's custom URL blacklist.
   * @return boolean Always returns TRUE unless the query failed in which case FALSE was returned.
   */
  public function addBlacklistURL($url = NULL) {
    $data = array(
	    'url' => $url,
    );

    if ($result = $this->query('mollom.addBlacklistURL', $url)) {
      return TRUE;
    }

	  return FALSE;
  }

  /**
   * Implements mollom.removeBlacklistURL
   *
   * @param String $url A URL to be removed from your site's custom URL blacklist.
   * @return boolean Always returns TRUE unless the query failed in which case FALSE was returned.
   */
  public function removeBlacklistURL($url = NULL) {
  	$data = array(
	    'url' => $url,
  	);
	
  	if ($result = $this->query('mollom.removeBlacklistURL', $data)) {
	    return TRUE;
	  }
	
    return FALSE;	
  }

  /**
  * Get a list of blacklisted URL's
  *
  * Get the list of url's which the administrator has blacklisted with Mollom for the
  * current domain.
  *
  * @return array|boolean Either an array with the response or false if the query failed
  */
  public function listBlacklistURL() {
    if ($blacklist = $this->query('mollom.listBlacklistURL')) {
	    return $blacklist;
	  }

	  return FALSE;
  }

  /**
   * Implements mollom.getStatistics
   * 
   * The mollom.getStatistics call retrieves usage statistics from Mollom. In addition to the 
   * authentication parameters, an extra type  field be passed in. There are several possible 
   * string values for type:
   * - total_days — Number of days Mollom has been used.
   * - total_accepted — Total accepted posts.
   * - total_rejected — Total rejected spam posts.
   * - yesterday_accepted — Number of posts accepted yesterday.
   * - yesterday_rejected — Number of spam posts blocked yesterday.
   * - today_accepted — Number of posts accepted today.
   * - today_rejected — Number of spam posts rejected today.
   * 
   * @param String $type Type of statistics requested
   * @return array|boolean Either an array with the response or false if the query failed
   */
  public function getStatistics($type = 'total_days') {
    $types = array('total_days',
								 'total_accepted',
								 'total_rejected',
								 'yesterday_accepted',
								 'yesterday_rejected',
								 'today_accepted',
								 'today_rejected',
	        );
	 
	  if (in_array($type, $types)) {
		  $this->query('mollom.getStatistics', array('type' => $type));
		  return $this->mollom_response;
	  }
	
	  return FALSE;
  }

  // @todo: make these arguments compulsory or not?
  public function checkContent($message = array()) {
    // set data in an array we'll pass along with the call
    $data = array('session_id'     => (!is_null($message['session_id'])) ? $message['session_id'] : $this->session_id,
	              'post_title'     => $message['post_title'],
	              'post_body'      => $message['post_body'],
	              'author_name'    => $message['author_name'],
	              'author_url'     => $message['author_url'],
	              'author_mail'    => $message['author_mail'],
	              'author_openid'  => $message['author_openid'],
	              'author_ip'      => (!is_null($message['author_ip'])) ? $message['author_ip'] : $this->get_author_ip(),
	              'author_id'      => $message['author_id'],
	        );
	
	  if ($this->query('mollom.checkContent', $data)) {
	    $this->session_id = $this->mollom_response['session_id'];
      return $this->mollom_response;
	  }
	
    return FALSE;
  }

  /**
  * Check the captcha response
  *
  * Check the respone which has been submitted to the captcha generated during this
  * session.
  *
  * @param string $method the API function you like to call
  * @param array $data the arguments the called API function you want to pass
  * @return array|boolean Either an array with the response or FALSE if the query failed
  */
  public function checkCaptcha($session_id = NULL, $captcha_solution) {
    $data = array('session_id'     => (!is_null($sesion_id)) ? $session_id : $this->session_id,
                  'solution'       => $captcha_solution,
            );

	  if ($this->query('mollom.checkCaptcha', $data)) {
	    $this->session_id = $this->mollom_response['session_id'];
      return $this->mollom_response;
	  }

    return FALSE;
  }

  /**
  * Get an image captcha
  *
  * Get an image captcha. This is a link to an image file you can embed in your site.
  *
  * @param string $method the API function you like to call
  * @param array $data the arguments the called API function you want to pass
  * @return array|Boolean Either an array with the response or false if the query failed
  */
  public function getImageCaptcha($session_id = NULL, $author_ip = NULL) {
    $data = array('session_id'     => (!is_null($sesion_id)) ? $session_id : $this->session_id,
	              'author_ip'      => (!is_null($author_ip)) ? $author_ip : $this->get_author_ip(),
            );

	  if ($this->query('mollom.getImageCaptcha', $data)) {
	    $this->session_id = $this->mollom_response['session_id'];
      return $this->mollom_response;
	  }

	  return FALSE;
  }

  /**
  * Get an audio captcha
  *
  * Get an audio captcha. This is a link to a playable MP3 file
  *
  * @param string $method the API function you like to call
  * @param array $data the arguments the called API function you want to pass
  * @return mixed Either an array with the response or false if the query failed
  */
  public function getAudioCaptcha($session_id = NULL, $author_ip = NULL) {
    $data = array('session_id'     => (!is_null($sesion_id)) ? $session_id : $this->session_id,
	                'author_ip'      => (!is_null($author_ip)) ? $author_ip : $this->get_author_ip(),
            );

    if ($this->query('mollom.getAudioCaptcha', $data)) {
	    $this->session_id = $this->mollom_response['session_id'];
      return $this->mollom_response;
	  }

    return FALSE;
  }

  /**
  * Send a query to the Mollom servers
  *
  * Create a valid query and send it to the public Mollom API over XML-RPC.
  *
  * @param string $method the API function you like to call
  * @param array $data the arguments the called API function you want to pass
  * @return mixed Either a WP_Error on error or a mixed return depending on the called API function
  */
  private function query($method, $data = array()) {
    // check if we need to refresh the server list
    if (is_null($this->mollom_servers)) {
      if (!$this->refresh_server_list()) {
        return FALSE;
      }
    }

	  // fail-over/loadbalancing act
    foreach ($this->mollom_servers as $server) {
     	$this->mollom_rpcclient->setServer($server . '/' . MOLLOM_API_VERSION);
      $result = $this->mollom_rpcclient->query($method, $data + $this->authenticate());

      if ($this->mollom_rpcclient->getErrorCode()) {
	      // refresh the server list and try again
        if ($this->mollom_rpcclient->getErrorCode() == MOLLOM_REFRESH) {
	        if (!$this->refresh_server_list()) {
	          return FALSE;
	        }
        }

        // redirect to a different server
        else if ($this->mollom_rpcclient->getErrorCode() == MOLLOM_REDIRECT) {
          // $server is overloaded, let's try the next one
          // do nothing, travel through the loop again and try the next server in the list
        }

        // Mollom triggered an error
        else if ($this->mollom_rpcclient->getErrorCode() == MOLLOM_ERROR) {
          // The Mollom API triggered an error
          $this->mollom_errors[$this->mollom_rpcclient->getErrorCode()] = $this->mollom_rpcclient->getErrorMessage();
          return FALSE;
        } 

        else {
          // Something went dead wrong!
          $this->mollom_errors[$this->mollom_rpcclient->getErrorCode()] = $this->mollom_rpcclient->getErrorMessage();
          return FALSE;	
        }
      } else {
        // return a response if all went well
        $this->mollom_response = $this->mollom_rpcclient->getResponse();
        return true;
      }
    }

    // we failed, but let's refresh the serverlist - maybe this fixes things next time
    $this->refresh_server_list();
    return FALSE;
  }


  /**
  * Retrieve a list of available servers from Mollom.
  *
  * retrieves a list of available Mollom servers. These are the actual servers which will be handling
  * API calls
  * @return boolean true if a list was succesfully retrieved and stored. Otherwise, Mollom breaks here.
  **/
  public function refresh_server_list() {
    // hard coded list cfr API documentation, section 9
	  $servers = array(
                 'http://xmlrpc1.mollom.com/',
                 'http://xmlrpc2.mollom.com/',
                 'http://xmlrpc3.mollom.com/'
               );

    foreach($servers as $server) {
	    $this->mollom_rpcclient->setServer($server . MOLLOM_API_VERSION);
      if(!$this->mollom_rpcclient->query('mollom.getServerList', $this->authenticate())) {
        // Something went wrong! Let's try the next one in the list
      } else {
        $this->mollom_servers = $this->mollom_rpcclient->getResponse();
        $this->mollom_servers_refreshed = TRUE; // raise a flag to indicate a refresh occured
        return TRUE;
      }
    }

    $this->mollom_errors[$this->mollom_rpcclient->getErrorCode()] = $this->mollom_rpcclient->getErrorMessage();
    return FALSE;
  }

  /**
  * Generate authentication data
  *
  * Set an array with all the neccessary data to authenticate to Mollom: hash, publickey, nonce and a timestamp
  * @return array $data the array with necessary authentication data
  */
  private function authenticate() {
    // Generate a timestamp according to the dateTime format (http://www.w3.org/TR/xmlschema-2/#dateTime):
    $time = gmdate("Y-m-d\TH:i:s.\\0\\0\\0O", time());

    // generate a random nonce
  	$nonce = $this->nonce();

    // Calculate a HMAC-SHA1 according to RFC2104 (http://www.ietf.org/rfc/rfc2104.txt):
	  $hash =  base64_encode(
	  pack("H*", sha1((str_pad($this->private_key, 64, chr(0x00)) ^ (str_repeat(chr(0x5c), 64))) .
      pack("H*", sha1((str_pad($this->private_key, 64, chr(0x00)) ^ (str_repeat(chr(0x36), 64))) . 
   	  $time . ':' . $nonce . ':' . $this->private_key))))
    );

    // Store everything in an array. Elsewhere in the code, we'll add the
    // actual data before we pass it onto the XML-RPC library:
    $data['public_key'] = $this->public_key;
    $data['time'] = $time;
    $data['hash'] = $hash;
    $data['nonce'] = $nonce;

    return $data;
  }

  /** 
  * Generate a random nonce
  *
  * generate a random nonce: a unique number we'll use once
  * @return string A random generated nonce of 32 characters
  */
  private function nonce() {
    $str = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";

    srand((double)microtime()*100000);
    for($i = 0; $i < 32; $i++) {
      $num = rand() % strlen($str);
      $tmp = substr($strn, $num, 1);
      $nonce .= $tmp;
    }

    return $nonce;
  }

  /** 
  * Fetch the IP of the author of the data submitted to Mollom.
  *
  * Fetch the IP of the author of the data submitted to Mollom. This function also
  * deals with reverse proxies if there are any. Consult your host if you don't know
  * whether or not you're behind a proxy.
  *
  * @return string The IP of the host from which the request originates
  */
  private function get_author_ip() {
    $ip_address = $_SERVER['REMOTE_ADDR'];

    if (is_array($this->reverse_proxy_addresses)) {
      if (array_key_exists('HTTP_X_FORWARDED_FOR', $_SERVER)) {
        if (!empty($this->reverse_proxy_addressses) && in_array($ip_address, $this->reverse_proxy_addresses, TRUE)) {
          // If there are several arguments, we need to check the most
          // recently added one, ie the last one.
          $ip_address = array_pop(explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']));
        }
	    }
  	}

    // If WP is run in a clustered environment
    if (array_key_exists('HTTP_X_CLUSTER_CLIENT_IP', $_SERVER)) {
      $ip_address = $_SERVER['HTTP_X_CLUSTER_CLIENT_IP'];
    }

    return $ip_address;
  }
}


?>