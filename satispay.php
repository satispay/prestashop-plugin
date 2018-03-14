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

if (!defined('_PS_VERSION_')) {
    exit;
}

require_once(dirname(__FILE__).'/includes/online-api-php-sdk/init.php');

class Satispay extends PaymentModule
{
    protected $config_form = false;

    public function __construct()
    {
        $this->name = 'satispay';
        $this->tab = 'payments_gateways';
        $this->version = '1.4.3';
        $this->author = 'Satispay';
        $this->need_instance = 0;
        $this->bootstrap = true;
        $this->module_key = '812ed8ea2509dd2146ef979a6af24ee5';

        parent::__construct();

        $this->displayName = $this->l('Satispay');
        $this->description = $this
            ->l('Satispay is a new payment system that allows you to pay stores or friends from your smartphone');

        $this->limited_currencies = array('EUR');

        $this->ps_versions_compliancy = array('min' => '1.6', 'max' => _PS_VERSION_);

        \SatispayOnline\Api::setSecurityBearer(Configuration::get('SATISPAY_SECURITY_BEARER'));
        \SatispayOnline\Api::setStaging(Configuration::get('SATISPAY_STAGING'));

        \SatispayOnline\Api::setPluginName('PrestaShop');
        \SatispayOnline\Api::setPluginVersion($this->version);
        \SatispayOnline\Api::setPlatformVersion(_PS_VERSION_);
        \SatispayOnline\Api::setType('ECOMMERCE-PLUGIN');
    }

    public function install()
    {
        Configuration::updateValue('SATISPAY_STAGING', false);
        Configuration::updateValue('SATISPAY_SECURITY_BEARER', null);

        return parent::install() &&
            $this->registerHook('payment') &&
            $this->registerHook('paymentOptions');
    }

    public function uninstall()
    {
        Configuration::deleteByName('SATISPAY_STAGING');
        Configuration::deleteByName('SATISPAY_SECURITY_BEARER');
        return parent::uninstall();
    }

    public function getContent()
    {
        $postProcessResult = null;
        if (((bool)Tools::isSubmit('submitSatispayModule')) == true) {
            $postProcessResult = $this->postProcess();
        }

        $this->context->smarty->assign('module_dir', $this->_path);
        $output = $this->context->smarty->fetch($this->local_path.'views/templates/admin/configure.tpl');

        return $output.$this->renderForm($postProcessResult);
    }

    protected function renderForm($postProcessResult)
    {
        $helper = new HelperForm();

        $helper->show_toolbar = false;
        $helper->table = $this->table;
        $helper->module = $this;
        $helper->default_form_language = $this->context->language->id;
        $helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG', 0);

        $helper->fields_value = array(
            'SATISPAY_REFUND_ID' => '',
            'SATISPAY_REFUND_AMOUNT' => ''
        );

        $helper->identifier = $this->identifier;
        $helper->submit_action = 'submitSatispayModule';
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false)
            .'&configure='.$this->name.'&tab_module='.$this->tab.'&module_name='.$this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');

        $helper->tpl_vars = array(
            'fields_value' => $this->getConfigFormValues(),
            'languages' => $this->context->controller->getLanguages(),
            'id_language' => $this->context->language->id
        );

        $successMessage = null;
        $errorMessage = null;
        if (!empty($postProcessResult)) {
            if ($postProcessResult['success']) {
                $successMessage = $postProcessResult['message'];
            } else {
                $errorMessage = $postProcessResult['message'];
            }
        }

