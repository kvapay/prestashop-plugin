<?php
/**
 * @author    KvaPay <info@kvapay.com>
 * @copyright 2023 KvaPay
 * @license   https://www.opensource.org/licenses/MIT  MIT License
 */
if (!defined('_PS_VERSION_')) {
    exit;
}

require_once _PS_MODULE_DIR_ . '/kvapay/vendor/kvapay-php/init.php';

class Kvapay extends PaymentModule
{
    public $api_key;
    public $api_secret;
    public $test;

    public function __construct()
    {
        $this->name = 'kvapay';
        $this->tab = 'payments_gateways';
        $this->version = '1.0.0';
        $this->author = 'KvaPay.com';
        $this->is_eu_compatible = 1;
        $this->controllers = ['payment', 'redirect', 'callback', 'cancel', 'success'];
        $this->ps_versions_compliancy = ['min' => '1.6', 'max' => _PS_VERSION_];
        $this->module_key = '8173070fca98275a284e1e694b09dc5b';

        $this->currencies = true;
        $this->currencies_mode = 'checkbox';

        $this->bootstrap = true;

        $config = Configuration::getMultiple(
            [
                'KVAPAY_API_KEY',
                'KVAPAY_API_SECRET',
                'KVAPAY_TEST',
            ]
        );

        if (!empty($config['KVAPAY_API_KEY'])) {
            $this->api_key = $config['KVAPAY_API_KEY'];
        }

        if (!empty($config['KVAPAY_API_SECRET'])) {
            $this->api_secret = $config['KVAPAY_API_SECRET'];
        }

        if (!empty($config['KVAPAY_TEST'])) {
            $this->test = $config['KVAPAY_TEST'];
        }

        parent::__construct();

        $this->displayName = $this->l('KvaPay');
        $this->description = $this->l('Accept Bitcoin and other cryptocurrencies as a payment method with KvaPay');
        $this->confirmUninstall = $this->l('Are you sure you want to delete your details?');

        if (empty($this->api_key) || empty($this->api_secret)) {
            $this->warning = $this->l('API Access details must be configured in order to use this module correctly.');
        }
    }

    public function hookDisplayPaymentEU()
    {
        $payment_options = [
            'cta_text' => $this->l('Crypto payment'),
            'logo' => $this->getLogo(),
            'action' => $this->context->link->getModuleLink($this->name, 'redirect', [], true)
        ];

        return $payment_options;
    }

    public function getLogo($file = 'logo.png')
    {
        
        return Media::getMediaPath(_PS_MODULE_DIR_ . $this->name . '/' . $file);
    }


    public function install()
    {
        if (!function_exists('curl_version')) {
            $this->_errors[] = $this->l('This module requires cURL PHP extension in order to function normally.');

            return false;
        }

        $order_pending = new OrderState();
        $order_pending->name = array_fill(0, 10, 'Awaiting KvaPay payment');
        $order_pending->send_email = 0;
        $order_pending->invoice = 0;
        $order_pending->color = 'RoyalBlue';
        $order_pending->unremovable = false;
        $order_pending->logable = 0;

        $order_expired = new OrderState();
        $order_expired->name = array_fill(0, 10, 'KvaPay payment expired');
        $order_expired->send_email = 0;
        $order_expired->invoice = 0;
        $order_expired->color = '#DC143C';
        $order_expired->unremovable = false;
        $order_expired->logable = 0;

        $order_confirming = new OrderState();
        $order_confirming->name = array_fill(0, 10, 'Awaiting KvaPay payment confirmations');
        $order_confirming->send_email = 0;
        $order_confirming->invoice = 0;
        $order_confirming->color = '#d9ff94';
        $order_confirming->unremovable = false;
        $order_confirming->logable = 0;

        $order_invalid = new OrderState();
        $order_invalid->name = array_fill(0, 10, 'KvaPay invoice is invalid');
        $order_invalid->send_email = 0;
        $order_invalid->invoice = 0;
        $order_invalid->color = '#8f0621';
        $order_invalid->unremovable = false;
        $order_invalid->logable = 0;

        if ($order_pending->add()) {
            copy(
                _PS_ROOT_DIR_ . '/modules/kvapay/logo.png',
                _PS_ROOT_DIR_ . '/img/os/' . (int) $order_pending->id . '.gif'
            );
        }

        if ($order_expired->add()) {
            copy(
                _PS_ROOT_DIR_ . '/modules/kvapay/logo.png',
                _PS_ROOT_DIR_ . '/img/os/' . (int) $order_expired->id . '.gif'
            );
        }

        if ($order_confirming->add()) {
            copy(
                _PS_ROOT_DIR_ . '/modules/kvapay/logo.png',
                _PS_ROOT_DIR_ . '/img/os/' . (int) $order_confirming->id . '.gif'
            );
        }

        if ($order_invalid->add()) {
            copy(
                _PS_ROOT_DIR_ . '/modules/kvapay/logo.png',
                _PS_ROOT_DIR_ . '/img/os/' . (int) $order_invalid->id . '.gif'
            );
        }

        Configuration::updateValue('KVAPAY_PENDING', $order_pending->id);
        Configuration::updateValue('KVAPAY_EXPIRED', $order_expired->id);
        Configuration::updateValue('KVAPAY_CONFIRMING', $order_confirming->id);
        Configuration::updateValue('KVAPAY_INVALID', $order_invalid->id);

        if (!parent::install()
            || !$this->registerHook('payment')
            || !$this->registerHook('displayPaymentEU')
            || !$this->registerHook('paymentReturn')
            || !$this->registerHook('displayOrderDetail')
            || !$this->registerHook('paymentOptions')) {
            return false;
        }

        return true;
    }

