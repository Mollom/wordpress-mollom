<?php

//require_once(ABSPATH . '/wp-content/plugins/' . dirname(plugin_basename(__FILE__)) . '/page.class.php');

class Client_Configuration_Page {
	
	public function display() {
		mollom_theme('client_configuration_page');
	}
	
	public function process() {
	}
}