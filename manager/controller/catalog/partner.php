<?php
class ControllerCatalogPartner extends Controller {
	private $error = array();

	public function index() {
		$this->load->language('catalog/partner');

		$this->document->setTitle($this->language->get('heading_title'));

		$this->load->model('catalog/partner');

		$this->getList();
	}
	public function upload_file(){
		$this->load->language('catalog/partner');
		$this->load->model('catalog/partner');
		$json = array();
		if ($this->request->server['REQUEST_METHOD'] == 'POST' ) {
			$partner_id = $this->request->get['partner_id'];
			if (!empty($_FILES['file_0']) && is_file($_FILES['file_0']['tmp_name'])) {
				$file = $_FILES['file_0']['tmp_name'];
				$json = $this->model_catalog_partner->upload($partner_id,$file);
				
			}elseif (!empty($this->request->post['partner_fid_url'])) {
				$file = $this->request->post['partner_fid_url'];
				$json = $this->model_catalog_partner->upload($partner_id,$file);
				
			}else{
						$json = array();
			}
			
		}else{
			$json = array();
		}
		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));

	}

	public function add() {
		$this->load->language('catalog/partner');

		$this->document->setTitle($this->language->get('heading_title'));

		$this->load->model('catalog/partner');

		if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validateForm()) {
			$this->model_catalog_partner->addPartner($this->request->post);

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

			$this->response->redirect($this->url->link('catalog/partner', 'user_token=' . $this->session->data['user_token'] . $url, true));
		}

		$this->getForm();
	}

	public function edit() {
		$this->load->language('catalog/partner');

		$this->document->setTitle($this->language->get('heading_title'));

		$this->load->model('catalog/partner');

		if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validateForm()) {
			$this->log->write($this->request->post);
			$this->model_catalog_partner->editPartner($this->request->get['partner_id'], $this->request->post);

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

			$this->response->redirect($this->url->link('catalog/partner', 'user_token=' . $this->session->data['user_token'] . $url, true));
		}

		$this->getForm();
	}

	public function delete() {
		$this->load->language('catalog/partner');

		$this->document->setTitle($this->language->get('heading_title'));

		$this->load->model('catalog/partner');

		if (isset($this->request->post['selected']) && $this->validateDelete()) {
			foreach ($this->request->post['selected'] as $partner_id) {
				$this->model_catalog_partner->deletePartner($partner_id);
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

			$this->response->redirect($this->url->link('catalog/partner', 'user_token=' . $this->session->data['user_token'] . $url, true));
		}

		$this->getList();
	}

	protected function getList() {
		if (isset($this->request->get['sort'])) {
			$sort = $this->request->get['sort'];
		} else {
			$sort = 'partner_name';
		}

		if (isset($this->request->get['order'])) {
			$order = $this->request->get['order'];
		} else {
			$order = 'ASC';
		}

		if (isset($this->request->get['page'])) {
			$page = $this->request->get['page'];
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
			'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('heading_title'),
			'href' => $this->url->link('catalog/partner', 'user_token=' . $this->session->data['user_token'] . $url, true)
		);

		$data['add'] = $this->url->link('catalog/partner/add', 'user_token=' . $this->session->data['user_token'] . $url, true);
		$data['delete'] = $this->url->link('catalog/partner/delete', 'user_token=' . $this->session->data['user_token'] . $url, true);

		$data['partners'] = array();

		$filter_data = array(
			'sort'  => $sort,
			'order' => $order,
			'start' => ($page - 1) * $this->config->get('config_limit_admin'),
			'limit' => $this->config->get('config_limit_admin')
		);

		$partner_total = $this->model_catalog_partner->getTotalPartners();

		$results = $this->model_catalog_partner->getPartners($filter_data);

		foreach ($results as $result) {
			$data['partners'][] = array(
				'partner_id' => $result['partner_id'],
				'partner_code'            => $result['partner_code'],
				'partner_name'            => $result['partner_name'],
				'edit'            => $this->url->link('catalog/partner/edit', 'user_token=' . $this->session->data['user_token'] . '&partner_id=' . $result['partner_id'] . $url, true)
			);
		}

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

		$data['sort_name'] = $this->url->link('catalog/partner', 'user_token=' . $this->session->data['user_token'] . '&sort=partner_name' . $url, true);
		$data['sort_sort_order'] = $this->url->link('catalog/partner', 'user_token=' . $this->session->data['user_token'] . '&sort=sort_order' . $url, true);

		$url = '';

		if (isset($this->request->get['sort'])) {
			$url .= '&sort=' . $this->request->get['sort'];
		}

		if (isset($this->request->get['order'])) {
			$url .= '&order=' . $this->request->get['order'];
		}

		$pagination = new Pagination();
		$pagination->total = $partner_total;
		$pagination->page = $page;
		$pagination->limit = $this->config->get('config_limit_admin');
		$pagination->url = $this->url->link('catalog/partner', 'user_token=' . $this->session->data['user_token'] . $url . '&page={page}', true);

		$data['pagination'] = $pagination->render();

		$data['results'] = sprintf($this->language->get('text_pagination'), ($partner_total) ? (($page - 1) * $this->config->get('config_limit_admin')) + 1 : 0, ((($page - 1) * $this->config->get('config_limit_admin')) > ($partner_total - $this->config->get('config_limit_admin'))) ? $partner_total : ((($page - 1) * $this->config->get('config_limit_admin')) + $this->config->get('config_limit_admin')), $partner_total, ceil($partner_total / $this->config->get('config_limit_admin')));

		$data['sort'] = $sort;
		$data['order'] = $order;

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('catalog/partner_list', $data));
	}

	public function get_parents($tree, $search_key, &$parents) { // Функция определения всех потомков где $tree - массив, $search_key - ключ, $parents - цепочка потомков
		
		if(is_array($tree)) {
			
			foreach($tree as $key => $value) {
			
				if($key == $search_key) {
				
					$parents[] = $value["name"];
				
					return true;
			
				} elseif($value && $this->get_parents($value, $search_key, $parents)) {
				
					$parents[] = $value["name"];
				
					return true;
			
				}

			}
 
			return false;

		}

	}

	protected function getForm() {
		$data['text_form'] = !isset($this->request->get['partner_id']) ? $this->language->get('text_add') : $this->language->get('text_edit');

		if (isset($this->error['warning'])) {
			$data['error_warning'] = $this->error['warning'];
		} else {
			$data['error_warning'] = '';
		}

		if (isset($this->error['name'])) {
			$data['error_name'] = $this->error['name'];
		} else {
			$data['error_name'] = '';
		}

		if (isset($this->error['keyword'])) {
			$data['error_keyword'] = $this->error['keyword'];
		} else {
			$data['error_keyword'] = '';
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
			'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('heading_title'),
			'href' => $this->url->link('catalog/partner', 'user_token=' . $this->session->data['user_token'] . $url, true)
		);

		if (!isset($this->request->get['partner_id'])) {
			$data['action'] = $this->url->link('catalog/partner/add', 'user_token=' . $this->session->data['user_token'] . $url, true);
		} else {
			$data['action'] = $this->url->link('catalog/partner/edit', 'user_token=' . $this->session->data['user_token'] . '&partner_id=' . $this->request->get['partner_id'] . $url, true);
		}

		$data['cancel'] = $this->url->link('catalog/partner', 'user_token=' . $this->session->data['user_token'] . $url, true);

		if (isset($this->request->get['partner_id']) && ($this->request->server['REQUEST_METHOD'] != 'POST')) {
			$partner_info = $this->model_catalog_partner->getPartner($this->request->get['partner_id']);
			$data['partner_id'] = $this->request->get['partner_id'];
		}else{
			$data['partner_id'] = '';
		}

		$data['user_token'] = $this->session->data['user_token'];

		if (isset($this->request->post['partner_name'])) {
			$data['partner_name'] = $this->request->post['partner_name'];
		} elseif (!empty($partner_info)) {
			$data['partner_name'] = $partner_info['partner_name'];
		} else {
			$data['partner_name'] = '';
		}
		
		if (isset($this->request->post['partner_code'])) {
			$data['partner_code'] = $this->request->post['partner_code'];
		} elseif (!empty($partner_info)) {
			$data['partner_code'] = $partner_info['partner_code'];
		} else {
			$data['partner_code'] = '';
		}
		
		if (isset($this->request->post['partner_fio'])) {
			$data['partner_fio'] = $this->request->post['partner_fio'];
		} elseif (!empty($partner_info)) {
			$data['partner_fio'] = $partner_info['partner_fio'];
		} else {
			$data['partner_fio'] = '';
		}

		if (isset($this->request->post['partner_phone'])) {
			$data['partner_phone'] = $this->request->post['partner_phone'];
		} elseif (!empty($partner_info)) {
			$data['partner_phone'] = $partner_info['partner_phone'];
		} else {
			$data['partner_phone'] = '';
		}

		if (isset($this->request->post['partner_email'])) {
			$data['partner_email'] = $this->request->post['partner_email'];
		} elseif (!empty($partner_info)) {
			$data['partner_email'] = $partner_info['partner_email'];
		} else {
			$data['partner_email'] = '';
		}

		if (isset($this->request->post['partner_fid_url'])) {
			$data['partner_fid_url'] = $this->request->post['partner_fid_url'];
		} elseif (!empty($partner_info)) {
			$data['partner_fid_url'] = $partner_info['partner_fid_url'];
		} else {
			$data['partner_fid_url'] = '';
		}

		if (isset($this->request->post['partner_date_fid'])) {
			$data['partner_date_fid'] = $this->request->post['partner_date_fid'];
		} elseif (!empty($partner_info)) {
			$data['partner_date_fid'] = $partner_info['partner_date_fid'];
		} else {
			$data['partner_date_fid'] = '';
		}



		$this->load->model('catalog/category');

		$data['categories'] = array();

		$categories_filter_data = array(
			'sort' => 'name',
			'start' => 0,
			'limit' => 1000
		);

		$results = $this->model_catalog_category->getCategories($categories_filter_data);

		foreach ($results as $result) {
			$data['categories'][] = array(
				'category_id' => $result['category_id'],
				'name'        => $result['name']
			);
		}

		$data['partner_categories'] = array();
		$partner_categories = $this->model_catalog_partner->getPartnerCategories($this->request->get['partner_id']);

		foreach ($partner_categories as $category) {
			$raw_partner_categories[$category['partner_category_id']] = array(
				'partner_category_id' => $category['partner_category_id'],
				'name'        => $category['name'],
				'selected' => $category['selected'],
				'description' => $category['description'],
				'category' => $category['category'],
				'nacenka' => $category['nacenka'],
				'parent_id' => $category['parent_id']
			);
			$category_list_id[] = (String)$category['partner_category_id'];
		}
		$tree["0"] = ['partner_category_id' => "", 'parent_id' => "", 'name' => "",'selected' => '',
				'description' => '',
				'category' => '',
				'nacenka' => '']; // Начало дерева вложенных категорий
		foreach($raw_partner_categories as $category_item){
			$tree[$category_item['partner_category_id']] = $category_item;
		}
		// Формируем дерево категорий согласно их иерархии
	
		foreach($tree as $category_id => $category_value) {
			
			if(isset($tree[$category_value["parent_id"]])) {
				
				$tree[$category_value["parent_id"]][$category_id] = &$tree[$category_id];
			
			}
		
		}
		$separator = " > "; 
		// Переобходим категории, составляем их цепочки и сохраняем результат

		foreach($category_list_id as $key) {
			
			$parents = [];

			$this->get_parents($tree[0], $key, $parents);

			$parents = array_reverse($parents, true);
			$data['partner_categories'][] = array(
				'partner_category_id' => $raw_partner_categories[$key]['partner_category_id'],
				'name'        => implode($separator, $parents),
				'selected' => $raw_partner_categories[$key]['selected'],
				'description' => $raw_partner_categories[$key]['description'],
				'category' => $raw_partner_categories[$key]['category'],
				'nacenka' => $raw_partner_categories[$key]['nacenka'],
				'parent_id' => $raw_partner_categories[$key]['parent_id']
			);

		}
		

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('catalog/partner_form', $data));
	}

	protected function validateForm() {
		if (!$this->user->hasPermission('modify', 'catalog/partner')) {
			$this->error['warning'] = $this->language->get('error_permission');
		}

		if ((utf8_strlen($this->request->post['partner_name']) < 1) || (utf8_strlen($this->request->post['partner_name']) > 256)) {
			$this->error['partner_name'] = $this->language->get('error_name');
		}

		

		return !$this->error;
	}

	protected function validateDelete() {
		if (!$this->user->hasPermission('modify', 'catalog/partner')) {
			$this->error['warning'] = $this->language->get('error_permission');
		}

		$this->load->model('catalog/product');

		foreach ($this->request->post['selected'] as $partner_id) {
			$product_total = $this->model_catalog_product->getTotalProductsByPartnerId($partner_id);

			if ($product_total) {
				$this->error['warning'] = sprintf($this->language->get('error_product'), $product_total);
			}
		}

		return !$this->error;
	}

	public function autocomplete() {
		$json = array();

		if (isset($this->request->get['filter_name'])) {
			$this->load->model('catalog/partner');

			$filter_data = array(
				'filter_name' => $this->request->get['filter_name'],
				'start'       => 0,
				'limit'       => 5
			);

			$results = $this->model_catalog_partner->getPartners($filter_data);

			foreach ($results as $result) {
				$json[] = array(
					'partner_id' => $result['partner_id'],
					'partner_name'            => strip_tags(html_entity_decode($result['partner_name'], ENT_QUOTES, 'UTF-8'))
				);
			}
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}
}