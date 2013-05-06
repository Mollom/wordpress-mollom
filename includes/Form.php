<?php

/**
 * @file
 * Form API helpers.
 */

/**
 * Form construction, processing, validation, and rendering.
 *
 * Stupidly simplified and UGLIFIED re-implementation of Drupal's sophisticated
 * form handling.
 *
 * If you like the basic concepts, use Drupal: http://drupal.org
 * Alternatively, use Symfony: http://symfony.com
 * Either way, build your site on a platform that knows how to treat user input.
 */
class MollomForm {

  /**
   * Adds HTML for Mollom form fields to a given array of form fields.
   *
   * @param array $fields
   *   An associative array whose keys are input field names and whose values
   *   are raw HTML representations of the form fields to output.
   *
   * @return array
   *   $fields with additional 'mollom' key (containing multiple elements).
   */
  public static function addMollomFields($fields) {
    $values = (isset($_POST['mollom']) ? $_POST['mollom'] : array());
    $values += array(
      'contentId' => '',
      'captchaId' => '',
      'homepage' => '',
    );

    $fields['mollom'] = MollomForm::formatInput('hidden', 'mollom[contentId]', $values['contentId']);
    $fields['mollom'] .= MollomForm::formatInput('hidden', 'mollom[captchaId]', $values['captchaId']);

    $fields['mollom'] .= '<div class="hidden">';
    $fields['mollom'] .= MollomForm::formatInput('text', 'mollom[homepage]', $values['homepage']);
    $fields['mollom'] .= '</div>';

    if (!empty($_POST['mollom']['captchaId'])) {
      // @todo Automatically retrieve a new CAPTCHA in case captchaUrl doesn't
      //   exist for whatever reason?
      $output = '<div>';
      $output .= '<img src="' . $_POST['mollom']['captchaUrl'] . '" alt="Type the characters you see in this picture." />';
      $output .= '</div>';
      $output .= MollomForm::formatInput('text', 'mollom[solution]', '', array('required' => NULL, 'size' => 10));
      $fields['mollom'] .= MollomForm::formatItem('text', __('Word verification'), $output);
    }
    return $fields;
  }

  /**
   * Form pre-render callback.
   *
   * Starts output buffering for MollomForm::afterFormRendering().
   */
  public static function beforeFormRendering() {
    if (empty($_POST['mollom'])) {
      return;
    }
    ob_start();
  }

  /**
   * Form post-render callback.
   *
   * Re-injects previously submitted POST values back into a newly rendered form.
   */
  public static function afterFormRendering() {
    if (empty($_POST['mollom'])) {
      return;
    }
    // Retrieve the captured form output.
    $output = ob_get_contents();
    ob_end_clean();
  
    // Prepare all POST parameter values for re-injection.
    $values = array();
    foreach (explode('&', http_build_query($_POST)) as $param) {
      list($key, $value) = explode('=', $param);
      $values[urldecode($key)] = urldecode($value);
    }
  
    // Re-inject all POST values into the form.
    $dom = self::loadDOM($output);
    foreach ($dom->getElementsByTagName('input') as $input) {
      if ($name = $input->getAttribute('name')) {
        if (isset($values[$name])) {
          $input->setAttribute('value', $values[$name]);
        }
      }
    }
    foreach ($dom->getElementsByTagName('textarea') as $input) {
      if ($name = $input->getAttribute('name')) {
        if (isset($values[$name])) {
          $input->nodeValue = htmlspecialchars($values[$name], ENT_QUOTES, 'UTF-8');
        }
      }
    }
    // Inject error messages.
    // After form#commentform anchor/jump-target, but before form fields.
    $form = $dom->getElementsByTagName('form')->item(0);
    $errors = $dom->createElement('div');
    $errors->setAttribute('class', 'p messages error');
    if (count($_POST['_errors']) == 1) {
      $errors->nodeValue = $_POST['_errors'][0];
    }
    else {
      $list = $dom->createElement('ul');
      foreach ($_POST['_errors'] as $message) {
        $list->appendChild($dom->createElement('li', $message));
      }
      $errors->appendChild($list);
    }
    $form->insertBefore($errors, $form->firstChild);
  
    // Output the form again.
    echo self::serializeDOM($dom);
  }

