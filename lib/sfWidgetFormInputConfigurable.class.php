<?php
/**
 * sfWidgetFormInput should really have a 'value' option. This class adds one.
 * 
 * @author al
 *
 */
class sfWidgetFormInputConfigurable extends sfWidgetFormInput
{
  /**
   * Constructor.
   *
   * @param array $options     An array of options
   * @param array $attributes  An array of default  HTML attributes
   *
   * @see sfWidgetForm
   */
  protected function configure($options = array(), $attributes = array()) 
  { 
    $this->addOption('value');
    
    parent::configure($options, $attributes);
  }

  /**
   * @param  string $name        The element name
   * @param  string $value       The value displayed  in this widget
   * @param  array  $attributes  An array of HTML  attributes to be merged with the default HTML attributes
   * @param  array  $errors      An array of errors  for the field
   *
   * @return string A string (value of the widget)
   *
   * @see sfWidgetForm
   */
  public function render($name, $value = null, $attributes = array(), $errors = array())
  {
    // just return the value, here you could wrap it into  a call to content_tag('span') and add your options,
    // just have a look on how to do that in sfWidgetFormInput.class.php  in the symfony library directory
    $value = ($this->getOption('value') !== false || !$value || is_array($value)) ? $this->getOption('value') : $value;
    
    return $this->renderTag('input', array_merge(array('type' => $this->getOption('type'), 'name' => $name, 'value' => $value), $attributes));
  }
}