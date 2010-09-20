<?php

class Watchdog_Page {
	
	/**
	 * Logic before displaying this page
	 */
	public function display($form_state = array()) {
		global $wpdb;
		$watchdog_tbl =  $wpdb->prefix . MOLLOM_WATCHDOG;
		
    $current = (isset($_GET['apage'])) ? $_GET['apage'] : 1;
    $number = 20;
    $offset = ($current * $number) - $number;

	  $count_query = "SELECT COUNT(*) FROM $watchdog_tbl";
    if (isset($form_state['watchdog_level'])) {
      $count_query .= " WHERE severity = " . $form_state['watchdog_level'];
    }

    $total_messages = $wpdb->get_var($count_query);

	  $query = "SELECT * FROM $watchdog_tbl";
    if (isset($form_state['watchdog_level'])) {
      $query .= " WHERE severity = " . $form_state['watchdog_level'];
    }
    $query .= " ORDER BY created DESC LIMIT $offset, $number";

		$watchdog_messages = $wpdb->get_results($query);

		$page_links = paginate_links( array(
			'base' => add_query_arg( 'apage', '%#%' ),
			'format' => '',
			'prev_text' => __('&laquo;'),
			'next_text' => __('&raquo;'),
			'total' => ceil($total_messages / $number),
			'current' => $current,
		));

		mollom_theme('core_watchdog_page', $page_links, $watchdog_messages);
	}
	
	/**
	 * Process form values
	 */
	public function process($form_values) {
		$form_state = NULL;
		if ($form_values['watchdog_level'] != "all") {
  		$form_state['watchdog_level'] = $form_values['watchdog_level'];
    }
		return $form_state;
	}
}