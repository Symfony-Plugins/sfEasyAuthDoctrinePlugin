<html>
<body>
<p>Hi,</p>

<p>Please activate your account on <?php echo sfConfig::get('app_site_name') ?> by clicking
on the link below:</p>

<p><a href="<?php 
    // need to make a helper for creating auto-login links
    echo sfConfig::get('app_sf_easy_auth_base_url') . url_for('@sf_easy_auth_email_confirmation_confirm') . '?' . 
      http_build_query(
        array(
          'uid' => $eaUser->getId(), 
          'alh' => $eaUser->getAutoLoginHash()
        )
      ); ?>">Activate your account</a></p>

<p>Thanks,</p>
<p><?php echo sfConfig::get('app_sf_easy_auth_email_confirmation_from_name')?></p>
</body>
</html>