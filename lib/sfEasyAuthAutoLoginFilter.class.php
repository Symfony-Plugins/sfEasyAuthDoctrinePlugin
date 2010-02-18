<?php
/**
 * Auto-login filter for sfEasyAuth.
 * 
 * This filter examines all requested urls for GET parameters that indicate that we
 * should auto-log the user in. If these are present, it will attempt to 
 * authenticate a user and log them in without displaying a log in form. If successful,
 * users will be redirected to the page they were attempting to access.
 * 
 * @author al
 * @see sfEasyAuthAutoLoginFilter::execute
 *
 */
class sfEasyAuthAutoLoginFilter extends sfFilter
{
  /**
   * Automatically logs users in who have correctly set 'uid' and 'alh' parameters
   * in the url
   * 
   * @param $filterChain
   */
  public function execute($filterChain)
  {
    if ($this->isFirstCall())
    {
      // check whether the GET parameters 'uid' and 'alh' are present. 
      if (in_array('uid', array_keys($_GET)) && in_array('alh', array_keys($_GET)))
      {
        if ($sfUser = $this->getContext()->getUser())
        {
          // if the user is already logged in, just send them down the chain
          if ($sfUser->isAuthenticated())
          {
            return $filterChain->execute();
          }

          $request = $this->getContext()->getRequest();

          // if we're here, we've got an auto-login link, so try to log the user in.
          if ($sfUser->authenticateAutoLogin($request->getGetParameter('uid'), $request->getGetParameter('alh')))
          {
            // if it worked, redirect the user to the current page so they are seemlessly 
            // logged in
            
            // strip out the id and hash, and redirect them to the same url - we need
            // to do this otherwise users won't end up where they wanted to go, and
            // browsers will be confused if a resource redirects to itself, GET params
            // and all
            
            // the following was commented by al at 11:40, 2009-11-04 because there is
            // no need to redirect the users and it may be causing empty sessions to be 
            // created/no sessions to be created at all.
            /*
            $url = sfEasyAuthUtils::removeGetParametersFromUrl(
              $request->getUri(), 
              array('uid', 'alh')
            ); 
            
            $this->getContext()->getController()->redirect($url);
            */
          }
        }
      }
    }
    
    $filterChain->execute();
  }
}