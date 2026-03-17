<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/vendor/autoload.php';

// WordPress constants required by production code.
if (!defined('ABSPATH')) {
    define('ABSPATH', '/tmp/wp/');
}

if (!defined('AUTH_KEY')) {
    define('AUTH_KEY', 'test-auth-key-for-phpunit-only');
}

if (!defined('SECURE_AUTH_KEY')) {
    define('SECURE_AUTH_KEY', 'test-secure-auth-key-for-phpunit-only');
}

if (!defined('WP_DEBUG')) {
    define('WP_DEBUG', false);
}

// Plugin constants.
if (!defined('APPFOUNDRY_VERSION')) {
    define('APPFOUNDRY_VERSION', '0.0.0-test');
}
if (!defined('APPFOUNDRY_PATH')) {
    define('APPFOUNDRY_PATH', dirname(__DIR__) . '/');
}
if (!defined('APPFOUNDRY_URL')) {
    define('APPFOUNDRY_URL', 'http://localhost/wp-content/plugins/rb-app-foundry/');
}

// Lightweight WordPress class stubs for PHPUnit (no WordPress install needed).
require_once __DIR__ . '/Stubs/WordPress.php';
