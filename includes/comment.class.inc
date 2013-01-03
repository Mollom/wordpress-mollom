<?php

/**
 * @file
 *
 * @author
 *   Matthias Vandermaesen
 */

class MollomComment {

  private $mollom_table;

  public function __construct() {
    global $wpdb;

    $this->mollom_table = $wpdb->prefix . MOLLOM_TABLE;
  }

  /**
   * Describes the schema
   *
   * Maps the columns of the mollom table to the datatypes. We use
   * this mapping to determine the placeholders when creating a new entry.
   */
  private function schema() {
    return array(
      'comment_ID' => '%d',
      'content_ID' => '%s',
      'captcha_ID' => '%s',
      'form_ID' => '%s',
      'moderate' => '%d',
      'changed' => '%d',
      'spamScore' => '%f',
      'spamClassification' => '%s',
      'solved' => '%d',
      'qualityScore' => '%f',
      'profanityScore' => '%f',
      'reason' => '%s',
      'languages' => '%s',
    );
  }

  /**
   * Create a new entry in the mollom table
   *
   * @param unknown_type $data
   */
  public function create($data) {
    global $wpdb;

    $schema = $this->schema();
    $placeholders = array();
    foreach ($schema as $key => $placeholder) {
      if (isset($data[$key])) {
        $placeholders[] = $placeholder;
      }
    }

    $mollom_table = $wpdb->prefix . MOLLOM_TABLE;

    $wpdb->show_errors(TRUE);
    $result = $wpdb->insert($mollom_table, $data, $placeholders);

    return $result;
  }

  /**
   * Get mollom information from the database.
   *
   * Get the record associated with a comment id from the database.
   *
   * @param unknown_type $comment_ID
   */
  public function get($comment_ID) {
    global $wpdb;
    $mollom_comment = $wpdb->get_row($wpdb->prepare("SELECT * FROM " . $this->mollom_table . " WHERE comment_ID = %d", $comment_ID));
    return $mollom_comment;
  }

  /**
   * Delete an entry from the mollom table
   * 
   * @param unknown_type $comment_ID
   */
  public function delete($comment_ID) {
    global $wpdb;
    $count = $wpdb->query($wpdb->prepare("DELETE FROM " . $this->mollom_table . " WHERE comment_ID = %d", $comment_ID));
  }
}