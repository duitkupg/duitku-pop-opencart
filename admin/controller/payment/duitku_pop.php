<?php
namespace Opencart\Admin\Controller\Extension\DuitkuPop\Payment;
class DuitkuPop extends \Opencart\System\Engine\Controller {

  private $error = array();

  public function index(): void {
    $this->load->language('extension/duitku_pop/payment/duitku_pop');

    //$this->load->language('cache/cleaner');
    $this->document->setTitle($this->language->get('heading_title'));

    $this->load->model('setting/setting');
    $this->load->model('localisation/order_status');
    $this->config->get('currency');

    if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
      $this->model_setting_setting->editSetting('payment_duitku_pop', $this->request->post);

      $this->session->data['success'] = $this->language->get('text_success');

      $this->response->redirect($this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=payment', true));
    }

    $language_entries = array(
      'heading_title',
      'text_enabled',
      'text_disabled',
      'text_yes',
      'text_live',
      'text_successful',
      'text_fail',
      'text_all_zones',
      'text_edit',
      'entry_merchant',
      'entry_api_key',
      'entry_test',
      'entry_total',
      'entry_order_status',
      'entry_geo_zone',
      'entry_status',
      'entry_expiry_period',
      'entry_ui_mode',
      'entry_sort_order',
      'entry_duitku_pop_success_mapping',
      'entry_duitku_pop_pending_mapping',
      'entry_duitku_pop_failure_mapping',
      'entry_display_name',
      'entry_plugin_status',
      'entry_endpoint',
      'button_save',
      'button_cancel',
      'sandbox_status',
      'production_status',
      'popup_mode',
      'redirect_mode',
    );

    foreach ($language_entries as $language_entry) {
      $data[$language_entry] = $this->language->get($language_entry);
    }

    if (isset($this->error)) {
      $data['error'] = $this->error;
    } else {
      $data['error'] = array();
    }

    $data['breadcrumbs'] = array();

    $data['breadcrumbs'][] = array(
      'text' => $this->language->get('text_home'),
      'href' => $this->url->link('common/dashboard', 'user_token=' . @$this->session->data['user_token'], true),
      // 'separator' => false
    );

    $data['breadcrumbs'][] = array(
      'text' => $this->language->get('text_payment'),
      'href' => $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=payment', true),
      // 'separator' => ' :: '
    );

    $data['breadcrumbs'][] = array(
      'text' => $this->language->get('heading_title'),
      'href' => $this->url->link('extension/duitku_pop/payment/duitku_pop', 'user_token=' . $this->session->data['user_token'], true),
      // 'separator' => ' :: '
    );

    $data['action'] = $this->url->link('extension/duitku_pop/payment/duitku_pop', 'user_token=' . $this->session->data['user_token'], true);

    $data['cancel'] = $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=payment', true);

    $inputs = array(
      'payment_duitku_pop_merchant',
      'payment_duitku_pop_environment',
      'payment_duitku_pop_api_key',
      'payment_duitku_pop_endpoint',
      'payment_duitku_pop_debug',
      'payment_duitku_pop_total',
      'payment_duitku_pop_order_status_id',
      'payment_duitku_pop_geo_zone_id',
      'payment_duitku_pop_sort_order',
      'payment_duitku_pop_plugin_status',
      'payment_duitku_pop_status',
      'payment_duitku_pop_expiry_period',
      'payment_duitku_pop_ui_mode',
      'payment_duitku_pop_success_mapping',
      'payment_duitku_pop_pending_mapping',
      'payment_duitku_pop_failure_mapping',
      'payment_duitku_pop_challenge_mapping',
      'payment_duitku_pop_display_name',
      'payment_duitku_pop_sanitization',
    );

    foreach ($inputs as $input) {
      if (isset($this->request->post[$input])) {
        $data[$input] = $this->request->post[$input];
      } else {
        $data[$input] = $this->config->get($input);
      }
    }

    $this->load->model('localisation/order_status');

    $data['statuses'] = array('payment_duitku_pop_success_mapping', 'payment_duitku_pop_pending_mapping', 'payment_duitku_pop_failure_mapping');
    $data['order_statuses'] = $this->model_localisation_order_status->getOrderStatuses();

    $this->load->model('localisation/geo_zone');

    $data['geo_zones'] = $this->model_localisation_geo_zone->getGeoZones();

    $data['column_left'] = $this->load->controller('common/column_left');
    $data['header'] = $this->load->controller('common/header');
    $data['footer'] = $this->load->controller('common/footer');


    if(!$this->currency->has('IDR'))
    {
      $data['curr'] = true;
    }
    else
    {
      $data['curr'] = false;
    }
    $this->response->setOutput($this->load->view('extension/duitku_pop/payment/duitku_pop',$data));

  }

  protected function validate() {

    if (!$this->user->hasPermission('modify', 'extension/duitku_pop/payment/duitku_pop')) {
      $this->error['warning'] = $this->language->get('error_permission');
    }

    // check for empty values
    if (!$this->request->post['payment_duitku_pop_display_name']) {
      $this->error['display_name'] = $this->language->get('error_display_name');
    }

    // check for empty values
    if (!$this->request->post['payment_duitku_pop_api_key']) {
      $this->error['api_key'] = $this->language->get('error_api_key');
    }

    if (!$this->request->post['payment_duitku_pop_merchant']) {
      $this->error['merchant_code'] = $this->language->get('error_merchant_code');
    }

    if (!$this->request->post['payment_duitku_pop_endpoint']) {
      $this->error['endpoint'] = $this->language->get('error_endpoint');
    }

    if (!$this->error) {
      return true;
    } else {
      return false;
    }
  }

  private function separator():string
    {
        if (VERSION >= '4.0.2.0') {
            return '.';
        }

        return '|';
    }
}
?>
