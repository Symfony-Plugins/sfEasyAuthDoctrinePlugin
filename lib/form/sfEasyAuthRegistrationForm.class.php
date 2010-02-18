<?php
/**
 * Registration form for sfEasyAuth.
 * 
 * This form won't create or save a profile. To use profiles, create a form
 * that merges this form along with a form derived from your profile form.
 * 
 * Save the profile form first, then call setProfileId() on the user 
 * object, passing in the profile object's ID.
 * 
 * @author al
 *
 */
class sfEasyAuthRegistrationForm extends BasesfEasyAuthUserForm
{
  public function configure()
  {
    if (!$this->getOption('userType'))
    {
      throw new InvalidArgumentException("The 'userType' option is mandatory. New users
        will have the specified type. Set it to the name of an sfEasyAuthUser
        subclass, e.g. sfEasyAuthMember.");
    }
    
    $this->useFields(
      array(
        'username',
        'email',
        'password'
      )
    );
    
    $this->widgetSchema['password'] = new sfWidgetFormInputPassword();
    $this->widgetSchema['confirm_password'] = new sfWidgetFormInputPassword();
    
    $this->widgetSchema->setNameFormat('registration[%s]');

    $this->validatorSchema['email'] = new sfValidatorEmail(
      array(
        'max_length' => 255
      )
    );   
    $this->validatorSchema['password'] = new sfValidatorString(
      array(
        'max_length' => 255
      )
    );
    $this->validatorSchema['confirm_password'] = new sfValidatorString(
      array(
        'max_length' => 255
      )
    );
    
    $this->mergePostValidator(
      new sfValidatorSchemaCompare(
        'confirm_password',
        sfValidatorSchemaCompare::IDENTICAL,
        'password',
        array(),
        array('invalid' => 'Your passwords don\'t match. Please enter them again.')
      )
    );
    
    $this->mergePostValidator(
      new sfValidatorDoctrineUnique(
        array(
          'model' => 'sfEasyAuthUser',
          'column' => 'email'
        ),
        array(
          'invalid' => 'An account with that email address already exists. Please ' .
            'enter a different one.'
        )
      )
    );
    
    $this->mergePostValidator(
      new sfValidatorDoctrineUnique(
        array(
          'model' => 'sfEasyAuthUser',
          'column' => 'username'
        ),
        array(
          'invalid' => 'An account with that username already exists. Please ' .
            'enter a different one.'
        )
      )
    );
  }
  
  /**
   * Override the save method to set the user type
   * 
   * (non-PHPdoc)
   * @see addon/sfFormObject#save()
   */
  public function save($con = null)
  {
    $this->getObject()->setType($this->getOption('userType'));
    return parent::save($con);
  }
}