  /**
   * Parses an HTML snippet and returns it as a DOM object.
   *
   * This function loads the body part of a partial (X)HTML document and returns
   * a full DOMDocument object that represents this document.
   *
   * @param $text
   *   The partial (X)HTML snippet to load. Invalid markup will be corrected on
   *   import.
   *
   * @return
   *   A DOMDocument that represents the loaded (X)HTML snippet.
   */
  public static function loadDOM($text) {
    $dom_document = new DOMDocument();
    // Ignore warnings during HTML soup loading.
    @$dom_document->loadHTML('<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd"><html xmlns="http://www.w3.org/1999/xhtml"><head><meta http-equiv="Content-Type" content="text/html; charset=utf-8" /></head><body>' . $text . '</body></html>');

    return $dom_document;
  }

  /**
   * Converts a DOM object back to an HTML snippet.
   *
   * The function serializes the body part of a DOMDocument back to an XHTML
   * snippet. The resulting XHTML snippet will be properly formatted to be
   * compatible with HTML user agents.
   *
   * @param $dom_document
   *   A DOMDocument object to serialize, only the tags below
   *   the first <body> node will be converted.
   *
   * @return
   *   A valid (X)HTML snippet, as a string.
   */
  public static function serializeDOM($dom_document) {
    $body_node = $dom_document->getElementsByTagName('body')->item(0);
    $body_content = '';
    foreach ($body_node->childNodes as $child_node) {
      $body_content .= $dom_document->saveXML($child_node);
    }
    return $body_content;
  }

  /**
   * Enqueues files for inclusion in the head of a page
   */
  public static function enqueueScripts() {
    wp_enqueue_style('mollom', MOLLOM_PLUGIN_URL . '/css/mollom.css');
  }

  /**
   * Formats HTML for the privacy policy notice/link.
   *
   * @todo $options is specific to comment_form().
   */
  public static function formatPrivacyPolicyLink($options) {
    if (get_option('mollom_privacy_link', TRUE)) {
      $options['comment_notes_after'] .= "\n";
      $options['comment_notes_after'] .= '<p class="description">';
      $options['comment_notes_after'] .= vsprintf(__('By submitting this form, you accept the <a href="%s" target="_blank" rel="nofollow">Mollom privacy policy</a>.', MOLLOM_L10N), array(
        '//mollom.com/web-service-privacy-policy',
      ));
      $options['comment_notes_after'] .= '</p>';
    }
    return $options;
  }

  /**
   * Formats a form element item/container as HTML.
   *
   * @param string $type
   *   The form element type; e.g., 'text', 'email', or 'textarea'.
   * @param string $label
   *   The (raw) label for the form element.
   * @param string $children
   *   The inner HTML content for the form element; typically a form input
   *   element generated by MollomForm::formatInput().
   * @param string $description
   *   (optional) A (sanitized) description for the form element.
   * @param array $attributes
   *   (optional) An associative array of attributes to apply to the form input
   *   element; see format_attributes().
   *
   * @return string
   *   The formatted HTML form element.
   */
  public static function formatItem($type, $label, $children, $description = NULL, $attributes = array()) {
    $attributes += array(
      'item' => array(),
      'label' => array(),
    );
    $attributes['item']['class'][] = 'form-item';
    $attributes['item']['class'][] = 'form-type-' . $type;

    $output = '<div ' . self::formatAttributes($attributes['item']) . '>';
    if (isset($label)) {
      $label = htmlspecialchars($label, ENT_QUOTES, 'UTF-8');
      if ($type == 'checkbox' || $type == 'radio') {
        $output .= $children;
        $output .= ' <label ' . self::formatAttributes($attributes['label']) . '>' . $label . '</label>';
      }
      else {
        $output .= '<label ' . self::formatAttributes($attributes['label']) . '>' . $label . '</label>';
        $output .= $children;
      }
    }
    else {
      $output .= $children;
    }
    if (!empty($description)) {
      $output .= '<p class="description">';
      $output .= $description;
      $output .= '</p>';
    }
    $output .= '</div>';
    return $output;
  }

