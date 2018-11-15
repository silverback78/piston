<?php
require_once('Config.php');

class ReCaptchaResponse {
    public $success;
}

class ReCaptcha {
    public static $success;
    public static function Validate($recaptchaResponse) {
      $response = new ReCaptchaResponse();
      $response->success = self::$success;
      return $response;
    }
}

?>