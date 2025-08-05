<?php
namespace Opencart\Catalog\Controller\Extension\DuitkuPop\Payment;

use Symfony\Component\Validator\Constraints\NotNull;

class DuitkuPop extends \Opencart\System\Engine\Controller
{

  public function index()
  {

    $data['errors'] = array();
    $data['button_confirm'] = $this->language->get('button_confirm');

    $data['text_loading'] = $this->language->get('text_loading');

    $data['process_order'] = 'extension/payment/duitku_pop/process_order';

    if (version_compare(VERSION, '3.0.0.0') < 0) {
      // CODE HERE IF LOWER
      if (file_exists(DIR_TEMPLATE . $this->config->get('config_template') . '/template/extension/payment/duitkutpl')) {
        return $this->load->view($this->config->get('config_template') . '/template/extension/payment/duitkutpl', $data);
      } else {
        return $this->load->view('default/template/extension/payment/duitkutpl', $data);
      }
    } else {
      // CODE HERE IF HIGHER OR EQUAL
      return $this->load->view('extension/payment/duitkutpl', $data);
    }
  }

  /**
   * Called when a customer checkouts.
   * If it runs successfully, it will redirect to Duitku payment page.
   */
  public function process_order()
  {
    $this->load->model('extension/payment/duitku_pop');
    $this->load->model('checkout/order');
    //$this->load->model('total/shipping');

    $order_info = $this->model_checkout_order->getOrder($this->session->data['order_id']);

    //generate Signature
    $merchant_code = $this->config->get('payment_duitku_pop_merchant');
    $api_key = $this->config->get('payment_duitku_pop_api_key');
    $expiry_period = $this->config->get('payment_duitku_pop_expiry_period');
    $ui_mode = $this->config->get('payment_duitku_pop_ui_mode');
    $order_id = $this->session->data['order_id'];
    $def_curr = $this->config->get('config_currency');
    $order_total = trim($this->currency->format($order_info['total'], 'IDR', '', false));
    //$signature = md5($merchant_code . $order_id . intval($order_total) . $api_key);

    $tstamp = round(microtime(true) * 1000);
    $mcode = $merchant_code;
    $header_signature = hash('sha256', $mcode . $tstamp . $api_key);

    // Prepare Parameters
    $params = array(
      'merchantOrderId' => (string)$order_id,
      'merchantUserInfo' => $order_info['email'],
      'expiryPeriod' => (int)$expiry_period,
      'paymentAmount' => intval($order_total), //transform order into integer
      'paymentMethod' => "",
      'productDetails' => $this->config->get('config_name') . ' Order : #' . $order_id,
      'additionalParam' => $order_info['payment_firstname'] . " " . $order_info['payment_lastname'],
      'email' => $order_info['email'],
      'phoneNumber' => $order_info['telephone'],
      'returnUrl' => $this->url->link('extension/payment/duitku_pop/payment_notification'),
      'callbackUrl' => $this->url->link('extension/payment/duitku_pop/payment_notification'),
    );

    $customer_detail = array(
      'firstName' => $order_info['firstname'],
      'lastName' => $order_info['lastname'],
      'email' => $order_info['email'],
      'phoneNumber' => $order_info['telephone'],
    );

    $billing_address = array(
      'firstName' => (string)$order_info['payment_firstname'],
      'lastName' => (string)$order_info['payment_lastname'],
      'address' => (string)$order_info['payment_address_1'] . ', ' . (string)$order_info['payment_address_2'] . ', ' . (string)$order_info['payment_city'] . ', ' . (string)$order_info['payment_zone'] . ' ' . (string)$order_info['payment_postcode'] . ', ' . (string)$order_info['payment_country'],
      'city' => (string)$order_info['payment_city'],
      'postalCode' => (string)$order_info['payment_postcode'],
      'phone' => (string)$order_info['telephone'],
      'countryCode' => (string)$order_info['payment_iso_code_2'],
    );

    if ($this->cart->hasShipping()) {
      $shipping_address = array(
        'firstName' => (string)$order_info['shipping_firstname'],
        'lastName' => (string)$order_info['shipping_lastname'],
        'address' => (string)$order_info['shipping_address_1'] . ', ' . (string)$order_info['shipping_address_2'] . ', ' . (string)$order_info['shipping_city'] . ', ' . (string)$order_info['shipping_zone'] . ' ' . (string)$order_info['shipping_postcode'] . ', ' . (string)$order_info['shipping_country'],
        'city' => (string)$order_info['shipping_city'],
        'postalCode' => (string)$order_info['shipping_postcode'],
        'phone' => (string)$order_info['telephone'],
        'countryCode' => (string)$order_info['shipping_iso_code_2'],
      );
    } else {
      $shipping_address = $billing_address;
    }

    $products = $this->cart->getProducts();

    $item_details = array();

    foreach ($products as $product) {
      $item = array(
        'price'    => (int)($product['price'] * $product['quantity']),
        'quantity' => (int)$product['quantity'],
        'name'     => $product['name']
      );
      $item_details[] = $item;
    }

    if ($this->cart->hasShipping()) {
      $shipping_data = $this->session->data['shipping_method'];
      if (($this->config->get('config_customer_price') && $this->customer->isLogged()) || !$this->config->get('config_customer_price')) {
        $shipping_data['cost'] = $this->tax->calculate(
          $shipping_data['cost'],
          $shipping_data['tax_class_id'],
          $this->config->get('config_tax')
        );
      }

      $shipping_item = array(
        'price' => $shipping_data['cost'],
        'quantity' => 1,
        'name' => 'Shipping Fee'
      );
      $item_details[] = $shipping_item;
    }

    $amount_price = 0;
    foreach ($item_details as $item) {
      $amount_price += $item['price'];
    }

    if ($amount_price != $order_total) {
      $coupon_item = array(
        'price'    => (int)($order_total - $amount_price),
        'quantity' => 1,
        'name'     => 'Coupon'
      );
      $item_details[] = $coupon_item;
    }

    $customer_detail['shippingAddress'] = $shipping_address;
    $customer_detail['billingAddress'] = $billing_address;
    $params['customerDetail'] = $customer_detail;
    $params['itemDetails'] = $item_details;

    // echo "<pre>".var_export($params)."</pre>";

    $url = $this->config->get('payment_duitku_pop_endpoint') . '/api/merchant/createInvoice';

    if (extension_loaded('curl')) {
      try {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
          'Content-Type: application/json',
          'x-duitku-signature: ' . $header_signature,
          'x-duitku-timestamp: ' . $tstamp,
          'x-duitku-merchantCode: ' . $mcode
        ));
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($params));

