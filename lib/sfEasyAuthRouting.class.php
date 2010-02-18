<?php

/*
 * Based on sfGuardRouting from sfGuardPlugin
 */

/**
 *
 * @package    symfony
 * @subpackage plugin
 * @author     Fabien Potencier <fabien.potencier@symfony-project.com>
 * @version    SVN: $Id: sfGuardRouting.class.php 13346 2008-11-25 19:10:17Z FabianLange $
 */
class sfEasyAuthRouting
{
  /**
   * Listens to the routing.load_configuration event.
   *
   * @param sfEvent An sfEvent instance
   */
  static public function listenToRoutingLoadConfigurationEvent(sfEvent $event)
  {
    $r = $event->getSubject();

    // preprend our routes
    $r->prependRoute('sf_easy_auth_login', 
      new sfRoute('/login', 
        array(
          'module' => 'sfEasyAuth', 
          'action' => 'login'
        )
      )
    );
    $r->prependRoute('sf_easy_auth_logout', 
      new sfRoute('/logout', 
        array(
          'module' => 'sfEasyAuth', 
          'action' => 'logout'
        )
      )
    );
    $r->prependRoute('sf_easy_auth_secure', 
      new sfRoute('/secure', 
        array(
          'module' => 'sfEasyAuth', 
          'action' => 'secure'
        )
      )
    );
    $r->prependRoute('sf_easy_auth_password_reset_send_email', 
      new sfRoute('/pw-reset', 
        array(
          'module' => 'sfEasyAuth', 
          'action' => 'passwordResetSendEmail'
        )
      )
    );
    $r->prependRoute('sf_easy_auth_password_reset_set_password', 
      new sfRoute('/pw-reset-set-password', 
        array(
          'module' => 'sfEasyAuth', 
          'action' => 'passwordResetSetPassword'
        )
      )
    );
    
    // just clicking on the auto-login link will activate their account, so we can 
    // send the user wherever we want. The homepage is a reasonable place to start 
    $r->prependRoute('sf_easy_auth_email_confirmation_confirm', 
      new sfRoute('/activate-account', 
        array(
          'module' => 'homepage', 
          'action' => 'index'
        )
      )
    );
  }

  static public function addRouteForAdminUser(sfEvent $event)
  {
    $event->getSubject()->prependRoute('sf_easy_auth_user', new sfDoctrineRouteCollection(array(
      'name'                 => 'sf_easy_auth_user',
      'model'                => 'sfEasyAuthUser',
      'module'               => 'sfEasyAuthUser',
      'prefix_path'          => 'sf_easy_auth_user',
      'with_doctrine_route'  => true,
      'requirements'         => array(),
    )));
  }
}
