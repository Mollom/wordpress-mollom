<?php
/* 
Plugin Name: WP Mollom Core
Plugin URI: http://wordpress.org/extend/plugins/wp-mollom/
Description: Enable <a href="http://www.mollom.com">Mollom</a> on your wordpress blog. This plugin provides Mollom core functionality.
Author: Matthias Vandermaesen
Version: 0.1.0-alpha
Author URI: http://www.colada.be
Email: matthias@colada.be
*/

/*  Copyright 2008, 2009, 2010 Matthias Vandermaesen  (email : matthias@colada.be) 
   This program is free software; you can redistribute it and/or modify
   it under the terms of the GNU General Public License as published by
   the Free Software Foundation; either version 2 of the License, or 
   (at your option) any later version.
   This program is distributed in the hope that it will be useful, 
   but WITHOUT ANY WARRANTY; without even the implied warranty of
   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
   GNU General Public License for more details.
   You should have received a copy of the GNU General Public License
   along with this program; if not, write to the Free Software
   Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
*/

/* define version information */
define( 'MOLLOM_CORE_VERSION', '0.1.0-alpha' );
define( 'MOLLOM_USER_AGENT', 'WP Mollom for Wordpress ' . MOLLOM_CORE_VERSION );

/* define WP Mollom's Watchdog table */
define( 'MOLLOM_WATCHDOG', 'mollom_watchdog');

/* define WP Mollom's comment table */
define( 'MOLLOM_TABLE', 'mollom' );

/* define WP Mollom's i18n namespace */
define( 'MOLLOM_I18N', 'wp-mollom' );

/* define the path to the plugin directory */
define( 'MOLLOM_PLUGIN_PATH', 'wp-content/plugins/' . dirname(plugin_basename(__FILE__)) );
define( 'MOLLOM_BASE_PATH', ABSPATH . MOLLOM_PLUGIN_PATH );

/* define which messages are kept in the watchdog */
define ( 'MOLLOM_LOG_LEVEL', 1 );

class WP_Mollom {
	
	private $mollom;
	
  function __construct() {
	  // load functions
	  require_once(ABSPATH . '/wp-content/plugins/' . dirname(plugin_basename(__FILE__)) . '/includes/common.inc.php');
	  require_once(ABSPATH . '/wp-content/plugins/' . dirname(plugin_basename(__FILE__)) . '/includes/templates.inc.php');
		
    // load an instance of type Mollom
    $this->mollom = WP_Mollom::factory();
    $this->refresh_statistics();

  	// load the text domain for localization
	  load_plugin_textdomain(MOLLOM_I18N, false, dirname(plugin_basename(__FILE__)));
	
	  // register the installer
   	register_activation_hook(__FILE__, array(&$this, 'installer'));

    // register the administration page
	  add_action('admin_menu',array(&$this, 'register_administration_page'));
	
	  // register a poormans' cron function
	  add_action('init', array(&$this, 'cron'));
	
	  // register check_comment function
	  add_action('preprocess_comment', array(&$this, 'check_comment'));
	
  	//add_action('wp_set_comment_status', array(&$this, 'mollom_manage_wp_queue'));
	 /* add_action('preprocess_comment', array(&$this, 'check_comment')); 
		add_action("admin_print_scripts", array(&$this, 'js_libs'));
    add_action("admin_print_styles", array(&$this, 'style_libs'));
    add_action('wp_ajax_mollom_statistics', array(&$this, 'statistics'));	 */
  }

  function js_libs() {
	  wp_enqueue_script('jquery');
	  wp_enqueue_script('thickbox');
  }

  function style_libs() {
	  wp_enqueue_style('thickbox');
  }

  function statistics() {
	  // @todo remove someDomain string
	  ?>
	  <embed src="http://mollom.com/statistics.swf?key=<?php echo get_option('mollom_public_key'); ?>"
		quality="high" width="600" height="425" name="Mollom" align="middle"
		play="true" loop="false" allowScriptAccess="sameDomain"
		type="application/x-shockwave-flash"
		pluginspage="http://www.adobe.com/go/getflashplayer"></embed>
	  <?php
	  exit();
  }

  // @todo: revise this
  function refresh_statistics() {
	  // refresh statistics every hour. We don't want to make an API call with each request
    $_statistics = get_option('mollom_statistics');
    if ($_statistics['cache_time'] < (time() + 3600)) {
	    $statistics = array();
			$statistic_types = array('total_days',
									 'total_accepted',
									 'total_rejected',
									 'yesterday_accepted',
									 'yesterday_rejected',
									 'today_accepted',
									 'today_rejected',
		  );
	
  	  foreach ($statistic_types as $type) {
      	$statistic = $this->mollom->getStatistics($type);
        if ($statistic === FALSE) {
	        return FALSE;
        }
        $statistics['mollom_' . $type] = $statistic;	
	
	      // @todo watchdog if error!
	      $statistics['cache_time'] = time();
      }
      update_option('mollom_statistics', serialize($statistics));
    }	
  }

