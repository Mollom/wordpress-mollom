<style type="text/css">
#wpcontent select {
  height: 10em;
  width: 140px;
}
ul.tabs li {
  display: inline;
}
</style>

<div class="wrap">
  <ul class="tabs">
	  <?php foreach ($tabs as $tab) : ?>
	    <li><?php print $tab; ?>
  	<?php endforeach; ?>
  </ul>

  <h2><?php _e('Mollom Client Configuration', MOLLOM_I8N); ?></h2>
  
  <div class="narrow">
   <!-- <div id="message" class="updated fade"><p><strong><?php _e('Options saved.') ?></strong></p></div> -->
     <?php print $messages; ?>
