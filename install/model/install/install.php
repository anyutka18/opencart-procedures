<?php
class ModelInstallInstall extends Model {
	public function database($data) {
		$db = new DB($data['db_driver'], htmlspecialchars_decode($data['db_hostname']), htmlspecialchars_decode($data['db_username']), htmlspecialchars_decode($data['db_password']), htmlspecialchars_decode($data['db_database']), $data['db_port']);

		$file = DIR_APPLICATION . 'opencart.sql';

		if (!file_exists($file)) {
			exit('Could not load sql file: ' . $file);
		}

		$lines = file($file);

		if ($lines) {
			$sql = '';

			foreach($lines as $line) {
				if ($line && (substr($line, 0, 2) != '--') && (substr($line, 0, 1) != '#')) {
					$sql .= $line;

					if (preg_match('/;\s*$/', $line)) {
						$sql = str_replace("DROP TABLE IF EXISTS `oc_", "DROP TABLE IF EXISTS `" . $data['db_prefix'], $sql);
						$sql = str_replace("CREATE TABLE `oc_", "CREATE TABLE `" . $data['db_prefix'], $sql);
						$sql = str_replace("INSERT INTO `oc_", "INSERT INTO `" . $data['db_prefix'], $sql);

						$db->query($sql);


						$sql = '';
					}
				}
			}
			$prodcedure_category_params = array("IN category_id INT", "IN filter_manufacturer_id INT", " IN filter_filter  varchar(256)", " IN filter_name text character set utf8", " IN filter_description varchar(256)", " IN filter_tag varchar(256)", " IN filter_sort varchar(256)", " IN filter_order varchar(256)", " IN filter_start INT", " IN filter_limit INT", " IN temp_table varchar(256)", " IN language_id INT", " IN store_id INT", " IN customer_group_id INT");
			$prodcedure_category_sql = '
				DECLARE sql_stmt varchar(4000);
SET @sql_stmt = sql_stmt;
SET @temp_table = temp_table;
SET @varsort = 
CASE filter_sort';
$prodcedure_category_sql .= "WHEN 'p.price' THEN '(CASE WHEN special IS NOT NULL THEN special WHEN discount IS NOT NULL THEN discount ELSE p.price END) '
WHEN 'pd.name' THEN CONCAT('LCASE(',filter_sort, ')')
WHEN 'p.model' THEN CONCAT('LCASE(',filter_sort, ')')
ELSE filter_sort
END;
SET @varorder = 
CASE filter_order 
WHEN 'DESC' THEN ' DESC, LCASE(pd.name) DESC'
ELSE ' ASC, LCASE(pd.name) ASC'
END;
SET @varmanufacturer = 
CASE WHEN filter_manufacturer_id > 0 THEN CONCAT('AND p.manufacturer_id = ',filter_manufacturer_id) ELSE '' END;";
$prodcedure_category_sql .= "
SET @case_name = 
CASE WHEN filter_name != '' THEN CONCAT(\" AND ( LCASE(p.model) = '\",filter_name,\"' OR LCASE(p.sku) = '\", filter_name, \"' OR LCASE(p.upc) = '\", filter_name, \"' OR LCASE(p.ean) = '\", filter_name, \"' OR LCASE(p.jan) = '\", filter_name, \"' OR LCASE(p.isbn) = '\", filter_name, \"' OR LCASE(p.mpn) = '\", filter_name, \"')\")
ELSE ''
END;

SET @sql_stmt = CONCAT(\"SELECT p.product_id AS product_id, p.model AS model, p.sku AS sku, pd.name AS name, pd.description AS description, p.image AS image, p.quantity AS quantity, p.weight_class_id AS weight_class_id, p.tax_class_id AS tax_class_id, p.date_available AS date_available, p.weight AS weight, p.minimum AS minimum, p.price AS price, p2ca.category_id AS category_id, (SELECT wcd.unit FROM ya_weight_class_description wcd WHERE p.weight_class_id = wcd.weight_class_id AND wcd.language_id = \", language_id, \") AS weight_class, p.sort_order AS sort_order, p.status AS status, p.date_added AS date_added, (SELECT price FROM ya_product_special ps WHERE ps.product_id = p.product_id AND ps.customer_group_id = customer_group_id  AND ((ps.date_start = '0000-00-00' OR ps.date_start < NOW()) AND (ps.date_end = '0000-00-00' OR ps.date_end > NOW())) ORDER BY ps.priority ASC, ps.price ASC LIMIT 1) AS special, (SELECT price FROM ya_product_discount pd2 WHERE pd2.product_id = p.product_id AND pd2.customer_group_id = \", customer_group_id,\" AND pd2.quantity = '1' AND ((pd2.date_start = '0000-00-00' OR pd2.date_start < NOW()) AND (pd2.date_end = '0000-00-00' OR pd2.date_end > NOW())) ORDER BY pd2.priority ASC, pd2.price ASC LIMIT 1) AS discount FROM ya_product p LEFT JOIN ya_product_description pd ON (p.product_id = pd.product_id) LEFT JOIN ya_product_to_store p2s ON (p.product_id = p2s.product_id) LEFT JOIN ya_product_filter pf ON (p.product_id = pf.product_id) LEFT JOIN ya_product_to_category p2ca ON (p.product_id = p2ca.product_id)  WHERE p.product_id IN (SELECT p.product_id  FROM ya_product_to_category p2c  LEFT JOIN ya_product p ON (p2c.product_id = p.product_id)  LEFT JOIN ya_product_description pd ON (p.product_id = pd.product_id) LEFT JOIN ya_product_to_store p2s ON (p.product_id = p2s.product_id) LEFT JOIN ya_product_to_category p2ca   ON (p2ca.product_id = p.product_id) WHERE pd.language_id = \", language_id, \"  AND p.status = '1' AND p2s.store_id = \", store_id, \"  AND p2c.category_id = \", category_id, \" ) AND p.status = '1'  \", @varmanufacturer ,@case_name,\" ORDER BY \", @varsort , @varorder, \" LIMIT \", filter_start , ", ",filter_limit);
PREPARE stmt FROM @sql_stmt;
EXECUTE stmt;
				";
			$db->setProcedureParams('GetCategoryProducts', $prodcedure_category_params);
			$db->createProcedure('GetCategoryProducts', $prodcedure_category_sql);

			$prodcedure_add_category_products_vew_params = array("IN language_id INT","IN config_store_id INT", "IN config_customer_group_id INT");
			$db->setProcedureParams('AddCategoryProductsView', $prodcedure_add_category_products_vew_params);
			$db->createProcedure();

			$db->query("SET CHARACTER SET utf8");

			$db->query("DELETE FROM `" . $data['db_prefix'] . "user` WHERE user_id = '1'");

			$db->query("INSERT INTO `" . $data['db_prefix'] . "user` SET user_id = '1', user_group_id = '1', username = '" . $db->escape($data['username']) . "', salt = '" . $db->escape($salt = token(9)) . "', password = '" . $db->escape(sha1($salt . sha1($salt . sha1($data['password'])))) . "', firstname = 'John', lastname = 'Doe', email = '" . $db->escape($data['email']) . "', status = '1', date_added = NOW()");

			$db->query("DELETE FROM `" . $data['db_prefix'] . "setting` WHERE `key` = 'config_email'");
			$db->query("INSERT INTO `" . $data['db_prefix'] . "setting` SET `code` = 'config', `key` = 'config_email', value = '" . $db->escape($data['email']) . "'");

			$db->query("DELETE FROM `" . $data['db_prefix'] . "setting` WHERE `key` = 'config_encryption'");
			$db->query("INSERT INTO `" . $data['db_prefix'] . "setting` SET `code` = 'config', `key` = 'config_encryption', value = '" . $db->escape(token(1024)) . "'");

			$db->query("UPDATE `" . $data['db_prefix'] . "product` SET `viewed` = '0'");

			$db->query("INSERT INTO `" . $data['db_prefix'] . "api` SET username = 'Default', `key` = '" . $db->escape(token(256)) . "', status = 1, date_added = NOW(), date_modified = NOW()");

			$api_id = $db->getLastId();

			$db->query("DELETE FROM `" . $data['db_prefix'] . "setting` WHERE `key` = 'config_api_id'");
			$db->query("INSERT INTO `" . $data['db_prefix'] . "setting` SET `code` = 'config', `key` = 'config_api_id', value = '" . (int)$api_id . "'");
			
			// set the current years prefix
			$db->query("UPDATE `" . $data['db_prefix'] . "setting` SET `value` = 'INV-" . date('Y') . "-00' WHERE `key` = 'config_invoice_prefix'");
		}
	}
}
