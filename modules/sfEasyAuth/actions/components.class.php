<?php
/**
 * Component to easily allow embedding the forms in web site
 * 
 * @author al
 *
 */
class sfEasyAuthComponents extends sfComponents
{
  /**
   * Displays the log in form if a user isn't logged in
   * 
   * @param $request
   * @return unknown_type
   */
  public function executeLoginForm(sfWebRequest $request)
  {
    $sfUser = $this->getUser();
    
    $this->loginForm = new sfEasyAuthLoginForm();
    $this->resetForm = new sfEasyAuthPasswordResetForm();
  }
}