    public function hookDisplayOrderDetail($params)
    {
        $order = $params['order'];
        if ($order->module != $this->name) {
            return;
        }

        $allowed_state = [
            Configuration::get('KVAPAY_PENDING'),
            Configuration::get('KVAPAY_EXPIRED'),
            Configuration::get('KVAPAY_INVALID'),
        ];

        if (in_array($order->current_state, $allowed_state)) {
            $context = Context::getContext();
            $sandbox = (Configuration::get('KVAPAY_TEST') == 1 ? true : false);
            $customer = new Customer((int) $order->id_customer);
            $currency = new Currency($order->id_currency);

            // $url_payment = $this->getLinkPaymentOnShop($params['order']->id);

            $payment_url = $this->context->link->getModuleLink('kvapay', 'redirect', [
                'id_order' => $order->id,
                'key' => $customer->secure_key,
            ]);

            $this->smarty->assign([
                'kvapay_sandbox' => $sandbox,
                'kvapay_url_payment' => $payment_url,
                'kvapay_id_order' => $order->id,
                'kvapay_reference_order' => $order->reference,
                'kvapay_total_to_pay' => Tools::displayPrice($order->total_paid, $currency, false),
            ]);

            if (_PS_VERSION_ < 1.7) {
                return $this->display(__FILE__, 'kvapay_payment.tpl');
            }

            return $this->fetch('module:kvapay/views/templates/front/kvapay_payment.tpl');
        }
    }

    public function uninstall()
    {
        $order_state_pending = new OrderState(Configuration::get('KVAPAY_PENDING'));
        $order_state_expired = new OrderState(Configuration::get('KVAPAY_EXPIRED'));
        $order_state_confirming = new OrderState(Configuration::get('KVAPAY_CONFIRMING'));
        $order_state_invalid = new OrderState(Configuration::get('KVAPAY_INVALID'));

        return Configuration::deleteByName('KVAPAY_API_KEY')
            && Configuration::deleteByName('KVAPAY_API_SECRET')
            && Configuration::deleteByName('KVAPAY_TEST')
            && $order_state_pending->delete()
            && $order_state_expired->delete()
            && $order_state_confirming->delete()
            && $order_state_invalid
            && parent::uninstall();
    }

    private function postValidation()
    {
        if (Tools::isSubmit('btnSubmit')) {
            if (!Tools::getValue('KVAPAY_API_KEY')) {
                $this->context->controller->errors[] = $this->l('API Key is required.');
            }
            if (!Tools::getValue('KVAPAY_API_SECRET')) {
                $this->context->controller->errors[] = $this->l('API Secret is required.');
            }
        }
    }

    private function postProcess()
    {
        if (Tools::isSubmit('btnSubmit')) {
            Configuration::updateValue(
                'KVAPAY_API_KEY',
                $this->stripString(Tools::getValue('KVAPAY_API_KEY'))
            );
            Configuration::updateValue(
                'KVAPAY_API_SECRET',
                $this->stripString(Tools::getValue('KVAPAY_API_SECRET'))
            );
            Configuration::updateValue('KVAPAY_TEST', Tools::getValue('KVAPAY_TEST'));
        }
    }

