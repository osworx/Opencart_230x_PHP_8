<?php
class ControllerExtensionCurrencyFixer extends Controller {
	private $error = array();

	public function index(): void {
		$this->load->language('extension/currency/fixer');

		$this->document->setTitle($this->language->get('heading_title'));

		$this->load->model('setting/setting');

		if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
			$this->model_setting_setting->editSetting('fixer', $this->request->post);

			$this->session->data['success'] = $this->language->get('text_success');

			$this->response->redirect($this->url->link('extension/extension', 'token=' . $this->session->data['token'] . '&type=currency', true));
		}

		$data['heading_title'] = $this->language->get('heading_title');

		$data['text_edit'] = $this->language->get('text_edit');
		$data['text_enabled'] = $this->language->get('text_enabled');
		$data['text_disabled'] = $this->language->get('text_disabled');
		$data['text_signup'] = $this->language->get('text_signup');
		$data['text_support'] = $this->language->get('text_support');

		$data['entry_status'] = $this->language->get('entry_status');
		$data['entry_sort_order'] = $this->language->get('entry_sort_order');
		$data['entry_api'] = $this->language->get('entry_api');

		$data['button_save'] = $this->language->get('button_save');
		$data['button_cancel'] = $this->language->get('button_cancel');

		if (isset($this->error['warning'])) {
			$data['error_warning'] = $this->error['warning'];
		} else {
			$data['error_warning'] = '';
		}

		if (isset($this->error['api'])) {
			$data['error_api'] = $this->error['api'];
		} else {
			$data['error_api'] = '';
		}

		$data['breadcrumbs'] = array();

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/dashboard', 'token=' . $this->session->data['token'], true)
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_extension'),
			'href' => $this->url->link('extension/extension', 'token=' . $this->session->data['token'] . '&type=currency', true)
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('heading_title'),
			'href' => $this->url->link('extension/currency/fixer', 'token=' . $this->session->data['token'], true)
		);

		$data['action'] = $this->url->link('extension/currency/fixer', 'token=' . $this->session->data['token'], true);

		$data['cancel'] = $this->url->link('extension/extension', 'token=' . $this->session->data['token'] . '&type=currency', true);

		if (isset($this->request->post['fixer_api'])) {
			$data['fixer_api'] = $this->request->post['fixer_api'];
		} else {
			$data['fixer_api'] = $this->config->get('fixer_api');
		}

		if (isset($this->request->post['fixer_status'])) {
			$data['fixer_status'] = $this->request->post['fixer_status'];
		} else {
			$data['fixer_status'] = $this->config->get('fixer_status');
		}

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('extension/currency/fixer', $data));
	}

	protected function validate(): bool {
		if (!$this->user->hasPermission('modify', 'extension/currency/fixer')) {
			$this->error['warning'] = $this->language->get('error_permission');
		}

		if (!$this->request->post['fixer_api']) {
			$this->error['api'] = $this->language->get('error_api');
		}

		return !$this->error;
	}

	public function currency(string $default = ''): void {
		if ($this->config->get('fixer_status')) {
			$curl = curl_init();

			curl_setopt($curl, CURLOPT_URL, 'http://data.fixer.io/api/latest?access_key=' . $this->config->get('fixer_api'));
			curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($curl, CURLOPT_HEADER, false);
			curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
			curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 30);
			curl_setopt($curl, CURLOPT_TIMEOUT, 30);

			$response = curl_exec($curl);

			curl_close($curl);

			$response_info = json_decode($response, true);

			if (is_array($response_info) && isset($response_info['rates'])) {
				// Compile all the rates into an array
				$currencies = array();

				$currencies['EUR'] = 1.0000;

				foreach ($response_info['rates'] as $key => $value) {
					$currencies[$key] = $value;
				}

				$this->load->model('localisation/currency');

				$results = $this->model_localisation_currency->getCurrencies();

				foreach ($results as $result) {
					if (isset($currencies[$result['code']])) {
						$from = $currencies['EUR'];

						$to = $currencies[$result['code']];

						$this->model_localisation_currency->editValueByCode($result['code'], 1 / ($currencies[$default] * ($from / $to)));
					}
				}

				$this->model_localisation_currency->editValueByCode($default, 1);

				$this->cache->delete('currency');
			}
		}
	}
}