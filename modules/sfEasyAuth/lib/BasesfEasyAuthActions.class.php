<?php

/**
 * sfEasyAuth actions.
 *
 * @package    .
 * @subpackage sfEasyAuth
 * @author     Ally
 * @version    SVN: $Id: actions.class.php 12479 2008-10-31 10:54:40Z fabien $
 */
class BasesfEasyAuthActions extends sfActions
{
 /**
  * Executes login action
  *
  * @param sfRequest $request A request object
  */
  public function executeLogin(sfWebRequest $request)
  {
    $sfUser = $this->getUser();

    // user is already authenticated, so send them to the success url
    if ($sfUser->isAuthenticated())
    {
      $this->logMessage('User is already authenticated. Redirecting.', 'debug');
      $this->redirect(sfConfig::get('app_sf_easy_auth_login_success_url', '@homepage'));
    }
    
    if ($this->handleLogIn($request))
    {
      // log in successful, so redirect
      // to a URL set in app.yml
      // or to the homepage
      $url = sfConfig::get('app_sf_easy_auth_login_success_url', '@homepage');

      // if the original url the user was trying to access was saved, redirect them
      // there
      $url = ($this->getUser()->getAttribute('sf_easy_auth.restricted_url')) ? 
        $this->getUser()->getAttribute('sf_easy_auth.restricted_url') : $url;
      
      // call an event after logging the user out and before redirecting them
      $url = $this->getContext()->getEventDispatcher()->filter(new sfEvent(
        $this,
        'sf_easy_auth.filter_login_redirect_url',
        array(
          'sfUser' => $sfUser,
        )
      ), $url)->getReturnValue();
      
      $this->logMessage("Redirecting user to $url", 'debug');
      
      $this->redirect($url);
    }
  }
  
  /**
   * Handles both ordinary log-ins and when a user requires more credentials
   *  
   * @param sfWebRequest $request
   * @return boolean True if the a user's details matched details in the database
   */
  protected function handleLogIn(sfWebRequest $request)
  {
    $sfUser = $this->getUser();

    $this->loginForm = new sfEasyAuthLoginForm();
    $this->resetForm = new sfEasyAuthPasswordResetForm();

    if ($request->isMethod('post'))
    {
      $this->loginForm->bind($request->getParameter($this->loginForm->getName()));
      
      if ($this->loginForm->isValid())
      {
        $this->logMessage('Valid form data submitted.', 'debug');
        
        $authenticateMethod = sfConfig::get('app_sf_easy_auth_authenticate_callable', '');
        
        $username = $this->loginForm->getValue('username');
        $password = $this->loginForm->getValue('password');

        // call an event prior to authentication
        $this->getContext()->getEventDispatcher()->notify(new sfEvent(
          $this,
          'sf_easy_auth.pre_authentication',
          array(
            'username' => $username,
            'password' => $password
          )
        ));
        
        if (is_array($authenticateMethod) && count($authenticateMethod) == 2)
        {
          $this->logMessage('Authenticating user with custom authentication method ' . implode('::', $authenticateMethod), 'debug');
          
          $result = call_user_func($authenticateMethod, $username, $password);
        }
        else
        {
          $this->logMessage('Authenticating user with default authentication method.', 'debug');
          
          $result = $sfUser->authenticate($username, $password);
        }
        
        // call an event after authentication, but before processing the result
        $this->getContext()->getEventDispatcher()->notify(new sfEvent(
          $this,
          'sf_easy_auth.post_authentication',
          array(
            'result' => $result
          )
        ));
        
        if ($result === true)
        {
          $this->logMessage('User was successfully authenticated.', 'debug');
          
          // set the remember me cookie if they want it
          if ($this->loginForm->getValue('remember'))
          {
            $this->logMessage('Setting remember me cookie.', 'debug');
            
            $sfUser->setRememberCookie();
          }
          
          return $sfUser->logIn();
        }
        else
        {
          $this->logMessage('Authentication failed.', 'debug');
          
          // log in failed.
          $this->setFlash(sfConfig::get('app_sf_easy_auth_invalid_credentials'));
        }
      }
      else
      {
        $this->logMessage('Invalid form data submitted', 'debug');
        
        return false;
      }
    }
    else
    {
      // store the uri of the page they were trying to access if the user isn't 
      // just trying to log in normally
      if (sfContext::getInstance()->getRouting()->getCurrentInternalUri() != 'sfEasyAuth/login')
      {
        $this->getUser()->setAttribute('sf_easy_auth.restricted_url', $request->getUri());
      }
      else
      {
        // otherwise clear the attribute in case the user is navigating around the site.
        
        // actually, the following line prevents this - i.e. redirecting users back to what
        // they were looking at before being told they need to log in.        
        // $this->getUser()->getAttributeHolder()->remove('sf_easy_auth.restricted_url');
      }
    }
  }
  
