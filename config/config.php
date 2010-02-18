<?php
/*
 * Modified from sfGuardPlugin. Registers default routes
 */
if (sfConfig::get('app_sf_easy_auth_plugin_routes_register', true) && in_array('sfEasyAuth', sfConfig::get('sf_enabled_modules', array())))
{
  $this->dispatcher->connect('routing.load_configuration', array('sfEasyAuthRouting', 'listenToRoutingLoadConfigurationEvent'));
}

foreach (array('sfEasyAuthUser') as $module)
{
  if (in_array($module, sfConfig::get('sf_enabled_modules')))
  {
    $this->dispatcher->connect('routing.load_configuration', array('sfEasyAuthRouting', 'addRouteForAdmin'.str_replace('sfEasyAuth', '', $module)));
  }
}
