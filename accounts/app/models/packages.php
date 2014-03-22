<?php
/**
 * Package management
 * 
 * @package blesta
 * @subpackage blesta.app.models
 * @copyright Copyright (c) 2010, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class Packages extends AppModel {
	
	/**
	 * Initialize Packages
	 */
	public function __construct() {
		parent::__construct();
		Language::loadLang(array("packages"));
	}
	
	/**
	 * Adds a new package to the system
	 *
	 * @param array $vars An array of package information including:
	 * 	- module_id The ID of the module this package belongs to (optional, default NULL)
	 * 	- name The name of the package
	 * 	- description The description of the package (optional, default NULL)
	 * 	- description_html The HTML description of the package (optional, default NULL)
	 * 	- qty The maximum quantity available in this package, if any (optional, default NULL)
	 * 	- module_row The module row this package belongs to (optional, default 0)
	 * 	- module_group The module group this package belongs to (optional, default NULL)
	 * 	- taxable Whether or not this package is taxable (optional, default 0)
	 * 	- single_term Whether or not services derived from this package should be canceled at the end of term (optional, default 0)
	 * 	- status The status of this package, 'active', 'inactive', 'restricted' (optional, default 'active')
	 * 	- company_id The ID of the company this package belongs to
	 * 	- email_content A numerically indexed array of email content including:
	 * 		- lang The language of the email content
	 * 		- html The html content for the email (optional)
	 * 		- text The text content for the email, will be created automatically from html if not given (optional)
	 * 	- pricing A numerically indexed array of pricing info including:
	 * 		- term The term as an integer 1-65535 (optional, default 1)
	 * 		- period The period, 'day', 'week', 'month', 'year', 'onetime' (optional, default 'month')
	 * 		- price The price of this term (optional, default 0.00)
	 * 		- setup_fee The setup fee for this package (optional, default 0.00)
	 * 		- cancel_fee The cancelation fee for this package (optional, default 0.00)
	 * 		- currency The ISO 4217 currency code for this pricing (optional, default USD)
	 * 	- groups A numerically indexed array of package group assignments (optional)
	 * 	- option_groups A numerically indexed array of package option group assignments (optional)
	 * 	- * A set of miscellaneous fields to pass, in addition to the above fields, to the module when adding the package (optional)
	 * @return int The package ID created, void on error
	 */
	public function add(array $vars) {
		
		if (isset($vars['module_group']) && $vars['module_group'] != "")
			$vars['module_row'] = 0;
		if (!isset($vars['company_id']))
			$vars['company_id'] = Configure::get("Blesta.company_id");
		
		// Attempt to validate $vars with the module, set any meta fields returned by the module
		if (isset($vars['module_id']) && $vars['module_id'] != "") {
			
			if (!isset($this->ModuleManager))
				Loader::loadModels($this, array("ModuleManager"));
				
			$module = $this->ModuleManager->initModule($vars['module_id']);
			
			if ($module) {
				$vars['meta'] = $module->addPackage($vars);
				
				// If any errors encountered through the module, set errors and return
				if (($errors = $module->errors())) {
					$this->Input->setErrors($errors);
					return;
				}
			}
		}
		
		$this->Input->setRules($this->getRules($vars));
		
		if ($this->Input->validates($vars)) {
			
			// Fetch company settings on clients
			Loader::loadComponents($this, array("SettingsCollection"));
			$company_settings = $this->SettingsCollection->fetchSettings(null, $vars['company_id']);
			
			// Creates subquery to calculate the next package ID value on the fly
			$sub_query = new Record();
			/*
			$values = array($company_settings['packages_start'], $company_settings['packages_increment'],
				$company_settings['packages_start'], $company_settings['packages_increment'],
				$company_settings['packages_start'], $company_settings['packages_pad_size'],
				$company_settings['packages_pad_str']);
			*/
			$values = array($company_settings['packages_start'], $company_settings['packages_increment'],
				$company_settings['packages_start']);
			
			/*
			$sub_query->select(array("LPAD(IFNULL(GREATEST(MAX(t1.id_value),?)+?,?), " .
				"GREATEST(CHAR_LENGTH(IFNULL(MAX(t1.id_value)+?,?)),?),?)"), false)->
			*/
			$sub_query->select(array("IFNULL(GREATEST(MAX(t1.id_value),?)+?,?)"), false)->
				appendValues($values)->
				from(array("packages"=>"t1"))->
				where("t1.company_id", "=", $vars['company_id'])->
				where("t1.id_format", "=", $company_settings['packages_format']);
			// run get on the query so $sub_query->values are built
			$sub_query->get();
			
			$vars['id_format'] = $company_settings['packages_format'];
			// id_value will be calculated on the fly using a subquery
			$vars['id_value'] = $sub_query;
			
			// Assign subquery values to this record component
			$this->Record->appendValues($sub_query->values);
			// Ensure the subquery value is set first because its the first value
			$vars = array_merge(array('id_value'=>null), $vars);
			
			// Add package
			$fields = array("id_format", "id_value", "module_id", "name", "description", "description_html",
				"qty", "module_row", "module_group", "taxable", "single_term", "status", "company_id"
			);
			$this->Record->insert("packages", $vars, $fields);
			
			$package_id = $this->Record->lastInsertId();
			
			// Add package email contents
			if (!empty($vars['email_content']) && is_array($vars['email_content'])) {
				for ($i=0; $i<count($vars['email_content']); $i++) {
					$vars['email_content'][$i]['package_id'] = $package_id;
					$fields = array("package_id", "lang", "html", "text");
					$this->Record->insert("package_emails", $vars['email_content'][$i], $fields);
				}
			}
			
			// Add package pricing			
			if (!empty($vars['pricing']) && is_array($vars['pricing'])) {
				
				for ($i=0; $i<count($vars['pricing']); $i++) {
					$vars['pricing'][$i]['package_id'] = $package_id;
					
					// Default one-time package to a term of 0 (never renews)
					if (isset($vars['pricing'][$i]['period']) && $vars['pricing'][$i]['period'] == "onetime")
						$vars['pricing'][$i]['term'] = 0;
						
					$vars['pricing'][$i]['company_id'] = $vars['company_id'];
					$this->addPackagePricing($package_id, $vars['pricing'][$i]);
				}
			}
			
			// Add package meta data
			if (isset($vars['meta']) && !empty($vars['meta']) && is_array($vars['meta']))
				$this->setMeta($package_id, $vars['meta']);
			
			// Set package option groups, if given
			$this->removeOptionGroups($package_id);
			if (!empty($vars['option_groups']))
				$this->setOptionGroups($package_id, $vars['option_groups']);
			
			// Add all package groups given
			if (isset($vars['groups']))
				$this->setGroups($package_id, $vars['groups']);
			
			return $package_id;
		}
	}
	
	/**
	 * Update an existing package ID with the data given
	 *
	 * @param int $package_id The ID of the package to update
	 * @param array $vars An array of package information including:
	 * 	- module_id The ID of the module this package belongs to (optional, default NULL)
	 * 	- name The name of the package
	 * 	- description The description of the package (optional, default NULL)
	 * 	- description_html The HTML description of the package (optional, default NULL)
	 * 	- qty The maximum quantity available in this package, if any (optional, default NULL)
	 * 	- module_row The module row this package belongs to (optional, default 0)
	 * 	- module_group The module group this package belongs to (optional, default NULL)
	 * 	- taxable Whether or not this package is taxable (optional, default 0)
	 * 	- single_term Whether or not services derived from this package should be canceled at the end of term (optional, default 0)
	 * 	- status The status of this package, 'active', 'inactive', 'restricted' (optional, default 'active')
	 * 	- company_id The ID of the company this package belongs to (optional)
	 * 	- email_content A numerically indexed array of email content including:
	 * 		- lang The language of the email content
	 * 		- html The html content for the email (optional)
	 * 		- text The text content for the email, will be created automatically from html if not given (optional)
	 * 	- pricing A numerically indexed array of pricing info including:
	 * 		- id The pricing ID (optional, required if an edit else will add as new)
	 * 		- term The term as an integer 1-65535 (optional, default 1), if term is empty will remove this pricing option
	 * 		- period The period, 'day', 'week', 'month', 'year', 'onetime' (optional, default 'month')
	 * 		- price The price of this term (optional, default 0.00)
	 * 		- setup_fee The setup fee for this package (optional, default 0.00)
	 * 		- cancel_fee The cancelation fee for this package (optional, default 0.00)
	 * 		- currency The ISO 4217 currency code for this pricing (optional, default USD)
	 * 	- groups A numerically indexed array of package group assignments (optional), if given will replace all package group assignments with those given
	 * 	- option_groups A numerically indexed array of package option group assignments (optional)
	 * 	- * A set of miscellaneous fields to pass, in addition to the above fields, to the module when adding the package (optional)
	 */
	public function edit($package_id, array $vars) {
		
		if (isset($vars['module_group']) && $vars['module_group'] != "")
			$vars['module_row'] = 0;
		
		$package = $this->get($package_id);
		
		// Attempt to validate $vars with the module, set any meta fields returned by the module
		if (isset($vars['module_id']) && $vars['module_id'] != "") {
			
			if (!isset($this->ModuleManager))
				Loader::loadModels($this, array("ModuleManager"));
				
			$module = $this->ModuleManager->initModule($vars['module_id']);
			
			if ($module) {
				$vars['meta'] = $module->editPackage($package, $vars);
				
				// If any errors encountered through the module, set errors and return
				if (($errors = $module->errors())) {
					$this->Input->setErrors($errors);
					return;
				}
			}
		}
		
		// Set company ID if not given, it's necessary to have this in order to validate package groups
		if (!isset($vars['company_id']))
			$vars['company_id'] = $package->company_id;
		
		$rules = $this->getRules($vars);
		
		$rules['pricing[][id]']['format'] = array(
			'if_set' => true,
			'rule' => array(array($this, "validateExists"), "id", "package_pricing"),
			'message' => $this->_("Packages.!error.pricing[][id].format")
		);
		
		$this->Input->setRules($rules);
		
		if ($this->Input->validates($vars)) {
			// Update packages
			$fields = array("module_id", "name", "description", "description_html",
				"qty", "module_row", "module_group", "taxable", "single_term", "status", "company_id"
			);
			$this->Record->where("id", "=", $package_id)->update("packages", $vars, $fields);
			
			// Update package email
			if (!empty($vars['email_content']) && is_array($vars['email_content'])) {
				for ($i=0; $i<count($vars['email_content']); $i++) {
					$fields = array("package_id", "lang", "html", "text");
					$vars['email_content'][$i]['package_id'] = $package_id;
					
					$this->Record->duplicate("html", "=", isset($vars['email_content'][$i]['html']) ? $vars['email_content'][$i]['html'] : null)->
						duplicate("text", "=", isset($vars['email_content'][$i]['text']) ? $vars['email_content'][$i]['text'] : null)->
						insert("package_emails", $vars['email_content'][$i], $fields);
				}
			}
			
			// Insert/update package prices
			for ($i=0; $i<count($vars['pricing']); $i++) {
				// Default one-time package to a term of 0 (never renews)
				if (isset($vars['pricing'][$i]['period']) && $vars['pricing'][$i]['period'] == "onetime")
					$vars['pricing'][$i]['term'] = 0;
				
				$vars['pricing'][$i]['company_id'] = $vars['company_id']
				;
				if (!empty($vars['pricing'][$i]['id'])) {
					
					$this->editPackagePricing($vars['pricing'][$i]['id'], $vars['pricing'][$i]);
				}
				else {
					$this->addPackagePricing($package_id, $vars['pricing'][$i]);
				}
			}
			
			// Update package meta data
			$this->setMeta($package_id, $vars['meta']);
			
			// Set package option groups, if given
			$this->removeOptionGroups($package_id);
			if (!empty($vars['option_groups']))
				$this->setOptionGroups($package_id, $vars['option_groups']);
			
			// Replace all group assignments with those that are given (if any given)
			if (isset($vars['groups']))
				$this->setGroups($package_id, $vars['groups']);
		}
	}
	
	/**
	 * Permanently removes the given package from the system. Packages can only
	 * be deleted if no services exist for that package.
	 *
	 * @param int $package_id The package ID to delete
	 */
	public function delete($package_id) {

		$vars = array('package_id' => $package_id);
		
		$rules = array(
			'package_id' => array(
				'exists' => array(
					'rule' => array(array($this, "validateServiceExists")),
					'negate' => true,
					'message' => $this->_("Packages.!error.package_id.exists")
				)
			)
		);
		
		$this->Input->setRules($rules);
		
		if ($this->Input->validates($vars)) {
			// No services exist for this package, so it's safe to delete it
			$this->Record->from("packages")->
				leftJoin("package_emails", "package_emails.package_id", "=", "packages.id", false)->
				leftJoin("package_meta", "package_meta.package_id", "=", "packages.id", false)->
				leftJoin("package_pricing", "package_pricing.package_id", "=", "packages.id", false)->
				leftJoin("pricings", "pricings.id", "=", "package_pricing.pricing_id", false)->
				leftJoin("package_group", "package_group.package_id", "=", "packages.id", false)->
				leftJoin("package_option", "package_option.package_id", "=", "packages.id", false)->
				where("packages.id", "=", $package_id)->
				delete(array("packages.*", "package_emails.*", "package_meta.*", "package_pricing.*", "pricings.*", "package_group.*", "package_option.*"));
		}
	}
	
	/**
	 * Save the packages for the given group in the provided order
	 *
	 * @param int $package_group_id The ID of the package group to order packages for
	 * @param array $package_ids A numerically indexed array of package IDs
	 */
	public function orderPackages($package_group_id, array $package_ids) {
		for ($i=0; $i<count($package_ids); $i++) {
			$this->Record->where("package_id", "=", $package_ids[$i])->
				where("package_group_id", "=", $package_group_id)->
				update("package_group", array('order' => $i));
		}
	}
	
	/**
	 * Fetches the given package
	 *
	 * @param int $package_id The package ID to fetch
	 * @return mixed A stdClass object representing the package, false if no such package exists
	 */
	public function get($package_id) {
		$fields = array(
			"packages.*",
			"REPLACE(packages.id_format, ?, packages.id_value)" => "id_code",
		);
		
		$package = $this->Record->select($fields)->
			appendValues(array($this->replacement_keys['packages']['ID_VALUE_TAG']))->
			from("packages")->
			where("id", "=", $package_id)->fetch();
		if ($package) {
			$package->email_content = $this->getPackageEmails($package->id);
			$package->pricing = $this->getPackagePricing($package->id);
			$package->meta = $this->getPackageMeta($package->id);
			$package->groups = $this->getPackageGroups($package->id);
			$package->option_groups = $this->getPackageOptionGroups($package->id);
		}
		
		return $package;
	}
	
	/**
	 * Fetches the given package by package pricing ID
	 *
	 * @param int $package_pricing_id The package pricing ID to use to fetch the package
	 * @return mixed A stdClass object representing the package, false if no such package exists
	 */
	public function getByPricingId($package_pricing_id) {
		$fields = array(
			"packages.*",
			"REPLACE(packages.id_format, ?, packages.id_value)" => "id_code",
		);
		
		$package = $this->Record->select($fields)->
			appendValues(array($this->replacement_keys['packages']['ID_VALUE_TAG']))->
			from("packages")->innerJoin("package_pricing", "package_pricing.package_id", "=", "packages.id", false)->
			where("package_pricing.id", "=", $package_pricing_id)->fetch();
		if ($package) {
			$package->email_content = $this->getPackageEmails($package->id);
			$package->pricing = $this->getPackagePricing($package->id);
			$package->meta = $this->getPackageMeta($package->id);
			$package->groups = $this->getPackageGroups($package->id);
			$package->option_groups = $this->getPackageOptionGroups($package->id);
		}
		
		return $package;
	}
	
	/**
	 * Fetch all packages belonging to the given company
	 *
	 * @param int $company_id The ID of the company to fetch pages for
	 * @param array $order The sort order in key = value order, where 'key' is the field to sort on and 'value' is the order to sort (asc or desc)
	 * @param string $status The status type of packages to retrieve ('active', 'inactive', 'restricted', default null for all)
	 * @param string $type The type of packages to retrieve ('standard', 'addon', default null for all)
	 * @return array An array of stdClass objects each representing a package
	 */
	public function getAll($company_id, array $order=array('name'=>"ASC"), $status=null, $type=null) {
		// If sorting by ID code, use id code sort mode
		if (isset($order_by['id_code']) && Configure::get("Blesta.id_code_sort_mode")) {
			$temp = $order_by['id_code'];
			unset($order_by['id_code']);
			
			foreach ((array)Configure::get("Blesta.id_code_sort_mode") as $key) {
				$order_by[$key] = $temp;
			}
		}
		
		$this->Record = $this->getPackages($status);
		
		if ($type) {
			$this->Record->innerJoin("package_group", "package_group.package_id", "=", "packages.id", false)->
				innerJoin("package_groups", "package_groups.id", "=", "package_group.package_group_id", false)->
				where("package_groups.type", "=", $type)->
				order(array('package_group.order' => "ASC"))->
				group(array("packages.id"));
		}
		
		return $this->Record->order($order)->where("packages.company_id", "=", $company_id)->fetchAll();
	}
	
	/**
	 * Fetches a list of all packages
	 *
	 * @param int $page The page to return results for (optional, default 1)
	 * @param array $order_by The sort and order conditions (e.g. array('sort_field'=>"ASC"), optional)
	 * @param string $status The status type of packages to retrieve ('active', 'inactive', 'restricted', default null for all)
	 * @return array An array of stdClass objects each representing a package
	 */
	public function getList($page=1, array $order_by=array('id_code'=>"asc"), $status=null) {
		// If sorting by ID code, use id code sort mode
		if (isset($order_by['id_code']) && Configure::get("Blesta.id_code_sort_mode")) {
			$temp = $order_by['id_code'];
			unset($order_by['id_code']);
			
			foreach ((array)Configure::get("Blesta.id_code_sort_mode") as $key) {
				$order_by[$key] = $temp;
			}
		}
		
		$this->Record = $this->getPackages($status);
		
		return $this->Record->where("packages.company_id", "=", Configure::get("Blesta.company_id"))->
			order($order_by)->
			limit($this->getPerPage(), (max(1, $page) - 1)*$this->getPerPage())->fetchAll();
	}
	
	/**
	 * Search packages
	 *
	 * @param string $query The value to search packages for
	 * @param int $page The page number of results to fetch (optional, default 1)
	 * @return array An array of packages that match the search criteria
	 */
	public function search($query, $page=1) {
		$this->Record = $this->searchPackages($query);
		
		// Set order by clause
		$order_by = array();
		if (Configure::get("Blesta.id_code_sort_mode")) {
			foreach ((array)Configure::get("Blesta.id_code_sort_mode") as $key) {
				$order_by[$key] = "ASC";
			}
		}
		else
			$order_by = array("name"=>"DESC");
			
		return $this->Record->order($order_by)->
			limit($this->getPerPage(), (max(1, $page) - 1)*$this->getPerPage())->fetchAll();
	}
	
	/**
	 * Return the total number of packages returned from Packages::search(), useful
	 * in constructing pagination
	 *
	 * @param string $query The value to search services for
	 * @see Packages::search()
	 */
	public function getSearchCount($query) {
		$this->Record = $this->searchPackages($query);
		return $this->Record->numResults();
	}
	
	/**
	 * Partially constructs the query for searching packages
	 *
	 * @param string $query The value to search packages for
	 * @return Record The partially constructed query Record object
	 * @see Packages::search(), Packages::getSearchCount()
	 */
	private function searchPackages($query) {
		$this->Record = $this->getPackages();
		
		$sub_query_sql = $this->Record->get();
		$values = $this->Record->values;
		$this->Record->reset();
		
		$this->Record = $this->Record->select()->appendValues($values)->from(array($sub_query_sql => "temp"))->
			like("CONVERT(temp.id_code USING utf8)", "%" . $query . "%", true, false)->
			orLike("temp.name", "%" . $query . "%")->
			orLike("temp.module_name", "%" . $query . "%");
		return $this->Record;
	}
	
	/**
	 * Retrieves a list of package pricing periods
	 *
	 * @param boolean $plural True to return language for plural periods, false for singular
	 * @return array Key=>value pairs of package pricing periods
	 */
	public function getPricingPeriods($plural=false) {
		if (!isset($this->Pricings))
			Loader::loadModels($this, array("Pricings"));
		return $this->Pricings->getPeriods($plural);
	}
	
	/**
	 * Retrieves a list of package status types
	 *
	 * @return array Key=>value pairs of package status types
	 */
	public function getStatusTypes() {
		return array(
			"active"=>$this->_("Packages.getStatusTypes.active"),
			"inactive"=>$this->_("Packages.getStatusTypes.inactive"),
			"restricted"=>$this->_("Packages.getStatusTypes.restricted")
		);
	}
	
	/**
	 * Caclulates the cost in one or more package pricings for a client with the given coupon.
	 * Tax is only apply if the package is configured as taxable and there exist
	 * tax rules that apply to the given client.
	 *
	 * @param int $client_id The ID of the client to which the pricings are to be applied
	 * @param array $package_pricings A numerical array of packacing pricing and quantity values of the form:
	 * 	- pricing_id The package pricing ID
	 * 	- qty The qty being purchased for the package pricing ID
	 * 	- fees A numerical array of fee types to include in the pricing calculations, including:
	 * 		- setup
	 * 		- cancel
	 * 	- configoptions An array of key/value pairs where each key is the package option ID and each value is the package option value
	 * @param string $coupon_code The coupon code to apply to each package pricing ID
	 * @param array A numerically indexed array of stdClass objects each representing a tax rule to apply to this client or client group. Must be provided if $client_id not specified
	 * @param int $client_group_id The ID of the client group to calculate line totals for
	 * @param string The ISO 4217 currency code to calculate totals in (null defaults to default client or client group currency)
	 * @return array An array of pricing information including:
	 * 	- subtotal The total before discount, fees, and tax
	 * 	- discount The total savings
	 * 	- fees An array of fees requested including:
	 * 		- setup The setup fee
	 * 		- cancel The cancel fee
	 * 	- total The total after discount, fees, but before tax
	 * 	- total_w_tax The total after discount, fees, and tax
	 * 	- tax The total tax
	 */
	public function calcLineTotals($client_id, array $package_pricings, $coupon_code = null, array $tax_rules = null, $client_group_id = null, $currency = null) {
		Loader::loadHelpers($this, array("CurrencyFormat"=>array(Configure::get("Blesta.company_id"))));
		Loader::loadComponents($this, array("SettingsCollection"));
		Loader::loadModels($this, array("Invoices", "Coupons", "Currencies"));
		
		if ($client_id)
			$client_settings = $this->SettingsCollection->fetchClientSettings($client_id);
		else
			$client_settings = $this->SettingsCollection->fetchClientGroupSettings($client_group_id);	

		if (!$currency)
			$currency = $client_settings['default_currency'];

		// Fetch all tax rules that apply to this client
		if (!$tax_rules)
			$tax_rules = $this->Invoices->getTaxRules($client_id);
		
		// Set cascade tax setting
		foreach ($tax_rules as &$tax_rule) {
			$tax_rule->cascade = $client_settings['cascade_tax'] == "true" ? 1 : 0;
		}
		unset($tax_rule);
		
		$totals = array();
	
		// Subtotal sum
		$subtotal = 0;
		// Discount
		$discount = 0;
		// Fees
		$fees = array();
		// Total setup fees
		$setup_fees = 0;
		// Total sum
		$total = 0;
		// Total due
		$total_due = 0;
		// Tax total sum (for rules that should be applied to totals i.e. "inclusive")
		$tax_subtotal = 0;
		// Tax total sum including both inclusive and exclusive taxes
		$tax_total = 0;
		// Tax totals
		$tax = array();
		
		// Fetch pricing for each package price
		$package_ids = array();
		foreach ($package_pricings as &$pricing) {
			$pricing['qty'] = (int)$pricing['qty'];
			
			// Skip calculations on any lines that are blank
			if ($pricing['qty'] <= 0)
				continue;
			
			$package_price = $this->getAPackagePricing($pricing['pricing_id']);

			// Skip if pricing doesn't exist
			if (!$package_price)
				continue;
			
			$package_ids[] = $package_price->package_id;
			$pricing['price'] = $package_price;
		}
		unset($pricing);
		
		$coupon = false;
		if ($coupon_code)
			$coupon = $this->Coupons->getForPackages($coupon_code, null, $package_ids);
		
		// Caclulate totals for each pricing option given
		foreach ($package_pricings as $pricing) {
			$pricing['qty'] = (int)$pricing['qty'];
			
			// Skip calculations on any lines that are blank
			if ($pricing['qty'] <= 0)
				continue;
			
			$package_price = $pricing['price'];
			
			if ($package_price)
				$package_price = $this->convertPricing($package_price, $currency, $client_settings['multi_currency_pricing']);
			
			// Skip if pricing doesn't exist
			if (!$package_price)
				continue;
			
			$config_price = null;
			if (isset($pricing['configoptions']))
				$config_price = $this->calcConfigOptionTotals($package_price, $pricing['configoptions'], $currency, $client_settings['multi_currency_pricing']);

			$line_total = $pricing['qty'] * $package_price->price;
			
			if ($config_price)
				$line_total += $config_price->price;
				
			$subtotal += $line_total;
			
			// Calculate discount (if exclusive)
			if ($coupon && $coupon->type == "exclusive") {
				
				$coupon_allowed = false;
				foreach ($coupon->packages as $discount_pack) {
					if ($discount_pack->package_id == $package_price->package_id) {
						$coupon_allowed = true;
						break;
					}
				}
				
				if ($coupon_allowed) {
					foreach ($coupon->amounts as $amount) {
						if ($amount->currency == $currency) {
							if ($amount->type == "amount")
								$discount += ($pricing['qty'] * $amount->amount);
							else
								$discount += ($pricing['qty'] * $package_price->price * $amount->amount / 100);
							break;
						}
					}
					
					// Remove discount from taxable amount
					$line_total -= $discount;
				}
			}			
			
			$setup_fee = 0;
			if (isset($pricing['fees'])) {
				foreach ($pricing['fees'] as $fee) {
					if ($fee == "cancel") {
						if (!isset($fees['cancel']))
							$fees['cancel'] = 0;
						$fees['cancel'] += $package_price->cancel_fee;
						
						if ($config_price)
							$fees['cancel'] += $config_price->cancel_fee;
					}
					elseif ($fee == "setup") {
						if (!isset($fees['setup']))
							$fees['setup'] = 0;
						
						$setup_fee = $package_price->setup_fee;
						$fees['setup'] += $setup_fee;

						if ($config_price) {
							$setup_fee += $config_price->setup_fee;
							$fees['setup'] += $config_price->setup_fee;
						}
						
						$setup_fees += $setup_fee;
					}
				}
			}
			
			// Calculate tax for each line item that is taxable IFF tax is enabled
			if ($client_settings['tax_exempt'] != "true" && $client_settings['enable_tax'] == "true" && $package_price->taxable) {
				$tax_totals = $this->Invoices->getTaxTotals($line_total, $tax_rules);
				$tax_subtotal += $tax_totals['tax_subtotal'];
				$tax_total += $tax_totals['tax_total'];
				
				// Format tax amount for each tax rule
				foreach ($tax_totals['tax'] as $level_index => $tax_rule) {
					// If a tax is already defined at this level, increment the values
					if (isset($tax[$level_index]))
						$tax_rule['amount'] += $tax[$level_index]['amount'];
					
					// Set tax percentage 
					$tax_rule['percentage'] = $tax_rule['percentage'];
					
					// Format the tax amount
					$tax_rule['amount_formatted'] = $this->CurrencyFormat->format($tax_rule['amount'], $currency);
					$tax[$level_index] = $tax_rule;
				}
				unset($tax_rule);
				
				// Only include setup fee in line total if it is taxable
				if ($setup_fee > 0 && $client_settings['setup_fee_tax'] == "true") {
					$tax_totals = $this->Invoices->getTaxTotals($setup_fee, $tax_rules);
					
					$tax_subtotal += $tax_totals['tax_subtotal'];
					$tax_total += $tax_totals['tax_total'];
					
					// Format tax amount for each tax rule
					foreach ($tax_totals['tax'] as $level_index => $tax_rule) {
						
						// If a tax is already defined at this level, increment the values
						if (isset($tax[$level_index]))
							$tax_rule['amount'] += $tax[$level_index]['amount'];
						
						// Set tax percentage 
						$tax_rule['percentage'] = $tax_rule['percentage'];
						
						// Format the tax amount
						$tax_rule['amount_formatted'] = $this->CurrencyFormat->format($tax_rule['amount'], $currency);
						$tax[$level_index] = $tax_rule;
					}
					unset($tax_rule);
				}
				
			}
		}
		unset($tax_rules);
		$total = $subtotal + $setup_fees + -$discount + $tax_subtotal;
		$total_w_tax = $subtotal + $setup_fees + -$discount + $tax_total;
		
		// Calculate discount (if inclusive)
		if ($coupon && $coupon->type == "inclusive") {
			#
			# TODO: Do exactly as we did for each package, but just for this discount
			# as a negative amount (so total = subtotal + -discount) and then
			# tax the negative discount and add it to the taxes as well that way
			# the taxes are reduced
			#
			#
		}
		
		if (isset($fees['setup']))
			$fees['setup'] = array('amount' => $fees['setup'], 'amount_formatted' => $this->CurrencyFormat->format($fees['setup'], $currency));
		if (isset($fees['cancel']))
			$fees['cancel'] = array('amount' => $fees['cancel'], 'amount_formatted' => $this->CurrencyFormat->format($fees['cancel'], $currency));
			
		$totals = array(
			'subtotal' => array('amount' => $subtotal, 'amount_formatted' => $this->CurrencyFormat->format($subtotal, $currency)),
			'total' => array('amount' => $total, 'amount_formatted' => $this->CurrencyFormat->format($total, $currency)),
			'total_w_tax' => array('amount' => $total_w_tax, 'amount_formatted' => $this->CurrencyFormat->format($total_w_tax, $currency)),
			'tax' => $tax,
			'discount' => array('amount' => -$discount, 'amount_formatted' => $this->CurrencyFormat->format(-$discount, $currency)),
			'coupon' => $coupon,
			'fees' => $fees
		);

		return $totals;
	}
	
	/**
	 * Calculate the total for all configurable options
	 *
	 * @param stdClass $package_pricing The package pricing object
	 * @param array $options A key/value pair array of config options
	 * @param string $currency The ISO 4217 currency code to convert to
	 * @param boolean $allow_conversion True to allow converion, false otherwise
	 * @return stdClass The stdClass object representing the config option totals
	 */
	private function calcConfigOptionTotals($package_pricing, array $options, $currency, $allow_conversion) {
		Loader::loadModels($this, array("PackageOptions"));
		
		$pricing = new stdClass();
		$pricing->price = 0;
		$pricing->setup_fee = 0;
		$pricing->cancel_fee = 0;
		$pricing->currency = $currency;
		
		foreach ($options as $option_id => $option_value) {
			$value = $this->PackageOptions->getValue($option_id, $option_value);
			
			if (!$value)
				continue;
			
			// If can't convert to given currency using package pricing currency
			if (!$allow_conversion)
				$currency = $package_pricing->currency;
			
			$price = $this->PackageOptions->getValuePrice($value->id, $package_pricing->term, $package_pricing->period, $package_pricing->currency, $currency);
			
			if ($price) {
				$pricing->price += ($value->value === null ? $option_value*$price->price : $price->price);
				$pricing->setup_fee += $price->setup_fee;
				$pricing->cancel_fee += $price->cancel_fee;
			}
		}
		return $pricing;
	}
	
	/**
	 * Convert pricing to the given currency if allowed
	 *
	 * @param stdClass $pricing A stdClass object representing a package pricing
	 * @param string $currency The ISO 4217 currency code to convert to
	 * @param boolean $allow_conversion True to allow converion, false otherwise
	 * @return mixed The stdClass object representing the converted pricing (if conversion allowed), null otherwise
	 */
	public function convertPricing($pricing, $currency, $allow_conversion) {
		if (!isset($this->Currencies))
			Loader::loadModels($this, array("Currencies"));
			
		$company_id = Configure::get("Blesta.company_id");
		
		if ($pricing->currency == $currency)
			return $pricing;
		elseif ($allow_conversion) {
			// Convert prices and set converted currency
			$pricing->price = $this->Currencies->convert($pricing->price, $pricing->currency, $currency, $company_id);
			$pricing->setup_fee = $this->Currencies->convert($pricing->setup_fee, $pricing->currency, $currency, $company_id);
			if (isset($pricing->cancel_fee))
				$pricing->cancel_fee = $this->Currencies->convert($pricing->cancel_fee, $pricing->currency, $currency, $company_id);
			$pricing->currency = $currency;
			
			return $pricing;
		}
		
		return null;
	}
	
	/**
	 * Return the total number of packages returned from Packages::getList(),
	 * useful in constructing pagination for the getList() method.
	 *
	 * @param string $status The status type of packages to retrieve ('active', 'inactive', 'restricted', default null for all)
	 * @return int The total number of packages
	 * @see Packages::getList()
	 */
	public function getListCount($status=null) {
		$this->Record = $this->getPackages($status);
		
		return $this->Record->where("packages.company_id", "=", Configure::get("Blesta.company_id"))->numResults();
	}
	
	/**
	 * Fetches all package groups belonging to a company, or optionally, all package
	 * groups belonging to a specific package
	 *
	 * @param int $company_id The company ID
	 * @param int $package_id The package ID to fetch groups of (optional, default null)
	 * @param string $type The type of group to fetch (null, standard, addon)
	 * @return mixed An array of stdClass objects representing package groups, or false if none found
	 */
	public function getAllGroups($company_id, $package_id=null, $type=null) {
		$fields = array("package_groups.id", "package_groups.name", "package_groups.type",
			"package_groups.company_id"
		);
		
		$this->Record->select($fields)->from("package_groups");
		
		if ($package_id != null) {
			$this->Record->innerJoin("package_group", "package_group.package_group_id", "=", "package_groups.id", false)->
				innerJoin("packages", "packages.id", "=", "package_group.package_id", false)->
				where("packages.id", "=", $package_id);
		}
		
		if ($type)
			$this->Record->where("package_groups.type", "=", $type);
		
		return $this->Record->where("package_groups.company_id", "=", $company_id)->
			order(array('package_groups.name' => "asc"))->fetchAll();
	}
	
	/**
	 * Returns all addon package groups for the given package group.
	 *
	 * @param int $parent_group_id The ID of the parent package group
	 * @return array A list of addon package groups
	 */
	public function getAllAddonGroups($parent_group_id) {
		$fields = array("package_groups.id", "package_groups.name", "package_groups.type",
			"package_groups.company_id"
		);
		
		return $this->Record->select($fields)->from("package_group_parents")->
			on("package_groups.type", "=", "addon")->
			innerJoin("package_groups", "package_groups.id", "=", "package_group_parents.group_id", false)->
			where("package_group_parents.parent_group_id", "=", $parent_group_id)->fetchAll();
	}
	
	/**
	 * Fetches all packages belonging to a specific package group
	 *
	 * @param int $package_group_id The ID of the package group
	 * @param string $status The status type of packages to retrieve ('active', 'inactive', 'restricted', default null for all)
	 * @return mixed An array of stdClass objects representing packages, or false if none exist
	 */
	public function getAllPackagesByGroup($package_group_id, $status=null) {
		$this->Record = $this->getPackages($status);
		$packages = $this->Record->innerJoin("package_group", "packages.id", "=", "package_group.package_id", false)->
			where("package_group.package_group_id", "=", $package_group_id)->
			order(array('package_group.order' => "ASC"))->
			fetchAll();
			
		foreach ($packages as &$package)
			$package->pricing = $this->getPackagePricing($package->id);
			
		return $packages;
	}
	
	/**
	 * Get all compatible packages
	 *
	 * @param int $package_id The ID of the package to fetch all compatible packages for
	 * @param int $module_id The ID of the module to include compatible packages for
	 * @param string $type The type of package group to include ("standard", "addon")
	 * @return array An array of stdClass objects, each representing a compatible package and its pricing
	 */
	public function getCompatiblePackages($package_id, $module_id, $type) {
		$subquery_record = clone $this->Record;
		$subquery_record->select(array("package_group.*"))->from("packages")->
			innerJoin("package_group", "package_group.package_id", "=", "packages.id", false)->
			where("packages.id", "=", $package_id);
		$subquery = $subquery_record->get();
		$values = $subquery_record->values;
		unset($subquery_record);
			
		
		$this->Record = $this->getPackages();
		$packages = $this->Record->
			innerJoin("package_group", "packages.id", "=", "package_group.package_id", false)->
			appendValues($values)->
			innerJoin(array($subquery => "temp"), "temp.package_group_id", "=", "package_group.package_group_id", false)->
			innerJoin("package_groups", "package_groups.id", "=", "package_group.package_group_id", false)->
			where("package_groups.type", "=", $type)->
			where("packages.module_id", "=", $module_id)->
			order(array('package_group.order' => "ASC"))->
			fetchAll();
		
		foreach ($packages as &$package)
			$package->pricing = $this->getPackagePricing($package->id);

		return $packages;
	}
	
	/**
	 * Fetches all emails created for the given package
	 *
	 * @param int $package_id The package ID to fetch email for
	 * @return array An array of stdClass objects representing email content
	 */
	private function getPackageEmails($package_id) {
		return $this->Record->select(array("lang", "html", "text"))->from("package_emails")->
			where("package_id", "=", $package_id)->fetchAll();
	}

	/**
	 * Fetches all pricing for the given package
	 *
	 * @param int $package_id The package ID to fetch pricing for
	 * @return array An array of stdClass objects representing package pricing
	 */
	private function getPackagePricing($package_id) {
		$fields = array("package_pricing.id", "package_pricing.pricing_id", "package_pricing.package_id", "pricings.term",
			"pricings.period", "pricings.price", "pricings.setup_fee",
			"pricings.cancel_fee", "pricings.currency");
		return $this->Record->select($fields)->from("package_pricing")->
			innerJoin("pricings", "pricings.id", "=", "package_pricing.pricing_id", false)->
			where("package_pricing.package_id", "=", $package_id)->
			order(array('period' => "ASC", 'term' => "ASC"))->fetchAll();
	}
	
	/**
	 * Fetches a single pricing, including its package's taxable status
	 *
	 * @param int $package_pricing_id The ID of the package pricing to fetch
	 * @return mixed A stdClass object representing the package pricing, false if no such package pricing exists
	 */
	private function getAPackagePricing($package_pricing_id) {
		$fields = array("package_pricing.id", "package_pricing.pricing_id", "package_pricing.package_id", "pricings.term",
			"pricings.period", "pricings.price", "pricings.setup_fee",
			"pricings.cancel_fee", "pricings.currency",
			"packages.taxable");
		return $this->Record->select($fields)->from("package_pricing")->
			innerJoin("pricings", "pricings.id", "=", "package_pricing.pricing_id", false)->
			innerJoin("packages", "packages.id", "=", "package_pricing.package_id", false)->
			where("package_pricing.id", "=", $package_pricing_id)->fetch();
	}
	
	/**
	 * Adds a pricing and package pricing record
	 *
	 * @param int package_id The pacakge ID to add pricing for
	 * @param array $vars An array of pricing info including:
	 * 	- company_id The company ID to add pricing for
	 * 	- term The term as an integer 1-65535 (optional, default 1)
	 * 	- period The period, 'day', 'week', 'month', 'year', 'onetime' (optional, default 'month')
	 * 	- price The price of this term (optional, default 0.00)
	 * 	- setup_fee The setup fee for this package (optional, default 0.00)
	 * 	- cancel_fee The cancelation fee for this package (optional, default 0.00)
	 * 	- currency The ISO 4217 currency code for this pricing (optional, default USD)
	 * @return int The package pricing ID
	 */
	private function addPackagePricing($package_id, array $vars) {
		if (!isset($this->Pricings))
			Loader::loadModels($this, array("Pricings"));
		
		$pricing_id = $this->Pricings->add($vars);
		
		if (($errors = $this->Pricings->errors())) {
			$this->Input->setErrors($errors);
			return;
		}
		
		if ($pricing_id) {
			$this->Record->insert("package_pricing",
				array('package_id' => $package_id, 'pricing_id' => $pricing_id));
			return $this->Record->lastInsertId();
		}
	}
	
	/**
	 * Edit package pricig, removes any pricing with a missing term
	 *
	 * @param int $package_pricing_id The package pricing ID to update
	 * @param array $vars An array of pricing info including:
	 * 	- package_id The pacakge ID to add pricing for
	 * 	- company_id The company ID to add pricing for
	 * 	- term The term as an integer 1-65535 (optional, default 1)
	 * 	- period The period, 'day', 'week', 'month', 'year', 'onetime' (optional, default 'month')
	 * 	- price The price of this term (optional, default 0.00)
	 * 	- setup_fee The setup fee for this package (optional, default 0.00)
	 * 	- cancel_fee The cancelation fee for this package (optional, default 0.00)
	 * 	- currency The ISO 4217 currency code for this pricing (optional, default USD)
	 */
	private function editPackagePricing($package_pricing_id, array $vars) {
		if (!isset($this->Pricings))
			Loader::loadModels($this, array("Pricings"));
		
		$package_pricing = $this->getAPackagePricing($package_pricing_id);
		
		if (isset($vars['term'])) {
			$fields = array("term", "period", "price", "setup_fee", "cancel_fee", "currency");
			$this->Pricings->edit($package_pricing->pricing_id, array_intersect_key($vars, array_flip($fields)));
		}
		// Remove the package pricing, term not set
		else {
			$this->Pricings->delete($package_pricing->pricing_id);
			
			$this->Record->where("id", "=", $package_pricing->id)->
				where("package_id", "=", $package_pricing->package_id)->
				from("package_pricing")->delete();
		}
	}

	/**
	 * Fetches all package meta data for the given package
	 *
	 * @param int $package_id The package ID to fetch meta data for
	 * @return array An array of stdClass objects representing package meta data
	 */	
	private function getPackageMeta($package_id) {
		$fields = array("key", "value", "serialized", "encrypted");
		$this->Record->select($fields)->from("package_meta")->
			where("package_id", "=", $package_id);
			
		return $this->formatRawMeta($this->Record->fetchAll());
	}
	
	/**
	 * Fetches all package group assignment for the given package
	 *
	 * @param int $package_id The package ID to fetch pricing for
	 * @return array An array of stdClass objects representing package groups
	 */
	private function getPackageGroups($package_id) {
		$fields = array("package_groups.id", "package_groups.name", "package_groups.type");
		return $this->Record->select($fields)->from("package_group")->
			innerJoin("package_groups", "package_groups.id", "=", "package_group.package_group_id", false)->
			where("package_group.package_id", "=", $package_id)->fetchAll();
	}
	
	/**
	 * Fetches all package option groups assigned to the given package
	 *
	 * @param int $package_id The package ID to fetch option groups for
	 * @return array An array of stdClass objects representing package option groups
	 */
	private function getPackageOptionGroups($package_id) {
		$fields = array("package_option_groups.id", "package_option_groups.name", "package_option_groups.description");
		return $this->Record->select($fields)->from("package_option")->
			innerJoin("package_option_groups", "package_option_groups.id", "=", "package_option.option_group_id", false)->
			where("package_option.package_id", "=", $package_id)->fetchAll();
	}
	
	/**
	 * Partially constructs the query required by both Packages::getList() and
	 * Packages::getListCount()
	 *
	 * @param string $status The status type of packages to retrieve ('active', 'inactive', 'restricted', default null for all)
	 * @return Record The partially constructed query Record object
	 */
	private function getPackages($status=null) {
		$fields = array("packages.id", "packages.module_id",
			"packages.name", "packages.description", "packages.description_html", "packages.qty",
			"packages.module_row", "packages.taxable", "packages.status",
			"packages.company_id", "REPLACE(packages.id_format, ?, packages.id_value)" => "id_code", "modules.name"=>"module_name",
			"packages.id_format", "packages.id_value"
		);
		
		$this->Record->select($fields)->
			appendValues(array($this->replacement_keys['packages']['ID_VALUE_TAG']))->
			from("packages")->
			leftJoin("module_rows", "module_rows.module_id", "=", "packages.module_id", false)->
			leftJoin("modules", "modules.id", "=", "module_rows.module_id", false);
		
		// Set a specific package status
		if ($status != null)
			$this->Record->where("packages.status", "=", $status);
		
		return $this->Record->group("packages.id");
	}
	
	/**
	 * Removes all existing groups set for the given package, replaces them with
	 * the given list of groups
	 *
	 * @param int $package_id The package to replace groups on
	 * @param array $groups A numerically-indexed array of group IDs
	 * @param mixed $groups An array of groups to add to the package, null or empty string to replace will nothing
	 */
	private function setGroups($package_id, $groups=null) {
		// Remove all existing groups
		$this->Record->from("package_group")->
			where("package_id", "=", $package_id)->delete();
			
		// Add all given groups
		if (!empty($groups) && is_array($groups)) {
			for ($i=0; $i<count($groups); $i++) {
				$vars = array(
					'package_id'=>$package_id,
					'package_group_id'=>$groups[$i]
				);
				$this->Record->insert("package_group", $vars);
			}
		}
	}
	
	/**
	 * Assigns the given package option group to the given package
	 *
	 * @param int $package_id The ID of the package to be assigned the option group
	 * @param array $option_groups A numerically-indexed array of package option groups to assign
	 */
	private function setOptionGroups($package_id, array $option_groups) {
		foreach ($option_groups as $option_group_id) {
			$vars = array('package_id' => $package_id, 'option_group_id' => $option_group_id);
			$this->Record->duplicate("option_group_id", "=", $option_group_id)->insert("package_option", $vars);
		}
	}
	
	/**
	 * Removes all package option groups assigned to this package
	 *
	 * @param int $package_id The ID of the package
	 */
	private function removeOptionGroups($package_id) {
		$this->Record->from("package_option")->where("package_id", "=", $package_id)->delete();
	}
	
	/**
	 * Updates the meta data for the given package, removing all existing data and replacing it with the given data
	 *
	 * @param int $package_id The ID of the package to update
	 * @param array $vars A numerically indexed array of meta data containing:
	 * 	- key The key for this meta field
	 * 	- value The value for this key
	 * 	- encrypted Whether or not this field should be encrypted (default 0, not encrypted)
	 */
	private function setMeta($package_id, array $vars) {
		
		// Delete all old meta data for this package
		$this->Record->from("package_meta")->
			where("package_id", "=", $package_id)->delete();
		
		// Add all new module data
		$fields = array("package_id", "key", "value", "serialized", "encrypted");
		$num_vars = count($vars);
		for ($i=0; $i<$num_vars; $i++) {
			$serialize = !is_scalar($vars[$i]['value']);
			$vars[$i]['package_id'] = $package_id;
			$vars[$i]['serialized'] = (int)$serialize;
			$vars[$i]['value'] = $serialize ? serialize($vars[$i]['value']) : $vars[$i]['value'];
			
			if (isset($vars[$i]['encrypted']) && $vars[$i]['encrypted'] == "1")
				$vars[$i]['value'] = $this->systemEncrypt($vars[$i]['value']);
			
			$this->Record->insert("package_meta", $vars[$i], $fields);
		}
	}
	
	/**
	 * Formats an array of raw meta stdClass objects into a stdClass
	 * object whose public member variables represent meta keys and whose values
	 * are automatically decrypted and unserialized as necessary.
	 *
	 * @param array $raw_meta An array of stdClass objects representing meta data
	 */
	private function formatRawMeta($raw_meta) {
		
		$meta = new stdClass();
		// Decrypt data as necessary
		foreach ($raw_meta as &$data) {
			if ($data->encrypted > 0)
				$data->value = $this->systemDecrypt($data->value);
				
			if ($data->serialized > 0)
				$data->value = unserialize($data->value);
			
			$meta->{$data->key} = $data->value;
		}
		return $meta;
	}
	
	/**
	 * Checks whether a service exists for a specific package ID
	 *
	 * @param int $package_id The package ID to check
	 * @return boolean True if a service exists for this package, false otherwise
	 */
	public function validateServiceExists($package_id) {
		$count = $this->Record->select("services.id")->from("package_pricing")->
			innerJoin("services", "services.pricing_id", "=", "package_pricing.id", false)->
			where("package_pricing.package_id", "=", $package_id)->numResults();
			
		if ($count > 0)
			return true;
		return false;
	}
	
	/**
	 * Validates the package 'status' field type
	 *
	 * @param string $status The status type
	 * @return boolean True if validated, false otherwise
	 */
	public function validateStatus($status) {
		switch ($status) {
			case "active":
			case "inactive":
			case "restricted":
				return true;
		}
		return false;
	}
	
	/**
	 * Validates that the term is valid for the period. That is, the term must be > 0
	 * if the period is something other than "onetime".
	 *
	 * @param int $term The Term to validate
	 * @param string $period The period to validate the term against
	 * @return boolean True if validated, false otherwise
	 */
	public function validateTerm($term, $period) {
		if ($period == "onetime")
			return true;
		return $term > 0;
	}
	
	/**
	 * Validates the pricing 'period' field type
	 *
	 * @param string $period The period type
	 * @return boolean True if validated, false otherwise
	 */
	public function validatePeriod($period) {
		$periods = $this->getPricingPeriods();
		
		if (isset($periods[$period]))
			return true;
		return false;
	}
	
	/**
	 * Validates that the given group belongs to the given company ID
	 *
	 * @param int $group_id The ID of the group to test
	 * @param int $company_id The ID of the company to validate exists for the given group
	 * @return boolean True if validated, false otherwise
	 */
	public function validateGroup($group_id, $company_id) {
		return (boolean)$this->Record->select(array("id"))->
			from("package_groups")->where("company_id", "=", $company_id)->fetch();
	}
	
	/**
	 * Validates that the given group is valid
	 *
	 * @param int $option_group_id The ID of the package option group to validate
	 * @return boolean True if the package option group is valid, or false otherwise
	 */
	public function validateOptionGroup($option_group_id, $company_id) {
		// Group may not be given
		if (empty($option_group_id))
			return true;
		
		// Check whether this is a valid option group
		$count = $this->Record->select(array("id"))->from("package_option_groups")->
			where("id", "=", $option_group_id)->where("company_id", "=", $company_id)->numResults();
		
		return ($count > 0);
	}
	
	/**
	 * Validates that the given price is in use
	 *
	 * @param string $term The term of the price point, if non-empty no check is performed.
	 * @param int $pricing_id The package pricing ID
	 * @return boolean True if the price is in use, false otherwise
	 */
	public function validatePriceInUse($term, $pricing_id) {
		if ($term != "" || $pricing_id == "" || !is_numeric($pricing_id))
			return false;
		
		return (boolean)$this->Record->select(array("id"))->from("services")->
			where("pricing_id", "=", $pricing_id)->fetch();
	}
	
	/**
	 * Formats the pricing term
	 *
	 * @param int $term The term length
	 * @param string $period The period of this term
	 * @return mixed The term formatted in accordance to the period, if possible
	 */
	public function formatPricingTerm($term, $period) {
		if ($period == "onetime")
			return 0;
		return $term;
	}
	
	/**
	 * Fetches the rules for adding/editing a package
	 *
	 * @return array The package rules
	 */
	private function getRules($vars) {
		$rules = array(
			// Package rules
			'module_id' => array(
				'exists' => array(
					'if_set' => true,
					'rule' => array(array($this, "validateExists"), "id", "modules"),
					'message' => $this->_("Packages.!error.module_id.exists")
				)
			),
			'name' => array(
				'empty' => array(
					'rule' => "isEmpty",
					'negate' => true,
					'message' => $this->_("Packages.!error.name.empty")
				)
			),
			'qty' => array(
				'format' => array(
					'if_set' => true,
					'rule' => array("matches", "/^([0-9]+)?$/"),
					'message' => $this->_("Packages.!error.qty.format")
				)
			),
			'option_groups[]' => array(
				'valid' => array(
					'if_set' => true,
					'rule' => array(array($this, "validateOptionGroup"), $this->ifSet($vars['company_id'])),
					'message' => $this->_("Packages.!error.option_groups[].valid")
				)
			),
			'module_row' => array(
				'format' => array(
					'if_set' => true,
					'rule' => array(array($this, "validateExists"), "id", "module_rows"),
					'message' => $this->_("Packages.!error.module_row.format")
				)
			),
			'module_group' => array(
				'format' => array(
					'if_set' => true,
					'rule' => array(array($this, "validateExists"), "id", "module_groups"),
					'message' => $this->_("Packages.!error.module_group.format")
				)
			),
			'taxable' => array(
				'format' => array(
					'if_set' => true,
					'rule' => "is_numeric",
					'message' => $this->_("Packages.!error.taxable.format")
				),
				'length' => array(
					'if_set' => true,
					'rule' => array("maxLength", 1),
					'message' => $this->_("Packages.!error.taxable.length")
				)
			),
			'single_term' => array(
				'valid' => array(
					'if_set' => true,
					'rule' => array("in_array", array("0","1")),
					'message' => $this->_("Packages.!error.single_term.valid")
				)
			),
			'status' => array(
				'format' => array(
					'rule' => array(array($this, "validateStatus")),
					'message' => $this->_("Packages.!error.status.format")
				)
			),
			'company_id' => array(
				'exists' => array(
					'rule' => array(array($this, "validateExists"), "id", "companies"),
					'message' => $this->_("Packages.!error.company_id.exists")
				)
			),		
			// Package Email rules
			'email_content[][lang]' => array(
				'empty' => array(
					'rule' => "isEmpty",
					'negate' => true,
					'message' => $this->_("Packages.!error.email_content[][lang].empty")
				),
				'length' => array(
					'rule' => array("maxLength", 5),
					'message' => $this->_("Packages.!error.email_content[][lang].length")
				)
			),
			// Package Pricing rules
			'pricing[][term]' => array(
				'format' => array(
					'if_set' => true,
					'pre_format' => array(array($this, "formatPricingTerm"), array('_linked'=>"pricing[][period]")),
					'rule' => "is_numeric",
					'message' => $this->_("Packages.!error.pricing[][term].format")
				),
				'length' => array(
					'if_set' => true,
					'rule' => array("maxLength", 5),
					'message' => $this->_("Packages.!error.pricing[][term].length")
				),
				'valid' => array(
					'if_set' => true,
					'rule' => array(array($this, "validateTerm"), array('_linked'=>"pricing[][period]")),
					'message' => $this->_("Packages.!error.pricing[][term].valid")
				),
				'deletable' => array(
					'rule' => array(array($this, "validatePriceInUse"), array('_linked' => "pricing[][id]")),
					'negate' => true,
					'message' => $this->_("Packages.!error.pricing[][term].deletable")
				)
			),
			'pricing[][period]' => array(
				'format' => array(
					'if_set' => true,
					'rule' => array(array($this, "validatePeriod")),
					'message' => $this->_("Packages.!error.pricing[][period].format")
				)
			),
			'pricing[][price]' => array(
				'format' => array(
					'if_set' => true,
					'pre_format' => array(array($this, "currencyToDecimal"), array('_linked'=>"pricing[][currency]"), 4),
					'rule' => "is_numeric",
					'message' => $this->_("Packages.!error.pricing[][price].format")
				)
			),
			'pricing[][setup_fee]' => array(
				'format' => array(
					'if_set' => true,
					'pre_format'=>array(array($this, "currencyToDecimal"), array('_linked'=>"pricing[][currency]"), 4),
					'rule' => "is_numeric",
					'message' => $this->_("Packages.!error.pricing[][setup_fee].format")
				)
			),
			'pricing[][cancel_fee]' => array(
				'format' => array(
					'if_set' => true,
					'pre_format'=>array(array($this, "currencyToDecimal"), array('_linked'=>"pricing[][currency]"), 4),
					'rule' => "is_numeric",
					'message' => $this->_("Packages.!error.pricing[][cancel_fee].format")
				)
			),
			'pricing[][currency]' => array(
				'format' => array(
					'if_set' => true,
					'rule' => array("matches", "/^(.*){3}$/"),
					'message' => $this->_("Packages.!error.pricing[][currency].format")
				)
			),
			'groups[]' => array(
				'exists' => array(
					'if_set' => true,
					'rule' => array(array($this, "validateExists"), "id", "package_groups"),
					'message' => $this->_("Packages.!error.groups[].exists")
				),
				'valid' => array(
					'if_set' => true,
					'rule' => array(array($this, "validateGroup"), isset($vars['company_id']) ? $vars['company_id'] : null),
					'message' => $this->_("Packages.!error.groups[].valid")
				)
			)
		);
		
		if (!isset($vars['module_row']) || $vars['module_row'] == 0)
			unset($rules['module_row']);
		
		return $rules;
	}
}
?>