<?php

/**
 * @file
 * Entity wrapping and form mapping logic.
 */

/**
 * Defines methods for comment entities.
 */
class MollomEntityComment extends MollomEntity {

  /**
   * The submitted comment form values, as generated by wp-comments-post.php.
   *
   * Not the comment entity (which is an object).
   *
   * @see MollomEntityComment::validateForm()
   *
   * @var array
   */
  protected $comment;

  /**
   * Constructs a new comment entity wrapper class instance.
   */
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
    $fields['comment_notes_after'] .= parent::buildForm($fields);
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

  /**
   * Validates a submitted comment.
   *
   * @param array $comment
   *   An associative array containing comment data.
   *
   * @return array
   *   The passed-in $comment array. Or, in case of validation errors, the page
   *   containing the comment form is re-rendered to allow the user to e.g.
   *   solve CAPTCHA or remove profanity and try again.
   */
  public function validateForm($comment) {
    if ($this->isPrivileged()) {
      return $comment;
    }
    // Make $comment available to MollomEntityComment::afterFormRendering().
    $this->comment = &$comment;

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
    if ($this->hasErrors()) {
      add_action('wp_enqueue_scripts', array('MollomForm', 'enqueueScripts'));
      add_action('comment_form_before', array($this, 'beforeFormRendering'), -100);
      add_action('comment_form_after', array($this, 'afterFormRendering'), 100);

      // @see http://codex.wordpress.org/Function_Reference/WP_Query
      $id = $comment['comment_post_ID'];
      $post = get_post($id);
      $post_type = $post->post_type;

      // initialize defaults
      $field = 'p';

      // @see template-loader.php
      $template = get_single_template();

      if ($post_type === 'attachment') {
          $field = 'attachment_id';
          $template = get_attachment_template();
      } elseif ($post_type === 'page') {
          $field = 'page_id';
          $template = get_page_template();
      }

      query_posts($field . '=' . $id);

      // do not rely on a specialized template to be present, it is not a requirement for a theme
      if (empty($template)) {
        $template = get_index_template();
      }

      include $template;
      // Prevent wp_new_comment() from processing this POST further.
      exit;
    }

    $comment['mollom_content_id'] = $data['contentId'];
    return $comment;
  }

  /**
   * Overrides Entity::afterFormRendering().
   *
   * Additionally handles comment-reply.js behavior to retain/resemble reply UX.
   *
   * @see comment-reply.js
   */
  public function afterFormRendering() {
    parent::afterFormRendering();

    // When replying to a parent comment, comment-reply.js injects the parent
    // comment ID into form values on the fly and re-positions the comment form
    // in the DOM. In case the comment form is re-rendered, the submitted
    // 'comment_parent' form value still exists, but the comment-reply.js
    // behavior is not re-triggered.
    if (!empty($this->comment['comment_parent'])) {
      $parent_id = $this->comment['comment_parent'];
      // The comment-reply.js code is not written in a modular way and cannot be
      // invoked programmatically; the required parameters are constructed
      // dynamically for each comment right before it is rendered. Therefore,
      // this hack parses and re-executes the 'onclick' attribute of the
      // corresponding comment reply link.
      $comment_reply_js = <<<EOD
<script type="text/javascript">
(function () {
try {
  var onclick = document.getElementById('comment-{$parent_id}')
    .getElementsByClassName('comment-reply-link')[0]
    .getAttribute('onclick');
  eval(onclick.substring(7));
}
catch (e) {}
})();
</script>
EOD;
      echo $comment_reply_js;
    }
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
