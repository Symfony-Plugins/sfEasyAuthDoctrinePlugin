<?php

/**
 * PluginsfEasyAuthUser form.
 *
 * @package    ##PROJECT_NAME##
 * @subpackage form
 * @author     ##AUTHOR_NAME##
 * @version    SVN: $Id: sfDoctrineFormPluginTemplate.php 23810 2009-11-12 11:07:44Z Kris.Wallsmith $
 */
abstract class PluginsfEasyAuthUserForm extends BasesfEasyAuthUserForm
{
  /**
   * @var sfEasyAuthUser $eaUser The easy auth user this form represents
   */
  protected $eaUser;
  
  public function configure()
  {
    unset($this['salt'],
          $this['created_at'],
          $this['updated_at'],
          $this['auto_login_hash'],
          $this['has_extra_credentials'],
          $this['password_reset_token'],
          $this['password_reset_token_created_at'],
          $this['profile_id']);
          
    if (!$eaUser = Doctrine::getTable('sfEasyAuthUser')->find($this->getObject()->getId()))
    {
      throw new RuntimeException("No user exists with ID " . $this->getObject()->getId());
    }
    
    $this->widgetSchema->setHelps(
      array(
        'password' => 'Use this box to set a new password for the user.'
      )
    );
          
    $this->widgetSchema['password'] = new sfWidgetFormInputConfigurable(
      array(
        'value' => ($this->isNew()) ? '' : sfEasyAuthUser::PASSWORD_MASK
      )
    );

    $this->widgetSchema['type'] = new sfWidgetFormChoice(
      array(
        'choices' => sfEasyAuthUserPeer::getTypes(),
        'expanded' => true
      )
    );
    
    $this->widgetSchema['extra_credentials'] = new sfWidgetFormChoice(
      array(
        'choices' => $eaUser->getPossibleExtraCredentials(),
        'expanded' => true,
        'multiple' => true
      )
    );

    // select the options that should be selected
    $this->widgetSchema['extra_credentials']->setDefault($eaUser->getCredentials());
    
    $this->getWidgetSchema()->setHelps(
      array('type' => 'Do <u>not</u> edit this')
    );
    
    // set up the validator
    $this->setValidator('extra_credentials', 
      new sfValidatorChoice(
        array(
          'choices' => $eaUser->getPossibleExtraCredentials(),
          'multiple' => true,
          'required' => false
        )
      )
    );
    
    // don't allow admins to edit the user's type, or it could break foreign key relationships
    // with profiles
    $this->setValidator('type',
      new sfValidatorRegex(
        array(
          'pattern' => '/' . $eaUser->getType() . '/' 
        ),
        array(
          'invalid' => 'You cannot change the user type. Please set it back to ' . $eaUser->getType() . '.'
        )
      )
    );
    
    $this->eaUser = $eaUser;
  }
  
  /**
   * Overrides the save method to correctly handle extra credentials
   * 
   * @param $con Database connection
   */
  public function save($con=null)
  {
    if ($return = parent::save($con))
    {
      // save extra credentials
      $extraCredentials = (is_array($this->values['extra_credentials'])) ? 
        $this->values['extra_credentials'] : array();
      $this->eaUser->saveExtraCredentials($extraCredentials);
    }
  }
}
