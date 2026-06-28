<?php

namespace App\Exceptions;

use App\Builder\ReturnApi;

class ApiException extends \Exception
{
    public array $data;

    public function __construct(string $message = "Erro inesperado", int $code = 500, array $data = [])
    {
        parent::__construct($message, $code);
        $this->data = $data;
    }

    public function render(): \Illuminate\Http\JsonResponse
    {
        return ReturnApi::error($this->getMessage(), $this->data, $this->getCode());
    }
}
