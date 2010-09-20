<style type="text/css">
.column-watchdog-severity {
	width: 10%;
}
.column-watchdog-message {
	width: 65%;
}
.column-watchdog-time {
	width: 15%;
}
ul.tabs li {
  display: inline;
}
</style>
<div class="wrap">
	<ul class="tabs">
	  <?php foreach ($tabs as $tab) : ?>
	    <li><?php print $tab; ?></li>
  	<?php endforeach; ?>
  </ul>

	<h2>
		<?php _e('Mollom watchdog', MOLLOM_I18N); ?>
	</h2>
	
	<?php print $messages; ?>
	
	<?php if (empty($watchdog_messages )) : ?>
		<p><?php _e('The watchdog hasn\'t logged any messages yet or all messages were purged.', MOLLOM_I18N); ?></p>
	<?php else : ?>

	<form id="watchdog-form" action="" method="post">	
	<div class="tablenav">
  
	<?php if ( $page_links ) : ?>
	    <div class="tablenav-pages">
	      <?php $page_links_text = sprintf( '<span class="displaying-num">' . __( 'Displaying %s&#8211;%s of %s' ) . '</span>%s',
		      number_format_i18n( $start + 1 ),
    	  	number_format_i18n( min( $page * $comments_per_page, $total ) ),
	  	    '<span class="total-type-count">' . number_format_i18n( $total ) . '</span>',
		      $page_links
	      ); echo $page_links_text; ?>
	    </div>
	
    	<input type="hidden" name="_total" value="<?php echo esc_attr($total); ?>" />
	    <input type="hidden" name="_per_page" value="<?php echo esc_attr($comments_per_page); ?>" />
	    <input type="hidden" name="_page" value="<?php echo esc_attr($page); ?>" />
	
	  <?php endif; ?>	

		<div class="alignleft actions">

      <!-- TODO: filter functionality -->
			<select name="watchdog_level">
				<option value="all"><?php _e('Show all watchdog messages'); ?></option>
			<?php

				foreach ( $severity_levels as $level => $label ) {
					echo "	<option value='" . esc_attr($level) . "'";
					selected( $comment_type, $type );
					echo ">$label</option>\n";
				}
			?>
			</select>
			<input type="submit" id="post-query-submit" value="<?php esc_attr_e('Filter'); ?>" class="button-secondary" />

		</div>
  </div>

	 	<table cellspacing="0" class="widefat watchdog fixed">
			<thead>
				<tr>
					<th style="" class="manage-column column-watchdog-severity" id="author" scope="col">
						<?php _e('Severity', MOLLOM_I18N); ?>
					</th>
					<th style="" class="manage-column column-watchdog-message" id="comment" scope="col">
						<?php _e('Message', MOLLOM_I18N); ?>
					</th>
					<th style="" class="manage-column column-watchdog-time" id="comment" scope="col">
						<?php _e('Time', MOLLOM_I18N); ?>
					</th>
				</tr>
			</thead>
		
			<tfoot>
				<tr>
					<th style="" class="manage-column column-watchdog-severity" id="severity" scope="col">
						<?php _e('Severity', MOLLOM_I18N); ?>
					</th>
					<th style="" class="manage-column column-watchdog-message" id="message" scope="col">
						<?php _e('Message', MOLLOM_I18N); ?>
					</th>
					<th style="" class="manage-column column-watchdog-time" id="time" scope="col">
						<?php _e('Time', MOLLOM_I18N); ?>
					</th>
				</tr>
			</tfoot>
		
			<tbody class="list:watchdog" id="the-watchdog-list">
			
				<?php foreach ($watchdog_messages as $message) :?>

				<tr class="watchdog-<?php print $severity_levels[$message->severity]; ?>" id="watchdog-<?php print $message->watchdog_ID; ?>">
					<td class="severity column-watchdog-severity">
						<?php print $severity_levels[$message->severity]; ?>
					</td>
					<td class="message column-watchdog-message">
						<?php print $message->message; ?>
					</td>
					<td class="created column-watchdog-message">
						<?php print $message->created; ?>
					</td>						
				</tr>
			
				<?php endforeach; ?>
			
			</tbody>
		</table>
  </form>

  <?php endif; ?>
</div>
