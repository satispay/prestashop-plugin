<?php
/**
* 2007-2019 PrestaShop
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
*  @copyright 2007-2019 PrestaShop SA
*  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*  International Registered Trademark & Property of PrestaShop SA
*/

class SatispayPaymentModuleFrontController extends ModuleFrontController
{
    public function postProcess()
    {
        $cart = $this->context->cart;
        $currency = $this->context->currency;
        $amountUnit = $cart->getOrderTotal(true, Cart::BOTH) * 100;

        $redirectUrl = urldecode($this->context->link->getModuleLink(
            $this->module->name,
            'redirect',
            array(
                'payment_id' => '{uuid}',
            ),
            true,
        ));

        $callbackUrl = urldecode($this->context->link->getModuleLink(
            $this->module->name,
            'callback',
            array(
                'payment_id' => '{uuid}',
            ),
            true,
        ));

        $payment = \SatispayGBusiness\Payment::create(array(
            'flow' => 'MATCH_CODE',
            'amount_unit' => $amountUnit,
            'currency' => $currency->iso_code,
            'callback_url' => $callbackUrl,
            'metadata' => array(
                'cart_id' => $cart->id,
                'redirect_url' => $redirectUrl,
            ),
        ));

        $satispayUrl = 'https://online.satispay.com';
        if (\SatispayGBusiness\Api::getSandbox()) {
            $satispayUrl = 'https://staging.online.satispay.com';
        }

        Tools::redirect(sprintf('%s/pay/%s', $satispayUrl, $payment->id));
    }
}
