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

require_once(dirname(__FILE__).'/satispay-sdk/init.php');

class Satispay extends PaymentModule
{
    /**
     * Satispay Prestashop configuration
     * use Configuration::get(Satispay::CONST_NAME) to return a value
     */
    const SATISPAY_PENDING_STATE = 'SATISPAY_PENDING_STATE';
    const SATISPAY_DEFAULT_UNPROCESSED_TIME = 4;

    protected $config_form = false;

    public function __construct()
    {
        $this->name = 'satispay';
        $this->tab = 'payments_gateways';
        $this->version = '2.4.1';
        $this->author = 'Satispay';
        $this->need_instance = 1;
        $this->module_key = '812ed8ea2509dd2146ef979a6af24ee5';
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('Satispay');
        $this->description = $this->l('Save time and money by accepting payments from your customers with Satispay. Free, simple, secure! #doitsmart');
        $this->limited_currencies = array('EUR');
        $this->ps_versions_compliancy = array('min' => '1.6', 'max' => _PS_VERSION_);

        $this->loadConfiguration();
    }

    protected function loadConfiguration()
    {
        $currentSandbox = Configuration::get('SATISPAY_SANDBOX', false);
        $currentKeyId = Configuration::get('SATISPAY_KEY_ID', '');
        $currentPrivateKey = Configuration::get('SATISPAY_PRIVATE_KEY', '');
        $currentPublicKey = Configuration::get('SATISPAY_PUBLIC_KEY', '');

        \SatispayGBusiness\Api::setSandbox($currentSandbox);
        \SatispayGBusiness\Api::setKeyId($currentKeyId);
        \SatispayGBusiness\Api::setPrivateKey($currentPrivateKey);
        \SatispayGBusiness\Api::setPublicKey($currentPublicKey);

        \SatispayGBusiness\Api::setPluginNameHeader('PrestaShop');
        \SatispayGBusiness\Api::setPluginVersionHeader($this->version);
        \SatispayGBusiness\Api::setPlatformVersionHeader(_PS_VERSION_);
        \SatispayGBusiness\Api::setTypeHeader('ECOMMERCE-PLUGIN');
    }

    public function install()
    {
        if (!parent::install()) {
            return false;
        }

        if (!($this->registerHook('payment') &&
            $this->registerHook('paymentOptions'))) {
            return false;
        }

        if (!$this->installOrderState()) {
            return false;
        }

        return true;
    }

    public function uninstall()
    {
        Configuration::deleteByName('SATISPAY_SANDBOX');
        Configuration::deleteByName('SATISPAY_ACTIVATION_CODE');
        Configuration::deleteByName('SATISPAY_KEY_ID');
        Configuration::deleteByName('SATISPAY_PRIVATE_KEY');
        Configuration::deleteByName('SATISPAY_PUBLIC_KEY');
        Configuration::deleteByName('SATISPAY_UNPROCESSED_TIME');

        return parent::uninstall();
    }

    /**
     * Create order state
     * @return boolean
     */
    public function installOrderState()
    {
        /* Create Order State for Satispay */
        if (!Configuration::get(self::SATISPAY_PENDING_STATE)
            || !Validate::isLoadedObject(new OrderState(Configuration::get(self::SATISPAY_PENDING_STATE)))) {
            $order_state = new OrderState();
            $order_state->name = array();
            foreach (Language::getLanguages() as $language) {
                switch (Tools::strtolower($language['iso_code'])) {
                    case 'it':
                        $order_state->name[$language['id_lang']] = pSQL('In attesa di pagamento con Satispay');
                        break;

                    default:
                        $order_state->name[$language['id_lang']] = pSQL('Waiting for Satispay payment');
                        break;
                }
            }
            $order_state->invoice = false;
            $order_state->send_email = false;
            $order_state->logable = false;     
            $order_state->color = '#EF5350';
            $order_state->module_name = $this->name;
            $order_state->add();

            Configuration::updateValue(self::SATISPAY_PENDING_STATE, $order_state->id);
        }

        return true;
    }

    /**
     * Load the configuration form
     */
    public function getContent()
    {
        $postProcessConfigResult = null;
        if (((bool)Tools::isSubmit('submitSatispayModuleConfig')) == true) {
            $postProcessConfigResult = $this->postProcessConfig();
        }

        $postProcessRefundResult = null;
        if (((bool)Tools::isSubmit('submitSatispayModuleRefund')) == true) {
            $postProcessRefundResult = $this->postProcessRefund();
        }

        return $this->renderForm($postProcessConfigResult, $postProcessRefundResult);
    }

