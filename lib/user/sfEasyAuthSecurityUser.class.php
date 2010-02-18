<?php
/**
 * An easy authenication class
 * 
 * @author al
 *
 */
class sfEasyAuthSecurityUser extends sfBasicSecurityUser
{
  /**
   * @var The sfEasyAuthUser
   */
  protected $eaUser;
  
  /**
   * Returns the sfEasyAuthUser currently set in the class
   * 
   * @return sfEasyAuthUser
   */
  public function getAuthUser()
  {
    if (!$this->eaUser && $id = $this->getAttribute('security_user_id'))
    {
      $this->eaUser = Doctrine::getTable('sfEasyAuthUser')->find($id);

      if (!$this->eaUser)
      {
        // the user does not exist anymore in the database
        $this->logOut();

        throw new sfException('The user does not exist in the database.');
      }  
    }
    
    return $this->eaUser;
  }
  
  /**
   * Returns the username of the current sfEasyAuthUser
   * 
   * @return mixed A string on success, null on failure
   */
  public function getUsername()
  {
    return (is_object($this->eaUser)) ? $this->eaUser->getUsername() : null;
  }
  
  /**
   * Logs in a user whose ID and hash match a user in the database
   * 
   * @param int $id
   * @param string $hash The user's auto login hash
   * @return boolean
   */
  public function authenticateAutoLogin($id, $hash)
  {
    if ($eaUser = sfEasyAuthUserTable::findOneByIdAndAutoLoginHash($id, $hash))
    {
      // make sure their account is enabled. This allows them to log in via an
      // auto log-in link even if their account has been suspended due to too many
      // incorrect log-in attempts
      if (!$eaUser->accountLockedByAdmins())
      {
        $this->eaUser = $eaUser;
        
        if (!$this->eaUser->getEmailConfirmed())
        {
          // confirm the user's email address
          $this->eaUser->setEmailConfirmed(true);
          $this->eaUser->save();
          
          // call an event indicating that a user has confirmed their email address
          sfContext::getInstance()->getEventDispatcher()->notify(new sfEvent(
            $this,
            'sf_easy_auth.email_confirmed',
            array(
              'eaUser' => $this->eaUser
            )
          ));
        }
        
        return $this->logIn();
      }
    }
    
    return false;
  }
  
  /**
   * Sets a flash called 'message' for a user depending on whether we should i18n strings
   * 
   * @param string $message The message to set as a flash
   */
  protected function setMessage($message)
  {
    if (!$this->hasFlash('message'))
    {
      if (sfConfig::get('app_sf_easy_auth_use_i18n'))
      {
        return $this->setFlash('message', sfContext::getInstance()->getI18n()->__($message));
      }
      else
      {
        return $this->setFlash('message', $message);
      }
    }
  }
  