  static function factory() {
		require_once(ABSPATH . '/wp-includes/class-IXR.php');	
    require_once(ABSPATH . '/wp-content/plugins/' . dirname(plugin_basename(__FILE__)) . '/includes/classes/mollom.class.php');	
    require_once(ABSPATH . '/wp-content/plugins/' . dirname(plugin_basename(__FILE__)) . '/includes/classes/mollomrpcclientwrapper.class.php');	

		$xmlrpc_instance =& new MollomRPCClientWrapper('0.0.0.0', false, 80, 10);
		$xmlrpc_instance->user_agent = MOLLOM_USER_AGENT;

		$mollom =& new Mollom(get_option('mollom_public_key'), get_option('mollom_private_key'), $xmlrpc_instance);
		
		$mollom_servers = get_option('mollom_servers', NULL);
		$mollom->setServerList($mollom_servers);
				
		return $mollom;
  }

  static function register_administration_tabs() {
	  return array(
	    'core_configuration' => array(
		     'name'  => 'Configuration',
		     'class' => 'Configuration_Page',
		     'file'  => 'core.configuration.class.php',
	     ),
	    'core_watchdog' => array(
		     'name' => 'Watchdog',
		     'class' => 'Watchdog_Page',
		     'file'  => 'core.watchdog.class.php',
		  ),
		  'core_text_blacklist' => array(
			   'name' => 'Text blacklist',
			   'class' => 'Text_Blacklist_Page',
			   'file' => 'core.textblacklist.class.php',
			),
      'client_configuration' => array(
 	      'name' => 'Comment configuration',
	      'class' => 'Client_Configuration_Page',
	      'file' => 'client.configuration.class.php',
	    ),
	  );
	}
	
  /**
   * Register pages in the configuration section
   */
  function register_administration_page() {
	  add_submenu_page('options-general.php', __('Mollom', MOLLOM_I18N), __('Mollom', MOLLOM_I18N), 'manage_options', 'mollom-key-config', array(&$this, 'configuration_page'));
	  add_action('admin_init', array(&$this, 'register_configuration_options'));
  }

  function register_configuration_options() {
    register_setting('mollom_configuration_settings', 'mollom_public_key');
    register_setting('mollom_configuration_settings', 'mollom_private_key');
    register_setting('mollom_configuration_settings', 'mollom_roles');
    register_setting('mollom_configuration_settings', 'mollom_site_policy'); 
    register_setting('mollom_configuration_settings', 'mollom_reverse_proxy');
    register_setting('mollom_configuration_settings', 'mollom_reverse_proxy_addresses');
  }

  function configuration_page() {
	  // fetch tabs and set a default page
		$active_tab = (!isset($_GET['tab'])) ? 'core_configuration' : $_GET['tab'];
		mollom_load_page($active_tab);
	}	

  /**
   * Installer
   *
   * Installs WP Mollom. Creates database tables and variables in the {prefix}_options table
   */
  function installer() {
		if(!get_option('mollom_ham_count'))
			add_option('mollom_ham_count', 0);
		if(!get_option('mollom_spam_count'))
			add_option('mollom_spam_count', 0);
		if(!get_option('mollom_unsure_count'))
			add_option('mollom_unsure_count', 0);
		if(!get_option('mollom_count_moderated'))
			add_option('mollom_count_moderated', 0);
		if(!get_option('mollom_site_policy'))
			add_option('mollom_site_policy', true);
		if(!get_option('mollom_statistics'))
			add_option('mollom_statistics', NULL);
		if(!get_option('mollom_roles')) {
			$mollom_roles = array();
			foreach ($wp_roles->roles as $role => $data) {		
				$mollom_roles[] = $role;
			}
			add_option('mollom_roles', serialize($mollom_roles));
		}	
	
	  $sql = "watchdog_ID BIGINT( 20 ) UNSIGNED NOT NULL auto_increment,
            comment_ID BIGINT( 20 ) NULL DEFAULT NULL,
	          type VARCHAR ( 40 ) NULL DEFAULT NULL,
	          message LONGTEXT NULL,
  	        severity SMALLINT ( 1 ) NOT NULL DEFAULT NULL,
            created datetime NOT NULL default '0000-00-00 00:00:00',
	         PRIMARY KEY ( watchdog_ID )";

	  mollom_run_install(MOLLOM_WATCHDOG, '1', $sql);
	
		if (get_option('mollom_version_api') != MOLLOM_VERSION) {
			// updates of the database if the plugin  was already installed
			$version = MOLLOM_VERSION;
			update_option('mollom_version_api', $version);
		}
		
  	mollom_watchdog('installer', 'Mollom successfully installed');
  }

