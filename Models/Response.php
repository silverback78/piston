<?php

abstract class Response {
    public $statusCode = 200;
    public $referenceCode;
    public $message;
    public $exception = false;

    public function ResponseError($statusCode, $referenceCode, $message) {
        $this->ResetValues();
        $this->statusCode = $statusCode;
        $this->referenceCode = $referenceCode;
        $this->message = $message;
        $this->exception = true;

        return $this;
    }

    abstract function ResetValues();
}

?>