        // Receive server response ...
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $server_output = curl_exec($ch);

        curl_close($ch);

        $respond = json_decode($server_output);
        if (isset($respond->statusCode)) {
          if ($respond->statusCode == '00') {
            if ($ui_mode == 'popup') {
              $redirUrl = $this->url->link('extension/payment/duitku_pop/proceed', 'reference=' . $respond->reference, true);
              $this->response->setOutput($redirUrl);
            } else {
              $this->response->setOutput($respond->paymentUrl);
            }
          }
        } else {
          $this->session->data['current_amount'] = $order_total;
          $this->session->data['warning_message'] = $server_output;
          $warningUrl = $this->url->link('extension/payment/duitku_pop/warning', true);
          $this->response->setOutput($warningUrl);
          // throw new \Exception($server_output);
        }
      } catch (Exception $e) {
        echo $e->getMessage();
      }
    } else {
      throw new Exception("Duitku payment need curl extension, please enable curl extension in your web server", "duitku");
    }
  }

  public function warning()
  {
    $this->load->language('extension/payment/duitku_pop');
    $this->document->setTitle($this->language->get('warning_title'));
    $data['heading_title'] = $this->language->get('warning_title');
    if (isset($this->request->server['HTTPS']) && (($this->request->server['HTTPS'] == 'on') || ($this->request->server['HTTPS'] == '1'))) {
      $data['base_url'] = $this->config->get('config_ssl');
    } else {
      $data['base_url'] = $this->config->get('config_url');
    }

    $data['column_left'] = $this->load->controller('common/column_left');
    $data['column_right'] = $this->load->controller('common/column_right');
    $data['content_top'] = $this->load->controller('common/content_top');
    $data['content_bottom'] = $this->load->controller('common/content_bottom');
    $data['footer'] = $this->load->controller('common/footer');
    $data['header'] = $this->load->controller('common/header');
    $data['warning_message'] = $this->session->data['warning_message'];
    $data['current_amount'] = $this->session->data['current_amount'];

    if (version_compare(VERSION, '3.0.0.0') < 0) {
      // CODE HERE IF LOWER
      if (file_exists(DIR_TEMPLATE . $this->config->get('config_template') . '/template/extension/payment/duitku_pop_warning.tpl')) {
        $this->response->setOutput($this->load->view($this->config->get('config_template') . '/template/extension/payment/duitku_pop_warning.tpl', $data));
      } else {
        $this->response->setOutput($this->load->view('default/template/extension/payment/duitku_pop_warning', $data));
      }
    } else {
      // CODE HERE IF HIGHER OR EQUAL
      $this->response->setOutput($this->load->view('extension/payment/duitku_pop_warning', $data));
    }
  }

  public function proceed()
  {
    $this->load->model('checkout/order');
    $this->load->model('extension/payment/duitku_pop');
    $this->load->language('extension/payment/duitku_pop');

    $this->document->setTitle($this->language->get('heading_title'));
    $data['heading_title'] = $this->language->get('heading_title');
    $get_str = $_SERVER['QUERY_STRING'];
    $string_q = parse_str($get_str, $get_array);
    $plugin_status = $this->config->get('payment_duitku_pop_plugin_status');

    if ($plugin_status == 'sandbox') {
      $url_lib = 'https://app-sandbox.duitku.com/lib/js/duitku.js';
    } elseif ($plugin_status == 'production') {
      $url_lib = 'https://app-prod.duitku.com/lib/js/duitku.js';
    }

    $data['reference_number'] = $get_array['amp;reference'];
    $data['url_library'] = $url_lib;
    $data['url_redirect'] = $this->url->link('extension/payment/duitku_pop/payment_notification');
    $data['column_left'] = $this->load->controller('common/column_left');
    $data['column_right'] = $this->load->controller('common/column_right');
    $data['content_top'] = $this->load->controller('common/content_top');
    $data['content_bottom'] = $this->load->controller('common/content_bottom');
    $data['footer'] = $this->load->controller('common/footer');
    $data['header'] = $this->load->controller('common/header');
    //$data['checkout_url'] = $this->url->link('checkout/cart');
    if (version_compare(VERSION, '3.0.0.0') < 0) {
      // CODE HERE IF LOWER
      if (file_exists(DIR_TEMPLATE . $this->config->get('config_template') . '/template/extension/payment/duitku_pop.tpl')) {
        $this->response->setOutput($this->load->view($this->config->get('config_template') . '/template/extension/payment/duitku_pop.tpl', $data));
      } else {
        $this->response->setOutput($this->load->view('default/template/extension/payment/duitku_pop', $data));
      }
    } else {
      // CODE HERE IF HIGHER OR EQUAL
      $this->response->setOutput($this->load->view('extension/payment/duitku_pop', $data));
    }
  }

  public function payment_notification()
  {
    $this->load->model('checkout/order');
    $this->load->model('extension/payment/duitku_pop');

    if (!empty($_REQUEST['merchantOrderId'])) {
      $order_id = $_REQUEST['merchantOrderId'];
    } else {
      $str = $_SERVER['QUERY_STRING'];
      parse_str($str, $output);
      if (!empty($output['amp;merchantOrderId'])) {
        $order_id = $output['amp;merchantOrderId'];
      } else {
        $order_id = $output['merchantOrderId'];
      }
    }

    $merchantcode = $this->config->get('payment_duitku_pop_merchant');
    $apikey = $this->config->get('payment_duitku_pop_api_key');
    $endpoint = $this->config->get('payment_duitku_pop_endpoint');
    $url = $endpoint . '/api/merchant/transactionStatus';
    $signature = md5($merchantcode . $order_id . $apikey);
    $params = array(
      'merchantCode' => $merchantcode,
      'merchantOrderId' => $order_id,
      'signature' => $signature
    );

    if (extension_loaded('curl')) {
      try {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
          'Content-Type: application/json'
        ));
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($params));
        // Receive server response ...
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $server_output = curl_exec($ch);
        curl_close($ch);

        $respondStatus = json_decode($server_output);
        if ($respondStatus->statusCode == '00') {
          $data['merchantOrderId'] = $respondStatus->merchantOrderId;
          $data['reference'] = $respondStatus->reference;
          $data['amount'] = $respondStatus->amount;
          $data['statusCode'] = $respondStatus->statusCode;
          $data['statusMessage'] = $respondStatus->statusMessage;
          $data['fee'] = $respondStatus->fee;

          $this->model_checkout_order->addOrderHistory($order_id, $this->config->get('payment_duitku_pop_success_mapping'), 'Duitku payment success.');
          $this->cart->clear();
        } elseif ($respondStatus->statusCode == '01') {
          $data['merchantOrderId'] = $respondStatus->merchantOrderId;
          $data['reference'] = $respondStatus->reference;
          $data['amount'] = $respondStatus->amount;
          $data['statusCode'] = $respondStatus->statusCode;
          $data['statusMessage'] = $respondStatus->statusMessage;
          $data['fee'] = $respondStatus->fee;

          $this->model_checkout_order->addOrderHistory($order_id, $this->config->get('payment_duitku_pop_pending_mapping'), 'Duitku payment pending.');
          $this->cart->clear();
        } elseif ($respondStatus->statusCode == '02') {
          $data['merchantOrderId'] = $respondStatus->merchantOrderId;
          $data['reference'] = $respondStatus->reference;
          $data['amount'] = $respondStatus->amount;
          $data['statusCode'] = $respondStatus->statusCode;
          $data['statusMessage'] = $respondStatus->statusMessage;
          $data['fee'] = $respondStatus->fee;

          $this->model_checkout_order->addOrderHistory($order_id, $this->config->get('payment_duitku_pop_failure_mapping'), 'Duitku payment failed.');
        }
      } catch (Exception $e) {
        echo $e->getMessage();
      }
    } else {
      throw new Exception("Duitku payment need curl extension, please enable curl extension in your web server", "duitku");
    }

    $data['column_left'] = $this->load->controller('common/column_left');
    $data['column_right'] = $this->load->controller('common/column_right');
    $data['content_top'] = $this->load->controller('common/content_top');
    $data['content_bottom'] = $this->load->controller('common/content_bottom');
    $data['footer'] = $this->load->controller('common/footer');
    $data['header'] = $this->load->controller('common/header');
    $data['checkout_url'] = $this->url->link('checkout/cart');
    $data['home_url'] = $this->url->link('common/home');

    if (version_compare(VERSION, '3.0.0.0') < 0) {
      // CODE HERE IF LOWER
      if (file_exists(DIR_TEMPLATE . $this->config->get('config_template') . '/template/extension/payment/result_transaction.tpl')) {
        $this->response->setOutput($this->load->view($this->config->get('config_template') . '/template/extension/payment/result_transaction.tpl', $data));
      } else {
        $this->response->setOutput($this->load->view('default/template/extension/payment/result_transaction', $data));
      }
    } else {
      // CODE HERE IF HIGHER OR EQUAL
      $this->response->setOutput($this->load->view('extension/payment/result_transaction', $data));
    }
  }
}
