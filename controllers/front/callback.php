<?php
/**
* 2007-2024 PrestaShop
*
* NOTICE OF LICENSE
*
* This source file is subject to the Academic Free License (AFL 3.0)
* that is bundled with this package in the file LICENSE.txt.
* It is also available through the world-wide-web at this URL:
* http://opensource.org/licenses/afl-3.0.php
* If you did not receive a copy of the license and are unable to
* obtain it through the world-wide-web, please send an email
* to license@prestashop.com so we can send you a copy immediately.
*
* DISCLAIMER
*
* Do not edit or add to this file if you wish to upgrade PrestaShop to newer
* versions in the future. If you wish to customize PrestaShop for your
* needs please refer to http://www.prestashop.com for more information.
*
*  @author    PrestaShop SA <contact@prestashop.com>
*  @copyright 2007-2024 PrestaShop SA
*  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*  International Registered Trademark & Property of PrestaShop SA
*/
if (!defined('_PS_VERSION_')) {
    exit;
}

class SatispayCallbackModuleFrontController extends ModuleFrontController
{
    public function postProcess()
    {
        $paymentId = Tools::getValue('payment_id');
        $payment = \SatispayGBusiness\Payment::get($paymentId);
        $orderId = Order::getOrderByCartId($payment->metadata->cart_id);
        $order = new Order($orderId);

        if ($order->current_state == (int)(Configuration::get('SATISPAY_PENDING_STATE'))) {
            $history = new OrderHistory();
            $history->id_order = (int)$orderId;

            if ($payment->status === 'ACCEPTED') {
                //using existing payment so it's not doubled
                $history->changeIdOrderState((int)(Configuration::get('PS_OS_PAYMENT')), $orderId, true);
                $history->save();
            }

            if ($payment->status === 'CANCELED') {
                $order->setCurrentState((int)(Configuration::get('PS_OS_CANCELED')));
                $order->save();
            }
        }
        exit;
    }
}
