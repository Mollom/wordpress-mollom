<?php

/**
 * This suite tests check_comment filter.
 * 
 * This is a set of tests to check whether or not comment filtering happens
 * consistently.
 * 
 * If you want to run these, you'll need PHP Unit and the Wordpress Tests project.
 * @link https://github.com/nb/wordpress-tests
 */
class Mollom_Test_Check_Comment extends WP_UnitTestCase {

  public $plugin_slug = 'wp-mollom';

	private $comment;
	private $wpmollom;

	public function setUp() {
		parent::setUp();
		
		$this->wpmollom = WPMollom::getInstance();
		$this->comment = array(
		
		);
	}

	public function test_check_ham_comment() {
	}
	
  
}