        return $helper->generateForm(array($this->getConfigForm($successMessage, $errorMessage)));
    }

    protected function getConfigForm($successMessage, $errorMessage)
    {
        return array(
            'form' => array(
                'tabs' => array(
                    'settings' => $this->l('Settings'),
                    'refund' => $this->l('Refund')
                ),
                'success' => $successMessage,
                'error' => $errorMessage,
                'input' => array(
                    array(
                        'tab' => 'settings',
                        'type' => 'switch',
                        'label' => $this->l('Sandbox'),
                        'name' => 'SATISPAY_STAGING',
                        'is_bool' => true,
                        'desc' => $this->l('Enable Sandbox for testing'),
                        'values' => array(
                            array(
                                'id' => 'active_on',
                                'value' => true,
                                'label' => $this->l('Yes')
                            ),
                            array(
                                'id' => 'active_off',
                                'value' => false,
                                'label' => $this->l('No')
                            )
                        )
                    ),
                    array(
                        'tab' => 'settings',
                        'type' => 'text',
                        'name' => 'SATISPAY_SECURITY_BEARER',
                        'label' => $this->l('Security Bearer'),
                        'desc' => $this->l('Get from business.satispay.com'),
                    ),
                    array(
                        'tab' => 'refund',
                        'type' => 'text',
                        'name' => 'SATISPAY_REFUND_ID',
                        'label' => $this->l('Order ID'),
                        'desc' => $this->l('Get from Order list or details')
                    ),
                    array(
                        'tab' => 'refund',
                        'type' => 'text',
                        'name' => 'SATISPAY_REFUND_AMOUNT',
                        'label' => $this->l('Amount'),
                        'desc' => $this->l('Leave empty to refund the total amount')
                    )
                ),
                'submit' => array(
                    'title' => $this->l('Save'),
                )
            )
        );
    }

    protected function getConfigFormValues()
    {
        return array(
            'SATISPAY_STAGING' => Configuration::get('SATISPAY_STAGING', false),
            'SATISPAY_SECURITY_BEARER' => Configuration::get('SATISPAY_SECURITY_BEARER', null),
            'SATISPAY_REFUND_ID' => null,
            'SATISPAY_REFUND_AMOUNT' => null
        );
    }

    protected function postProcess()
    {
        $refund_id = Tools::getValue('SATISPAY_REFUND_ID');
        if (!empty($refund_id)) {
            $order = new Order($refund_id);
            if (empty($order->id)) {
                return array(
                    'success' => false,
                    'message' => $this->l('Invalid Order ID')
                );
            }

            $payments = $order->getOrderPaymentCollection();
            $transaction_id = $payments[0]->transaction_id;

            $refund_amount = Tools::getValue('SATISPAY_REFUND_AMOUNT');
            if (empty($refund_amount)) {
                $refund_amount = $order->total_products_wt;
            }

            $currency = new Currency($order->id_currency);

            try {
                \SatispayOnline\Refund::create(array(
                    'charge_id' => $transaction_id,
                    'currency' => $currency->iso_code,
                    'amount' => round($refund_amount * 100),
                    'description' => '#'.$order->reference
                ));
            } catch (\Exception $ex) {
                return 'Satispay Error '.$ex->getCode().': '.$ex->getMessage();
            }
    
            if ($refund_amount === $order->total_products_wt) {
                $order->setCurrentState(7);
            }

            return array(
                'success' => true,
                'message' => $this->l('Successfully refunded')
            );
        } else {
            $form_values = $this->getConfigFormValues();
            foreach (array_keys($form_values) as $key) {
                Configuration::updateValue($key, Tools::getValue($key));
            }

            return array(
                'success' => true,
                'message' => $this->l('Successfully saved')
            );
        }
    }

    public function hookPayment($params)
    {
        $currency_id = $params['cart']->id_currency;
        $currency = new Currency((int)$currency_id);

        if (in_array($currency->iso_code, $this->limited_currencies) == false) {
            return false;
        }

        $this->smarty->assign('module_dir', $this->_path);
        return $this->display(__FILE__, 'views/templates/hook/payment.tpl');
    }

    public function hookPaymentOptions($params)
    {
        $currency_id = $params['cart']->id_currency;
        $currency = new Currency((int)$currency_id);

        if (in_array($currency->iso_code, $this->limited_currencies) == false) {
            return false;
        }

        $option = new PrestaShop\PrestaShop\Core\Payment\PaymentOption();
        $option->setCallToActionText($this->l('Satispay'))
            ->setAction($this->context->link->getModuleLink($this->name, 'payment', array(), true));
        return array($option);
    }
}
