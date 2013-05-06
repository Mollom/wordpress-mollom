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

      <h3><label><?php _e('Reverse proxy addresses', MOLLOM_I18N); ?></label></h3>
      <input type="text" size="50" name="mollom_reverseproxy_addresses" id="mollom-reverseproxy-addresses" value="<?php print $mollom_reverseproxy_addresses; ?>" />
      <p class="description">
      <?php _e('If your site resides behind one or more reverse proxies, enter their IP addresses as a comma separated list.'); ?>
      </p>
