<?php
require_once __DIR__ . '/../../bootstrap.php';
require_role('admin', 'staff');
$type = 'IN';
require BASE_PATH . '/views/stock_transaction.php';
