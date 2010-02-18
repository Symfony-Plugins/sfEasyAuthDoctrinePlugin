<?php

/**
 * sfEasyAuth password reset form.
 *
 * @package    .
 * @subpackage form
 * @author     Your name here
 * @version    SVN: $Id: sfPropelFormTemplate.php 10377 2008-07-21 07:10:32Z dwhittle $
 */
class sfEasyAuthPasswordResetForm extends sfFormDoctrine
{
  public function configure()
  {
    $this->setWidgets(
      array(
        'email'  => new sfWidgetFormInput(),
      )
    );

    // use a better formatter than the default on provided by symfony
    $oDecorator = new sfEasyAuthWidgetFormSchemaFormatterDiv($this->getWidgetSchema());
    $this->getWidgetSchema()->addFormFormatter('div', $oDecorator);
    $this->getWidgetSchema()->setFormFormatterName('div');
    
    $this->widgetSchema->setLabels(
      array(
        'email' => 'Please enter your email address'
      )
    );

    $this->widgetSchema->setNameFormat('pw_reset[%s]');

    $this->setValidators(
      array(
        'email' => new sfValidatorAnd(
          array( 
            new sfValidatorEmail(
              array('required' => true), 
              array('required' => sfConfig::get('app_sf_easy_auth_username_required_message'))
            ),
            new sfValidatorDoctrineChoice(
              array(
                'model' => 'sfEasyAuthUser',
                'column' => 'email'
              )
            )
          )
        )
      )
    );
  }
  
  /**
   * Returns the name of the model associated with this form.
   */
  public function getModelName()
  {
    return 'sfEasyAuthUser';
  }
}
