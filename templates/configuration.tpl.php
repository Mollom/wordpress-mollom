<div class="wrap">
  <h2><?php _e('Mollom Settings', MOLLOM_I18N); ?></h2>

  <div class="narrow">
    <?php print $messages; ?>
  <div class="column-left">

    <p><?php _e('Mollom is a web service that helps you identify content quality and, more importantly, helps you stop comment and contact form spam. When moderation becomes easier, you can spend more time and energy to interact with your web community.', MOLLOM_I18N); ?></p>
	 
    <form action="options-general.php?page=mollom-key-config" method="post" id="mollom_configuration" style="margin: auto;">
      <?php settings_fields( 'mollom_configuration_settings' ); ?>
 
      <p><?php _e('You need a public and a private key before you can make use of Mollom. <a href="http://mollom.com/user/register">Register</a> with Mollom to get your keys.', MOLLOM_I18N); ?></p>
 
      <h3><label><?php _e('Public key', MOLLOM_I18N); ?></label></h3>
      <input type="text" size="35" maxlength="32" name="mollom_public_key" class="mollom-public-key<?php print ' ' . $fault['mollom-public-key']; ?>" value="<?php print $mollom_public_key; ?>" />

      <h3><label><?php _e('Private key', MOLLOM_I18N); ?></label></h3>
      <input type="text" size="35" maxlength="32" name="mollom_private_key" id="mollom-private-key" value="<?php print $mollom_private_key; ?>" />

			<?php mollom_nonce_field($mollom_nonce); ?>
      <input type="submit" name="submit" value="<?php _e('Update options &raquo;', MOLLOM_I18N); ?>" id="submit"/>
    </form>
  </div> 
  
</div>