  /**
   * Attempt to authenticate a user with the supplied credentials
   * 
   * @param string $username
   * @param string $password
   * @param boolean $remember
   * @return boolean
   */
  public function authenticate($username, $password, $remember=0)
  {
    sfEasyAuthUtils::logDebug("Authenticating... Username: $username, password: $password");

    $eaUser = Doctrine::getTable('sfEasyAuthUser')->findOneByUsername($username);
    
    if (!$eaUser && sfConfig::get('app_sf_easy_auth_allow_emails_as_usernames_for_login'))
    {
      $eaUser = Doctrine::getTable('sfEasyAuthUser')->findOneByEmail($username);
    }
    
    if (is_object($eaUser))
    {
      sfEasyAuthUtils::logDebug('User retrieved. Checking password...');

      if ($eaUser->checkPassword($password))
      {
        sfEasyAuthUtils::logDebug('Password valid.');
        
        // check whether admins have locked the account
        if ($eaUser->accountLockedByAdmins() == 1)
        {
          sfEasyAuthUtils::logDebug("The account for user $username has been locked by admins.");
          
          $this->setMessage(sfConfig::get('app_sf_easy_auth_account_locked_by_admins'));
          return false;
        }

        // if email accounts need confirming, before users can log in, make sure the
        // user has confirmed their email address
        if (sfConfig::get('app_sf_easy_auth_require_email_confirmation'))
        {
          if (!$eaUser->getEmailConfirmed())
          {
            sfEasyAuthUtils::logDebug('User must confirm their email before being allowed to log in.');
            
            $this->setMessage(sfConfig::get('app_sf_easy_auth_must_confirm_email'));
            return false;
          }
        }

        // make sure the threshold for login attempts hasn't been exceeded
        if ($eaUser->accountTemporarilyLocked())
        {
          sfEasyAuthUtils::logDebug('User\'s account is temporarily disabled.');
          
          $this->setMessage(sfConfig::get('app_sf_easy_auth_account_temporarily_locked'));
          return false;
        }

        sfEasyAuthUtils::logDebug('User successfully authenticated.');
        
        $this->eaUser = $eaUser;
        return true;
      }
      else
      {
        sfEasyAuthUtils::logDebug('Invalid password supplied.');
        
        // user name matched, but password failed. Record the attempt to prevent 
        // brute forcing

        // if the users last log in attempt is outside the lockout duration, reset their
        // failed login counter
        if (($eaUser->getLastLoginAttempt('U') +
        sfConfig::get('app_sf_easy_auth_lockout_duration')) < time())
        {
          $eaUser->setFailedLogins(0);
        }

        $currentlyTemporarilyLocked = $eaUser->accountTemporarilyLocked();
        
        $eaUser->setFailedLogins($eaUser->getFailedLogins()+1);
        $eaUser->setLastLoginAttempt(strftime('%F %T'));
        $eaUser->save();

        if ($eaUser->accountTemporarilyLocked())
        {
          // if the user's account has just been locked, notify the system
          if (!$currentlyTemporarilyLocked)
          {
            // call an event to notify that a user's account will be temporarily locked
            sfContext::getInstance()->getEventDispatcher()->notify(new sfEvent(
              $this,
              'sf_easy_auth.account_temporarily_locked',
              array(
                'eaUser' => $eaUser
              )
            ));
          }
          
          sfEasyAuthUtils::logDebug('User\'s account has been temporarily locked.');
          
          $this->setMessage(sfConfig::get('app_sf_easy_auth_account_temporarily_locked'));
        }
        return false;
      }
    }
    else
    {
      sfEasyAuthUtils::logDebug("Unable to locate user with username $username");
      
      return false;
    }
    
    // we should never reach here
    return false;
  }

  /**
   * Sets a cookie that can be used to automatically log a user in to the site
   * 
   * The cookie has 3 parts, a user id, time stamp and checksum, all separated
   * by hyphens. There is no need to save anything to the database.
   */
  public function setRememberCookie()
  {
    if (!is_object($this->eaUser))
    {
      throw new RuntimeException("Can't set a remember cookie for a non-existent user");
    }

    $userId = $this->eaUser->getId();
    $now = time();

    $checksum = $this->createRememberMeChecksum($userId, $now, sfConfig::get('app_sf_easy_auth_remember_me_salt'));
    $cookieValue = $userId . '-' .  $now . '-' . $checksum;
    $expiry = $now + sfConfig::get('app_sf_easy_auth_remember_me_duration', 30 * 24 * 60 * 60);
    
    sfContext::getInstance()->getResponse()->setCookie(sfConfig::get('app_sf_easy_auth_remember_cookie_name'), $cookieValue, $expiry);
  }
  
  /**
   * Creates a checksum for the remember me cookie
   * 
   * @param int $userId The user's id
   * @param int $time A unix timestamp
   * @param string $salt A salt
   * @return string An md5 string combining all the above parameters
   */
  protected function createRememberMeChecksum($userId, $time, $salt)
  {
    return md5($userId  . $time . $salt);
  }
  
  /**
   * Actually logs the user in, giving them their credentials
   * 
   * @param sfEasyAuthUser $eaUser The user to log in. If not set, 
   * the value of $this->eaUser will be used if it is an object
   * @return boolean
   */
  public function logIn(sfEasyAuthUser $eaUser=null)
  {
    $eaUser = (is_object($eaUser)) ? $eaUser : $this->eaUser;

    if (!$eaUser instanceof sfEasyAuthUser)
    {
      throw new RuntimeException("Error, user is not an instanceof sfEasyAuthUser");
    }

    sfEasyAuthUtils::logDebug('Logging user in and adding credentials.');
    
    $eaUser->unblockAccount();
    $eaUser->setLastLogin(strftime('%F %T'));
    $eaUser->save();
    $this->eaUser = $eaUser;
    
    $this->setAttribute('security_user_id', $eaUser->getId());
    $this->setAuthenticated(true);
    $this->clearCredentials();
    
    foreach ($eaUser->getCredentials() as $credential)
    {
      $this->addCredential($credential);
    }
    
    return true;
  }
  
