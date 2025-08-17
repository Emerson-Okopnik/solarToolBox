<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Solar System Configuration
    |--------------------------------------------------------------------------
    |
    | Configurações específicas para cálculos do sistema solar
    |
    */

    'compatibility' => [
        'default_voltage_limit_percent' => env('SOLAR_DEFAULT_COMPATIBILITY_LIMIT', 5),
        'default_current_limit_percent' => env('SOLAR_DEFAULT_COMPATIBILITY_LIMIT', 5),
    ],

    'temperature' => [
        'min_celsius' => env('SOLAR_DEFAULT_TEMP_MIN', -5),
        'max_celsius' => env('SOLAR_DEFAULT_TEMP_MAX', 70),
        'noct_standard' => 45, // Nominal Operating Cell Temperature
        'stc_temperature' => 25, // Standard Test Conditions
    ],

    'calculations' => [
        'irradiance_stc' => 1000, // W/m² - Standard Test Conditions
        'irradiance_noct' => 800, // W/m² - NOCT conditions
        'ambient_temp_noct' => 20, // °C - Ambient temperature at NOCT
    ],

    'validation' => [
        'max_strings_per_mppt' => 20,
        'max_modules_per_string' => 30,
        'min_modules_per_string' => 1,
    ],
];
