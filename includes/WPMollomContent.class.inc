<?php

class WPMollomContent extends WPMollomBase {

  /**
   * Callback. Perform the actual Mollom check on a new comment
   *
   * This function hooks onto the comment preprocessing. It will pass the comment
   * to Mollom. Depending on the result, it will either pass the comment to WP as ham,
   * block it as spam or show captcha if unsure. Trackbacks are also passed to Mollom.
   *
   * @param array $comment The preprocessed comment
   * @return array The comment if it passed the check, or void to block it from the database
   */
  public function check_comment($comment) {
    // If a logged in user exists check if the role is exempt from a Mollom check
    // non-registered visitors don't have a role so their submissions are always checked
    $user = wp_get_current_user();
    if ($user->ID) {
      $mollom_roles = get_option('mollom_roles');
      $detected = array_intersect($user->roles, $mollom_roles);
      if (count($detected) > 0) {
        return $comment;
      }
    }
    return $comment;
  }

  /**
   * Fetch the IP address of the user who posts data to Mollom
   *
   * This function tries to retrieve the correct IP address of a user posting data
   * to Mollom. Since an IP address can be hidden through a reverse proxy, we need to resolve
   * this correctly by parsing the http incoming request.
   * First we try to determine if the request matches a list of proxies, if yes, substitute
   * with the HTTP_X_FORWARDED_FOR property.
   * Second we'll look if this site runs in a clustered environment. If yes, substitute with
   * the HTTP_X_CLUSTER_CLIENT_IP property.
   *
   * @return string
   *   The IP of the host from which the request originates
   */
  private function fetch_author_ip() {
    $reverse_proxy_option = get_option('mollom_reverseproxy_addresses', '');
    $ip_address = $_SERVER['REMOTE_ADDR'];

    if (!empty($reverse_proxy_option)) {
      $reverse_proxy_addresses = explode(',', $reverse_proxy_option);
      if (!empty($reverse_proxy_addresses)) {
        if (array_key_exists('HTTP_X_FORWARDED_FOR', $_SERVER)) {
          if (in_array($ip_address, $reverse_proxy_addresses, TRUE)) {
            // If there are several arguments, we need to check the most
            // recently added one, ie the last one.
            $ip_address = array_pop(explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']));
          }
        }
      }
    }

    // If WP is run in a clustered environment
    if (array_key_exists('HTTP_X_CLUSTER_CLIENT_IP', $_SERVER)) {
      $ip_address = $_SERVER['HTTP_X_CLUSTER_CLIENT_IP'];
    }

    return $ip_address;
  }
}