  /**
   * Executes the action called when users need additional privileges
   * 
   * @param sfWebRequest $request
   */
  public function executeSecure(sfWebRequest $request)
  {
    $sfUser = $this->getUser();
    
    $loginResult = $this->handleLogIn($request);
    
    if ($loginResult)
    {
      // log in successful, so redirect
      // to a URL set in app.yml
      // or to the homepage
      $url = sfConfig::get('app_sf_easy_auth_login_success_url', '@homepage');

      $this->redirect($url);
    }
    else if ($loginResult !== false && $sfUser->hasAttribute('sf.easy.auth.not.first.secure.attempt'))
    {
      $this->setFlash(sfConfig::get('app_sf_easy_auth_insufficient_privileges'));
    }
    else
    {
      $sfUser->setAttribute('sf.easy.auth.not.first.secure.attempt', 1);
    }
  }
  
 /**
  * Executes logout action
  *
  * @param sfRequest $request A request object
  */
  public function executeLogout(sfWebRequest $request)
  {
    // call an event before logging the user out
    $this->getContext()->getEventDispatcher()->notify(new sfEvent(
      $this,
      'sf_easy_auth.pre_logout',
      array('sfUser' => $this->getUser())
    ));

    $this->getUser()->logOut();

    $url = sfConfig::get('app_sf_easy_auth_logout_success_url', '@homepage');
    
    // call an event after logging the user out and before redirecting them
    $url = $this->getContext()->getEventDispatcher()->filter(new sfEvent(
      $this,
      'sf_easy_auth.filter_logout_redirect_url',
      array(
        'sfUser' => $this->getUser(),
      )
    ), $url)->getReturnValue();
    
    $this->redirect($url);
  }
  
  /**
   * Action that sends users an email to let them reset their password
   * 
   * @param sfRequest $request A request object
   */
  public function executePasswordResetSendEmail(sfWebRequest $request)
  {
    if ($request->isMethod('post'))
    {
      $this->form = new sfEasyAuthPasswordResetForm();
      
      $this->form->bind($request->getParameter($this->form->getName()));
      
      if ($this->form->isValid())
      {
        $email = $this->form->getValue('email');

        // try to retrieve the user with this email address
        if ($eaUser = Doctrine::getTable('sfEasyAuthUser')->findOneByEmail($email))
        {
          // call an event before sending the reset message
          $this->getContext()->getEventDispatcher()->notify(new sfEvent(
            $this,
            'sf_easy_auth.pre_password_reset_message',
            array('eaUser' => $eaUser)
          ));
          
          // send the user an email with an auto log in link with a parameter directing
          // them to a page to pick a new password
          $this->sendPasswordResetMessage($eaUser);
        }
      }
      else
      {
        $this->setFlash(sfConfig::get('app_sf_easy_auth_reset_user_not_found'));
        $this->redirect(sprintf('%s?%s=true', $this->generateUrl('sf_easy_auth_login'), 
          sfConfig::get('app_sf_easy_auth_reset_user_not_found_url_token')));
      }
    }
  }
  
  /**
   * Action that lets users set a new password. Note, this requires that the auto-login
   * filter is enabled.
   * 
   * @param sfRequest $request A request object
   */
  public function executePasswordResetSetPassword(sfWebRequest $request)
  {
    $sfUser = $this->getUser();
    
    // if the user clicked on an auto-log-in link, and the link
    // failed to log them in, send them a message that they need to
    // request a new password reset email - we can't identify them ourselves
    if (!$sfUser->isAuthenticated() || !$sfUser->getAuthUser())
    {
      // tell them that link has expired or is invalid
      $this->setTemplate('passwordResetFailed');
      
      return;      
    }

    $this->form = new sfEasyAuthPasswordResetSetPasswordForm();
    
    // the form will be posted back to this action when the user resets their password
    if ($request->isMethod('post'))
    {
      $this->form->bind($request->getParameter($this->form->getName()));
      
      if ($this->form->isValid())
      {
        // set and save the new password
        $sfUser->updatePassword($this->form->getValue('password'));
                  
        // call an event before updating the user's password
        $this->getContext()->getEventDispatcher()->notify(new sfEvent(
          $this,
          'sf_easy_auth.post_password_reset',
          array(
            'sfUser' => $sfUser,
            'password' => $this->form->getValue('password'))
        ));
        
        // clear the password reset token so the link can't be used again
        $sfUser->invalidatePasswordResetToken();
        
        // send them a success message
        $this->setTemplate('passwordResetPasswordUpdated');
      }
    }
  }
  
  /**
   * Sets a flash for a user depending on whether we should i18n strings
   * 
   * @param string $message The message to set as a flash
   */
  protected function setFlash($message)
  {
    if (!$sfUser = $this->getUser())
    {
      return false;
    }
    
    if (!$sfUser->hasFlash('message'))
    {
      if (sfConfig::get('app_sf_easy_auth_use_i18n'))
      {
        return $sfUser->setFlash('message', $this->getContext()->getI18n()->__($message));
      }
      else
      {
        return $sfUser->setFlash('message', $message);
      }
    }
  }
  
  /**
   * Sends a password reset message
   * 
   * @param sfEasyAuthUser $eaUser The user to send a message to
   */
  protected function sendPasswordResetMessage(sfEasyAuthUser $eaUser)
  {
    $message = $this->getPartial('sfEasyAuth/passwordResetEmail', array('eaUser' => $eaUser));
    $htmlMessage = $this->getPartial('sfEasyAuth/passwordResetEmailHtml', array('eaUser' => $eaUser));
    return $eaUser->sendPasswordResetMessage($message, $htmlMessage);
  }
}
