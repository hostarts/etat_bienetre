<?php
/**
 * PHPUnit Bootstrap File
 */

// Set error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Define paths
define('ROOT_PATH', dirname(__DIR__));
define('APP_PATH', ROOT_PATH . '/app');

// Load environment for testing
$_ENV['APP_ENV'] = 'testing';
$_ENV['DB_NAME'] = 'bienetre_pharma_test';

// Simple autoloader for tests
spl_autoload_register(function ($class) {
    $file = APP_PATH . '/' . str_replace('\\', '/', $class) . '.php';
    if (file_exists($file)) {
        require_once $file;
    }
});

echo "Test environment initialized\n";