    protected function renderForm($postProcessConfigResult, $postProcessRefundResult)
    {
        $this->loadConfiguration();

        $configForm = new HelperForm();

        $configForm->show_toolbar = false;
        $configForm->table = $this->table;
        $configForm->module = $this;
        $configForm->default_form_language = $this->context->language->id;
        $configForm->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG', 0);

        $configForm->identifier = $this->identifier;
        $configForm->submit_action = 'submitSatispayModuleConfig';
        $configForm->currentIndex = $this->context->link->getAdminLink('AdminModules', false)
            .'&configure='.$this->name.'&tab_module='.$this->tab.'&module_name='.$this->name;
        $configForm->token = Tools::getAdminTokenLite('AdminModules');

        $configForm->tpl_vars = array(
            'fields_value' => $this->getConfigFormValues(),
            'languages' => $this->context->controller->getLanguages(),
            'id_language' => $this->context->language->id,
        );


        $refundForm = new HelperForm();

        $refundForm->show_toolbar = false;
        $refundForm->table = $this->table;
        $refundForm->module = $this;
        $refundForm->default_form_language = $this->context->language->id;
        $refundForm->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG', 0);

        $refundForm->identifier = $this->identifier;
        $refundForm->submit_action = 'submitSatispayModuleRefund';
        $refundForm->currentIndex = $this->context->link->getAdminLink('AdminModules', false)
            .'&configure='.$this->name.'&tab_module='.$this->tab.'&module_name='.$this->name;
        $refundForm->token = Tools::getAdminTokenLite('AdminModules');

        $refundForm->tpl_vars = array(
            'fields_value' => $this->getRefundFormValues(),
            'languages' => $this->context->controller->getLanguages(),
            'id_language' => $this->context->language->id,
        );


        $configFormSuccess = '';
        $configFormError = '';
        if (!empty($postProcessConfigResult)) {
            $error = $postProcessConfigResult['error'];
            $success = $postProcessConfigResult['success'];

            if (!empty($error)) {
                $configFormError .= $error.'<br />';
            }

            if (!empty($success)) {
                $configFormSuccess = $success;
            }
        }

        try {
            \SatispayGBusiness\Payment::all();
        } catch (\Exception $ex) {
            $configFormError .= sprintf($this->l('Satispay is not correctly configured, get an Activation Code from Online Shop section on %sSatispay Dashboard%s.'), '<a href="https://dashboard.satispay.com/signup" target="_blank">', '</a>').'<br />';
        }

        $refundFormSuccess = '';
        $refundFormError = '';
        if (!empty($postProcessRefundResult)) {
            $error = $postProcessRefundResult['error'];
            $success = $postProcessRefundResult['success'];

            if (!empty($error)) {
                $refundFormError = $error;
            }

            if (!empty($success)) {
                $refundFormSuccess = $success;
            }
        }

        return $configForm->generateForm(array($this->getConfigForm($configFormSuccess, $configFormError))).
            $refundForm->generateForm(array($this->getRefundForm($refundFormSuccess, $refundFormError)));
    }

    protected function getConfigForm($configFormSuccess, $configFormError)
    {
        return array(
            'form' => array(
                'legend' => array(
                    'title' => $this->l('Settings'),
                    'icon' => 'icon-cogs',
                ),
                'success' => $configFormSuccess,
                'error' => $configFormError,
                'input' => array(
                    array(
                        'col' => 3,
                        'type' => 'text',
                        'label' => $this->l('Activation Code'),
                        'name' => 'SATISPAY_ACTIVATION_CODE',
                        'desc' => sprintf($this->l('Get a six characters activation code from Online Shop section on %sSatispay Dashboard%s.'), '<a href="https://dashboard.satispay.com/signup" target="_blank">', '</a>'),
                    ),
                    array(
                        'type' => 'switch',
                        'label' => $this->l('Sandbox Mode'),
                        'name' => 'SATISPAY_SANDBOX',
                        'is_bool' => true,
                        'desc' => sprintf($this->l('Sandbox Mode can be used to test the module.').'<br />'.$this->l('Request a %sSandbox Account%s.'), '<a href="https://developers.satispay.com/docs/sandbox-account" target="_blank">', '</a>'),
                        'values' => array(
                            array(
                                'id' => 'active_on',
                                'value' => true,
                                'label' => $this->l('Enabled')
                            ),
                            array(
                                'id' => 'active_off',
                                'value' => false,
                                'label' => $this->l('Disabled')
                            )
                        ),
                    ),
                    array(
                        'col' => 3,
                        'type' => 'text',
                        'label' => $this->l('Finalize pending payments up to'),
                        'name' => 'SATISPAY_UNPROCESSED_TIME',
                        'desc' => sprintf($this->l('Choose a number of hours, default is four and minimum is two.').'<br />'.$this->l('More details on our %sdocumentation%s.'), '<a href="http://developers.satispay.com/docs/prestashop" target="_blank">', '</a>'),
                        'validation' => 'isInt',
                        'cast' => 'intval',
                        'defaultValue' => self::SATISPAY_DEFAULT_UNPROCESSED_TIME,
                        ),
                ),
                'submit' => array(
                    'title' => $this->l('Save'),
                ),
            ),
        );
    }

    protected function getConfigFormValues()
    {
        return array(
            'SATISPAY_SANDBOX' => Configuration::get('SATISPAY_SANDBOX', false),
            'SATISPAY_ACTIVATION_CODE' => Configuration::get('SATISPAY_ACTIVATION_CODE', ''),
            'SATISPAY_UNPROCESSED_TIME' => (Configuration::get('SATISPAY_UNPROCESSED_TIME') ? Configuration::get('SATISPAY_UNPROCESSED_TIME') : self::SATISPAY_DEFAULT_UNPROCESSED_TIME),
        );
    }

