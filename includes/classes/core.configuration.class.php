<?php

class Configuration_Page {
	
	function __construct() {
	}
	
	public function display() {
		mollom_theme('core_configuration_page');
	}
	
	public function process() {
    $mollom = WP_Mollom::factory();
    if ($mollom->verifyKey()) {
	  	mollom_watchdog('configuration', __('Keys successfully verified', MOLLOM_I18N), MOLLOM_WATCHDOG_NOTICE);
      if($mollom->checkServerListRefreshed()) {
	      update_option('mollom_servers', $mollom->getServerList());
   	  	mollom_watchdog('configuration', __('Server list has been refreshed', MOLLOM_I18N), MOLLOM_WATCHDOG_NOTICE);
      }
    } else if ($mollom->getErrors()) { 
      foreach ($mollom->getErrors() as $error_code => $error_msg) {
	      mollom_watchdog('configuration', $error_msg, MOLLOM_WATCHDOG_ERROR);
        if ($error_code != MOLLOM_ERROR) {
          $error_msg = __('Something went wrong with the connection to the Mollom servers.', MOLLOM_I18N);
        }
  	    mollom_set_message($error_msg, 'error');
      }
    } else {
	      $error_msg = __('You need a public and a private key before you can make use of Mollom. <a href="http://mollom.com/user/register">Register</a> with Mollom to get your keys.', MOLLOM_I8N);
	      mollom_set_message($error_msg, 'error');
	  }
	}
}