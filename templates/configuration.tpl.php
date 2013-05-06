<div class="wrap">
<?php screen_icon(); ?>
<h2><?php print $title; ?></h2>

<form action="options.php" method="post">
<?php settings_fields('mollom'); ?>

<?php do_settings_sections('mollom'); ?>

<?php submit_button(); ?>

</form>
</div>

<?php return; ?>

      <h3><label><?php _e('Roles', MOLLOM_I18N); ?></label></h3>
      <p><?php _e('Select the roles you want to exclude from the mandatory Mollom check. Default: all roles are exempt.', MOLLOM_I18N); ?></p>
      <?php print $mollom_roles; ?>

      <h3><label><?php _e('Reverse proxy addresses', MOLLOM_I18N); ?></label></h3>
      <input type="text" size="50" name="mollom_reverseproxy_addresses" id="mollom-reverseproxy-addresses" value="<?php print $mollom_reverseproxy_addresses; ?>" />
      <p class="description">
      <?php _e('If your site resides behind one or more reverse proxies, enter their IP addresses as a comma separated list.'); ?>
      </p>

      <h3><label><?php _e('Remote moderation', MOLLOM_I18N); ?></label></h3>
      <p><input type="checkbox" name="moderation_redirect" value="block" <?php echo $mollom_moderation_redirect; ?> />&nbsp;&nbsp;<?php print strtr(__('Redirect <a href="@local-moderation">local moderation pages</a> to the <a href="@remote-moderation">hosted Mollom moderation system</a>', MOLLOM_I18N), array(
        '@local-moderation' => admin_url('edit-comments.php'),
        '@remote-moderation' => 'http://my.mollom.com',
      )); ?></p>