  /**
   * Formats a form input element as HTML.
   *
   * @param string $type
   *   The form element type; e.g., 'text', 'email', or 'textarea'.
   * @param string $name
   *   The (raw) form input name; e.g., 'body' or 'mollom[contentId]'.
   * @param string $value
   *   The (raw/unsanitized) form input value; e.g., 'sun & me were here'.
   * @param string $label
   *   (optional) The label for the form element.
   * @param array $attributes
   *   (optional) An associative array of attributes to apply to the form input
   *   element; see format_attributes().
   *
   * @return string
   *   The formatted HTML form input element.
   */
  public static function formatInput($type, $name, $value, $attributes = array()) {
    $attributes['name'] = $name;
    if ($type == 'textarea') {
      $attributes = self::formatAttributes($attributes);
      $output = "<$type $attributes>";
      $output .= htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
      $output .= "</$type>";
    }
    else {
      $attributes['type'] = $type;
      if ($type == 'checkbox') {
        if (!empty($value)) {
          $attributes['checked'] = NULL;
        }
        $attributes['value'] = 1;
      }
      else {
        $attributes['value'] = $value;
      }
      $attributes = self::formatAttributes($attributes);
      $output = "<input $attributes />";
    }
    $output .= "\n";
    return $output;
  }

  /**
   * Formats HTML/DOM element attributes.
   *
   * @param array $attributes
   *   (optional) An associative array of attributes to format; e.g.:
   *     array(
   *      'title' => 'Universal title',
   *      'class' => array('foo', 'bar'),
   *     )
   *   Pass NULL as an attribute's value to achieve a value-less DOM element
   *   property; e.g., array('required' => NULL).
   *
   * @return string
   *   A string containing the formatted HTML element attributes.
   */
  public static function formatAttributes($attributes = array()) {
    foreach ($attributes as $attribute => &$data) {
      if ($data === NULL) {
        $data = $attribute;
      }
      else {
        $data = implode(' ', (array) $data);
        $data = $attribute . '="' . htmlspecialchars($data, ENT_QUOTES, 'UTF-8') . '"';
      }
    }
    return $attributes ? implode(' ', $attributes) : '';
  }

  /**
   * Helper for add_settings_field().
   */
  public static function printItemsArray($options) {
    foreach ($options['options'] as $key => $label) {
      $item = array(
        'type' => rtrim($options['type'], 'es'),
        'name' => $options['type'] == 'radios' ? $options['name'] : $options['name'] . "[$key]",
        'label' => $label,
      );
      if (isset($options['values'][$key]) && is_array($options['values'])) {
        $item['value'] = $options['values'][$key];
      }
      elseif ($options['type'] == 'radios') {
        if ($options['value'] === $key) {
          $item['checked'] = NULL;
        }
        $item['value'] = $key;
      }
      elseif (isset($options['value'])) {
        $item['value'] = $options['value'];
      }
      else {
        $item['value'] = NULL;
      }
      self::printItemArray($item);
    }
    if (!empty($options['description'])) {
      print '<p class="description">';
      print $options['description'];
      print '</p>';
    }
  }

  /**
   * Helper for add_settings_field().
   */
  public static function printItemArray($options) {
    $options += array(
      'label' => NULL,
      'description' => NULL,
      'attributes' => array(),
    );
    $options['attributes'] += array(
      'item' => array(),
      'label' => array(),
    );
    $input_attributes = array_diff_key($options, array_flip(array('type', 'name', 'value', 'label', 'description', 'attributes')));

    if (!isset($input_attributes['id'])) {
      $input_attributes['id'] = preg_replace('@[^a-zA-Z0-9]@', '-', $options['name']);
    }
    if ($options['type'] == 'radio') {
      $input_attributes['id'] .= '-' . preg_replace('@[^a-zA-Z0-9]@', '-', $options['value']);
    }
    $input = self::formatInput($options['type'], $options['name'], $options['value'], $input_attributes);

    $options['attributes']['label'] += array('for' => $input_attributes['id']);
    $item = self::formatItem($options['type'], $options['label'], $input, $options['description'], $options['attributes']);
    print $item;
  }

  /**
   * Helper for add_settings_field().
   */
  public static function printInputArray($attributes) {
    $attributes = self::formatAttributes($attributes);
    $output = "<input $attributes />\n";
    print $output;
  }

}
