<?php

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

        foreach ($ordersToProcess as $order) {
            $orderPayments = OrderPayment::getByOrderId($order);
            $transactionId = $orderPayments[0]->transaction_id;
            if (empty($transactionId)) {
                continue;
            }
            $payment = \SatispayGBusiness\Payment::get($transactionId);
            $orderId = Order::getOrderByCartId($payment->metadata->cart_id);
            $order = new Order($orderId);

            if ($order->current_state == (int)(Configuration::get('SATISPAY_PENDING_STATE'))) {
                $history = new OrderHistory();
                $history->id_order = (int)$orderId;

                if ($payment->status === 'ACCEPTED') {
                    //using existing payment so it's not doubled
                    $history->changeIdOrderState((int)(Configuration::get('PS_OS_PREPARATION')), $orderId, true);
                    $order->setCurrentState((int)(Configuration::get('PS_OS_PREPARATION')));

                    $orderMessage = new Message();
                    $orderMessage->id_order = (int)$orderId;
                    $orderMessage->message = 'The following order has been finalized by cron.';
                    $orderMessage->private = true;
                    $orderMessage->save();

                    $order->save();
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
        if (!isset($scheduledTimeFrame)) {
            $scheduledTimeFrame = 2;
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
        // remove just 1 day so normal transactions can still be processed
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
