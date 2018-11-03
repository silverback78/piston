<?php

class Response {
    public $statusCode = 200;
    public $referenceCode;
    public $message;

    public function ResponseError($statusCode, $referenceCode, $message) {
        $this->statusCode = $statusCode;
        $this->referenceCode = $referenceCode;
        $this->message = $message;

        return false;
    }
}

?>