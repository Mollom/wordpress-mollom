<?php

/**
 * @file
 * Form API helpers.
 */

/**
 * Form construction, processing, validation, and rendering.
 *
 * Stupidly simplified and uglified re-implementation of Drupal's sophisticated
 * form handling.
 *
 * If you like the basic concepts, use Drupal: http://drupal.org
 * Alternatively, use Symfony's Form component: http://symfony.com
 * Either way, build your site on a platform that knows how to treat user input.
 */
class MollomForm {

  /**
   * Unescapes user input.
   *
   * We'll not make ourselves friends, but it has to be stated bluntly:
   *
   * WP core apparently has no idea at all what "user input" really means, and
   * how to deal with user input in a web application layer.
   *
   * All available original user input is unconditionally passed through
   * *database* query string escaping functions and is replaced without backup.
   * The WP core logic essentially resembles PHP's magic_quotes_gpc, which is not
   * only discouraged and deprecated, it even has been removed from PHP 5.4.
   * The inappropriate munging of user input happens during WP's bootstrap.
   * The security of all existing WP core functionality and contributed plugins
   * factually depends on this bogus string escaping behavior.
   *
   * @see wp_magic_quotes()
   * @see add_magic_quotes()
   * @see addslashes_gpc()
   *
   * @param array $input
   *   An associative array containing the bogusly escaped user input (typically
   *   $_POST).
   *
   * @return array
   *   The passed-in $input array, without escaping.
   */
  public static function unescapeUserInput($input) {
    // WP core developers identified this problem already and provided a helper
    // function that allows to revert the bogus escaping in one-off situations.
    return stripslashes_deep($input);
  }

  /**
   * Parses an HTML snippet and returns it as a DOM object.
   *
   * This function loads the body part of a partial (X)HTML document and returns
   * a full DOMDocument object that represents this document.
   *
   * @param string $text
   *   The partial (X)HTML snippet to load. Invalid markup will be corrected on
   *   import.
   *
   * @return DOMDocument
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
   * @param DOMDocument $dom_document
   *   A DOMDocument object to serialize, only the tags below
   *   the first <body> node will be converted.
   *
   * @return string
   *   A valid (X)HTML snippet, as a string.
   */
  public static function serializeDOM($dom_document) {
    $body_node = $dom_document->getElementsByTagName('body')->item(0);
    $body_content = '';

    foreach ($body_node->getElementsByTagName('script') as $node) {
      self::escapeDOMCdataElement($dom_document, $node);
    }

    foreach ($body_node->getElementsByTagName('style') as $node) {
      self::escapeDOMCdataElement($dom_document, $node, '/*', '*/');
    }

    foreach ($body_node->childNodes as $child_node) {
      $body_content .= $dom_document->saveXML($child_node);
    }
    return $body_content;
  }

  /**
   * Adds comments around the <!CDATA section in a dom element.
   *
   * DOMDocument::loadHTML in self::loadDOM() makes CDATA sections from the
   * contents of inline script and style tags.  This can cause HTML 4 browsers to
   * throw exceptions.
   *
   * This function attempts to solve the problem by creating a DocumentFragment
   * and commenting the CDATA tag.
   *
   * @param DOMDocument $dom_document
   *   The DOMDocument containing the $dom_element.
   * @param DOMElement $dom_element
   *   The element potentially containing a CDATA node.
   * @param string $comment_start
   *   String to use as a comment start marker to escape the CDATA declaration.
   * @param string $comment_end
   *   String to use as a comment end marker to escape the CDATA declaration.
   */
  public static function escapeDOMCdataElement($dom_document, $dom_element, $comment_start = '//', $comment_end = '') {
    foreach ($dom_element->childNodes as $node) {
      if (get_class($node) == 'DOMCdataSection') {
        $embed_prefix = "\n<!--{$comment_start}--><![CDATA[{$comment_start} ><!--{$comment_end}\n";
        $embed_suffix = "\n{$comment_start}--><!]]>{$comment_end}\n";

        // Prevent invalid cdata escaping as this would throw a DOM error.
        // This is the same behavior as found in libxml2.
        // Related W3C standard: http://www.w3.org/TR/REC-xml/#dt-cdsection
        // Fix explanation: http://en.wikipedia.org/wiki/CDATA#Nesting
        $data = str_replace(']]>', ']]]]><![CDATA[>', $node->data);

        $fragment = $dom_document->createDocumentFragment();
        $fragment->appendXML($embed_prefix . $data . $embed_suffix);
        $dom_element->appendChild($fragment);
        $dom_element->removeChild($node);
      }
    }
  }

  /**
   * Enqueues files for inclusion in the head of a page
   */
  public static function enqueueScripts() {
    wp_enqueue_style('mollom', MOLLOM_PLUGIN_URL . '/css/mollom.css');
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
