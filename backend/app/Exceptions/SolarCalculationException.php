<?php

namespace App\Exceptions;

use Exception;

class SolarCalculationException extends Exception
{
    protected $details;

    public function __construct($message = "Erro no cálculo solar", $details = [], $code = 0, Exception $previous = null)
    {
        parent::__construct($message, $code, $previous);
        $this->details = $details;
    }

    public function getDetails()
    {
        return $this->details;
    }

    public function render($request)
    {
        return response()->json([
            'success' => false,
            'message' => $this->getMessage(),
            'details' => $this->details,
            'type' => 'calculation_error'
        ], 400);
    }
}