  /**
   * Returns a boolean to indicate whether a 'remember me' cookie is valid or not
   * 
   * @param string $rememberCookie A remember me cookie
   * @return boolean
   */
  public function validateRememberMe($rememberCookie)
  {
    sfEasyAuthUtils::logDebug('Validating remember me checksum...');
    
    list($userId, $creationTime, $md5Sum) = explode('-', $rememberCookie);
    
    // check the checksum is valid
    if ($md5Sum == $this->createRememberMeChecksum($userId, $creationTime, sfConfig::get('app_sf_easy_auth_remember_me_salt')))
    {
      sfEasyAuthUtils::logDebug('Remember me cookie successfully validated. Checking expiry...');
      
      // make sure that the cookie shouldn't have expired
      if ($creationTime + sfConfig::get('app_sf_easy_auth_remember_me_duration', 30 * 24 * 60 * 60) > time())
      {
        sfEasyAuthUtils::logDebug("Remember me cookie shouldn't have expired, so that's ok. Checking user account.");
        
        // retrieve the user
        if (!$eaUser = Doctrine::getTable('sfEasyAuthUser')->find($userId))
        {
          sfEasyAuthUtils::logDebug('Unable to retrieve user with id ' . $userId);
          return false;
        }
        
        // finally, make sure that the user's account hasn't been temporarily locked
        if (!$eaUser->accountTemporarilyLocked())
        {
          sfEasyAuthUtils::logDebug('Remember me cookie successfully validated.');

          $this->eaUser = $eaUser;
          return true;
        }
        else
        {
          sfEasyAuthUtils::logDebug("User's account has been temporarily locked. Not logging in.");
        }
      }
      else
      {
        sfEasyAuthUtils::logDebug("Cookie should have expired. Not logging in.");
      }
    }
    else
    {
      sfEasyAuthUtils::logDebug("Cookie checksum failed.");
    }
    
    return false;
  }
  
  /**
   * Validates that the given password reset token is set for the current user
   * 
   * @param string $token The password reset token
   * @return boolean
   */
  public function validatePasswordResetToken($token)
  {
    // make sure the token set for the user is still valid
    if ($this->getAuthUser()->getPasswordResetTokenCreatedAt('U') + sfConfig::get('app_sf_easy_auth_reset_token_lifetime') > time())
    {
      return (strcmp($this->getAuthUser()->getPasswordResetToken(), $token) === 0);
    }
    
    return false;
  }
  
  /**
   * Logs a user out of the site
   */
  public function logOut()
  {
    sfEasyAuthUtils::logDebug('Logging user out...');
    
    sfContext::getInstance()->getResponse()->setCookie(sfConfig::get('app_sf_easy_auth_remember_cookie_name'), '', -1);
    
    $this->getAttributeHolder()->remove('security_user_id');
    $this->getAttributeHolder()->remove('sf_easy_auth.restricted_url');
    $this->setAuthenticated(false);
    $this->clearCredentials();
    $this->eaUser = null;
  }
  
  /**
   * Returns the profile for the current user if it exists
   * 
   * @return mixed
   */
  public function getProfile()
  {
    return $this->getAuthUser()->getProfile();
  }
  
  /**
   * Returns whether the user has a profile
   * 
   * @return boolean
   */
  public function hasProfile()
  {
    return $this->getAuthUser()->hasProfile();
  }
  
  /**
   * Sets and saves a user's password
   * 
   * @param string $password
   */
  public function updatePassword($password)
  {
    $this->getAuthUser()->setPassword($password);
    
    return $this->getAuthUser()->save();
  }
  
  /**
   * Invalidates the password reset token to prevent replay attacks
   */
  public function invalidatePasswordResetToken()
  {
    $this->getAuthUser()->setPasswordResetToken('');
    
    return $this->getAuthUser()->save();
  }
}
