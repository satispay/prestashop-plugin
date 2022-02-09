<?php

require_once __DIR__ . '/../../config/config.inc.php';
require_once __DIR__ . '/ProcessPendingOrders.php';
require_once __DIR__ . '/satispay-sdk/lib/Payment.php';
require_once __DIR__ . '/satispay-sdk/lib/Request.php';
require_once __DIR__ . '/satispay-sdk/lib/Api.php';
require_once __DIR__ . '/../../init.php';

$module = new ProcessPendingOrders();
