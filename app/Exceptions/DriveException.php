<?php

namespace App\Exceptions;

use Exception;

class DriveException extends Exception
{
    public string $errorCode;

    public function __construct(string $message, string $errorCode = 'DRIVE_GENERAL', int $code = 0, ?Exception $previous = null)
    {
        parent::__construct($message, $code, $previous);
        $this->errorCode = $errorCode;
    }
}
