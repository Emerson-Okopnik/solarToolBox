<?php

namespace App\Exceptions;

use Exception;

class CompatibilityException extends Exception
{
    protected $incompatibleItems;
    protected $recommendations;

    public function __construct($message = "Incompatibilidade detectada", $incompatibleItems = [], $recommendations = [], $code = 0, Exception $previous = null)
    {
        parent::__construct($message, $code, $previous);
        $this->incompatibleItems = $incompatibleItems;
        $this->recommendations = $recommendations;
    }

    public function getIncompatibleItems()
    {
        return $this->incompatibleItems;
    }

    public function getRecommendations()
    {
        return $this->recommendations;
    }

    public function render($request)
    {
        return response()->json([
            'success' => false,
            'message' => $this->getMessage(),
            'incompatible_items' => $this->incompatibleItems,
            'recommendations' => $this->recommendations,
            'type' => 'compatibility_error'
        ], 400);
    }
}
