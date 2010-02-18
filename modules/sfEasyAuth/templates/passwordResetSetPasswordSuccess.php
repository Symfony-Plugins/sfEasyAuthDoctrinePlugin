<?php use_stylesheet('/sfEasyAuthDoctrinePlugin/css/auth.css') ?>
<?php use_helper('I18N') ?>

<div id="authContainer">
  <p><?php echo __("Please set a new log in password") ?></p>
  
  <form action="<?php echo url_for('@sf_easy_auth_password_reset_set_password') ?>" method="post">
    <?php echo $form->renderHiddenFields(); ?>
    <?php echo $form ?>

    <input type="submit" value="Save" />
  </form>
</div>