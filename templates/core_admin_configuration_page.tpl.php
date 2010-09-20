  <style type="text/css">
  ul.tabs li {
	  display: inline;
  }

  div.narrow {
	  width: 80%;
  }
  
  div.column-left {
	  float: left;
  	width: 48%;
    margin-right: 2%;
  }

  div.column-right {
	  float: left; 
    width: 48%;
	  margin-left: 2%;
  }

  p.numbers span {
	  font-size: 15px;
	  font-weight: bold;
  }

  span.visualize {
	  background: url(/<?php print $mollom_template_path; ?>/images/icons.gif) top left no-repeat;
	  display: block;
	  font-size: 13px;
	  height: 23px;	
	  padding: 5px 0 0 30px;  
  }
  </style>

  <div class="wrap">
	  <ul class="tabs">
  	  <?php foreach ($tabs as $tab) : ?>
		    <li><?php print $tab; ?></li>
	  	<?php endforeach; ?>
	  </ul>
	
    <h2><?php _e('Mollom Flightdeck', MOLLOM_I18N); ?></h2>

    <div class="narrow">
	
		  <?php print $messages; ?>			
				
  	  <div class="column-left">
						  <p><?php _e('Mollom is a web service that helps you identify content quality and, more importantly, helps you stop comment and contact form spam. When moderation becomes easier, you can spend more time and energy to interact with your web community.', MOLLOM_I8N); ?></p>	
				       <form action="options.php" method="post" id="mollom_configuration" style="margin: auto;">

								<?php settings_fields( 'mollom_configuration_settings' ); ?>

					  <!--  <p><?php _e('You need a public and a private key before you can make use of Mollom. <a href="http://mollom.com/user/register">Register</a> with Mollom to get your keys.', MOLLOM_I8N); ?></p> -->

					    <h3><label><?php _e('Public key', MOLLOM_I8N); ?></label></h3>
					    <p><input type="text" size="35" maxlength="32" name="mollom_public_key" class="mollom-public-key<?php print ' ' . $fault['mollom-public-key']; ?>" value="<?php print $mollom_public_key; ?>" /></p>

					    <h3><label><?php _e('Private key', MOLLOM_I8N); ?></label></h3>
					    <p><input type="text" size="35" maxlength="32" name="mollom_private_key" id="mollom-private-key" value="<?php print $mollom_private_key; ?>" /></p>

					  <!--  <h3><label><?php _e('User roles', MOLLOM_I8N); ?></label></h3>
					    <p><?php _e('Select the roles you want to exclude from the mandatory Mollom check. Default: all roles are exempt.', MOLLOM_I8N); ?></p>

							<ul class="mollom-roles">
								<?php foreach ($mollom_roles as $role) :
									 print $role;
								  endforeach; ?>
							</ul>

					    <h3><label><?php _e('Policy mode', MOLLOM_I8N); ?></label></h3>
					    <p><input type="checkbox" name="mollom_site_policy" <?php print $mollom_site_policy; ?>/>&nbsp;&nbsp;<?php _e('If Mollom services are down, all comments are blocked by default.', MOLLOM_I8N); ?></p> -->

					<h3><label><?php _e('Reverse proxy', MOLLOM_I8N); ?></label></h3>
					<p><?php _e('Check this if your host is running a reverse proxy service (squid,...) and enter the ip address(es) of the reverse proxy your host runs as a commaseparated list.', MOLLOM_I8N); ?></p>
					<p><?php _e('When in doubt, just leave this off.', MOLLOM_I8N); ?></p>
					<p><?php _e('Enable: ', MOLLOM_I8N); ?><input type="checkbox" name="mollom_reverse_proxy" <?php print $mollom_reverseproxy; ?> />&nbsp;-&nbsp;
					<input type="text" size="35" maxlength="255" name="mollom_reverse_proxy_addresses" id="mollom-reverseproxy-addresses" value="<?php print $mollom_reverse_proxy_addresses; ?>" /></p>
					<p class="submit"><input type="submit" value="<?php _e('Update options &raquo;', MOLLOM_I8N); ?>" id="submit"/></p>
				</form>
		  </div>
  		<div class="column-right">
		  	<p class="numbers"><?php echo sprintf(__('Mollom was activated <span>%s</span> days ago. Until now, <span>%s</span> submissions were accepted and <span>%s</span> rejected. Yesterday, Mollom blocked <span>%s</span> spam attempts and accepted <span>%s</span> ham messages. So far, Mollom blocked <span>%s</span> spam attempts and <span>%s</span> ham messages today.', MOLLOM_I8N), $mollom_total_days, $mollom_total_accepted, $mollom_total_rejected, $mollom_yesterday_rejected, $mollom_yesterday_accepted, $mollom_today_rejected, $mollom_today_accepted); ?></p>

				<span class="visualize"><a class="thickbox" href="<?php echo get_option('siteurl'); ?>/wp-admin/admin-ajax.php?action=mollom_statistics&width=600&height=430"  title="Mollom by numbers">Visualize</a> these numbers.</p>

	      <p><a href="http://www.mollom.com" title="Mollom"><img src="/<?php print $mollom_template_path; ?>/images/mollom-logo.jpg" /></a></p>		
  		</div>
			
	  </div>
</div>