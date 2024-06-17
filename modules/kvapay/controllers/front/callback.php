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
 * KvapayCallbackModuleFrontController
 */
class KvapayCallbackModuleFrontController extends ModuleFrontController
{
    public $ssl = true;

    /** @var array */
    protected $requestData;

    protected $request;

    public function postProcess()
    {
        parent::postProcess();

        try {
            $this->request = Tools::file_get_contents('php://input');
            $this->logInfo('KvaPay reportPayload: ' . $this->request);
            $headers = $this->get_ds_headers();
            if (!array_key_exists('XSignature', $headers)) {
                $error_message = 'KvaPay X-SIGNATURE: not found';
                $this->logError($error_message);

                throw new Exception($error_message, 400);
            }

            $signature = $headers['XSignature'];

            $this->requestData = json_decode($this->request, true);

            if ($this->requestData['type'] !== 'PAYMENT') {
                $error_message = 'KvaPay Request: not valid request type';
                $this->logError($error_message);

                throw new Exception($error_message, 400);
            }

            $order_id = (int) $this->requestData['variableSymbol'];
            $order = new Order($order_id);
            $currency = new Currency($order->id_currency);

            if (!$order_id) {
                $error_message = 'Shop order #' . $this->requestData['variableSymbol'] . ' does not exists';
                $this->logError($error_message, $order_id);

                throw new Exception($error_message, 400);
            }

            if ($currency->iso_code != $this->requestData['currency']) {
                $error_message = 'KvaPay Currency: ' . $this->requestData['currency'] . ' is not valid';
                $this->logError($error_message, $order_id);

                throw new Exception($error_message, 400);
            }

            $apiKey = Configuration::get('KVAPAY_API_KEY');
            $environment = Configuration::get('KVAPAY_TEST') == 1;
            $client = new \KvaPay\Client($apiKey, $environment);

            $token = $client->generateSignature($this->request, Configuration::get('KVAPAY_API_SECRET'));

            if (empty($signature) || strcmp($signature, $token) !== 0) {
                $error_message = 'KvaPay X-SIGNATURE: ' . $signature;
                $this->logError($error_message, $order_id);

                throw new Exception($error_message, 400);
            }

            switch ($this->requestData['state']) {
                case 'SUCCESS':
                    if (((float) $order->getOrdersTotalPaid()) == ((float) $this->requestData['amount'])) {
                        $order_status = 'PS_OS_PAYMENT';

                        break;
                    }
                    $order_status = 'KVAPAY_INVALID';
                    $this->logError('PS Orders Total does not match with Kvapay Price Amount', $order_id);

                    break;
                case 'WAITING_FOR_PAYMENT':
                    $order_status = 'KVAPAY_PENDING';

                    break;
                case 'WAITING_FOR_CONFIRMATION':
                    $order_status = 'KVAPAY_CONFIRMING';

                    break;
                case 'EXPIRED':
                    $order_status = 'KVAPAY_EXPIRED';

                    break;
                default:
                    $order_status = false;
            }

            if ($order_status && Configuration::get($order_status) != $order->current_state && $order->current_state != Configuration::get('PS_OS_PAYMENT')) {
                $history = new OrderHistory();
                $history->id_order = $order->id;
                $history->changeIdOrderState((int) Configuration::get($order_status), $order->id);
                $history->addWithemail(true, [
                    'order_name' => $order_id,
                ]);

                $this->response('OK');
            } else {
                $this->response('Order Status ' . $this->requestData['state'] . ' not implemented');
            }
        } catch (Exception $e) {
            $this->response($e->getMessage(), $e->getCode());
        }

        if (_PS_VERSION_ >= '1.7') {
            $this->setTemplate('module:kvapay/views/templates/front/kvapay_payment_callback.tpl');
        } else {
            $this->setTemplate('kvapay_payment_callback.tpl');
        }
    }

    public function get_ds_headers()
    {
        $headers = [];
        foreach ($_SERVER as $key => $value) {
            if (strpos($key, 'HTTP_') === 0) {
                $headers[str_replace(' ', '', ucwords(str_replace('_', ' ', strtolower(substr($key, 5)))))] = $value;
            }
        }

        return $headers;
    }

    private function logInfo($message, $cart_id = null)
    {
        PrestaShopLogger::addLog($message, 1, null, 'Cart', $cart_id, true);
    }

    private function logError($message, $cart_id = null)
    {
        PrestaShopLogger::addLog($message, 3, null, 'Cart', $cart_id, true);
    }

    private function response($message, $status = 200)
    {
        header('Content-Type: application/json; charset=utf-8');
        http_response_code($status);
        if ($status === 200) {
            echo json_encode(['status' => 'success', 'message' => $message]);
        } else {
            echo json_encode(['status' => 'error', 'error' => $message]);
        }

        exit;
    }
}
