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
      <input type="text" size="50" maxlength="32" name="publicKey" class="mollom-public-key" value="<?php print $publicKey; ?>" />

      <h3><label><?php _e('Private key', MOLLOM_I18N); ?></label></h3>
      <input type="text" size="50" maxlength="32" name="privateKey" id="mollom-private-key" value="<?php print $privateKey; ?>" />

      <h3><label><?php _e('Roles', MOLLOM_I18N); ?></label></h3>
      <p><?php _e('Select the roles you want to exclude from the mandatory Mollom check. Default: all roles are exempt.', MOLLOM_I18N); ?></p>
      <?php print $mollom_roles; ?>

      <h3><label><?php _e('Reverse proxy addresses', MOLLOM_I18N); ?></label></h3>
      <input type="text" size="50" name="mollom_reverseproxy_addresses" id="mollom-reverseproxy-addresses" value="<?php print $mollom_reverseproxy_addresses; ?>" />
      <p class="description">
      <?php _e('If your site resides behind one or more reverse proxies, enter their IP addresses as a comma separated list.'); ?>
      </p>

      <h3><label><?php _e('Fallback mode', MOLLOM_I18N); ?></label></h3>
      <p><input type="checkbox" name="fallback_mode" value="block" <?php echo $mollom_fallback_mode; ?> />&nbsp;&nbsp;<?php _e('Block all posts when Mollom services are unavailable', MOLLOM_I18N); ?></p>
      <p class="description">
      <?php print strtr(__('In case the Mollom services are unreachable, no text analysis can be performed and no CAPTCHAs can be generated. Subscribers to <a href="@pricing-url">Mollom Plus</a> receive access to <a href="@sla-url">Mollom\'s high-availability backend infrastructure</a>, not available to free users, reducing potential downtime.', MOLLOM_I18N), array(
        '@pricing-url' => 'http://mollom.com/pricing',
        '@sla-url' => 'http://mollom.com/standard-service-level-agreement',
      )); ?>
      </p>

      <h3><label><?php _e('Text analysis strategies', MOLLOM_I18N); ?></label></h3>
      <?php print $mollom_check_types; ?>
      <p class="description">
      <?php _e('Different content analysis strategies are available. You can enable one or combine several strategies when analysing content. Defaults: Spam'); ?>
      </p>

      <h3><label><?php _e('Developer mode', MOLLOM_I18N); ?></label></h3>
      <p><input type="checkbox" name="developer_mode" value="on" <?php echo $mollom_developer_mode; ?> />&nbsp;&nbsp;<?php _e('Put your site in developer mode', MOLLOM_I18N); ?></p>
      <p class="description">
      <?php _e('When you are testing code against the Mollom API, you should switch to developer mode. API calls will be made against Molloms\'s testing API instead of the its\' production API'); ?>
      </p>

      <?php mollom_nonce_field($mollom_nonce); ?>
      <input type="submit" name="submit" value="<?php _e('Update options &raquo;', MOLLOM_I18N); ?>" id="submit"/>
    </form>
  </div>
  </div>

</div>