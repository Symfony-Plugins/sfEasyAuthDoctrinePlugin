<?php
/**
 * Simple class for sending emails. Uses PHP's 'mail' function
 * 
 * @author al
 *
 */
class sfEasyAuthSimpleMailer
{
  /**
   * Sends an email containing $message to $eaUser
   * 
   * @param sfEasyAuthUser $eaUser The recipient user
   * @param string $subject The message subject
   * @param string $message The message to send
   * @param string $htmlMessage The HTML message to send
   * 
   * @todo Implement sending html messages
   */
  public static function mail(sfEasyAuthUser $eaUser, $subject, $message, $htmlMessage='')
  {
    // i18n if necessary
    if (sfConfig::get('app_sf_easy_auth_use_i18n'))
    {
      $subject = sfContext::getInstance()->getI18n()->__($subject);
    }

    // send the email
    return mail($eaUser->getEmail(), $subject, $message);
  }
}