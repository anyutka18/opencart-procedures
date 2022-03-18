<?php
class ModelCatalogPartner extends Model {
	public function addPartner($data) {
		$this->db->query("INSERT INTO " . DB_PREFIX . "partner SET 
			partner_name = '" . $this->db->escape($data['partner_name']) . "', 
			partner_fio = '" . $this->db->escape($data['partner_fio']) . "', 
			partner_phone = '" . $this->db->escape($data['partner_phone']) . "', 
			partner_email = '" . $this->db->escape($data['partner_email']) . "', 
			partner_fid_url = '" . $this->db->escape($data['partner_fid_url']) . "',
			partner_date_fid = '" . $this->db->escape($data['partner_date_fid']) . "',
			partner_date_edit = NOW()");

		$partner_id = $this->db->getLastId();

		if (isset($partner_id)) {
			$this->db->query("UPDATE " . DB_PREFIX . "partner SET partner_code = 'P-" . $partner_id . "' WHERE partner_id = '" . (int)$partner_id . "'");
		}

		
		$this->cache->delete('partner');

		return $partner_id;
	}

	public function editPartner($partner_id, $data) {
		$this->db->query("UPDATE " . DB_PREFIX . "partner SET 
			partner_name = '" . $this->db->escape($data['partner_name']) . "', 
			partner_fio = '" . $this->db->escape($data['partner_fio']) . "', 
			partner_phone = '" . $this->db->escape($data['partner_phone']) . "', 
			partner_email = '" . $this->db->escape($data['partner_email']) . "', 
			partner_fid_url = '" . $this->db->escape($data['partner_fid_url']) . "',
			partner_date_fid = '" . $this->db->escape($data['partner_date_fid']) . "',
			partner_date_edit = NOW() WHERE partner_id = '".(int)$partner_id."'");

		$old_products = $this->db->query("SELECT * FROM " . DB_PREFIX . "product WHERE partner_id = '".(int)$partner_id."'");
		//$this->log->write('--- Products of partner -----');
		//$this->log->write($old_products);
		//$this->log->write('--- Products of partner -----');
		if ($old_products->num_rows) {

			foreach ($old_products->rows as $value) {
										
				$old_product_id_old = $value['product_id'];
				//	$this->log->write($this->db->query("SELECT * FROM " . DB_PREFIX . "product WHERE product_id = '".(int)$old_product_id_old."'"));
					$this->db->query("DELETE FROM " . DB_PREFIX . "product WHERE product_id = '".(int)$old_product_id_old."'");
				//	$this->log->write($this->db->query("SELECT * FROM " . DB_PREFIX . "product_description WHERE product_id = '".(int)$old_product_id_old."'"));
					$this->db->query("DELETE FROM " . DB_PREFIX . "product_description WHERE product_id = '".(int)$old_product_id_old."'");
				//	$this->log->write($this->db->query("SELECT * FROM " . DB_PREFIX . "product_to_category WHERE product_id = '".(int)$old_product_id_old."'"));
					$this->db->query("DELETE FROM " . DB_PREFIX . "product_to_category WHERE product_id = '" . (int)$old_product_id_old . "'");
				//	$this->log->write($this->db->query("SELECT * FROM " . DB_PREFIX . "product_attribute WHERE product_id = '".(int)$old_product_id_old."'"));
					$this->db->query("DELETE FROM " . DB_PREFIX . "product_attribute WHERE product_id = '" . (int)$old_product_id_old . "' ");
					$this->db->query("DELETE FROM " . DB_PREFIX . "product_to_store WHERE product_id = '" . (int)$old_product_id_old . "' ");
			}
		}


		//$this->db->query("DELETE FROM `" . DB_PREFIX . "product_to_partner` WHERE partner_id = '" . (int)$partner_id . "'");
		$settings = $this->getSettings($partner_id);
		if (isset($data['categories']) && !empty($data['categories'])) {
			$this->log->write(print_r($data['categories'],true));
			foreach ($data['categories'] as $key => $category) {
				$products = array();
				if (isset($category['selected']) && !empty($category['selected'])) {
					if (isset($category['category']) && !empty($category['category'])) {


						if (isset($category['nacenka']) && !empty($category['nacenka'])) {
								$nacenka = (100 + (int)$category['nacenka']) / 100;
						}else{
								$nacenka = 1;
						}
						if (isset($category['description']) && !empty($category['description'])) {
							$description = 1;
						}else{
							$description = 0;
						}
						$site_category = $category['category'];

						$this->db->query("UPDATE " . DB_PREFIX . "partner_to_category SET 
						selected = 1, 
						description = ".$description.",
						category = '".$site_category."',
						nacenka = '".(float)$category['nacenka']."'
						WHERE partner_category_id = '".$key."' AND  partner_id = '".$partner_id."'");

						$products = $this->getPartnerProductsByCategory($key, $partner_id);

						//$this->log->write($products);
						foreach ($products as $product) {
							$exists = false;
							
							if ($product['model'] == '' && $product['barcode'] == '') {
								$exists = true;
							}
							if ($exists === false) {
								$this->log->write('Product with model '.$product['model'].' not exists');
								$this->db->query("INSERT INTO " . DB_PREFIX . "product SET model = '" . $this->db->escape($product['model']) . "',sku = '" . $this->db->escape($product['model']) . "',  
								price = '" . round((float)$product['price'] * $nacenka, 2) . "', 
								quantity = 1000,
								status = 1, 
								in_order = 1,
								partner_id = '".(int)$partner_id."',
								partner_product_id = '".(int)$product['product_partner_id']."',
								image = '" . $this->db->escape($product['image']) . "',
								date_added = NOW(), date_modified = NOW()");
								$product_id = $this->db->getLastId();
								if ($product_id > 0) {
									$this->db->query("INSERT INTO " . DB_PREFIX . "product_description SET product_id = '" . (int)$product_id . "', 
									language_id = '" . (int)$this->config->get('config_language_id') . "', 
									name = '" . $this->db->escape($product['name']) . "', 
									meta_title = '" . $this->db->escape($product['name']) . "',
									description = '" . ($description > 0 ? $this->db->escape($product['description']) : ''). "'");

									if (isset($site_category)) {
											$this->db->query("INSERT INTO " . DB_PREFIX . "product_to_category SET product_id = '" . (int)$product_id . "', category_id = '" . (int)$site_category . "'");

									}
									$this->db->query("INSERT INTO " . DB_PREFIX . "product_to_store SET product_id = '" . (int)$product_id . "', store_id = '" . (int)$this->config->get('config_store_id') . "'");
									$this->db->query("DELETE FROM " . DB_PREFIX . "product_attribute WHERE product_id = '" . (int)$product_id . "' ");
									$this->db->query("INSERT INTO " . DB_PREFIX . "product_attribute SET product_id = '" . (int)$product_id . "', attribute_id = '12', language_id = '" . (int)$this->config->get('config_language_id') . "', text = '" .  $this->db->escape($product['country']) . "'");
									$this->db->query("INSERT INTO " . DB_PREFIX . "product_attribute SET product_id = '" . (int)$product_id . "', attribute_id = '13', language_id = '" . (int)$this->config->get('config_language_id') . "', text = '" .  $this->db->escape($product['vendor']) . "'");
								}
							}

							//$this->log->write($product_id );
						}

					}
				}else{
					$this->db->query("UPDATE " . DB_PREFIX . "partner_to_category SET 
						selected = 0, 
						description = '',
						category = '',
						nacenka = ''
						WHERE partner_category_id = '".$key."' AND  partner_id = '".$partner_id."'");
					$products = $this->getPartnerProductsByCategory($key, $partner_id);

						//$this->log->write('--- Products of partner if not selected category -----');
					//	$this->log->write($products);
					//	$this->log->write('--- Products of partner -----');
					if (isset($products) && count($products) > 0) {
						foreach ($products as $product) {
							$product_data = $this->db->query("SELECT * FROM " . DB_PREFIX . "product WHERE partner_product_id = '".(int)$product['product_partner_id']."'");
								//	$this->log->write('--- Products of partner if not selected category for delete products -----');
								///	$this->log->write($product_data);
								//	$this->log->write('--- Products of partner -----');
								if ($product_data->num_rows) {

									foreach ($product_data->rows as $value) {
										
										$product_id_old = $value['product_id'];
										if (isset($product_id_old) && !empty($product_id_old)) {
											$this->db->query("DELETE FROM " . DB_PREFIX . "product WHERE product_id = '".(int)$product_id_old."'");
											$this->db->query("DELETE FROM " . DB_PREFIX . "product_description WHERE product_id = '".(int)$product_id_old."'");
											$this->db->query("DELETE FROM " . DB_PREFIX . "product_to_category WHERE product_id = '" . (int)$product_id_old . "'");
											$this->db->query("DELETE FROM " . DB_PREFIX . "product_attribute WHERE product_id = '" . (int)$product_id_old . "' ");
										}
									}
								}
						}
					}
				}
			}
		}else{
			$this->db->query("UPDATE " . DB_PREFIX . "partner_to_category SET 
						selected = 0, 
						description = ".$description.",
						category = '".$site_category."',
						nacenka = '".(float)$category['nacenka']."'
						WHERE partner_id = '".$partner_id."'");
			$categories = $this->getPartnerCategories($partner_id);
			foreach ($categories as $category) {
				$products = $this->getPartnerProductsByCategory($category['partner_category_id'], $partner_id);

					if (isset($products) && $products->num_rows > 0) {
						foreach ($products as $product) {
							$product_data = $this->db->query("SELECT * FROM " . DB_PREFIX . "product WHERE partner_product_id = '".(int)$product['product_partner_id']."'");
								//	$this->log->write('--- Products of partner if not selected category for delete products -----');
								///	$this->log->write($product_data);
								//	$this->log->write('--- Products of partner -----');
								if ($product_data->num_rows) {

									foreach ($product_data->rows as $value) {
										
										$product_id_old = $value['product_id'];
										if (isset($product_id_old) && !empty($product_id_old)) {
											$this->db->query("DELETE FROM " . DB_PREFIX . "product WHERE product_id = '".(int)$product_id_old."'");
											$this->db->query("DELETE FROM " . DB_PREFIX . "product_description WHERE product_id = '".(int)$product_id_old."'");
											$this->db->query("DELETE FROM " . DB_PREFIX . "product_to_category WHERE product_id = '" . (int)$product_id_old . "'");
											$this->db->query("DELETE FROM " . DB_PREFIX . "product_attribute WHERE product_id = '" . (int)$product_id_old . "' ");
										}
									}
								}
						}
					}
			}
		}

		$this->cache->delete('partner');
	}

	public function deletePartner($partner_id) {
		$this->db->query("DELETE FROM `" . DB_PREFIX . "partner` WHERE partner_id = '" . (int)$partner_id . "'");
		$this->db->query("DELETE FROM `" . DB_PREFIX . "partner_to_category` WHERE partner_id = '" . (int)$partner_id . "'");
		$products = $this->getPartnerProducts($partner_id);
		if (isset($products) && !empty($products)) {
			foreach ($products as $product) {
				$product_id = $this->db->query("SELECT product_id FROM " . DB_PREFIX . "product WHERE partner_product_id = '".(int)$product['product_partner_id']."'")->row['product_id'];
				if (isset($product_id) && !empty($product_id)) {
					$this->db->query("DELETE FROM " . DB_PREFIX . "product WHERE product_id = '".(int)$product_id."'");
					$this->db->query("DELETE FROM " . DB_PREFIX . "product_description WHERE product_id = '".(int)$product_id."'");
					$this->db->query("DELETE FROM " . DB_PREFIX . "product_to_category WHERE product_id = '" . (int)$product_id . "'");
					$this->db->query("DELETE FROM " . DB_PREFIX . "product_to_store WHERE product_id = '" . (int)$product_id . "'");
					$this->db->query("DELETE FROM " . DB_PREFIX . "product_attribute WHERE product_id = '" . (int)$product_id . "' ");
				}
			}
		}
		$this->db->query("DELETE FROM `" . DB_PREFIX . "product_to_partner` WHERE partner_id = '" . (int)$partner_id . "'");

		$this->cache->delete('partner');
	}

	public function getPartner($partner_id) {
		$query = $this->db->query("SELECT DISTINCT * FROM " . DB_PREFIX . "partner WHERE partner_id = '" . (int)$partner_id . "'");

		return $query->row;
	}

	public function getPartners($data = array()) {
		$sql = "SELECT * FROM " . DB_PREFIX . "partner";

		if (!empty($data['filter_name'])) {
			$sql .= " WHERE partner_name LIKE '" . $this->db->escape($data['filter_name']) . "%'";
		}

		$sort_data = array(
			'name'
		);

		if (isset($data['sort']) && in_array($data['sort'], $sort_data)) {
			$sql .= " ORDER BY " . $data['sort'];
		} else {
			$sql .= " ORDER BY partner_name";
		}

		if (isset($data['order']) && ($data['order'] == 'DESC')) {
			$sql .= " DESC";
		} else {
			$sql .= " ASC";
		}

		if (isset($data['start']) || isset($data['limit'])) {
			if ($data['start'] < 0) {
				$data['start'] = 0;
			}

			if ($data['limit'] < 1) {
				$data['limit'] = 20;
			}

			$sql .= " LIMIT " . (int)$data['start'] . "," . (int)$data['limit'];
		}

		$query = $this->db->query($sql);

		return $query->rows;
	}
	
	public function getTotalPartners() {
		$query = $this->db->query("SELECT COUNT(*) AS total FROM " . DB_PREFIX . "partner");

		return $query->row['total'];
	}

	public function upload( $partner_id,$filename ) {
		$data = simplexml_load_file($filename);

		$json = array();


		$this->log->write(print_r($data, true));

		$json['date_fid'] = strval($data['date']);
		$this->db->query("UPDATE `" . DB_PREFIX . "partner` SET partner_date_fid = '".strval($data['date'])."' WHERE partner_id = '" . (int)$partner_id . "'");

		$categories = array();
		$products  = array();

		$products_data = $this->getPartnerProducts($partner_id);
		if (isset($products_data) && !empty($products_data)) {
			$this->log->write(print_r($products_data, true));
			foreach ($products_data as $product) {
				$product = $this->db->query("SELECT product_id FROM " . DB_PREFIX . "product WHERE partner_product_id = '".(int)$product['product_partner_id']."' LIMIT 1");
				if (!empty($product) && isset($product->row['product_id'])) {
					$this->db->query("DELETE FROM " . DB_PREFIX . "product WHERE product_id = '".(int)$product->row['product_id']."'");
					$this->db->query("DELETE FROM " . DB_PREFIX . "product_description WHERE product_id = '".(int)$product->row['product_id']."'");
					$this->db->query("DELETE FROM " . DB_PREFIX . "product_to_category WHERE product_id = '" . (int)$product->row['product_id'] . "'");
					$this->db->query("DELETE FROM " . DB_PREFIX . "product_to_store WHERE product_id = '" . (int)$product->row['product_id'] . "'");
					$this->db->query("DELETE FROM " . DB_PREFIX . "product_attribute WHERE product_id = '" . (int)$product_id . "' ");
				}
			}
		}
		$this->db->query("DELETE FROM `" . DB_PREFIX . "partner_to_category` WHERE partner_id = '" . (int)$partner_id . "'");
		$this->db->query("DELETE FROM `" . DB_PREFIX . "product_to_partner` WHERE partner_id = '" . (int)$partner_id . "'");

		foreach ($data->shop->categories->category as $row) {
			$id = intval($row['id']);
			$parent = intval($row['parentId']);
			$name = strval($row);

			//$this->log->write($id);

			$categories[$id] = array(
				'id' => $id,
				'parent' => $parent,
				'name' => $name
			);
			
		}
		foreach ($categories as $key => $value) {
			$json['categories'][] = array(
				'id' => $value['id'],
				'parent' => $value['parent'],
				'name' => ($value['parent'] > 0 ? $categories[$value['parent']]['name']. ' -> ' : '') . $value['name']
			);
			$this->db->query("INSERT INTO " . DB_PREFIX . "partner_to_category SET 
								partner_id = '".(int)$partner_id."',
								partner_category_id = '".$value['id']."',
								name = '".$this->db->escape($value['name'])."',
								parent_id = '".$value['parent']."'");
			$categories[$key]['products'] = array();
		}
		foreach ($data->shop->offers->offer as $row) {
			// id - идентификатор предложения.
			$id = intval($row['id']);
			
			if (isset($row->price)) {
				// price - актуальная цена.
				$price = strval($row->price);	
			}else{
				$price = 0;
			}

			if (isset($row->categoryId)) {
				// currencyId - идентификатор категории товара.
				$categoryId = intval($row->categoryId);
			}else{
				$categoryId = 0;
			}
			
			if (isset($row->name)) {
				// name - название товара.
				$name = strval($row->name);		
			}else{
				$name = '';
			}

			if (isset($row->model)) {
				// vendor - артикул производителя.
				$model_raw = strval($row->model);
				if ($name == '') {
					$name = $model_raw;
				}
			}else{
				$model_raw = '';
			}
			
			if (isset($row->vendor)) {
				// vendor - название производителя.
				$vendor = strval($row->vendor);
			}else{
				$vendor = '';
			}

			if (isset($row->country)) {
				// country - страна производителя.
				$country = strval($row->country);
			}else{
				if (isset($row->country_of_origin)) {
					// country - страна производителя.
					$country = strval($row->country_of_origin);
				}else{
					$country = '';
				}
			}


			if (isset($row->vendorCode)) {
				// vendor - артикул производителя.
				$model = strval($row->vendorCode);
				if ($name == '') {
					$name = $model;
				}
			}else{
				$model = '';
			}

			if (isset($row->barcode)) {
				// vendor - артикул производителя.
				$barcode = strval($row->barcode);
			}else{
				$barcode = '';
			}
			
			if (isset($row->picture)) {
				// picture - изображение.
				$picture = strval($row->picture);
			}else{
				$picture = '';
			}

			if (isset($row->description)) {
				// description - описание.
				$description = strval($row->description);
			}else{
				$description = '';
			}
			$params = array();
			if (isset($row->param)) {
				// params - параметры.
				   foreach($row->param as $param){
				   		if ($param[0]->attributes()->name[0] == 'Артикул') {
				   			$model = strval($param);
				   		}
						$params[] = array(
							'value' => strval($param),
							'name' => $param[0]->attributes()->name[0]
						);
				   }
			}

			$products[$categoryId][] = array(
				'id' => $id,
				'model' => $model,
				'barcode' => $barcode,
				'name' => $name,
				'category_id' => $categoryId,
				'price' => $price,
				'vendor' => $vendor,
				'country' => $country,
				'image' => $picture,
				'partner_id' => $partner_id,
				'in_order' => 1,
				'partner_product_id' => $id,
				'description' => $description,
				'params' => $params

			);
			$this->db->query("INSERT INTO " . DB_PREFIX . "product_to_partner SET model = '" . $this->db->escape($model) . "', barcode = '".$this->db->escape($barcode)."',
								price = '" . $price . "', 
								name = '" . $this->db->escape($name) . "',
								description = '" . $this->db->escape($description) . "',
								category_id = '".$categoryId."',
								vendor = '" . $this->db->escape($vendor) . "',
								country = '" . $this->db->escape($country) . "',
								image = '" . $this->db->escape($picture) . "',
								params = '" . $this->db->escape(json_encode($params)) . "',
								in_order = 1,
								partner_id = '".(int)$partner_id."',
								partner_product_id = '".$id."'");
		}

			foreach ($categories as $key => $value) {
				//$categories[$key]['products'] = $products[$key];
			}

		$this->editSettings($partner_id,$categories);
		return $json;

	}

	public function editSettings($partner_id, $settings) {
		$this->db->query("UPDATE " . DB_PREFIX . "partner SET 
			partner_settings = '" . $this->db->escape(json_encode($settings)) . "',
			partner_date_edit = NOW() WHERE partner_id = '".(int)$partner_id."'");

		$this->cache->delete('partner');
	}

	public function getSettings($partner_id) {
		$query = $this->db->query("SELECT partner_settings FROM " . DB_PREFIX . "partner WHERE partner_id = '".(int)$partner_id."'");

		return $query->row['partner_settings'];
	}

	public function getPartnerCategories($partner_id) {
		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "partner_to_category WHERE partner_id = '".(int)$partner_id."' ");


		return $query->rows;
	}

	public function getPartnerProductsByCategory($category_id, $partner_id) {
		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "product_to_partner WHERE category_id = '".(int)$category_id."' AND partner_id = '".(int)$partner_id."'");
		$products_data = array();
		if ($query->num_rows) {
			foreach($query->rows as $row){
				$products_data[] = array(
					'product_partner_id' => $row['product_partner_id'],
					'partner_product_id' => $row['partner_product_id'],
					'model' => $row['model'],
					'barcode' => $row['barcode'],
					'name' => $row['name'],
					'category_id' => $row['category_id'],
					'price' => $row['price'],
					'vendor' => $row['vendor'],
					'country' => $row['country'],
					'image' => $row['image'],
					'description' => $row['description'],
					'in_order' => $row['in_order']
				);
			}
		}
		//$this->log->write($products_data);
		return $products_data;
	}

	public function getPartnerProducts($partner_id) {
		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "product_to_partner WHERE partner_id = '".(int)$partner_id."'");

		return $query->rows;
	}

	public function getPartnerByProduct($product_id) {
		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "product_to_partner pp LEFT JOIN " . DB_PREFIX . "partner pa ON pa.partner_id = pp.partner_id WHERE pp.product_partner_id = (SELECT partner_product_id FROM " . DB_PREFIX . "product p WHERE p.product_id = '".(int)$product_id."')");
		$info = array();
		if ($query->num_rows) {
				$info = array(
					'partner_id' => $query->row['partner_id'],
					'partner_fio' => $query->row['partner_fio'],
					'partner_name' => $query->row['partner_name'],
					'partner_phone' => $query->row['partner_phone'],
					'model' => $query->row['model']
				);
		}

		return $info;
	}
}