    private function displayKvapay()
    {
        return $this->display(__FILE__, 'kvapay_infos.tpl');
    }

    private function displayKvapayInformation($renderForm)
    {
        $this->context->controller->addCSS($this->_path . '/views/css/tabs.css', 'all');
        $this->context->controller->addJS($this->_path . '/views/js/javascript.js', 'all');
        $this->context->smarty->assign('form', $renderForm);

        return $this->display(__FILE__, 'kvapay_information.tpl');
    }

    public function getContent()
    {
        $output = '';
        if (Tools::isSubmit('btnSubmit')) {
            $this->postValidation();
            if (empty($this->context->controller->errors)) {
                $this->postProcess();
                $output = $this->displayConfirmation($this->l('Settings updated'));
            }
        } else {
            $output = $this->displayKvapay();
        }

        $renderForm = $this->renderForm();
        $output .= $this->displayKvapayInformation($renderForm);

        return $output;
    }

    public function hookPayment($params)
    {
        if (_PS_VERSION_ >= 1.7) {
            return;
        }
        if (!$this->active) {
            return;
        }
        if (!$this->checkCurrency($params['cart'])) {
            return;
        }
        $this->smarty->assign([
            'this_path' => $this->_path,
            'this_path_bw' => $this->_path,
            'this_path_ssl' => Tools::getShopDomainSsl(true, true) . __PS_BASE_URI__ . 'modules/' . $this->name . '/',
        ]);

        return $this->display(__FILE__, 'kvapay_payment.tpl');
    }

    public function hookPaymentReturn($params)
    {
        if (!$this->active) {
            return;
        }

        if (Tools::getValue('kvapay_error') and Tools::getValue('kvapay_error') == 1) {
            return $this->fetch('module:kvapay/views/templates/front/kvapay_payment_cancel.tpl');
        }

        if (_PS_VERSION_ < 1.7) {
            $order = $params['objOrder'];
            $state = $order->current_state;
        } else {
            $order = $params['order'];
            $state = $params['order']->getCurrentState();
        }
        $customer = new Customer((int) $order->id_customer);
        $currency = new Currency($order->id_currency);

        $payment_url = $this->context->link->getModuleLink('kvapay', 'redirect', [
            'id_order' => $order->id,
            'key' => $customer->secure_key,
        ]);

        if (_PS_VERSION_ < 1.7) {
            if ($state == Configuration::get('PS_OS_PAYMENT')) {
                $this->context->smarty->assign([
                    'kvapay_production' => Configuration::get('KVAPAY_TEST') == 0,
                    'kvapay_id_order' => $order->id,
                    'kvapay_reference_order' => $order->reference,
                    'kvapay_total_to_pay' => Tools::displayPrice($order->total_paid, $currency, false),
                ]);

                return $this->display(__FILE__, 'kvapay_payment_success_old.tpl');
            }
            $this->context->smarty->assign([
                'kvapay_production' => Configuration::get('KVAPAY_TEST') == 0,
                'kvapay_id_order' => $order->id,
                'kvapay_url_payment' => $payment_url,
                'kvapay_reference_order' => $order->reference,
                'kvapay_total_to_pay' => Tools::displayPrice($order->total_paid, $currency, false),
            ]);

            return $this->display(__FILE__, 'kvapay_payment_cancel.tpl');
        }
        $this->smarty->assign([
            'state' => $state,
            'paid_state' => (int) Configuration::get('PS_OS_PAYMENT'),
            'this_path' => $this->_path,
            'kvapay_repeat_payment_url' => $payment_url,
            'this_path_bw' => $this->_path,
            'this_path_ssl' => Tools::getShopDomainSsl(true, true) . __PS_BASE_URI__ . 'modules/' . $this->name . '/',
        ]);

        return $this->fetch('module:kvapay/views/templates/hook/kvapay_confirmation.tpl');
    }

