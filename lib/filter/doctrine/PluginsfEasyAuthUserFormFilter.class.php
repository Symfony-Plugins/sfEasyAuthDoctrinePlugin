<?php

/**
 * PluginsfEasyAuthUser form.
 *
 * @package    ##PROJECT_NAME##
 * @subpackage filter
 * @author     ##AUTHOR_NAME##
 * @version    SVN: $Id: sfDoctrineFormFilterPluginTemplate.php 23810 2009-11-12 11:07:44Z Kris.Wallsmith $
 */
abstract class PluginsfEasyAuthUserFormFilter extends BasesfEasyAuthUserFormFilter
{
  public function configure()
  {
    $this->widgetSchema['type'] = new sfWidgetFormChoice(array(
      'choices' => array_merge(array('' => 'Any'), sfEasyAuthUserTable::getTypes()),
      'expanded' => false
    ));
  }
  
  /**
   * Trims whitespace from the email address
   */
  public function convertEmailValue($value)
  {
    foreach ($value as $k => $v)
    {
      $value[$k] = trim($v);
    }
    
    return $value;
  }
  
  /**
   * Trims whitespace from the user name
   */
  public function convertUsernameValue($value)
  {
    foreach ($value as $k => $v)
    {
      $value[$k] = trim($v);
    }
    
    return $value;
  }
}
