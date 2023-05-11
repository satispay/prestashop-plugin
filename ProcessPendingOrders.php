<?php
/**
* 2007-2023 PrestaShop
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
*  @copyright 2007-2023 PrestaShop SA
*  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*  International Registered Trademark & Property of PrestaShop SA
*/

if (!defined('_PS_VERSION_')) {
    exit;
}

class ProcessPendingOrders
{
    public function __construct()
    {
        $this->loadConfigurationsForRequest();
        $this->execute();
    }

    public function execute()
    {
        $ordersToProcess = $this->getPendingOrders();

        foreach ($ordersToProcess as $orderId) {
            $order = new Order($orderId);
            if (_PS_VERSION_ >= '8') {
                $orderPayments = $order->getOrderPayments();
            } else {
                $orderPayments = OrderPayment::getByOrderId($orderId);
            }
            $transactionId = $orderPayments[0]->transaction_id;
            if (empty($transactionId)) {
                continue;
            }
            $payment = \SatispayGBusiness\Payment::get($transactionId);
            $orderId = Order::getOrderByCartId($payment->metadata->cart_id);

            if ($order->current_state == (int)(Configuration::get('SATISPAY_PENDING_STATE'))) {
                $history = new OrderHistory();
                $history->id_order = (int)$orderId;

                if ($payment->status === 'ACCEPTED') {
                    //using existing payment so it's not doubled
                    $history->changeIdOrderState((int)(Configuration::get('PS_OS_PAYMENT')), $orderId, true);

                    $orderMessage = new Message();
                    $orderMessage->id_order = (int)$orderId;
                    $orderMessage->message = 'The following order has been finalized by cron.';
                    $orderMessage->private = true;
                    $orderMessage->save();

                    $history->save();
                }

                if ($payment->status === 'CANCELED') {
                    $order->setCurrentState((int)(Configuration::get('PS_OS_CANCELED')));
                    $order->save();
                }
            }
        }

        exit;
    }

    public function loadConfigurationsForRequest()
    {
        $module = \Module::getInstanceByName('satispay');

        $currentSandbox = Configuration::get('SATISPAY_SANDBOX', false);
        $currentKeyId = Configuration::get('SATISPAY_KEY_ID', '');
        $currentPrivateKey = Configuration::get('SATISPAY_PRIVATE_KEY', '');
        $currentPublicKey = Configuration::get('SATISPAY_PUBLIC_KEY', '');

        \SatispayGBusiness\Api::setSandbox($currentSandbox);
        \SatispayGBusiness\Api::setKeyId($currentKeyId);
        \SatispayGBusiness\Api::setPrivateKey($currentPrivateKey);
        \SatispayGBusiness\Api::setPublicKey($currentPublicKey);

        \SatispayGBusiness\Api::setPluginNameHeader('PrestaShop');
        \SatispayGBusiness\Api::setPluginVersionHeader($module->version);
        \SatispayGBusiness\Api::setPlatformVersionHeader(_PS_VERSION_);
        \SatispayGBusiness\Api::setTypeHeader('ECOMMERCE-PLUGIN');
    }

    /**
     * Get the start criteria for the scheduled datetime
     */
    private function getStartDateScheduledTime()
    {
        $now = new \DateTime();
        $scheduledTimeFrame = (int)(Configuration::get('SATISPAY_UNPROCESSED_TIME'));
        if (!($scheduledTimeFrame)) {
            $scheduledTimeFrame = Satispay::SATISPAY_DEFAULT_UNPROCESSED_TIME;
        }
        $tosub = new \DateInterval('PT'. $scheduledTimeFrame . 'H');
        return $now->sub($tosub)->format('Y-m-d H:i:s');
    }

    /**
     * Get the end criteria for the scheduled datetime
     */
    private function getEndDateScheduledTime()
    {
        $now = new \DateTime();
        // remove just 1 hour so normal transactions can still be processed
        $tosub = new \DateInterval('PT'. 1 . 'H');
        return $now->sub($tosub)->format('Y-m-d H:i:s');
    }

    private function getPendingOrders()
    {
        $rangeStart = $this->getStartDateScheduledTime();
        $rangeEnd = $this->getEndDateScheduledTime();
        $sql = 'SELECT id_order
                FROM ' . _DB_PREFIX_ . 'orders
                WHERE current_state = ' . (int)(Configuration::get('SATISPAY_PENDING_STATE')) . '
                AND date_upd <= \'' . pSQL($rangeEnd) . '\' AND date_upd >= \'' . pSQL($rangeStart) . '\'
                ORDER BY invoice_date ASC';

        $result = Db::getInstance()->executeS($sql);
        $ordersToProcess = [];
        foreach ($result as $data) {
            $ordersToProcess[] = (int) $data['id_order'];
        }
        return $ordersToProcess;
    }
}
