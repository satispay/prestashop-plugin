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

class SatispayPaymentModuleFrontController extends ModuleFrontController
{
    public function postProcess()
    {
        $cart = $this->context->cart;
        $currency = $this->context->currency;
        $amountUnit = round($cart->getOrderTotal(true, Cart::BOTH) * 100);

        //create order in Prestashop
        $customer = new Customer($cart->id_customer);
        $currency = new Currency($cart->id_currency);

        //set custom order state for Satispay orders in "pending"
        $this->module->validateOrder($cart->id, (int)(Configuration::get('SATISPAY_PENDING_STATE')), $amountUnit / 100, $this->module->displayName, null, array(
            ), $currency->id, false, $customer->secure_key);

        $orderId = Order::getOrderByCartId((int)($cart->id));
        $order = new Order($orderId);

        $redirectUrl = urldecode($this->context->link->getModuleLink(
            $this->module->name,
            'redirect',
            array(),
            true
        ));

        $callbackUrl = urldecode($this->context->link->getModuleLink(
            $this->module->name,
            'callback',
            array(
                'payment_id' => '{uuid}',
            ),
            true
        ));

        $payment = \SatispayGBusiness\Payment::create(array(
            'flow' => 'MATCH_CODE',
            'amount_unit' => $amountUnit,
            'currency' => $currency->iso_code,
            'callback_url' => $callbackUrl,
            'external_code' => $order->reference,
            'redirect_url' => $redirectUrl,
            'metadata' => array(
                'cart_id' => $cart->id,
            )
        ));

        if (!empty($order->id)) {
            $orderPaymentCollection = $order->getOrderPaymentCollection();
            $orderPayment = $orderPaymentCollection[0];
            $orderPayment->transaction_id = $payment->id;
            //check if the payment amount contains over 9 decimals
            if(strpos($orderPayment->amount, '.') !== false){
                $number = explode(".", $orderPayment->amount);
                $nDecimals = strlen($number[1]);
                if ($nDecimals > 9) {
                    $orderPayment->amount = $number[0] . '.' . substr($number[1], 0, 9);
                }
            }
            $orderPayment->update();
        }

        Tools::redirect($payment->redirect_url);
    }
}