    protected function getRefundForm($refundFormSuccess, $refundFormError)
    {
        return array(
            'form' => array(
                'legend' => array(
                    'title' => $this->l('Refund'),
                ),
                'success' => $refundFormSuccess,
                'error' => $refundFormError,
                'input' => array(
                    array(
                        'col' => 3,
                        'type' => 'text',
                        'label' => $this->l('Payment ID'),
                        'name' => 'SATISPAY_REFUND_PAYMENT_ID',
                        'desc' => $this->l('Get the Payment ID from Order details > Payment > Transaction ID.')
                    ),
                    array(
                        'col' => 3,
                        'type' => 'text',
                        'label' => $this->l('Amount'),
                        'name' => 'SATISPAY_REFUND_AMOUNT',
                        'desc' => $this->l('Leave empty to refund the total amount.').'<br />'.$this->l('Decimals must be divided with a dot.')
                    ),
                ),
                'submit' => array(
                    'title' => $this->l('Refund'),
                ),
            ),
        );
    }

    protected function getRefundFormValues()
    {
        return array(
            'SATISPAY_REFUND_PAYMENT_ID' => Configuration::get('SATISPAY_REFUND_PAYMENT_ID', ''),
            'SATISPAY_REFUND_AMOUNT' => Configuration::get('SATISPAY_REFUND_AMOUNT', ''),
        );
    }

    protected function postProcessConfig()
    {
        $postedSandbox = Tools::getValue('SATISPAY_SANDBOX');
        $postedUnprocessedTime = Tools::getValue('SATISPAY_UNPROCESSED_TIME');
        $postedActivationCode = Tools::getValue('SATISPAY_ACTIVATION_CODE');

        $currentActivationCode = Configuration::get('SATISPAY_ACTIVATION_CODE', '');

        if (!is_numeric($postedUnprocessedTime) || $postedUnprocessedTime < 2) {
            return array(
                'success' => '',
                'error' => sprintf($this->l('A numeric value has to be specified. Minimum is two.')),
            );
        }
        Configuration::updateValue('SATISPAY_UNPROCESSED_TIME', $postedUnprocessedTime);
        Configuration::updateValue('SATISPAY_SANDBOX', $postedSandbox);

        if ($postedActivationCode != $currentActivationCode) {
            if ($postedSandbox == '1') {
                \SatispayGBusiness\Api::setSandbox(true);
            }

            try {
                $authentication = \SatispayGBusiness\Api::authenticateWithToken($postedActivationCode);

                Configuration::updateValue('SATISPAY_ACTIVATION_CODE', $postedActivationCode);
                Configuration::updateValue('SATISPAY_KEY_ID', $authentication->keyId);
                Configuration::updateValue('SATISPAY_PRIVATE_KEY', $authentication->privateKey);
                Configuration::updateValue('SATISPAY_PUBLIC_KEY', $authentication->publicKey);
            } catch (\Exception $ex) {
                Configuration::deleteByName('SATISPAY_ACTIVATION_CODE');
                Configuration::deleteByName('SATISPAY_KEY_ID');
                Configuration::deleteByName('SATISPAY_PRIVATE_KEY');
                Configuration::deleteByName('SATISPAY_PUBLIC_KEY');

                return array(
                    'success' => '',
                    'error' => sprintf($this->l('The Activation Code "%s" is invalid.'), $postedActivationCode),
                );
            }
        }

        return array(
            'success' => $this->l('Successfully saved.'),
            'error' => '',
        );
    }

    protected function postProcessRefund()
    {
        $postedRefundPaymentId = Tools::getValue('SATISPAY_REFUND_PAYMENT_ID');
        $postedRefundAmount = Tools::getValue('SATISPAY_REFUND_AMOUNT');

        if (empty($postedRefundPaymentId)) {
            return array();
        }

        try {
            $refund = array(
                'flow' => 'REFUND',
                'currency' => 'EUR',
                'parent_payment_uid' => $postedRefundPaymentId
            );

            if ($postedRefundAmount != '') {
                $refund['amount_unit'] = $postedRefundAmount * 100;
            }

            \SatispayGBusiness\Payment::create($refund);
        } catch (\Exception $ex) {
            return array(
                'success' => '',
                'error' => sprintf($this->l('Unable to refund Payment "%s".'), $postedRefundPaymentId),
            );
        }

        return array(
            'success' => sprintf($this->l('Successfully refunded Payment "%s".'), $postedRefundPaymentId),
            'error' => '',
        );
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

        $paymentOption = new \PrestaShop\PrestaShop\Core\Payment\PaymentOption();
        $paymentOption->setCallToActionText($this->l('Pay with Satispay'))
            ->setAction($this->context->link->getModuleLink($this->name, 'payment', array(), true))
            ->setLogo(Media::getMediaPath(_PS_MODULE_DIR_.$this->name.'/views/img/payment_logo.png'));

        return array($paymentOption);
    }
}