	/** 
	* mollom_check_comment
	* Check if a comment is spam or ham
	* @param array $comment the comment passed by the preprocess_comment hook
	* @return array The comment passed by the preprocess_comment hook
	*/
	function check_comment($comment) {
		echo "<pre>";
			var_dump("value to hold");
		echo "</pre>";
		$mollom_comment_data = array('post_body' => $comment['comment_content'],
									 'author_name' => $comment['comment_author'],
									 'author_url' => $comment['comment_author_url'],
									 'author_mail' => $comment['comment_author_email']);

		$result = $this->mollom->checkContent($mollom_comment_data); //('mollom.checkContent', $mollom_comment_data);	
		echo "<pre>";
			var_dump($result);
		echo "</pre>";
		echo "<pre>";
			var_dump($this->mollom->getErrors());
		echo "</pre>";
		return $comment;
	}
	
	
	function stub_check_comment($comment) {
		global $mollom_sessionid;

		// if it's a trackback, pass it on
		if($comment['comment_type'] == 'trackback') {
			mollom_check_trackback($comment);
		}

		$private_key = get_option('mollom_private_key');
		$public_key = get_option('mollom_public_key');

		// check if the client is configured all toghether
		if ((empty($private_key)) || (empty($public_key))) {
			if (get_option('mollom_site_policy')) {
				wp_die(__('You haven\'t configured Mollom yet! Per the website\'s policy. We could not process your comment.', MOLLOM_I8N));
			}
		}

		// only check the captcha if there is an active Mollom session
		// skip to the captcha check if session ID was $_POST'ed
		
		// @todo: change this into a nonce check. The CAPTCHA form should get a nonce upon
		// generation. If that nonce exists and validates: execute the CAPTCHA logic. If the
		// nonce doesn't exist: treat it as a regular comment. If the nonce fails, display
		// an error
		
		if ($_POST['mollom_sessionid']) {
			$comment = mollom_check_captcha($comment);
			return $comment;
		} else {		
			// If a logged in user exists check if the role is exempt from a Mollom check
			// non-registered visitors don't have a role so their submissions are always checked
			$user = wp_get_current_user();
			if ($user->ID) {
				$mollom_roles = unserialize(get_option('mollom_roles'));
				$detected = array_intersect($user->roles, $mollom_roles);
				if (count($detected) > 0) {			
					return $comment;
				}
			}		

			$mollom_comment_data = array('post_body' => $comment['comment_content'],
										 'author_name' => $comment['comment_author'],
										 'author_url' => $comment['comment_author_url'],
										 'author_mail' => $comment['comment_author_email'],
										 'author_ip' => _mollom_author_ip());

			$result = mollom('mollom.checkContent', $mollom_comment_data);

			// quit if an error was thrown else return to WP Comment flow
			if (function_exists('is_wp_error') && is_wp_error($result)) {
				if(get_option('mollom_site_policy')) {
					wp_die($result, __('Something went wrong...', MOLLOM_I8N));
				} else {
					return $comment;
				}
			}

			$mollom_sessionid = $result['session_id'];

			switch($result['spam']) {
				case MOLLOM_ANALYSIS_HAM: {
					// let the comment pass
					global $spaminess;
					$spaminess = $result['quality'];
					_mollom_set_plugincount("ham");
					add_action('comment_post', '_mollom_save_session', 1);
					return $comment;
				}
				case MOLLOM_ANALYSIS_UNSURE: {
					// show a CAPTCHA and and set the count of blocked messages
					_mollom_set_plugincount("spam");
					$mollom_comment = _mollom_set_fields($_POST, $comment);
					$mollom_comment['mollom_sessionid'] = $result['session_id'];
					$mollom_comment['mollom_spaminess'] = $result['quality'];
					mollom_show_captcha('', $mollom_comment);
					die();
				}
				case MOLLOM_ANALYSIS_SPAM: {
					// kill the process here because of spam detection and set the count of blocked messages
					_mollom_set_plugincount("spam");	
					wp_die(__('Your comment has been marked as spam or unwanted by Mollom. It could not be accepted.', MOLLOM_I8N));
				}
				default: {
					// default behaviour: trigger an error (policy mode)
					if (function_exists('is_wp_error') && is_wp_error($result)) {
						if(get_option('mollom_site_policy')) {
							wp_die($result, __('Something went wrong...', MOLLOM_I8N));
						} else {
							return $comment;
						}
					}
				}
			}
		}

		// last resort: die to protect the db	
		wp_die(-6, __('Something went wrong...', MOLLOM_I8N));
	}
	
	function cron() {
		purge_mollom_watchdog();
	}
}

// Work it, baby!
$mollom = new WP_Mollom();