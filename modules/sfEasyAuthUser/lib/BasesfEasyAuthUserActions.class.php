<?php

require_once dirname(__FILE__).'/sfEasyAuthUserGeneratorConfiguration.class.php';
require_once dirname(__FILE__).'/sfEasyAuthUserGeneratorHelper.class.php';

/**
 * sfEasyAuthUser actions.
 *
 * @package    sfEasyAuthPlugin
 * @subpackage sfEasyAuthUser
 * @author     Fabien Potencier
 * @version    SVN: $Id: BasesfEasyAuthUserActions.class.php 12965 2008-11-13 06:02:38Z fabien $
 */
class BasesfEasyAuthUserActions extends autoSfEasyAuthUserActions
{
  protected function processForm(sfWebRequest $request, sfForm $form)
  {
    $form->bind($request->getParameter($form->getName()), $request->getFiles($form->getName()));
    if ($form->isValid())
    {
      $notice = $form->getObject()->isNew() ? 'The item was created successfully.' : 'The item was updated successfully.';

      $sf_easy_auth_user_base = $form->save();

      $this->dispatcher->notify(new sfEvent($this, 'admin.save_object', array('object' => $sf_easy_auth_user_base)));

      if ($request->hasParameter('_save_and_add'))
      {
        $this->getUser()->setFlash('notice', $notice.' You can add another one below.');

        $this->redirect('@sf_easy_auth_user_new');
      }
      else
      {
        $this->getUser()->setFlash('notice', $notice);

        $this->redirect(array('sf_route' => 'sf_easy_auth_user'));
      }
    }
    else
    {
      $this->getUser()->setFlash('error', 'The item has not been saved due to some errors.', false);
    }
  }
}
