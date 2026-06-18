<?php

/**
 * Canonical URLs for EFSC-YA / SAP deployment.
 * Override per environment in .env (see api/.env.example).
 */
return [
    'production_api' => env('PRODUCTION_API_URL', 'https://sap-api.innovisiq.com'),
    'production_web' => env('PRODUCTION_WEB_URL', 'https://sap.innovisiq.com'),
];
