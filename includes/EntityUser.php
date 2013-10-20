<?php

/**
 * @file
 * Entity wrapping and form mapping logic.
 */

/**
 * Defines methods for user entities.
 */
class MollomEntityUser extends MollomEntity {

  /**
   * Constructs a new user entity wrapper class instance.
   */
  public function __construct() {
    $this->type = 'user';
    parent::__construct();
  }

  /**
   * Prints HTML for Mollom form fields.
   *
   * @return string
   *   HTML markup for multiple form elements.
   *
   * @see register_form
   */
  public function buildForm($fields) {
    $output = parent::buildForm($fields);
    print $output;
    return $output;
  }

  /**
   * Validates a submitted user registration form.
   *
   * @param array $errors
   *   A WP_Error object holding form validation errors.
   * @param string $sanitized_user_login
   *   The requested username (sanitized).
   * @param string $user_email
   *   The requested e-mail address.
   *
   * @return WP_Error
   *   The passed-in $errors object.
   *
   * @see registration_errors
   */
  public function validateRegistrationForm($errors, $sanitized_user_login, $user_email) {
    if ($this->isPrivileged()) {
      return $errors;
    }
    $data = array(
      'type' => 'user',
      // Ensure to check the unsanitized user input.
      'authorName' => $_POST['user_login'],
      'authorMail' => $user_email,
    );
    // @todo wp_insert_user() is able to process additional user data:
    //   - user_url (authorUrl)
    //   - display_name, first_name, last_name (postTitle)
    //   - description (postBody)
    //   A custom user registration form might expose these values.
    $data = parent::validateForm($data);

    // In case of Mollom validation errors, transfer them into $errors.
    if ($this->hasErrors()) {
      foreach ($this->errors->get_error_codes() as $code) {
        foreach ($this->errors->get_error_messages($code) as $message) {
          $errors->add($code, $message);
        }
      }
    }
    return $errors;
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
    if ($action == 'spam' || $action == 'delete') {
      require_once ABSPATH . 'wp-admin/includes/user.php';
      return wp_delete_user($id);
    }
    return FALSE;
  }

}
