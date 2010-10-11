<?php

require_once(ABSPATH . '/wp-includes/class-IXR.php');	
require_once(ABSPATH . '/wp-content/plugins/' . dirname(plugin_basename(__FILE__)) . '/mollom.class.php');

class WPMollom extends Mollom implements MollomXMLRPC {
	private $IXRClient;
	
	public function __construct($public_key = NULL, $private_key = NULL) {
		parent::__construct($public_key, $private_key);
		$this->IXRClient = new IXRWrapper('0.0.0.0', false, 80, 10);
	}
	
	public function setServer($server, $path = false, $port = 80) {
		$this->IXRClient->setServer($server, $path, $port);
  }

  public function xmlrpc($method, $args = NULL) {
	  return $this->IXRClient->query($method, $args);
  }

  public function getResponse() {
    return $this->IXRClient->getResponse();	
  }

  public function isError() {
	  return $this->IXRClient->isError();
  }

  public function getErrorCode() {
	  return $this->IXRClient->getErrorCode();
  }
  
  public function getErrorMessage() {
	  return $this->IXRClient->getErrorMessage();
  }
}

class IXRWrapper extends IXR_Client {	
	
	public function __construct($server, $path = false, $port = 80, $timeout = false) {
		parent::__construct($server, $path, $port, $timeout);
	}
	
	/**
	 * This function allows dynamic setting of the server member of the IXR_CLient object
	 * The code is a copy of the IXR_Client constructor since it does just that.
	 *
   * @param String $server The IP address of the server the client connects with
   * @param String $path A specific subpath where the XML RPC service is listening
   * @param String $port A specific port on which the server is listening. Default is 80 (http)
	 */
	public function setServer($server, $path = false, $port = 80) {
    if (!$path) {
      // Assume we have been given a URL instead
      $bits = parse_url($server);
      $this->server = $bits['host'];
      $this->port = isset($bits['port']) ? $bits['port'] : 80;
      $this->path = isset($bits['path']) ? $bits['path'] : '/';

      // Make absolutely sure we have a path
      if (!$this->path) {
        $this->path = '/';
      }
    } else {
      $this->server = $server;
      $this->path = $path;
      $this->port = $port;
    }
  }
}