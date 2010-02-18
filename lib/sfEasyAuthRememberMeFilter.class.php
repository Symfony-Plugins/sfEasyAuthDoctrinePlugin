<?php
/**
 * Remember me filter. Logs users into the site if they have valid 'remember me' cookies set.
 * 
 * @author al
 *
 */
class sfEasyAuthRememberMeFilter extends sfFilter
{
  /**
   * Automatically logs users in who have valid 'remember me' cookies set
   * 
   * @param $filterChain
   */
  public function execute($filterChain)
  {
    if ($this->isFirstCall())
    {
      $request = sfContext::getInstance()->getRequest();
      $sfUser = sfContext::getInstance()->getUser();
      
      // see if the user has a 'remember me' cookie set
      if (!$sfUser->isAuthenticated() && $request->getCookie(sfConfig::get('app_sf_easy_auth_remember_cookie_name')))
      {
        // try to retrieve the user
        if ($sfUser->validateRememberMe($request->getCookie(sfConfig::get('app_sf_easy_auth_remember_cookie_name'))))
        {
          $sfUser->logIn();
        }
      }
    }
    
    $filterChain->execute();
  }
}