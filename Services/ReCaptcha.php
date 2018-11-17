<?php
require_once('Config.php');

class ReCaptcha {

    public static function Validate($recaptchaResponse) {
      if (!Config::$recaptchaEnabled) return;

      $url = Config::$recaptchaPostUrl;
      $data = array(
        'secret' => Config::$recaptchaSecret,
        'response' => $recaptchaResponse,
        'remoteip' => $_SERVER['REMOTE_ADDR']
      );
      $options = array(
        'http' => array (
                'header' => "Content-Type: application/x-www-form-urlencoded\r\n",
          'method' => 'POST',
          'content' => http_build_query($data)
        )
      );
      $context  = stream_context_create($options);
      $verify = file_get_contents($url, false, $context);
      $result=json_decode($verify);

      return $result;
    }
}

?>