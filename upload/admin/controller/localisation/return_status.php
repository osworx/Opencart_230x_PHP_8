<?php
class ControllerLocalisationReturnStatus extends Controller {
	private $error = array();

	public function index(): void {
		$this->load->language('localisation/return_status');

		$this->document->setTitle($this->language->get('heading_title'));

		$this->load->model('localisation/return_status');

		$this->getList();
	}

	public function add(): void {
		$this->load->language('localisation/return_status');

		$this->document->setTitle($this->language->get('heading_title'));

		$this->load->model('localisation/return_status');

		if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validateForm()) {
			$this->model_localisation_return_status->addReturnStatus($this->request->post);

			$this->session->data['success'] = $this->language->get('text_success');

			$url = '';

			if (isset($this->request->get['sort'])) {
				$url .= '&sort=' . $this->request->get['sort'];
			}

			if (isset($this->request->get['order'])) {
				$url .= '&order=' . $this->request->get['order'];
			}

			if (isset($this->request->get['page'])) {
				$url .= '&page=' . $this->request->get['page'];
			}

			$this->response->redirect($this->url->link('localisation/return_status', 'token=' . $this->session->data['token'] . $url, true));
		}

		$this->getForm();
	}

	public function edit(): void {
		$this->load->language('localisation/return_status');

		$this->document->setTitle($this->language->get('heading_title'));

		$this->load->model('localisation/return_status');

		if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validateForm()) {
			$this->model_localisation_return_status->editReturnStatus($this->request->get['return_status_id'], $this->request->post);

			$this->session->data['success'] = $this->language->get('text_success');

			$url = '';

			if (isset($this->request->get['sort'])) {
				$url .= '&sort=' . $this->request->get['sort'];
			}

			if (isset($this->request->get['order'])) {
				$url .= '&order=' . $this->request->get['order'];
			}

			if (isset($this->request->get['page'])) {
				$url .= '&page=' . $this->request->get['page'];
			}

			$this->response->redirect($this->url->link('localisation/return_status', 'token=' . $this->session->data['token'] . $url, true));
		}

		$this->getForm();
	}

	public function delete(): void {
		$this->load->language('localisation/return_status');

		$this->document->setTitle($this->language->get('heading_title'));

		$this->load->model('localisation/return_status');

		if (isset($this->request->post['selected']) && $this->validateDelete()) {
			foreach ($this->request->post['selected'] as $return_status_id) {
				$this->model_localisation_return_status->deleteReturnStatus($return_status_id);
			}

			$this->session->data['success'] = $this->language->get('text_success');

			$url = '';

			if (isset($this->request->get['sort'])) {
				$url .= '&sort=' . $this->request->get['sort'];
			}

			if (isset($this->request->get['order'])) {
				$url .= '&order=' . $this->request->get['order'];
			}

			if (isset($this->request->get['page'])) {
				$url .= '&page=' . $this->request->get['page'];
			}

			$this->response->redirect($this->url->link('localisation/return_status', 'token=' . $this->session->data['token'] . $url, true));
		}

		$this->getList();
	}

	protected function getList(): void {
		if (isset($this->request->get['sort'])) {
			$sort = $this->request->get['sort'];
		} else {
			$sort = 'name';
		}

		if (isset($this->request->get['order'])) {
			$order = $this->request->get['order'];
		} else {
			$order = 'ASC';
		}

		if (isset($this->request->get['page'])) {
			$page = (int)$this->request->get['page'];
		} else {
			$page = 1;
		}

		$url = '';

		if (isset($this->request->get['sort'])) {
			$url .= '&sort=' . $this->request->get['sort'];
		}

		if (isset($this->request->get['order'])) {
			$url .= '&order=' . $this->request->get['order'];
		}

		if (isset($this->request->get['page'])) {
			$url .= '&page=' . $this->request->get['page'];
		}

		$data['breadcrumbs'] = array();

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/dashboard', 'token=' . $this->session->data['token'], true)
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('heading_title'),
			'href' => $this->url->link('localisation/return_status', 'token=' . $this->session->data['token'] . $url, true)
		);

		$data['add'] = $this->url->link('localisation/return_status/add', 'token=' . $this->session->data['token'] . $url, true);
		$data['delete'] = $this->url->link('localisation/return_status/delete', 'token=' . $this->session->data['token'] . $url, true);

		$data['return_statuses'] = array();

		$filter_data = array(
			'sort'  => $sort,
			'order' => $order,
			'start' => ($page - 1) * $this->config->get('config_limit_admin'),
			'limit' => $this->config->get('config_limit_admin')
		);

		$return_status_total = $this->model_localisation_return_status->getTotalReturnStatuses();

		$results = $this->model_localisation_return_status->getReturnStatuses($filter_data);

		foreach ($results as $result) {
			$data['return_statuses'][] = array(
				'return_status_id' => $result['return_status_id'],
				'name'             => $result['name'] . (($result['return_status_id'] == $this->config->get('config_return_status_id')) ? $this->language->get('text_default') : null),
				'edit'             => $this->url->link('localisation/return_status/edit', 'token=' . $this->session->data['token'] . '&return_status_id=' . $result['return_status_id'] . $url, true)
			);
		}

		$data['heading_title'] = $this->language->get('heading_title');

		$data['text_list'] = $this->language->get('text_list');
		$data['text_no_results'] = $this->language->get('text_no_results');
		$data['text_confirm'] = $this->language->get('text_confirm');

		$data['column_name'] = $this->language->get('column_name');
		$data['column_action'] = $this->language->get('column_action');

		$data['button_add'] = $this->language->get('button_add');
		$data['button_edit'] = $this->language->get('button_edit');
		$data['button_delete'] = $this->language->get('button_delete');

		if (isset($this->error['warning'])) {
			$data['error_warning'] = $this->error['warning'];
		} else {
			$data['error_warning'] = '';
		}

		if (isset($this->session->data['success'])) {
			$data['success'] = $this->session->data['success'];

			unset($this->session->data['success']);
		} else {
			$data['success'] = '';
		}

		if (isset($this->request->post['selected'])) {
			$data['selected'] = (array)$this->request->post['selected'];
		} else {
			$data['selected'] = array();
		}

		$url = '';

		if ($order == 'ASC') {
			$url .= '&order=DESC';
		} else {
			$url .= '&order=ASC';
		}

		if (isset($this->request->get['page'])) {
			$url .= '&page=' . $this->request->get['page'];
		}

		$data['sort_name'] = $this->url->link('localisation/return_status', 'token=' . $this->session->data['token'] . '&sort=name' . $url, true);

		$url = '';

		if (isset($this->request->get['sort'])) {
			$url .= '&sort=' . $this->request->get['sort'];
		}

		if (isset($this->request->get['order'])) {
			$url .= '&order=' . $this->request->get['order'];
		}

		$pagination = new Pagination();
		$pagination->total = $return_status_total;
		$pagination->page = $page;
		$pagination->limit = $this->config->get('config_limit_admin');
		$pagination->url = $this->url->link('localisation/return_status', 'token=' . $this->session->data['token'] . $url . '&page={page}', true);

		$data['pagination'] = $pagination->render();

		$data['results'] = sprintf($this->language->get('text_pagination'), ($return_status_total) ? (($page - 1) * $this->config->get('config_limit_admin')) + 1 : 0, ((($page - 1) * $this->config->get('config_limit_admin')) > ($return_status_total - $this->config->get('config_limit_admin'))) ? $return_status_total : ((($page - 1) * $this->config->get('config_limit_admin')) + $this->config->get('config_limit_admin')), $return_status_total, ceil($return_status_total / $this->config->get('config_limit_admin')));

		$data['sort'] = $sort;
		$data['order'] = $order;

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('localisation/return_status_list', $data));
	}

	protected function getForm(): void {
		$data['heading_title'] = $this->language->get('heading_title');

		$data['text_form'] = !isset($this->request->get['return_status_id']) ? $this->language->get('text_add') : $this->language->get('text_edit');

		$data['entry_name'] = $this->language->get('entry_name');

		$data['button_save'] = $this->language->get('button_save');
		$data['button_cancel'] = $this->language->get('button_cancel');

		if (isset($this->error['warning'])) {
			$data['error_warning'] = $this->error['warning'];
		} else {
			$data['error_warning'] = '';
		}

		if (isset($this->error['name'])) {
			$data['error_name'] = $this->error['name'];
		} else {
			$data['error_name'] = array();
		}

		$url = '';

		if (isset($this->request->get['sort'])) {
			$url .= '&sort=' . $this->request->get['sort'];
		}

		if (isset($this->request->get['order'])) {
			$url .= '&order=' . $this->request->get['order'];
		}

		if (isset($this->request->get['page'])) {
			$url .= '&page=' . $this->request->get['page'];
		}

		$data['breadcrumbs'] = array();

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/dashboard', 'token=' . $this->session->data['token'], true)
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('heading_title'),
			'href' => $this->url->link('localisation/return_status', 'token=' . $this->session->data['token'] . $url, true)
		);

		if (!isset($this->request->get['return_status_id'])) {
			$data['action'] = $this->url->link('localisation/return_status/add', 'token=' . $this->session->data['token'] . $url, true);
		} else {
			$data['action'] = $this->url->link('localisation/return_status/edit', 'token=' . $this->session->data['token'] . '&return_status_id=' . $this->request->get['return_status_id'] . $url, true);
		}

		$data['cancel'] = $this->url->link('localisation/return_status', 'token=' . $this->session->data['token'] . $url, true);

		$this->load->model('localisation/language');

		$data['languages'] = $this->model_localisation_language->getLanguages();

		if (isset($this->request->post['return_status'])) {
			$data['return_status'] = $this->request->post['return_status'];
		} elseif (isset($this->request->get['return_status_id'])) {
			$data['return_status'] = $this->model_localisation_return_status->getReturnStatusDescriptions($this->request->get['return_status_id']);
		} else {
			$data['return_status'] = array();
		}

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('localisation/return_status_form', $data));
	}

	protected function validateForm(): bool {
		if (!$this->user->hasPermission('modify', 'localisation/return_status')) {
			$this->error['warning'] = $this->language->get('error_permission');
		}

		foreach ($this->request->post['return_status'] as $language_id => $value) {
			if ((utf8_strlen($value['name']) < 3) || (utf8_strlen($value['name']) > 32)) {
				$this->error['name'][$language_id] = $this->language->get('error_name');
			}
		}

		return !$this->error;
	}

	protected function validateDelete(): bool {
		if (!$this->user->hasPermission('modify', 'localisation/return_status')) {
			$this->error['warning'] = $this->language->get('error_permission');
		}

		$this->load->model('sale/return');

		foreach ($this->request->post['selected'] as $return_status_id) {
			if ($this->config->get('config_return_status_id') == $return_status_id) {
				$this->error['warning'] = $this->language->get('error_default');
			}

			$return_total = $this->model_sale_return->getTotalReturnsByReturnStatusId($return_status_id);

			if ($return_total) {
				$this->error['warning'] = sprintf($this->language->get('error_return'), $return_total);
			}

			$return_total = $this->model_sale_return->getTotalReturnHistoriesByReturnStatusId($return_status_id);

			if ($return_total) {
				$this->error['warning'] = sprintf($this->language->get('error_return'), $return_total);
			}
		}

		return !$this->error;
	}
}