    public function hookPaymentOptions($params)
    {
        if (!$this->active) {
            return;
        }

        if (!$this->checkCurrency($params['cart'])) {
            return;
        }

        $newOption = new PrestaShop\PrestaShop\Core\Payment\PaymentOption();
        $newOption->setCallToActionText($this->l('Crypto payment'))
            ->setAction($this->context->link->getModuleLink($this->name, 'redirect', [], true))
            ->setAdditionalInformation(
                $this->context->smarty->fetch('module:kvapay/views/templates/hook/kvapay_intro.tpl')
            );

        $payment_options = [$newOption];

        return $payment_options;
    }

    public function checkCurrency($cart)
    {
        $currency_order = new Currency($cart->id_currency);
        $currencies_module = $this->getCurrency($cart->id_currency);
        if (is_array($currencies_module)) {
            foreach ($currencies_module as $currency_module) {
                if ($currency_order->id == $currency_module['id_currency']) {
                    return true;
                }
            }
        }

        return false;
    }

    public function renderForm()
    {
        $this->context->smarty->assign([
            'kvapay_notification' => $this->context->link->getModuleLink($this->name, 'callback', [], true),
        ]);
        $notification = $this->context->smarty->fetch($this->local_path . 'views/templates/admin/kvapay_notification.tpl');
        $fields_form = [
            'form' => [
                'legend' => [
                    'title' => $this->l('KvaPay'),
                    'icon' => 'icon-bitcoin',
                ],
                'input' => [
                    [
                        'type' => 'html',
                        'label' => $this->l('Callback URL'),
                        'html_content' => $notification,
                        'desc' => $this->l('Set this value in the payment system store settings.'),
                        'name' => 'KVAPAY_NOTIFICATION',
                    ],
                    [
                        'type' => 'text',
                        'label' => $this->l('Your Api Key'),
                        'name' => 'KVAPAY_API_KEY',
                        'desc' => $this->l('Your Api Key (created on KvaPay)'),
                        'required' => true,
                    ],
                    [
                        'type' => 'text',
                        'label' => $this->l('Your Api Secret'),
                        'name' => 'KVAPAY_API_SECRET',
                        'desc' => $this->l('Your Api Secret (created on KvaPay)'),
                        'required' => true,
                    ],
                    [
                        'type' => 'select',
                        'label' => $this->l('Test Mode'),
                        'name' => 'KVAPAY_TEST',
                        'desc' => $this->l('To test on dev.crypay.com, turn Test Mode “On”. Please note, for Test Mode you must create a separate account on dev.crypay.com and generate API credentials there.'),
                        'required' => true,
                        'options' => [
                            'query' => [
                                [
                                    'id_option' => 0,
                                    'name' => 'Off',
                                ],
                                [
                                    'id_option' => 1,
                                    'name' => 'On',
                                ],
                            ],
                            'id' => 'id_option',
                            'name' => 'name',
                        ],
                    ],
                ],
                'submit' => [
                    'title' => $this->l('Save'),
                ],
            ],
        ];

        $helper = new HelperForm();
        $helper->show_toolbar = false;
        $helper->table = $this->table;
        $lang = new Language((int) Configuration::get('PS_LANG_DEFAULT'));
        $helper->default_form_language = $lang->id;
        $helper->allow_employee_form_lang = (Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG')
            ? Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG') : 0);
        $this->fields_form = [];
        $helper->id = (int) Tools::getValue('id_carrier');
        $helper->identifier = $this->identifier;
        $helper->submit_action = 'btnSubmit';
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false)
            . '&configure=' . $this->name . '&tab_module='
            . $this->tab . '&module_name=' . $this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->tpl_vars = [
            'fields_value' => $this->getConfigFieldsValues(),
            'languages' => $this->context->controller->getLanguages(),
            'id_language' => $this->context->language->id,
        ];

        return $helper->generateForm([$fields_form]);
    }

    public function getConfigFieldsValues()
    {
        return [
            'KVAPAY_API_KEY' => Tools::getValue(
                'KVAPAY_API_KEY',
                Configuration::get('KVAPAY_API_KEY')
            ),
            'KVAPAY_API_SECRET' => Tools::getValue(
                'KVAPAY_API_SECRET',
                Configuration::get('KVAPAY_API_SECRET')
            ),
            'KVAPAY_TEST' => Tools::getValue(
                'KVAPAY_TEST',
                Configuration::get('KVAPAY_TEST')
            ),
        ];
    }

    private function stripString($item)
    {
        return preg_replace('/\s+/', '', $item);
    }
}
