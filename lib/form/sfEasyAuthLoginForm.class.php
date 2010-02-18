<?php

/**
 * sfEasyAuth log-in form.
 *
 * @package    .
 * @subpackage form
 * @author     Your name here
 * @version    SVN: $Id: sfPropelFormTemplate.php 10377 2008-07-21 07:10:32Z dwhittle $
 */
class sfEasyAuthLoginForm extends sfForm
{
  public function configure()
  {
    $this->setWidgets(array(
      'username'  => new sfWidgetFormInput(),
      'password' => new sfWidgetFormInputPassword(),
      'remember' => new sfWidgetFormInputCheckbox(),
    ));

    // use a better formatter than the default on provided by symfony
    $oDecorator = new sfEasyAuthWidgetFormSchemaFormatterDiv(
      $this->getWidgetSchema()
    );
    $this->getWidgetSchema()->addFormFormatter('div', $oDecorator);
    $this->getWidgetSchema()->setFormFormatterName('div');
    
    $this->widgetSchema->setLabels(array(
      'username' => sfConfig::get('app_sf_easy_auth_login_form_username_label'),
      'password' => 'Password',
      'remember' => 'Remember me'
    ));

    $this->widgetSchema->setNameFormat('login[%s]');

    $this->setValidators(array(
      'username' => new sfValidatorString(
        array('required' => true), 
        array('required' => sfConfig::get('app_sf_easy_auth_username_required_message'))),
      'password' => new sfValidatorString(
        array('required' => true),
        array('required' => sfConfig::get('app_sf_easy_auth_password_required_message'))),
      'remember' => new sfValidatorBoolean()
      ));

  }
}
