<?php

/**
 * @file
 *
 * @author
 *   Matthias Vandermaesen
 */

function _mollom_comment_schema() {
  return array(
    'comment_ID' => '%d',
    'content_ID' => '%s',
    'captcha_ID' => '%s',
    'form_ID' => '%s',
    'moderate' => '%d',
    'changed' => '%d',
    'spamScore' => '%f',
    'spamClassification' => '%f',
    'solved' => '%d',
    'qualityScore' => '%f',
    'profanityScore' => '%f',
    'reason' => '%s',
    'languages' => '%s',
  );
}

function mollom_comment_create($data) {
  global $wpdb;

  $schema = _mollom_comment_schema();
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

function mollom_comment_update($data, $placeholders, $comment_id) {

}

function mollom_comment_delete($comment_ID) {
  global $wpdb;
  $count = $wpdb->query($wpdb->prepare("DELETE FROM " . $wpdb->prefix . MOLLOM_TABLE . " WHERE comment_ID = %d", $comment_ID));
}
