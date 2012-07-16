/**
 * Contains javascript logic for the WP Mollom plugin
 * 
 * @author Matthias Vandermaesen
 */

jQuery(document).ready(function($){
  
  // Switch between audio or image CAPTCHA
  $('.image-captcha a.switch').click(function () {
    $('.image-captcha').hide();
    $('.audio-captcha').show();
  });

  $('.audio-captcha a.switch').click(function () {
    $('.audio-captcha').hide();
    $('.image-captcha').show();
  });
  
});