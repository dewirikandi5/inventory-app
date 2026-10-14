<?php
/**
 * bootstrap.php
 * Dimuat di baris pertama setiap halaman di folder public/.
 */
define('BASE_PATH', __DIR__);

require_once BASE_PATH . '/config/app.php';
require_once BASE_PATH . '/config/database.php';
require_once BASE_PATH . '/helpers/escape.php';
require_once BASE_PATH . '/helpers/flash.php';
require_once BASE_PATH . '/helpers/validation.php';
require_once BASE_PATH . '/helpers/pagination.php';
require_once BASE_PATH . '/helpers/auth.php';
require_once BASE_PATH . '/helpers/product.php';
require_once BASE_PATH . '/helpers/stock.php';

header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: SAMEORIGIN');

start_secure_session();
