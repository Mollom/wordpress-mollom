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
class WP_Test_Check_Comment extends WP_UnitTestCase {

  public $plugin_slug = 'wp-mollom';

	private $comment;
	private $wpmollom;

	public function setUp() {
		parent::setUp();

		$this->wpmollom = WPMollom::getInstance();
		$this->comment = array(
		  'comment_post_ID' => 1,
			'comment_author' => 'admin',
			'comment_author_email' => 'author@domain.tld',
		  'comment_author_url' => 'http://authorsite.tld',
			'comment_content' => 'comment content',
			'comment_type' => '',
			'comment_parent' => 0,
			'user_ID'=> 1,
		);
	}

	public function test_check_ham_comment() {
		$this->comment['comment_content'] = 'ham';
		$result = $this->wpmollom->check_comment($this->comment);
		$this->assertEquals($result, $this->comment);
	}

  public function test_check_spam_comment() {
		$this->comment['comment_content'] = 'spam';
		$result = $this->wpmollom->check_comment($this->comment);
		$this->assertEquals($result, $this->comment);
	}

	public function test_check_unsure_comment() {
		$this->comment['comment_content'] = 'unsure';
		$result = $this->wpmollom->check_comment($this->comment);
		$this->assertEquals($result, $this->comment);
	}
}