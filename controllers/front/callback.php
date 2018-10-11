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

class SatispayCallbackModuleFrontController extends ModuleFrontController
{
    public function postProcess()
    {
        $charge = \SatispayOnline\Charge::get(Tools::getValue('charge_id'));
        if ($charge->status === 'SUCCESS') {
            $cart = new Cart($charge->metadata->cart_id);
            $customer = new Customer($cart->id_customer);
            $this->context->controller->module->validateOrder(
                $cart->id,
                2,
                $charge->amount / 100,
                $this->context->controller->module->displayName,
                null,
                array(
                    'transaction_id' => $charge->id
                ),
                null,
                null,
                $customer->secure_key
            );
        }
        exit;
    }
}
