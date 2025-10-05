<?php

namespace Mak8Tech\DpoPayments\Exceptions;

use Exception;

class DpoException extends Exception
{
    protected int $errorCode;

    public function __construct(string $message = "", int $code = 0, \Throwable $previous = null)
    {
        $this->errorCode = $code;
        parent::__construct($message, 0, $previous);
    }

    public function getErrorCode(): int
    {
        return $this->errorCode;
    }
}
