<?php
/**
* 2007-2017 PrestaShop
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
*  @copyright 2007-2017 PrestaShop SA
*  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*  International Registered Trademark & Property of PrestaShop SA
*/

require_once(dirname(__FILE__).'/../../includes/online-api-php-sdk/init.php');

class SatispayPaymentModuleFrontController extends ModuleFrontController
{
    public function postProcess()
    {
        $cart = $this->context->cart;
        $currency = new Currency($cart->id_currency);
        try {
            $checkout = \SatispayOnline\Checkout::create(array(
                'description' => '',
                'phone_number' => '',
                'redirect_url' => $this->context->link->getModuleLink(
                    'satispay',
                    'redirect',
                    array(),
                    true
                ),
                'callback_url' => urldecode($this->context->link->getModuleLink(
                    'satispay',
                    'callback',
                    array(
                        'charge_id' => '{uuid}'
                    ),
                    true
                )),
                'amount_unit' => round($cart->getOrderTotal(true, Cart::BOTH) * 100),
                'currency' => $currency->iso_code,
                'metadata' => array(
                    'cart_id' => $cart->id
                )
            ));
            Tools::redirect($checkout->checkout_url);
        } catch (\Exception $ex) {
            echo 'Satispay Error '.$ex->getCode().': '.$ex->getMessage();
            exit;
        }
    }
}
