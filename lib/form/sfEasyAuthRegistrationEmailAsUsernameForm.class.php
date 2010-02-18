<?php
/**
 * A simpler registration form. A user's username will be set to 
 * their email address.
 * 
 * @see sfEasyAuthRegistrationForm
 * @author al
 *
 */
class sfEasyAuthRegistrationEmailAsUsernameForm extends sfEasyAuthRegistrationForm
{
  public function configure()
  {
    parent::configure(); 
    
    $this->useFields(
      array(
        'email',
        'password',
        'confirm_password'
      )
    );
    
    $this->widgetSchema->setHelp('email', 'You\'ll need this to log in.');
  }  
  
  /**
   * Set the email address as the user name
   * 
   * (non-PHPdoc)
   * @see addon/sfFormObject#save()
   */
  public function save($con = null)
  {
    $this->getObject()->setUsername($this->getValue('email'));
    return parent::save($con);
  }
}
