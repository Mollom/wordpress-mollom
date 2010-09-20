<?php

class MollomRPCClientWrapper extends IXR_Client implements MollomRPCClient {
	
	/**
	 * This function allows dynamic setting of the server member of the IXR_CLient object
	 * The code is a copy of the IXR_Client constructor since it does just that.
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