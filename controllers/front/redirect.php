<?php
/**
* 2007-2022 PrestaShop
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
*  @copyright 2007-2022 PrestaShop SA
*  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*  International Registered Trademark & Property of PrestaShop SA
*/

class SatispayRedirectModuleFrontController extends ModuleFrontController
{
    public function postProcess()
    {
        //retrieve transaction id of last order by customer context
        $currentCustomerId = $this->context->customer->id;
        $customerOrders = Order::getCustomerOrders($currentCustomerId);
        $paymentId = null;
        if ($customerOrders) {
            $lastOrder = new Order((int) $customerOrders[0]['id_order']);
            $orderPayments = OrderPayment::getByOrderId($lastOrder->id);
            $paymentId = $orderPayments[0]->transaction_id;
        }
        if (empty($paymentId)) {
            // can't collect order/transaction_id from customer context, payment is still valid and no need to restore cart
            $orderLink = $this->context->link->getPageLink('order', true, null);
            Tools::redirect($orderLink);
        }

        $payment = \SatispayGBusiness\Payment::get($paymentId);

        if ($payment->status === 'ACCEPTED') {
            for ($i = 0; $i < 6; $i++) {
                $orderId = Order::getOrderByCartId($payment->metadata->cart_id);
                $order = new Order($orderId);

                if (!empty($order->id)) {
                    $customer = new Customer($order->id_customer);

                    $confirmationLink = $this->context->link->getPageLink('order-confirmation', true, null, array(
                        'id_cart' => $payment->metadata->cart_id,
                        'id_order' => $order->id,
                        'id_module' => $this->module->id,
                        'key' => $customer->secure_key
                    ));

                    Tools::redirect($confirmationLink);
                } else {
                    sleep(2);
                }
            }

            // TODO: error page
            $historyLink = $this->context->link->getPageLink('history', true, null);
            Tools::redirect($historyLink);
        } else {
            \SatispayGBusiness\Payment::update($payment->id, array(
                'action' => 'CANCEL'
            ));

            $orderLink = $this->context->link->getPageLink('order', true, null);
            Tools::redirect($orderLink);
        }
    }
}
