<?php

/**
 *
 * @file
 *   Cache handling logic
 *
 *   Each form intance gets an unique form id which is cached server side. When
 *   The visitor POST's a response, the form id is sent back and validated against the
 *   cache. This class acts as an interface with the cache store.
 *
 * @author
 *   Matthias Vandermaesen
 */

class MollomCache {

  private $cache_table;

  /**
   * Constructor
   */
  public function __construct() {
  	global $wpdb;

   	$this->cache_table = $wpdb->prefix . MOLLOM_CACHE_TABLE;
  }

  /**
   * Create a new entry in the cache table
   *
   * @param double created
   *   A unix timestamp denoting the time when the entry was created
   * @param string form_id
   *   The form id which will be stored
   */
  public function create($created, $form_id, $key) {
    global $wpdb;

    $data = array('created' => $created, 'form_id' => $form_id, 'key' => $key);
    $result = $wpdb->insert($this->cache_table, $data, array('%d', '%s', '%s'));

    return $result;
  }

  /**
   * Checks if the form id exists
   * 
   * Checks if the form id exists and returns an object if it does.
   * 
   * @param string form_id
   *   The form id which will be checked
   * @return mixed
   *   An object with the cached form_id data or FALSE if not found
   */
  public function exists($form_id) {
     global $wpdb;

     $result = $wpdb->get_row($wpdb->prepare("SELECT * FROM $this->cache_table WHERE form_id = %s", $form_id));

     if (is_null($result)) {
       return FALSE;
     }

     return $result;
  }

  /**
   * Delete the form_id from the cache
   * 
   * On cache invalidation, remove the entry from the cache
   *
   * @param string form_id
   *   The form id which will be removed from the cache
   * @return boolean TRUE when succesfull, FALSE if not
   */
  public function delete($form_id) {
  	global $wpdb;

    $count = $wpdb->query($wpdb->prepare("DELETE FROM $this->cache_table WHERE form_id = %s", $form_id));

    if (!$count) {
      return FALSE;
    }

    return TRUE;
  }

  /**
   * Flush the cache
   *
   * Flushes the cache table of entries which are older then a certain interval
   * 
   * @param int $time
   *   A UNIX timestamp
   * @param int $interval
   *   The interval in seconds. Will be substracted from $time. All entries
   *   older then $time - $intervall will be deleted
   * @return boolean TRUE when succesfull, FALSE if not
   */
  public function clear($time, $interval) {
    global $wpdb;

    $limit = $time - $interval;
    $count = $wpdb->query($wpdb->prepare("DELETE FROM $this->cache_table WHERE created < %d", $limit));

    if (!$count) {
      return FALSE;
    }

    return TRUE;
  }
}