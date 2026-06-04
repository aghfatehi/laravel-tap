<?php

namespace Aghfatehi\Tap\Exceptions;

use Exception;

class TapException extends Exception
{
    protected array $errors;
    protected array $rawResponse;

    public function __construct(
        string $message = '',
        array $errors = [],
        array $rawResponse = [],
        int $code = 0,
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
        $this->errors = $errors;
        $this->rawResponse = $rawResponse;
    }

    public function getErrors(): array
    {
        return $this->errors;
    }

    public function getRawResponse(): array
    {
        return $this->rawResponse;
    }
}
