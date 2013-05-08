<?php

/**
 * @file
 * Entity wrapping and form mapping logic.
 */

/**
 * Defines generic base definitions and methods shared across all entity types.
 */
class MollomEntityComment extends MollomEntity {

  public function __construct() {
    $this->type = 'comment';
    parent::__construct();
  }

  /**
   * Adds HTML for Mollom form fields to a given array of form fields.
   *
   * @param array $fields
   *   An associative array whose keys are input field names and whose values
   *   are raw HTML representations of the form fields to output.
   *
   * @return array
   *   $fields with additional 'mollom' key (containing HTML markup for multiple
   *   form elements).
   *
   * @see comment_form_defaults
   */
  public function buildForm($fields) {
    $fields['comment_notes_after'] .= parent::buildForm();
    return $fields;
  }

  /**
   * {@inherit}
   *
   * @see comment_form()
   */
  public function alterFormAction($url, $path) {
    if ($path === '/wp-comments-post.php') {
      $url .= '#commentform';
    }
    return $url;
  }

  public function validateForm($comment) {
    if ($this->isPrivileged()) {
      return $comment;
    }
    $data = array(
      'authorName' => $comment['comment_author'],
      'authorMail' => $comment['comment_author_email'],
      'authorUrl' => $comment['comment_author_url'],
    );
    if (!empty($comment['user_ID'])) {
      $data['authorId'] = $comment['user_ID'];
    }
    // These parameters should be sent regardless of whether they are empty.
    $data += array(
      'postBody' => isset($comment['comment_content']) ? $comment['comment_content'] : '',
      'contextUrl' => get_permalink(),
      'contextTitle' => get_the_title($comment['comment_post_ID']),
    );
    if (isset($comment['comment_type']) && $comment['comment_type'] == 'trackback') {
      $data['unsure'] = FALSE;
    }
    $data = parent::validateForm($data);

    // If there are errors, re-render the page containing the form.
    if ($this->errors->get_error_code()) {
      add_action('wp_enqueue_scripts', array('MollomForm', 'enqueueScripts'));
      add_action('comment_form_before', array($this, 'beforeFormRendering'), -100);
      add_action('comment_form_after', array($this, 'afterFormRendering'), 100);

      // @see http://codex.wordpress.org/Function_Reference/WP_Query
      $post = query_posts('p=' . $comment['comment_post_ID']);
      // @see template-loader.php
      $template = get_single_template();
      include $template;
      // Prevent wp_new_comment() from processing this POST further.
      exit;
    }

    $comment['mollom_content_id'] = $data['contentId'];
    return $comment;
  }

  /**
   * Reacts to comment status changes.
   *
   * When a spam comment is "unspammed", it might transition into either
   * "approved", "unapproved", or alternatively even into trash. The target
   * status is the original status, which is read from the internal
   * '_wp_trash_meta_status' meta data value.
   */
  public function transitionStatus($new_status, $old_status, $comment) {
    if ($old_status == 'spam' && $new_status != 'spam') {
      $this->sendFeedback($comment->comment_ID, 'approve');
    }
  }

  /**
   * Moderates an entity.
   *
   * @param int $id
   *   The entity ID.
   * @param string $action
   *   The moderation action to perform.
   *
   * @return bool
   *   TRUE on success, FALSE on error.
   */
  public function moderate($id, $action) {
    if ($action == 'delete') {
      $action = 'trash';
    }
    return wp_set_comment_status($id, $action);
  }

}
