<?php
/*
* Copyright (C) 2017-present, Facebook, Inc.
*
* This program is free software; you can redistribute it and/or modify
* it under the terms of the GNU General Public License as published by
* the Free Software Foundation; version 2 of the License.
* This program is distributed in the hope that it will be useful,
* but WITHOUT ANY WARRANTY; without even the implied warranty of
* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
* GNU General Public License for more details.
*/

/**
 * @package FacebookPixelPlugin
 */

namespace FacebookPixelPlugin\Integration;

defined('ABSPATH') or die('Direct access not allowed');

use FacebookPixelPlugin\Core\FacebookPixel;
use FacebookPixelPlugin\Core\FacebookWordpressOptions;

class FacebookWordpressWPForms extends FacebookWordpressIntegrationBase {
  const PLUGIN_FILE = 'wpforms-lite/wpforms.php';
  const TRACKING_NAME = 'wpforms-lite';

  private static $leadJS = "
jQuery(document).ready(function ($) {
  $('.wpforms-form').submit(function(e) {
    e.preventDefault();

    var email = $('.wpforms-field input[type=email]').val();
    if (
      email &&
      /^[a-z0-9.!#$%%&'*+\/=?^_`{|}~-]+@((?=[a-z0-9-]{1,63}\.)(xn--)?[a-z0-9]+(-[a-z0-9]+)*\.)+[a-z]{2,63}$/i.test(email)
    ) {
      var param = {
        em: email,
        fb_wp_tracking: '%s'
      };
      %s
    }
  });
});
";

  public static function injectPixelCode() {
    add_action(
      'wpforms_frontend_output',
      array(__CLASS__, 'injectLeadEventHook'),
      11);
  }

  public static function injectLeadEventHook($form_data) {
    add_action(
      'wp_footer',
      array(__CLASS__, 'injectLeadEvent'),
      11);
  }

  public static function injectLeadEvent() {
    if (is_admin()) {
      return;
    }

    $pixel_code = FacebookPixel::getPixelLeadCode('param', false);
    $listener_code = sprintf(
      self::$leadJS,
      self::TRACKING_NAME,
      $pixel_code);

    printf("
<!-- Facebook Pixel Event Code -->
<script>
%s
</script>
<!-- End Facebook Pixel Event Code -->
      ",
      $listener_code);
  }
}
