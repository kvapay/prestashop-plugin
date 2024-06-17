<?php
/**
 * 2007-2015 PrestaShop
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
 * @author    PrestaShop SA <contact@prestashop.com>
 * @copyright 2007-2015 PrestaShop SA
 * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 *  International Registered Trademark & Property of PrestaShop SA
 */
if (!defined('_PS_VERSION_')) {
    exit;
}

require_once _PS_MODULE_DIR_ . '/kvapay/vendor/kvapay-php/init.php';

/**
 * KvapayRedirectModuleFrontController
 */
class KvapayRedirectModuleFrontController extends ModuleFrontController
{
    public $ssl = true;

    public function initContent()
    {
        parent::initContent();

        $id_order = Tools::getValue('id_order');

        if ($id_order) {
            $key = Tools::getValue('key');
            $cart = Cart::getCartByOrderId($id_order);
            if (_PS_VERSION_ < '1.7') {
                $order = new Order((int)$id_order);
            } else {
                $order = Order::getByCartId((int)$cart->id);
            }
            $customer = new Customer((int)$order->id_customer);
            if ($key != $customer->secure_key) {
                echo 'Access denied for this operation';
                exit;
            }
        } else {
            $cart = $this->context->cart;
        }

        if (!$this->module->checkCurrency($cart)) {
            Tools::redirect('index.php?controller=order');
        }

        $total = (float)number_format($cart->getOrderTotal(true, Cart::BOTH), 2, '.', '');
        $currency = Context::getContext()->currency;

        $apiKey = Configuration::get('KVAPAY_API_KEY');
        $environment = Configuration::get('KVAPAY_TEST') == 1;

        try {
        $client = new \KvaPay\Client($apiKey, $environment);
        } catch (\Exception $e) {
            $this->logError($e->getMessage(), $cart->id);
            Tools::redirect('index.php?controller=order&step=3');
        }
        $client::setAppInfo('PrestashopMarketplace', $this->module->version);

        $customer = new Customer($cart->id_customer);

        if (!$id_order) {
            $this->module->validateOrder(
                (int)$cart->id,
                Configuration::get('KVAPAY_PENDING'),
                $total,
                $this->module->displayName,
                null,
                null,
                (int)$currency->id,
                false,
                $customer->secure_key
            );
            $order = new Order($this->module->currentOrder);
            $id_order = (int)$order->id;
        }

        $success_url = $this->context->link->getModuleLink('kvapay', 'success', [
            'id_order' => $id_order,
            'key' => $customer->secure_key,
        ]);

        $fail = $this->context->link->getModuleLink('kvapay', 'cancel', [
            'id_order' => $id_order,
            'key' => $customer->secure_key,
        ]);

        $params = [
            'symbol' => $currency->iso_code,
            'amount' => $total,
            'currency' => $currency->iso_code,
            'variableSymbol' => (string)$id_order,
            'successUrl' => $success_url,
            'failUrl' => $fail,
            'timestamp' => time(),
        ];

        $params['email'] = $customer->email;
        $params['name'] = ($customer->company) ? $customer->company : $customer->firstname . ' ' . $customer->lastname;
        $params['language'] = Language::getIsoById((int) $cart->id_lang);

        if ($environment) {
            $this->logInfo('send redirect params ' . json_encode($params));
        }

        try {
            $orderUrl = $client->payment->createPaymentShortLink($params);
        } catch (\Exception $e) {
            $this->logError($e->getMessage(), $cart->id);
            Tools::redirect('index.php?controller=order&step=3');
        }
        if (!isset($orderUrl) || !isset($orderUrl->shortLink) || !$orderUrl->shortLink) {
            Tools::redirect('index.php?controller=order&step=3');
        }

        Tools::redirect($orderUrl->shortLink);
    }

    private function logInfo($message, $cart_id = 0)
    {
        PrestaShopLogger::addLog($message, 1, null, 'Cart', $cart_id, true);
    }

    private function logError($message, $cart_id = 0)
    {
        PrestaShopLogger::addLog('[create kvapay order] ' . $message, 3, null, 'Cart', $cart_id, true);
    }
}
