<?php

if (!defined('_PS_VERSION_')) {
    exit;
}

/**
 * @param $module Satispay
 * @return bool
 */
function upgrade_module_2_4_4($module)
{
    // Register new Hook
    return $module->registerHook('actionEmailSendBefore');
}
