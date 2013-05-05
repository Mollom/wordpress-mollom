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

      <h3><label><?php _e('Fallback mode', MOLLOM_I18N); ?></label></h3>
      <p><input type="checkbox" name="fallback_mode" value="block" <?php echo $mollom_fallback_mode; ?> />&nbsp;&nbsp;<?php _e('Block all posts when Mollom services are unavailable', MOLLOM_I18N); ?></p>
      <p class="description">
      <?php print strtr(__('In case the Mollom services are unreachable, no text analysis can be performed and no CAPTCHAs can be generated. Subscribers to <a href="@pricing-url">Mollom Plus</a> receive access to <a href="@sla-url">Mollom\'s high-availability backend infrastructure</a>, not available to free users, reducing potential downtime.', MOLLOM_I18N), array(
        '@pricing-url' => 'http://mollom.com/pricing',
        '@sla-url' => 'http://mollom.com/standard-service-level-agreement',
      )); ?>
      </p>
      
      <h3><label><?php _e('Protection mode', MOLLOM_I18N); ?></label></h3>

      <div id="mollom-analysis-mode">
        <div id="form-element-mollom-mode-analysis">
          <label>
            <input type="radio" id="edit-mollom-mode-2" name="protection_mode[mode]" value="1" <?php print $mollom_protection_mode['analysis']; ?> class="form-radio">
            <span>Text analysis</span>
          </label>
        </div>
        <div id="form-element-mollom-mode-spam">
          <label>
            <input type="radio" id="edit-mollom-mode-2" name="protection_mode[mode]" value="2" <?php print $mollom_protection_mode['spam']; ?> class="form-radio">
            <span>CAPTCHA</span>
          </label>
        </div>
      </div>
      <p class="description">
      <?php _e('Different content analysis strategies are available. You can enable one or combine several strategies when analysing content. Defaults: Spam'); ?>
      </p>

      <h3><label><?php _e('Remote moderation', MOLLOM_I18N); ?></label></h3>
      <p><input type="checkbox" name="moderation_redirect" value="block" <?php echo $mollom_moderation_redirect; ?> />&nbsp;&nbsp;<?php print strtr(__('Redirect <a href="@local-moderation">local moderation pages</a> to the <a href="@remote-moderation">hosted Mollom moderation system</a>', MOLLOM_I18N), array(
        '@local-moderation' => admin_url('edit-comments.php'),
        '@remote-moderation' => 'http://my.mollom.com',
      )); ?></p>
