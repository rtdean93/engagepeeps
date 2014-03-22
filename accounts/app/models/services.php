<?php
/**
 * Service management
 * 
 * @package blesta
 * @subpackage blesta.app.models
 * @copyright Copyright (c) 2010, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class Services extends AppModel {
	
	/**
	 * Initialize Services
	 */
	public function __construct() {
		parent::__construct();
		Language::loadLang(array("services"));
	}
	
	/**
	 * Returns the number of results available for the given status
	 *
	 * @param int $client_id The ID of the client to select status count values for
	 * @param string $status The status value to select a count of ('active', 'canceled', 'pending', 'suspended')
	 * @param boolean $children True to fetch all services, including child services, or false to fetch only services without a parent (optional, default true)
	 * @return int The number representing the total number of services for this client with that status
	 */
	public function getStatusCount($client_id, $status="active", $children=true) {
		$this->Record->select(array("id"))->from("services")->
			where("client_id", "=", $client_id)->where("status", "=", $status);
		
		if (!$children)
			$this->Record->where("parent_service_id", "=", null);
		
		return $this->Record->numResults();
	}
	
	/**
	 * Returns a list of services for the given client and status
	 *
	 * @param int $client_id The ID of the client to select services for
	 * @param string $status The status to filter by (optional, default "active"), one of:
	 * 	- active All active services
	 * 	- canceled All canceled services
	 * 	- pending All pending services
	 * 	- suspended All suspended services
	 * 	- in_review All services that require manual review before they may become pending
	 * 	- scheduled_cancellation All services scheduled to be canceled
	 * 	- all All active/canceled/pending/suspended/in_review
	 * @param int $page The page to return results for (optional, default 1)
	 * @param array $order_by The sort and order conditions (e.g. array('sort_field'=>"ASC"), optional)
	 * @param boolean $children True to fetch all services, including child services, or false to fetch only services without a parent (optional, default true)
	 * @return array An array of stdClass objects representing services
	 */
	public function getList($client_id=null, $status="active", $page=1, $order_by=array('date_added'=>"DESC"), $children=true) {
		
		if (!isset($this->ModuleManager))
			Loader::loadModels($this, array("ModuleManager"));
		
		// If sorting by term, sort by both term and period
		if (isset($order_by['term'])) {
			$temp_order_by = $order_by;
			
			$order_by = array('period'=>$order_by['term'], 'term'=>$order_by['term']);
			
			// Sort by any other fields given as well
			foreach ($temp_order_by as $sort=>$order) {
				if ($sort == "term")
					continue;
				
				$order_by[$sort] = $order;
			}
		}
		
		// Get a list of services
		$this->Record = $this->getServices($client_id, $status, $children);
		
		$services = $this->Record->order($order_by)->
			limit($this->getPerPage(), (max(1, $page) - 1)*$this->getPerPage())->fetchAll();
		
		foreach ($services as &$service) {
			// Service meta fields
			$service->fields = $this->getFields($service->id);
			// Collect package pricing data
			$service->package_pricing = $this->getPackagePricing($service->pricing_id);
			// Collect package data
			$service->package = $this->Record->select()->from("packages")->
				where("packages.id", "=", $service->package_pricing->package_id)->fetch();
				
			$service->name = $this->ModuleManager->moduleRpc($service->package->module_id, "getServiceName", array($service));
		}
		
		return $services;
	}
	
	/**
	 * Returns the total number of services for a client, useful
	 * in constructing pagination for the getList() method.
	 *
	 * @param int $client_id The client ID
	 * @param string $status The status type of the services to fetch (optional, default 'active'), one of:
	 * 	- active All active services
	 * 	- canceled All canceled services
	 * 	- pending All pending services
	 * 	- suspended All suspended services
	 * 	- in_review All services that require manual review before they may become pending
	 * 	- scheduled_cancellation All services scheduled to be canceled
	 * 	- all All active/canceled/pending/suspended/in_review
	 * @param boolean $children True to fetch all services, including child services, or false to fetch only services without a parent (optional, default true)
	 * @return int The total number of services
	 * @see Services::getList()
	 */
	public function getListCount($client_id, $status="active", $children=true) {
		$this->Record = $this->getServices($client_id, $status, $children);
		
		// Return the number of results
		return $this->Record->numResults();
	}
	
	/**
	 * Search services
	 *
	 * @param string $query The value to search services for
	 * @param int $page The page number of results to fetch (optional, default 1)
	 * @param boolean $search_fields If true will also search service fields for the value
	 * @return array An array of services that match the search criteria
	 */
	public function search($query, $page=1, $search_fields = false) {
		$this->Record = $this->searchServices($query, $search_fields);
		
		// Set order by clause
		$order_by = array();
		if (Configure::get("Blesta.id_code_sort_mode")) {
			foreach ((array)Configure::get("Blesta.id_code_sort_mode") as $key) {
				$order_by[$key] = "ASC";
			}
		}
		else
			$order_by = array("date_added"=>"ASC");
		
		return $this->Record->order($order_by)->
			limit($this->getPerPage(), (max(1, $page) - 1)*$this->getPerPage())->fetchAll();
	}
	
	/**
	 * Return the total number of services returned from Services::search(), useful
	 * in constructing pagination
	 *
	 * @param string $query The value to search services for
	 * @see Transactions::search()
	 */
	public function getSearchCount($query, $search_fields = false) {
		$this->Record = $this->searchServices($query, $search_fields);
		return $this->Record->numResults();
	}
	
	/**
	 * Determines whether a service has a parent services of the given status
	 *
	 * @param int $service_id The ID of the service to check
	 * @return boolean True if the service has a parent, false otherwise
	 */
	public function hasParent($service_id) {
		return (boolean)$this->Record->select()->from("services")->
			where("parent_service_id", "!=", null)->
			where("id", "=", $service_id)->fetch();
	}
	
	/**
	 * Determines whether a service has any child services of the given status
	 *
	 * @param int $service_id The ID of the service to check
	 * @param string $status The status of any child services to filter on (e.g. "active", "canceled", "pending", "suspended", "in_review", or null for any status) (optional, default null)
	 * @return boolean True if the service has children, false otherwise
	 */
	public function hasChildren($service_id, $status=null) {
		$this->Record->select()->from("services")->
			where("parent_service_id", "=", $service_id);
		
		if ($status)
			$this->Record->where("status", "=", $status);
		
		return ($this->Record->numResults() > 0);
	}
	
	/**
	 * Retrieves a list of all services that are child of the given parent service ID
	 *
	 * @param int $parent_service_id The ID of the parent service whose child services to fetch
	 * @param string $status The status type of the services to fetch (optional, default 'all'):
	 * 	- active All active services
	 * 	- canceled All canceled services
	 * 	- pending All pending services
	 * 	- suspended All suspended services
	 * 	- in_review All services that require manual review before they may become pending
	 * 	- scheduled_cancellation All services scheduled to be canceled
	 * 	- all All active/canceled/pending/suspended/in_review
	 * @return array A list of stdClass objects representing each child service
	 */
	public function getAllChildren($parent_service_id, $status="all") {
		if (!isset($this->ModuleManager))
			Loader::loadModels($this, array("ModuleManager"));
			
		// Get all child services
		$services = $this->getServices(null, $status)->
			where("services.parent_service_id", "=", $parent_service_id)->
			fetchAll();
		
		foreach ($services as &$service) {
			// Service meta fields
			$service->fields = $this->getFields($service->id);
			// Collect package pricing data
			$service->package_pricing = $this->Record->select()->
				from("package_pricing")->
				where("package_pricing.id", "=", $service->pricing_id)->fetch();
			// Collect package data
			$service->package = $this->Record->select()->from("packages")->
				where("packages.id", "=", $service->package_pricing->package_id)->fetch();
				
			$service->name = $this->ModuleManager->moduleRpc($service->package->module_id, "getServiceName", array($service));
		}
		
		return $services;
	}
	
	/**
	 * Retrieves a list of services ready to be renewed for this client group
	 *
	 * @param int $client_group_id The client group ID to fetch renewing services from
	 * @return array A list of stdClass objects representing services set ready to be renewed
	 */
	public function getAllRenewing($client_group_id) {
		Loader::loadModels($this, array("ClientGroups"));
		Loader::loadHelpers($this, array("Form"));
		
		// Determine whether services can be renewed (invoiced) if suspended
		$client_group_settings = $this->ClientGroups->getSettings($client_group_id);
		$client_group_settings = $this->Form->collapseObjectArray($client_group_settings, "value", "key");
		$inv_suspended_services = ((isset($client_group_settings['inv_suspended_services']) && $client_group_settings['inv_suspended_services'] == "true") ? true : false);
		$inv_days_before_renewal = abs((int)$client_group_settings['inv_days_before_renewal']);
		unset($client_group_settings);
		
		$fields = array(
			"services.*",
			"pricings.term", "pricings.period", "pricings.price",
			"pricings.setup_fee", "pricings.cancel_fee", "pricings.currency",
			'packages.id' => "package_id", "packages.name"
		);
		
		$this->Record->select($fields)->
			from("services")->
			innerJoin("package_pricing", "package_pricing.id", "=", "services.pricing_id", false)->
			innerJoin("pricings", "pricings.id", "=", "package_pricing.pricing_id", false)->
			innerJoin("packages", "packages.id", "=", "package_pricing.package_id", false)->
			innerJoin("clients", "services.client_id", "=", "clients.id", false)->
			innerJoin("client_groups", "client_groups.id", "=", "clients.client_group_id", false)->
			open()->
				where("services.status", "=", "active");
			
		// Also invoice suspended services
		if ($inv_suspended_services)
			$this->Record->orWhere("services.status", "=", "suspended");
		
		$this->Record->close();
		
		// Ensure only fetching records for the current company
		// whose renew date is <= (today + invoice days before renewal)
		$invoice_date = date("Y-m-d 23:59:59", strtotime(date("c") . " +" . $inv_days_before_renewal . " days"));
		$this->Record->where("client_groups.company_id", "=", Configure::get("Blesta.company_id"))->
			where("client_groups.id", "=", $client_group_id)->
			where("services.date_renews", "!=", null)->
			where("pricings.period", "!=", "onetime")->
			where("pricings.term", ">", "0")->
			where("services.date_renews", "<=", $this->dateToUtc($invoice_date))->
			order(array('services.client_id' => "ASC"));
		
		return $this->Record->fetchAll();
	}
	
	/**
	 * Retrieves a list of renewable paid services
	 *
	 * @param string $date The date after which to fetch paid renewable services
	 * @return array A list of services that have been paid and may be processed
	 */
	public function getAllRenewablePaid($date) {
		// Get all active services
		$this->Record = $this->getServices();
		$this->Record->where("date_last_renewed", "!=", null);
		
		$sub_query_sql = $this->Record->get();
		$values = $this->Record->values;
		$this->Record->reset();
		
		// Get all invoices and attached services greater than the given date
		return $this->Record->select(array("temp_services.*"))->
			from("invoices")->
			innerJoin("invoice_lines", "invoice_lines.invoice_id", "=", "invoices.id", false)->
			appendValues($values)->
			innerJoin(array($sub_query_sql => "temp_services"), "temp_services.id", "=", "invoice_lines.service_id", false)->
			where("invoices.date_closed", ">", $this->dateToUtc($date))->
			group(array("temp_services.id"))->
			fetchAll();
	}
	
	/**
	 * Retrieves a list of paid pending services
	 *
	 * @param int $client_group_id The ID of the client group whose paid pending invoices to fetch
	 * @return array A list of services that have been paid and are still pending
	 */
	public function getAllPaidPending($client_group_id) {
		$current_time = $this->dateToUtc(date("c"));
		
		// Get pending services that are neither canceled nor suspended
		$this->Record = $this->getServices(null, "pending");
		$this->Record->open()->
				where("services.date_suspended", "=", null)->
				orWhere("services.date_suspended", ">", $current_time)->
			close()->
			open()->
				where("services.date_canceled", "=", null)->
				orWhere("services.date_canceled", ">", $current_time)->
			close();
		
		$sub_query_sql = $this->Record->get();
		$values = $this->Record->values;
		$this->Record->reset();
		
		// Get all pending services that have been paid
		$services = $this->Record->select(array("temp_services.*"))->
			appendValues($values)->
			from(array($sub_query_sql => "temp_services"))->
			leftJoin("invoice_lines", "temp_services.id", "=", "invoice_lines.service_id", false)->
			on("invoices.status", "=", "active")->
			leftJoin("invoices", "invoice_lines.invoice_id", "=", "invoices.id", false)->
			innerJoin("clients", "clients.id", "=", "temp_services.client_id", false)->
			innerJoin("client_groups", "client_groups.id", "=", "clients.client_group_id", false)->
			where("client_groups.id", "=", $client_group_id)->
			open()->
				where("invoices.date_closed", "!=", null)->
				orWhere("invoices.id", "=", null)->
			close()->
			group(array("temp_services.id"))->
			fetchAll();
		
		// Fetch each services' fields and add them to the list
		foreach ($services as &$service) {
			// Get all fields
			$service->fields = $this->getFields($service->id);
			// Collect service options
			$service->options = $this->getOptions($service->id);
		}
		
		return $services;
	}
	
	/**
	 * Retrieves a list of services ready to be suspended
	 *
	 * @param int $client_group_id The ID of the client group
	 * @param string $suspension_date The date before which service would be considered suspended
	 * @return array A list of stdClass objects representing services pending suspension
	 */
	public function getAllPendingSuspension($client_group_id, $suspension_date) {
		$this->Record = $this->getServices(null, "active");
		
		return $this->Record->
			innerJoin("invoice_lines", "invoice_lines.service_id", "=", "services.id", false)->
			innerJoin("invoices", "invoices.id", "=", "invoice_lines.invoice_id", false)->
			where("invoices.status", "=", "active")->
			where("invoices.date_closed", "=", null)->
			where("invoices.date_due", "<=", $this->dateToUtc($suspension_date))->
			where("client_groups.id", "=", $client_group_id)->
			group(array("services.id"))->
			fetchAll();
	}
	
	/**
	 * Retrieves a list of paid suspended services ready to be unsuspended. Will
	 * only return services that were automatically suspended (not manually
	 * suspended by a staff member).
	 *
	 * @param int $client_group_id The ID of the client group
	 * @return array A list of stdClass objects representing services pending unsuspension
	 */
	public function getAllPendingUnsuspension($client_group_id) {
		
		$open_invoices = clone $this->Record;
		$open_invoices_sql = $open_invoices->
			select(array("invoice_lines.service_id"))->from("invoice_lines")->
			on("invoices.status", "=", "active")->
			on("invoices.date_closed", "=", null)->
			on("invoices.date_due", "<=", $this->dateToUtc(date("c")))->
			innerJoin("invoices", "invoices.id", "=", "invoice_lines.invoice_id", false)->
			where("services.id", "=", "invoice_lines.service_id", false)->
			group(array("services.id"))->
			get();
			
		$open_invoices_values = $open_invoices->values;
		unset($open_invoices);
		
		$this->Record = $this->getServices(null, "suspended");
		
		$sql = $this->Record->
			select(array('MAX(log_services.date_added)' => "log_date_suspended", "log_services.status", "log_services.staff_id"))->
			innerJoin("log_services", "log_services.service_id", "=", "services.id", false)->
			where("client_groups.id", "=", $client_group_id)->
			group(array("services.id"))->
			having("log_services.status", "=", "suspended")->
			having("log_services.staff_id", "=", null)->
			where("(" . $open_invoices_sql . ")", "=", null, true, false)->
			get();
		$values = $this->Record->values;
		$this->Record->reset();
		
		// Popoff the having clause...
		$having_values = (array)array_pop($values);
		
		return $this->Record->query($sql, array_merge($values, $open_invoices_values, $having_values))->fetchAll();
	}
	
	/**
	 * Retrieves a list of services ready to be canceled
	 *
	 * @return array A list of stdClass objects representing services pending cancelation
	 */
	public function getAllPendingCancelation() {
		// Get services set to be canceled
		$this->Record = $this->getServices();
		return $this->Record->where("services.date_canceled", "<=", $this->dateToUtc(date("c")))->
			where("services.status", "=", "active")->fetchAll();
	}
	
	/**
	 * Searches services of the given module that contains the given service
	 * field key/value pair.
	 *
	 * @param int $module_id The ID of the module to search services on
	 * @param string $key They service field key to search
	 * @param string $value The service field value to search
	 * @return array An array of stdClass objects, each containing a service
	 */
	public function searchServiceFields($module_id, $key, $value) {
		$this->Record = $this->getServices(null, "all");
		return $this->Record->innerJoin("module_rows", "module_rows.id", "=", "services.module_row_id", false)->
			on("service_fields.key", "=", $key)->on("service_fields.value", "=", $value)->
			innerJoin("service_fields", "service_fields.service_id", "=", "services.id", false)->
			where("module_rows.module_id", "=", $module_id)->
			group("services.id")->fetchAll();
	}
	
	/**
	 * Partially constructs the query for searching services
	 *
	 * @param string $query The value to search services for
	 * @param boolean $search_fields If true will also search service fields for the value
	 * @return Record The partially constructed query Record object
	 * @see Services::search(), Services::getSearchCount()
	 */
	private function searchServices($query, $search_fields = false) {
		$this->Record = $this->getServices(null, "all");

		if ($search_fields) {
			$this->Record->select(array('service_fields.value' => "service_field_value"))->
				leftJoin("module_rows", "module_rows.id", "=", "services.module_row_id", false)->
				on("service_fields.serialized", "=", 0)->
				on("service_fields.encrypted", "=", 0)->
				leftJoin("service_fields", "service_fields.service_id", "=", "services.id", false)->
				open()->
					where("service_fields.value", "=", null)->
					orLike("service_fields.value", "%" . $query . "%")->
				close();
		}
		
		$sub_query_sql = $this->Record->get();
		$values = $this->Record->values;
		$this->Record->reset();
		
		$this->Record = $this->Record->select()->appendValues($values)->from(array($sub_query_sql => "temp"))->
			like("CONVERT(temp.id_code USING utf8)", "%" . $query . "%", true, false)->
			orLike("temp.name", "%" . $query . "%");
		if ($search_fields)
			$this->Record->orLike("temp.service_field_value", "%" . $query . "%");
		
		return $this->Record;
	}
	
	/**
	 * Partially constructs the query required by Services::getList() and others
	 *
	 * @param int $client_id The client ID (optional)
	 * @param string $status The status type of the services to fetch (optional, default 'active'):
	 * 	- active All active services
	 * 	- canceled All canceled services
	 * 	- pending All pending services
	 * 	- suspended All suspended services
	 * 	- in_review All services that require manual review before they may become pending
	 * 	- scheduled_cancellation All services scheduled to be canceled
	 * 	- all All active/canceled/pending/suspended/in_review
	 * @param boolean $children True to fetch all services, including child services, or false to fetch only services without a parent (optional, default true)
	 * @return Record The partially constructed query Record object
	 */
	private function getServices($client_id=null, $status="active", $children=true) {
		$fields = array(
			"services.*",
			'REPLACE(services.id_format, ?, services.id_value)' => "id_code",
			'REPLACE(clients.id_format, ?, clients.id_value)' => "client_id_code",
			'pricings.term', 'packages.name',
			'contacts.first_name' => "client_first_name",
			'contacts.last_name' => "client_last_name",
			'contacts.company' => "client_company",
			'contacts.address1' => "client_address1",
			'contacts.email' => "client_email"
		);
		
		$this->Record->select($fields)->appendValues(array($this->replacement_keys['services']['ID_VALUE_TAG'], $this->replacement_keys['clients']['ID_VALUE_TAG']))->
			from("services")->
			innerJoin("package_pricing", "package_pricing.id", "=", "services.pricing_id", false)->
			innerJoin("pricings", "pricings.id", "=", "package_pricing.pricing_id", false)->
			innerJoin("packages", "packages.id", "=", "package_pricing.package_id", false)->
			innerJoin("clients", "services.client_id", "=", "clients.id", false)->
			innerJoin("client_groups", "client_groups.id", "=", "clients.client_group_id", false)->
			on("contacts.contact_type", "=", "primary")->
			innerJoin("contacts", "contacts.client_id", "=", "clients.id", false);
		
		// Filter out child services
		if (!$children)
			$this->Record->where("services.parent_service_id", "=", null);
		
		// Filter on client ID
		if ($client_id != null)
			$this->Record->where("services.client_id", "=", $client_id);
		
		// Filter on status
		if ($status != "all") {
			$custom_statuses = array("scheduled_cancellation");
			
			if (!in_array($status, $custom_statuses))
				$this->Record->where("services.status", "=", $status);
			else {
				// Custom status type
				switch ($status) {
					case "scheduled_cancellation":
						$this->Record->where("services.date_canceled", ">", $this->dateToUtc(date("c")));
						break;
					default:
						break;
				}
			}
		}
		
		// Ensure only fetching records for the current company
		$this->Record->where("client_groups.company_id", "=", Configure::get("Blesta.company_id"));
		
		return $this->Record;
	}
	
	/**
	 * Fetches the pricing information for a service
	 *
	 * @param int $service_id The ID of the service whose pricing info te fetch
	 * @param string $currency_code The ISO 4217 currency code to convert pricing to (optional, defaults to service's currency)
	 * @return mixed An stdClass object representing service pricing fields, or false if none exist
	 */
	public function getPricingInfo($service_id, $currency_code = null) {
		Loader::loadModels($this, array("Currencies"));
		if (!isset($this->ModuleManager))
			Loader::loadModels($this, array("ModuleManager"));
		
		$fields = array(
			"services.*",
			"pricings.term", "pricings.period", "pricings.price",
			"pricings.setup_fee", "pricings.cancel_fee", "pricings.currency",
			"packages.name", "packages.module_id", "packages.taxable"
		);
		$service = $this->Record->select($fields)->from("services")->
			innerJoin("package_pricing", "package_pricing.id", "=", "services.pricing_id", false)->
			innerJoin("pricings", "pricings.id", "=", "package_pricing.pricing_id", false)->
			innerJoin("packages", "packages.id", "=", "package_pricing.package_id", false)->
			innerJoin("clients", "services.client_id", "=", "clients.id", false)->
			innerJoin("client_groups", "client_groups.id", "=", "clients.client_group_id", false)->
			where("services.id", "=", $service_id)->
			fetch();
		
		if ($service) {
			// Fetch the service fields
			$service->fields = $this->getFields($service->id);
			
			// Get the client setting for tax exemption
			Loader::loadComponents($this, array("SettingsCollection"));
			$tax_exempt = $this->SettingsCollection->fetchClientSetting($service->client_id, null, "tax_exempt");
			$tax_exempt = (isset($tax_exempt['value']) && $tax_exempt['value'] == "true" ? true : false);
			
			// Get the name of the service
			$service_name = $this->ModuleManager->moduleRpc($service->module_id, "getServiceName", array($service));
			
			// Set the pricing info to return
			$taxable = (!$tax_exempt && ($service->taxable == "1"));
			$pricing_info = array(
				'package_name' => $service->name,
				'name' => $service_name,
				'price' => $service->price,
				'tax' => $taxable,
				'setup_fee' => $service->setup_fee,
				'cancel_fee' => $service->cancel_fee,
				'currency' => ($currency_code ? strtoupper($currency_code) : $service->currency)
			);
			
			// Convert amounts if another currency has been given
			if ($currency_code && $currency_code != $service->currency) {
				$pricing_info['price'] = $this->Currencies->convert($service->price, $service->currency, $currency_code, Configure::get("Blesta.company_id"));
				$pricing_info['setup_fee'] = $this->Currencies->convert($service->setup_fee, $service->currency, $currency_code, Configure::get("Blesta.company_id"));
				$pricing_info['cancel_fee'] = $this->Currencies->convert($service->cancel_fee, $service->currency, $currency_code, Configure::get("Blesta.company_id"));
			}
			/* Removed precision limit on pricing (2 -> 4 decimal places)
			else {
				$pricing_info['price'] = $this->Currencies->toDecimal($service->price, $service->currency, Configure::get("Blesta.company_id"));
				$pricing_info['setup_fee'] = $this->Currencies->toDecimal($service->setup_fee, $service->currency, Configure::get("Blesta.company_id"));
				$pricing_info['cancel_fee'] = $this->Currencies->toDecimal($service->cancel_fee, $service->currency, Configure::get("Blesta.company_id"));
			}
			*/
			
			return (object)$pricing_info;
		}
		
		return false;
	}
	
	/**
	 * Fetch a single service, including service field data
	 *
	 * @param int $service_id The ID of the service to fetch
	 * @return mixed A stdClass object representing the service, false if no such service exists
	 */
	public function get($service_id) {
		
		if (!isset($this->ModuleManager))
			Loader::loadModels($this, array("ModuleManager"));
			
		$fields = array("services.*", 'REPLACE(services.id_format, ?, services.id_value)' => "id_code");
		
		$service = $this->Record->select($fields)->appendValues(array($this->replacement_keys['services']['ID_VALUE_TAG']))->
			from("services")->where("id", "=", $service_id)->fetch();
		
		if ($service) {
			// Collect service fields
			$service->fields = $this->getFields($service->id);
			// Collect package pricing data
			$service->package_pricing = $this->getPackagePricing($service->pricing_id);
			// Collect package data
			$service->package = $this->Record->select()->from("packages")->
				where("packages.id", "=", $service->package_pricing->package_id)->fetch();
			// Collect service options
			$service->options = $this->getOptions($service->id);
				
			$service->name = $this->ModuleManager->moduleRpc($service->package->module_id, "getServiceName", array($service));
		}
		
		return $service;
	}
	
	/**
	 * Get package pricing
	 *
	 * @param int $pricing_id
	 * @return mixed stdClass object representing the package pricing, false otherwise
	 */
	private function getPackagePricing($pricing_id) {
		$fields = array("package_pricing.*", "pricings.term",
			"pricings.period", "pricings.price", "pricings.setup_fee",
			"pricings.cancel_fee", "pricings.currency"
		);
		return $this->Record->select($fields)->
			from("package_pricing")->
			innerJoin("pricings", "pricings.id", "=", "package_pricing.pricing_id", false)->
			where("package_pricing.id", "=", $pricing_id)->fetch();
	}
	
	/**
	 * Adds a new service to the system
	 *
	 * @param array $vars An array of service info including:
	 * 	- parent_service_id The ID of the service this service is a child of (optional)
	 * 	- package_group_id The ID of the package group this service was added from (optional)
	 * 	- pricing_id The package pricing schedule ID for this service
	 * 	- client_id The ID of the client to add the service under
	 * 	- module_row_id The module row to add the service under (optional, default module will decide)
	 * 	- coupon_id The ID of the coupon used for this service (optional)
	 * 	- qty The quanity consumed by this service (optional, default 1)
	 *	- status The status of this service (optional, default 'pending'):
	 * 		- active
	 * 		- canceled
	 * 		- pending
	 * 		- suspended
	 * 		- in_review
	 * 	- date_added The date this service is added (default to today's date UTC)
	 * 	- date_renews The date the service renews (optional, default calculated by package term)
	 * 	- date_last_renewed The date the service last renewed (optional)
	 * 	- date_suspended The date the service was last suspended (optional)
	 * 	- date_canceled The date the service was last canceled (optional)
	 * 	- use_module Whether or not to use the module when creating the service ('true','false', default 'true', forced 'false' if status is 'pending' or 'in_review')
	 * 	- configoptions An array of key/value pairs of package options where the key is the package option ID and the value is the option value (optional)
	 * 	- * Any other service field data to pass to the module
	 * @param array $packages A numerically indexed array of packages ordered along with this service to determine if the given coupon may be applied
	 * @param boolean $notify True to notify the client by email regarding this service creation, false to not send any notification (optional, default false)
	 * @return int The ID of this service, void if error
	 */
	public function add(array $vars, array $packages = null, $notify = false) {
		
		// Validate that the service can be added
		$vars = $this->validate($vars, $packages);
		
		if ($errors = $this->Input->errors())
			return;		

		if (!isset($vars['status']))
			$vars['status'] = "pending";
		if (!isset($vars['use_module']))
			$vars['use_module'] = "true";
		
		// If status is pending or in_review can't allow module to add
		if ($vars['status'] == "pending" || $vars['status'] == "in_review")
			$vars['use_module'] = "false";
		
		if (!isset($this->ModuleManager))
			Loader::loadModels($this, array("ModuleManager"));
		if (!isset($this->Packages))
			Loader::loadModels($this, array("Packages"));
		if (!isset($this->PackageOptions))
			Loader::loadModels($this, array("PackageOptions"));
			
		$module_data = $this->getModuleClassByPricingId($vars['pricing_id']);
		
		if ($module_data) {
			$module = $this->ModuleManager->initModule($module_data->id, Configure::get("Blesta.company_id"));
		
			if ($module) {
				
				// Find the package and parent service/package used for this service
				$parent_package = null;
				$parent_service = null;
				$package = $this->Packages->getByPricingId($vars['pricing_id']);
				$config_options = isset($vars['configoptions']) ? $vars['configoptions'] : null;
				
				if (isset($vars['parent_service_id'])) {
					$parent_service = $this->get($vars['parent_service_id']);
					
					if ($parent_service)
						$parent_package = $this->Packages->getByPricingId($parent_service->pricing_id);
				}
				
				// Set the module row to use if not given
				if (!isset($vars['module_row_id'])) {
					
					// Set module row to that defined for the package if available
					if ($package->module_row)
						$vars['module_row_id'] = $package->module_row;
					// If no module row defined for the package, let the module decide which row to use
					else
						$vars['module_row_id'] = $module->selectModuleRow($package->module_group);
				}
				$module->setModuleRow($module->getModuleRow($vars['module_row_id']));				
				
				// Reformat $vars[configoptions] to support name/value fields defined by the package options
				if (isset($vars['configoptions']) && is_array($vars['configoptions']))
					$vars['configoptions'] = $this->PackageOptions->formatOptions($vars['configoptions']);
				
				// Add through the module
				$service_info = $module->addService($package, $vars, $parent_package, $parent_service, $vars['status']);
					
				// Set any errors encountered attempting to add the service
				if (($errors = $module->errors())) {
					$this->Input->setErrors($errors);
					return;
				}
				
				// Fetch company settings on services
				Loader::loadComponents($this, array("SettingsCollection"));
				$company_settings = $this->SettingsCollection->fetchSettings(null, Configure::get("Blesta.company_id"));
				
				// Creates subquery to calculate the next service ID value on the fly
				/*
				$values = array($company_settings['services_start'], $company_settings['services_increment'],
					$company_settings['services_start'], $company_settings['services_increment'],
					$company_settings['services_start'], $company_settings['services_pad_size'],
					$company_settings['services_pad_str']);
				*/
				$values = array($company_settings['services_start'], $company_settings['services_increment'],
					$company_settings['services_start']);
				
				$sub_query = new Record();
				/*
				$sub_query->select(array("LPAD(IFNULL(GREATEST(MAX(t1.id_value),?)+?,?), " .
					"GREATEST(CHAR_LENGTH(IFNULL(MAX(t1.id_value)+?,?)),?),?)"), false)->
				*/
				$sub_query->select(array("IFNULL(GREATEST(MAX(t1.id_value),?)+?,?)"), false)->
					appendValues($values)->
					from(array("services"=>"t1"))->
					innerJoin("clients", "clients.id", "=", "t1.client_id", false)->
					innerJoin("client_groups", "client_groups.id", "=", "clients.client_group_id", false)->
					where("client_groups.company_id", "=", Configure::get("Blesta.company_id"))->
					where("t1.id_format", "=", $company_settings['services_format']);
				// run get on the query so $sub_query->values are built
				$sub_query->get();
				
				// Copy record so that it is not overwritten during validation
				$record = clone $this->Record;	
				$this->Record->reset();
				
				$vars['id_format'] = $company_settings['services_format'];
				// id_value will be calculated on the fly using a subquery
				$vars['id_value'] = $sub_query;
				
				// Attempt to set cancellation date if package is single term
				if ($vars['status'] == "active" && isset($package->single_term) && $package->single_term == 1 && !isset($vars['date_canceled'])) {
					if (isset($vars['date_renews']))
						$vars['date_canceled'] = $vars['date_renews'];
				}
				
				// Add the service
				$fields = array("id_format", "id_value", "parent_service_id", "package_group_id", "pricing_id",
					"client_id", "module_row_id", "coupon_id", "qty", "status", "date_added",
					"date_renews", "date_last_renewed", "date_suspended", "date_canceled");
				
				// Assign subquery values to this record component
				$this->Record->appendValues($sub_query->values);
				// Ensure the subquery value is set first because its the first value
				$vars = array_merge(array('id_value'=>null), $vars);
				
				$this->Record->insert("services", $vars, $fields);

				$service_id = $this->Record->lastInsertId();
				
				// Add all service fields
				if (is_array($service_info))
					$this->setFields($service_id, $service_info);
					
				// Add all service options
				if (is_array($config_options))
					$this->setOptions($service_id, $config_options);
				
				// Decrement usage of quantity
				$this->decrementQuantity(isset($vars['qty']) ? $vars['qty'] : 1, $vars['pricing_id'], false);
				
				// Send an email regarding this service creation, only when active
				if ($notify && $vars['status'] == "active")
					$this->sendNotificationEmail($this->get($service_id), $package, $vars['client_id']);
				
				return $service_id;
			}
		}
	}
	
	/**
	 * Edits a service. Only one module action may be performend at a time. For
	 * example, you can't change the pricing_id and edit the module service
	 * fields in a single request.
	 *
	 * @param int $service_id The ID of the service to edit
	 * @param array $vars An array of service info:
	 * 	- parent_service_id The ID of the service this service is a child of
	 * 	- package_group_id The ID of the package group this service was added from
	 * 	- pricing_id The package pricing schedule ID for this service
	 * 	- client_id The ID of the client this service belongs to
	 * 	- module_row_id The module row to add the service under
	 * 	- coupon_id The ID of the coupon used for this service
	 * 	- qty The quanity consumed by this service
	 *	- status The status of this service:
	 * 		- active
	 * 		- canceled
	 * 		- pending
	 * 		- suspended
	 * 		- in_review
	 * 	- date_added The date this service is added
	 * 	- date_renews The date the service renews
	 * 	- date_last_renewed The date the service last renewed
	 * 	- date_suspended The date the service was last suspended
	 * 	- date_canceled The date the service was last canceled
	 * 	- use_module Whether or not to use the module for this request ('true','false', default 'true')
	 * 	- configoptions An array of key/value pairs of package options where the key is the package option ID and the value is the option value (optional)
	 * 	- * Any other service field data to pass to the module
	 * @param boolean $bypass_module $vars['use_module'] notifies the module of whether
	 * 	or not it should internally use its module connection to process the request, however
	 * 	in some instances it may be necessary to prevent the module from being notified of
	 * 	the request altogether. If true, this will prevent the module from being notified of the request.
	 * @param boolean $notify If true and the service is set to active will send the service activation notification
	 * @return int The ID of this service, void if error
	 */
	public function edit($service_id, array $vars, $bypass_module = false, $notify = false) {
		$service = $this->get($service_id);
		
		if (isset($vars['date_renews']) && $service->date_last_renewed != null)
			$vars['date_last_renewed'] = $this->dateToUtc(strtotime($service->date_last_renewed . "Z"), "c");
		
		if (!isset($vars['use_module']))
			$vars['use_module'] = "true";
			
		if (!isset($vars['pricing_id']))
			$vars['pricing_id'] = $service->pricing_id;
		
		if (!isset($vars['qty']))
			$vars['qty'] = $service->qty;
			
		$vars['current_qty'] = $service->qty;

		if (!isset($this->PackageOptions))
			Loader::loadModels($this, array("PackageOptions"));

		$this->Input->setRules($this->getRules($vars, true));
		
		if ($this->Input->validates($vars)) {
			
			extract($this->getRelations($service_id));

			$package_from = clone $package;
			$pricing_id = $service->pricing_id;
			$config_options = isset($vars['configoptions']) ? $vars['configoptions'] : null;
			
			// If changing pricing ID, load up module with the new pricing ID
			if (isset($vars['pricing_id']) && $vars['pricing_id'] != $pricing_id) {
				$pricing_id = $vars['pricing_id'];
				
				$package = $this->Packages->getByPricingId($pricing_id);
			}
			
			$module_data = $this->getModuleClassByPricingId($pricing_id);
			
			if ($module_data && !$bypass_module) {
				$module = $this->ModuleManager->initModule($module_data->id, Configure::get("Blesta.company_id"));
			
				if ($module) {
					
					// Set the module row used for this service
					$module_row_id = $service->module_row_id;
					// If changing module row ID, set the correct module row for this service
					if (isset($vars['module_row_id']))
						$module_row_id = $vars['module_row_id'];
					$module->setModuleRow($module->getModuleRow($module_row_id));
					
					// Reformat $vars[configoptions] to support name/value fields defined by the package options
					if (isset($vars['configoptions']) && is_array($vars['configoptions']))
						$vars['configoptions'] = $this->PackageOptions->formatOptions($vars['configoptions']);
					elseif (!isset($vars['configoptions'])) {
						$vars['configoptions'] = array();
						foreach ($service->options as $option) {
							$vars['configoptions'][$option->option_name] = $option->option_value;
						}
						unset($option);
					}
					
					$service_info = null;
					
					if (isset($vars['pricing_id']) && $service->pricing_id != $vars['pricing_id'] && $vars['use_module'] == "true")
						$module->changeServicePackage($package_from, $package, $service, $parent_package, $parent_service);
					// If service is currently pending and status is now "active", call addService on the module
					elseif ($service->status == "pending" && isset($vars['status']) && $vars['status'] == "active") {
						$vars['pricing_id'] = $service->pricing_id;
						$vars['client_id'] = $service->client_id;
						$service_info = $module->addService($package, $vars, $parent_package, $parent_service, $vars['status']);
					}
					else
						$service_info = $module->editService($package, $service, $vars, $parent_package, $parent_service);
					
					if (($errors = $module->errors())) {
						$this->Input->setErrors($errors);
						return;
					}
					
					// Set all service fields (if any given)
					if (is_array($service_info))
						$this->setFields($service_id, $service_info);
						
					// Add all service options
					if (is_array($config_options))
						$this->setOptions($service_id, $config_options);
						
					// Decrement usage of quantity
					$this->decrementQuantity(isset($vars['qty']) ? $vars['qty'] : 1, $vars['pricing_id'], false, $service->qty);
					
					// Send an email regarding this service creation, only when active
					if ($notify && isset($vars['status']) && $vars['status'] == "active")
						$this->sendNotificationEmail($this->get($service_id), $package, $service->client_id);
				}
			}
			
			// Attempt to set cancellation date if package is single term
			if ($service->status == "pending" && $vars['status'] == "active" &&
				isset($package->single_term) && $package->single_term == 1 && !isset($vars['date_canceled'])) {
				if (isset($vars['date_renews']))
					$vars['date_canceled'] = $vars['date_renews'];
				else
					$vars['date_canceled'] = $service->date_renews;
			}
			
			$fields = array(
				"parent_service_id", "package_group_id", "pricing_id", "client_id", "module_row_id",
				"coupon_id", "qty", "status", "date_added", "date_renews", "date_last_renewed",
				"date_suspended", "date_canceled"
			);
			
			// Only update if $vars contains something in $fields
			$interset = array_intersect_key($vars, array_flip($fields));
			if (!empty($interset))
				$this->Record->where("services.id", "=", $service_id)->update("services", $vars, $fields);
			
			return $service_id;
		}
	}
	
	/**
	 * Permanently deletes a pending service from the system
	 *
	 * @param int $service_id The ID of the pending service to delete
	 */
	public function delete($service_id) {
		// Set delete rules
		// A service may not be deleted if it has any children unless those children are all canceled
		$rules = array(
			'service_id' => array(
				'has_children' => array(
					'rule' => array(array($this, "validateHasChildren"), "canceled"),
					'negate' => true,
					'message' => $this->_("Services.!error.service_id.has_children")
				)
			),
			'status' => array(
				'valid' => array(
					'rule' => array("in_array", array("pending", "in_review")),
					'message' => $this->_("Services.!error.status.valid")
				)
			)
		);
		
		// Fetch the service's status
		$status = "";
		if (($service = $this->get($service_id)))
			$status = $service->status;
		
		$vars = array('service_id' => $service_id, 'status' => $status);
		$this->Input->setRules($rules);
		
		if ($this->Input->validates($vars)) {
			// Delete the pending service
			$this->Record->from("services")->
				leftJoin("service_fields", "service_fields.service_id", "=", "services.id", false)->
				where("services.id", "=", $service_id)->
				delete(array("services.*", "service_fields.*"));
		}
	}
	
	/**
	 * Sends the service (un)suspension email
	 *
	 * @param string $type The type of email to send (i.e. "suspend" or "unsuspend")
	 * @param stdClass $service An object representing the service
	 * @param stdClass $package An object representing the package associated with the service
	 */
	private function sendSuspensionNoticeEmail($type, $service, $package) {
		Loader::loadModels($this, array("Clients", "Contacts", "Emails"));
		
		// Fetch the client
		$client = $this->Clients->get($service->client_id);
		
		// Format package pricing
		if (!empty($service->package_pricing)) {
			Loader::loadModels($this, array("Currencies", "Packages"));
			
			// Format the currency values
			$service->package_pricing->price_formatted = $this->Currencies->toCurrency($service->package_pricing->price, $service->package_pricing->currency, $package->company_id);
			$service->package_pricing->setup_fee_formatted = $this->Currencies->toCurrency($service->package_pricing->setup_fee, $service->package_pricing->currency, $package->company_id);
			$service->package_pricing->cancel_fee_formatted = $this->Currencies->toCurrency($service->package_pricing->cancel_fee, $service->package_pricing->currency, $package->company_id);
			
			// Set pricing period to a language value
			$package_period_lang = $this->Packages->getPricingPeriods();
			if (isset($package_period_lang[$service->package_pricing->period]))
				$service->package_pricing->period_formatted = $package_period_lang[$service->package_pricing->period];
		}
		
		// Add each service field as a tag
		if (!empty($service->fields)) {
			$fields = array();
			foreach ($service->fields as $field)
				$fields[$field->key] = $field->value;
			$service = (object)array_merge((array)$service, $fields);
		}
		
		// Add each package meta field as a tag
		if (!empty($package->meta)) {
			$fields = array();
			foreach ($package->meta as $key => $value)
				$fields[$key] = $value;
			$package = (object)array_merge((array)$package, $fields);
		}
		
		$tags = array(
			'contact' => $this->Contacts->get($client->contact_id),
			'package' => $package,
			'pricing' => $service->package_pricing,
			'service' => $service,
			'client' => $client
		);
		
		$action = ($type == "suspend" ? "service_suspension" : "service_unsuspension");
		$this->Emails->send($action, $package->company_id, $client->settings['language'], $client->email, $tags, null, null, null, array('to_client_id' => $client->id));
	}
	
	/**
	 * Sends a service confirmation email
	 *
	 * @param stdClass $service An object representing the service created
	 * @param stdClass $package An object representing the package associated with the service
	 * @param int $client_id The ID of the client to send the notification to
	 */
	private function sendNotificationEmail($service, $package, $client_id) {
		Loader::loadModels($this, array("Clients", "Contacts", "Emails", "ModuleManager"));
		
		// Fetch the client
		$client = $this->Clients->get($client_id);
		
		// Look for the correct language of the email template to send, or default to English
		$service_email_content = null;
		foreach ($package->email_content as $index => $email) {
			// Save English so we can use it if the default language is not available
			if ($email->lang == "en_us")
				$service_email_content = $email;
			
			// Use the client's default language
			if ($client->settings['language'] == $email->lang) {
				$service_email_content = $email;
				break;
			}
		}
		
		// Set all tags for the email
		$language_code = ($service_email_content ? $service_email_content->lang : null);
		
		// Get the module and set the module host name
		$module = $this->ModuleManager->initModule($package->module_id, $package->company_id);
		$module_row = $this->ModuleManager->getRow($service->module_row_id);
		
		// Set only the module host name and nameservers as fields
		$module_fields = array();
		if (!empty($module_row->meta)) {
			foreach ($module_row->meta as $key => $value) {
				if ($key == "host_name" || $key == "name_servers") {
					$module_fields[$key] = $value;
				}
			}
		}
		$module = (object)$module_fields;
		
		// Format package pricing
		if (!empty($service->package_pricing)) {
			Loader::loadModels($this, array("Currencies", "Packages"));
			
			// Set pricing period to a language value
			$package_period_lang = $this->Packages->getPricingPeriods();
			if (isset($package_period_lang[$service->package_pricing->period]))
				$service->package_pricing->period = $package_period_lang[$service->package_pricing->period];
		}
		
		// Add each service field as a tag
		if (!empty($service->fields)) {
			$fields = array();
			foreach ($service->fields as $field)
				$fields[$field->key] = $field->value;
			$service = (object)array_merge((array)$service, $fields);
		}
		
		// Add each package meta field as a tag
		if (!empty($package->meta)) {
			$fields = array();
			foreach ($package->meta as $key => $value)
				$fields[$key] = $value;
			$package = (object)array_merge((array)$package, $fields);
		}
		
		$tags = array(
			'contact' => $this->Contacts->get($client->contact_id),
			'package' => $package,
			'pricing' => $service->package_pricing,
			'module' => $module,
			'service' => $service,
			'client' => $client,
			'package.email_html' => (isset($service_email_content->html) ? $service_email_content->html : ""),
			'package.email_text' => (isset($service_email_content->text) ? $service_email_content->text : "")
		);
		
		$this->Emails->send("service_creation", $package->company_id, $language_code, $client->email, $tags, null, null, null, array('to_client_id' => $client->id));
	}
	
	/**
	 * Fetches all relations (e.g. packages and services) for the given service ID
	 *
	 * @param int $service_id The ID of the service to fetch relations for
	 * @return array A array consisting of:
	 * 	- service The given service
	 * 	- package The service's package
	 * 	- parent_service The parent service
	 * 	- parent_package The parent service's package
	 */
	private function getRelations($service_id) {
		if (!isset($this->Packages))
			Loader::loadModels($this, array("Packages"));
		
		$service = $this->get($service_id);
		$package = $this->Packages->getByPricingId($service->pricing_id);
		$parent_package = null;
		$parent_service = null;
		
		if ($service->parent_service_id) {
			$parent_service = $this->get($service->parent_service_id);
			
			if ($parent_service)
				$parent_package = $this->Packages->getByPricingId($parent_service->pricing_id);
		}
		
		return array(
			'service' => $service,
			'package' => $package,
			'parent_service' => $parent_service,
			'parent_package' => $parent_package
		);
	}
	
	/**
	 * Schedule a service for cancellation. All cancellations requests are processed
	 * by the cron.
	 *
	 * @param int $service_id The ID of the service to schedule cancellation
	 * @param array $vars An array of service info including:
	 * 	- date_canceled The date the service is to be canceled. Possible values:
	 * 		- 'end_of_term' Will schedule the service to be canceled at the end of the current term
	 * 		- date greater than now will schedule the service to be canceled on that date
	 * 		- date less than now will immediately cancel the service
	 * 	- use_module Whether or not to use the module when canceling the service, if canceling now ('true','false', default 'true')
	 */
	public function cancel($service_id, array $vars) {
		
		if (!isset($vars['use_module']))
			$vars['use_module'] = "true";
		if (isset($vars['status']))
			unset($vars['status']);
			
		if (!isset($vars['date_canceled']))
			$vars['date_canceled'] = date("c");
		
		$rules = array(
			//'date_canceled' must be either a valid date or 'end_of_term'
			'date_canceled' => array(
				'valid' => array(
					'rule' => array(array($this, "validateDateCanceled")),
					'message' => $this->_("Services.!error.date_canceled.valid")
				)
			)
		);
		$this->Input->setRules($rules);
		
		if ($this->Input->validates($vars)) {
			
			extract($this->getRelations($service_id));
			
			if ($vars['date_canceled'] == "end_of_term")
				$vars['date_canceled'] = $service->date_renews;
			else
				$vars['date_canceled'] = $this->dateToUtc($vars['date_canceled']);

			// If date_canceled is greater than now use module must be false
			if (strtotime($vars['date_canceled']) > time())
				$vars['use_module'] = "false";
			// Set service to canceled if cancel date is <= now
			else
				$vars['status'] = "canceled";
				
				
			// Cancel the service using the module
			if ($vars['use_module'] == "true") {

				if (!isset($this->ModuleManager))
					Loader::loadModels($this, array("ModuleManager"));
				
				$module_data = $this->getModuleClassByPricingId($service->pricing_id);
				
				if ($module_data) {
					$module = $this->ModuleManager->initModule($module_data->id, Configure::get("Blesta.company_id"));
				
					if ($module) {
						// Set the module row used for this service
						$module->setModuleRow($module->getModuleRow($service->module_row_id));
						
						$service_info = $module->cancelService($package, $service, $parent_package, $parent_service);
						
						if (($errors = $module->errors())) {
							$this->Input->setErrors($errors);
							return;
						}
						
						// Set all service fields (if any given)
						if (is_array($service_info))
							$this->setFields($service_id, $service_info);
					}
				}
			}
			
			// Update the service
			$this->Record->where("services.id", "=", $service_id)->
				update("services", $vars, array("date_canceled", "status"));
			
			// Create an invoice regarding this cancelation
			if ($service->package_pricing->period != "onetime" && $service->package_pricing->cancel_fee > 0 && $service->date_renews != $vars['date_canceled']) {
				Loader::loadModels($this, array("Clients", "Invoices"));
				Loader::loadComponents($this, array("SettingsCollection"));
				
				// Get the client settings
				$client_settings = $this->SettingsCollection->fetchClientSettings($service->client_id, $this->Clients);
				
				// Get the pricing info
				if ($client_settings['default_currency'] != $service->package_pricing->currency)
					$pricing_info = $this->getPricingInfo($service->id, $client_settings['default_currency']);
				else
					$pricing_info = $this->getPricingInfo($service->id);
				
				// Create the invoice
				if ($pricing_info) {
					$invoice_vars = array(
						'client_id' => $service->client_id,
						'date_billed' => date("c"),
						'date_due' => date("c"),
						'status' => "active",
						'currency' => $pricing_info->currency,
						'delivery' => array($client_settings['inv_method']),
						'lines' => array(
							array(
								'service_id' => $service->id,
								'description' => Language::_("Invoices.!line_item.service_cancel_fee_description", true, $pricing_info->package_name, $pricing_info->name),
								'qty' => 1,
								'amount' => $pricing_info->cancel_fee,
								'tax' => (!isset($client_settings['cancelation_fee_tax']) || $client_settings['cancelation_fee_tax'] == "true" ? $pricing_info->tax : 0)
							)
						)
					);
					
					$this->Invoices->add($invoice_vars);
				}
			}
		}
	}
	
	/**
	 * Removes the scheduled cancellation for the given service
	 *
	 * @param int $service_id The ID of the service to remove scheduled cancellation from
	 */
	public function unCancel($service_id) {
		// Update the service
		$this->Record->where("services.id", "=", $service_id)->
			where("services.status", "!=", "canceled")->
			update("services", array('date_canceled' => null));
	}
	
	/**
	 * Suspends a service
	 *
	 * @param int $service_id The ID of the service to suspend
	 * @param array $vars An array of info including:
	 * 	- use_module Whether or not to use the module when suspending the service ('true','false', default 'true')
	 * 	- staff_id The ID of the staff member that issued the service suspension
	 */
	public function suspend($service_id, array $vars=array()) {
		
		if (!isset($vars['use_module']))
			$vars['use_module'] = "true";
		$vars['date_suspended'] = $this->dateToUtc(date("c"));
		$vars['status'] = "suspended";
			
		extract($this->getRelations($service_id));
		
		// Cancel the service using the module
		if ($vars['use_module'] == "true") {

			if (!isset($this->ModuleManager))
				Loader::loadModels($this, array("ModuleManager"));
			
			$module_data = $this->getModuleClassByPricingId($service->pricing_id);
			
			if ($module_data) {
				$module = $this->ModuleManager->initModule($module_data->id, Configure::get("Blesta.company_id"));
			
				if ($module) {
					// Set the module row used for this service
					$module->setModuleRow($module->getModuleRow($service->module_row_id));
					
					$service_info = $module->suspendService($package, $service, $parent_package, $parent_service);
					
					if (($errors = $module->errors())) {
						$this->Input->setErrors($errors);
						return;
					}
					
					// Set all service fields (if any given)
					if (is_array($service_info))
						$this->setFields($service_id, $service_info);
				}
			}
		}
		
		// Update the service
		$this->Record->where("services.id", "=", $service_id)->
			update("services", $vars, array("date_suspended", "status"));
		
		// Log the service suspension
		$log_service = array(
			'service_id' => $service_id,
			'staff_id' => (isset($vars['staff_id']) ? $vars['staff_id'] : null),
			'status' => "suspended",
			'date_added' => $this->dateToUtc(date("c"))
		);
		$this->Record->insert("log_services", $log_service);
		
		// Send the suspension email
		$this->sendSuspensionNoticeEmail("suspend", $service, $package);
	}

	/**
	 * Unsuspends a service
	 *
	 * @param int $service_id The ID of the service to unsuspend
	 * @param array $vars An array of info including:
	 * 	- use_module Whether or not to use the module when unsuspending the service ('true','false', default 'true')
	 * 	- staff_id The ID of the staff member that issued the service unsuspension
	 */	
	public function unsuspend($service_id, array $vars=array()) {
		
		if (!isset($vars['use_module']))
			$vars['use_module'] = "true";
		$vars['date_suspended'] = null;
		$vars['status'] = "active";
			
		extract($this->getRelations($service_id));
		
		// Cancel the service using the module
		if ($vars['use_module'] == "true") {

			if (!isset($this->ModuleManager))
				Loader::loadModels($this, array("ModuleManager"));
			
			$module_data = $this->getModuleClassByPricingId($service->pricing_id);
			
			if ($module_data) {
				$module = $this->ModuleManager->initModule($module_data->id, Configure::get("Blesta.company_id"));
			
				if ($module) {
					// Set the module row used for this service
					$module->setModuleRow($module->getModuleRow($service->module_row_id));
					
					$service_info = $module->unsuspendService($package, $service, $parent_package, $parent_service);
					
					if (($errors = $module->errors())) {
						$this->Input->setErrors($errors);
						return;
					}
					
					// Set all service fields (if any given)
					if (is_array($service_info))
						$this->setFields($service_id, $service_info);
				}
			}
		}
		
		// Update the service
		$this->Record->where("services.id", "=", $service_id)->
			update("services", $vars, array("date_suspended", "status"));
		
		// Log the service unsuspension
		$log_service = array(
			'service_id' => $service_id,
			'staff_id' => (isset($vars['staff_id']) ? $vars['staff_id'] : null),
			'status' => "unsuspended",
			'date_added' => $this->dateToUtc(date("c"))
		);
		$this->Record->insert("log_services", $log_service);
		
		// Send the unsuspension email
		$this->sendSuspensionNoticeEmail("unsuspend", $service, $package);
	}
	
	/**
	 * Processes the renewal for the given service by contacting the module
	 * (if supported by the module), to let it know that the service should be
	 * renewed. Note: This method does not affect the renew date of the service
	 * in Blesta, it merely notifies the module; this action takes place after
	 * a service has been paid not when its renew date is bumped.
	 *
	 * @param int $service_id The ID of the service to process the renewal for
	 */
	public function renew($service_id) {
		
		extract($this->getRelations($service_id));
		
		if (!$service)
			return;
		
		if (!isset($this->ModuleManager))
			Loader::loadModels($this, array("ModuleManager"));
		
		$module_data = $this->getModuleClassByPricingId($service->pricing_id);
		
		if ($module_data) {
			$module = $this->ModuleManager->initModule($module_data->id, Configure::get("Blesta.company_id"));
		
			if ($module) {
				$service_info = $module->renewService($package, $service, $parent_package, $parent_service);
				
				if (($errors = $module->errors())) {
					$this->Input->setErrors($errors);
					return;
				}
				
				// Set all service fields (if any given)
				if (is_array($service_info))
					$this->setFields($service_id, $service_info);
			}
		}
	}
	
	/**
	 * Retrieves a list of service status types
	 *
	 * @return array Key=>value pairs of status types
	 */
	public function getStatusTypes() {
		return array(
			'active' => $this->_("Services.getStatusTypes.active"),
			'canceled' => $this->_("Services.getStatusTypes.canceled"),
			'pending' => $this->_("Services.getStatusTypes.pending"),
			'suspended' => $this->_("Services.getStatusTypes.suspended"),
			'in_review' => $this->_("Services.getStatusTypes.in_review"),
		);
	}
	
	/**
	 * Returns all action options that can be performed for a service.
	 *
	 * @parm string $current_status Set to filter actions that may be performed if the service is in the given state options include:
	 * 	- active
	 * 	- suspended
	 * 	- canceled
	 * @return array An array of key/value pairs where each key is the action that may be performed and the value is the friendly name for the action
	 */
	public function getActions($current_status = null) {
		
		$actions = array(
			'suspend' => $this->_("Services.getActions.suspend"),
			'unsuspend' => $this->_("Services.getActions.unsuspend"),
			'cancel' => $this->_("Services.getActions.cancel"),
			'schedule_cancel' => $this->_("Services.getActions.schedule_cancel"),
			'change_renew' => $this->_("Services.getActions.change_renew")
		);
		
		switch ($current_status) {
			case "active":
				unset($actions['unsuspend']);
				break;
			case "suspended":
				unset($actions['suspend']);
				break;
			case "pending":
			case "canceled":
				return array();
		}
		return $actions;
	}
	
	/**
	 * Updates the field data for the given service, removing all existing data and replacing it with the given data
	 *
	 * @param int $service_id The ID of the service to set fields on
	 * @param array $vars A numerically indexed array of field data containing:
	 * 	- key The key for this field
	 * 	- value The value for this key
	 * 	- encrypted Whether or not this field should be encrypted ('true', 'false', default 'false')
	 */
	public function setFields($service_id, array $vars) {

		$do_delete = $this->Record->select()->from("service_fields")->
			where("service_fields.service_id", "=", $service_id)->numResults();

		$this->begin();
		
		// Avoid deadlock by not performing non-insert query within transaction unless record(s) exist
		if ($do_delete) {
			$this->Record->from("service_fields")->
				where("service_fields.service_id", "=", $service_id)->delete();
		}
		
		if (!empty($vars)) {
			foreach ($vars as $field) {
				$this->addField($service_id, $field);
			}
		}
			
		if ($this->Input->errors())
			$this->rollBack();
		else
			$this->commit();
	}
	
	/**
	 * Adds a service field for a particular service
	 *
	 * @param int $service_id The ID of the service to add to
	 * @param array $vars An array of service field info including:
	 * 	- key The name of the value to add
	 * 	- value The value to add
	 * 	- encrypted Whether or not to encrypt the value when storing ('true', 'false', default 'false')
	 */
	public function addField($service_id, array $vars) {

		$vars['service_id'] = $service_id;
		$this->Input->setRules($this->getFieldRules());
		
		if ($this->Input->validates($vars)) {
			
			// qty is a special key that may not be stored as a service field
			if ($vars['key'] == "qty")
				return;
			
			if (empty($vars['encrypted']))
				$vars['encrypted'] = "0";
			$vars['encrypted'] = $this->boolToInt($vars['encrypted']);
			
			$fields = array("service_id", "key", "value", "serialized", "encrypted");
			
			// Serialize if needed
			$serialize = !is_scalar($vars['value']);
			$vars['serialized'] = (int)$serialize;
			if ($serialize)
				$vars['value'] = serialize($vars['value']);
			
			// Encrypt if needed
			if ($vars['encrypted'] > 0)
				$vars['value'] = $this->systemEncrypt($vars['value']);
		
			$this->Record->insert("service_fields", $vars, $fields);
		}
	}
	
	/**
	 * Edit a service field for a particular service
	 *
	 * @param int $service_id The ID of the service to edit
	 * @param array $vars An array of service field info including:
	 * 	- key The name of the value to edit
	 * 	- value The value to update with
	 * 	- encrypted Whether or not to encrypt the value when storing ('true', 'false', default 'false')
	 */
	public function editField($service_id, array $vars) {
		
		$this->Input->setRules($this->getFieldRules());
		
		if ($this->Input->validates($vars)) {
			//if (empty($vars['encrypted']))
			//	$vars['encrypted'] = "0";
			if (array_key_exists("encrypted", $vars))
				$vars['encrypted'] = $this->boolToInt($vars['encrypted']);
			
			$fields = array("value", "serialized", "encrypted");
			
			// Serialize if needed
			$serialize = !is_scalar($vars['value']);
			$vars['serialized'] = (int)$serialize;
			if ($serialize)
			$vars['value'] = serialize($vars['value']);
			
			// Encrypt if needed
			if (array_key_exists("encrypted", $vars) && $vars['encrypted'] > 0)
				$vars['value'] = $this->systemEncrypt($vars['value']);
			
			$vars['service_id'] = $service_id;
			$fields[] = "key";
			$fields[] = "service_id";
			$this->Record->duplicate("value", "=", $vars['value'])->
				insert("service_fields", $vars, $fields);
		}
	}
	
	/**
	 * Returns the configurable options for the service
	 *
	 * @param int $service_id
	 * @return array An array of stdClass objects, each representing a service option
	 */
	public function getOptions($service_id) {
		$fields = array("service_options.*", 'package_option_values.value' => "option_value",
			'package_option_values.option_id' => "option_id",
			'package_options.label' => "option_label",
			'package_options.name' => "option_name",
			'package_options.type' => "option_type"
		);
		return $this->Record->select($fields)->from("service_options")->
			leftJoin("package_option_pricing", "package_option_pricing.id", "=", "service_options.option_pricing_id", false)->
			leftJoin("package_option_values", "package_option_values.id", "=", "package_option_pricing.option_value_id", false)->
			leftJoin("package_options", "package_options.id", "=", "package_option_values.option_id", false)->
			where("service_id", "=", $service_id)->fetchAll();
	}
	
	/**
	 * Sets the configurable options for the service
	 *
	 * @param int $service_id The ID of the service to set configurable options for
	 * @param array $config_options An array of key/value pairs where each key is the option ID and each value is the value of the option
	 */
	public function setOptions($service_id, array $config_options) {
		if (!isset($this->PackageOptions))
			Loader::loadModels($this, array("PackageOptions"));
		
		$service = $this->get($service_id);
		
		// Remove old service options
		$this->Record->from("service_options")->
			where("service_id", "=", $service_id)->delete();
		
		foreach ($config_options as $option_id => $value) {
			$option_value = $this->PackageOptions->getValue($option_id, $value);
			if ($option_value) {
				$price = $this->PackageOptions->getValuePrice($option_value->id, $service->package_pricing->term, $service->package_pricing->period, $service->package_pricing->currency);
				
				if (!$price)
					continue;
				
				$vars = array(
					'service_id' => $service_id,
					'option_pricing_id' => $price->id,
					'qty' => $option_value->value == null ? $value : 1
				);
				
				$this->Record->duplicate("option_pricing_id", "=", $vars['option_pricing_id'])->
					duplicate("qty", "=", $vars['qty'])->
					insert("service_options", $vars);				
			}
		}
	}
	
	/**
	 * Returns all default welcome email tags, which are set into the email that is
	 * delivered when a service is provisioned.
	 *
	 * @return array A multi-dimensional array of tags where the first dimension is the category and the second is a numeric array of tags
	 */
	public function getWelcomeEmailTags() {
		return array(
			'client' => array("id", "id_code", "first_name", "last_name"),
			'pricing' => array("term", "period", "currency", "price", "setup_fee", "cancel_fee")
		);
	}
	
	/**
	 * Calculates the next renew date using a given date, term, and period
	 *
	 * @param string $last_renew_date The date the service last renewed. If never renewed this should be the service add date
	 * @param int $term The term value relating to the given period
	 * @param string $period The period (day, week, month, year, onetime)
	 * @param string $format The date format to return the date in (optional, default 'Y-m-d H:i:s')
	 * @return string The date the service renews in UTC. In the event that the service does not renew or the renew date can not be calculated null is returned
	 */
	public function getNextRenewDate($last_renew_date, $term, $period, $format = "Y-m-d H:i:s") {
		
		if ($last_renew_date == null)
			return null;
		
		$last_renew_date = $this->dateToUtc($last_renew_date);
		
		switch ($period) {
			case "day":
				return $this->Date->cast(strtotime($last_renew_date . " +" . abs((int)$term) . " days"), $format);
			case "week":
				return $this->Date->cast(strtotime($last_renew_date . " +" . abs((int)$term) . " weeks"), $format);
			case "month":
				return $this->Date->cast(strtotime($last_renew_date . " +" . abs((int)$term) . " months"), $format);
			case "year":
				return $this->Date->cast(strtotime($last_renew_date . " +" . abs((int)$term) . " years"), $format);
		}
		return null;
	}
	
	/**
	 * Retrieves a list of coupons to be applied to an invoice for recurring services, assumes services given are for a single client only
	 * @see Cron::createRenewingServiceInvoices()
	 *
	 * @param array $services An array of stdClass objects, each representing a service
	 * @param string $default_currency The ISO 4217 currency code for the client
	 * @param array $coupons A reference to coupons that will need to be incremented
	 * @return array An array of coupon line items to append to an invoice
	 */
	public function buildServiceCouponLineItems(array $services, $default_currency, &$coupons) {
		Loader::loadModels($this, array("Coupons", "Currencies"));
		// Load Invoice language needed for line items
		if (!isset($this->Invoices))
			Language::loadLang(array("invoices"));
		
		$coupons = array();
		$coupon_service_ids = array();
		$service_list = array();
		$now_timestamp = $this->Date->toTime($this->Coupons->dateToUtc("c"));
		
		// Determine which coupons could be used
		foreach ($services as $service) {
			
			// Fetch the coupon associated with this service
			if ($service->coupon_id && !isset($coupons[$service->coupon_id]))
				$coupons[$service->coupon_id] = $this->Coupons->get($service->coupon_id);
			
			// Skip this service if it has no active coupon or it does not apply to renewing services
			if (!$service->coupon_id || !isset($coupons[$service->coupon_id]) ||
				$coupons[$service->coupon_id]->status != "active" ||
				($service->date_last_renewed != null && $coupons[$service->coupon_id]->recurring != "1")) {
				continue;
			}
			
			if (!isset($service->package_pricing))
				$service->package_pricing = $this->getPackagePricing($service->pricing_id);
			
			// See if this coupon has a discount available in the correct currency
			$coupon_amount = false;
			foreach ($coupons[$service->coupon_id]->amounts as $amount) {
				if ($amount->currency == $service->package_pricing->currency) {
					$coupon_amount = $amount;
					break;
				}
			}
			unset($amount);
			
			// Add the coupon if it is usable
			if ($coupon_amount) {
				// Verify coupon applies to this service
				$coupon_applies = false;
				foreach ($coupons[$service->coupon_id]->packages as $coupon_package) {
					if ($coupon_package->package_id == $service->package_pricing->package_id)
						$coupon_applies = true;
				}
				
				// Coupon applies to recurring
				if ($coupon_applies && $coupons[$service->coupon_id]->limit_recurring == "1") {
					// Coupon must be valid within start/end dates and must not exceed used quantity
					if ($now_timestamp >= $this->Date->toTime($coupons[$service->coupon_id]->start_date) &&
						$now_timestamp <= $this->Date->toTime($coupons[$service->coupon_id]->end_date) &&
						$coupons[$service->coupon_id]->used_qty < $coupons[$service->coupon_id]->max_qty) {
						
						// Add the coupon to the list
						if (!isset($coupon_service_ids[$service->coupon_id]))
							$coupon_service_ids[$service->coupon_id] = array();
						$coupon_service_ids[$service->coupon_id][] = $service->id;
						$service_list[$service->id] = $service;
						
						// Include this coupon in the list of coupons that should have their used_qty incremented (only once)
						// removed since $coupons[] is already an object and should stay that way
						//$coupons[$service->coupon_id] = $service->coupon_id;
					}
				}
				elseif ($coupon_applies) {
					// Ignore coupon dates/quantity, the coupon still applies
					if (!isset($coupon_service_ids[$service->coupon_id]))
						$coupon_service_ids[$service->coupon_id] = array();
					$coupon_service_ids[$service->coupon_id][] = $service->id;
					$service_list[$service->id] = $service;
				}
			}
		}
		
		// Create the line items for the coupons set
		$line_items = array();
		foreach ($coupon_service_ids as $coupon_id => $service_ids) {
			// Skip if coupon is not available
			if (!isset($coupons[$coupon_id]) || !$coupons[$coupon_id])
				continue;
			
			$line_item_amount = null;
			$line_item_description = null;
			$line_item_quantity = 1;
			$currency = null;
			
			// Exclusive coupons can be added with any service
			if ($coupons[$coupon_id]->type == "exclusive") {
				$discount_amount = null;
				$service_total = 0;
				
				// Set the line item amount/description
				foreach ($coupons[$coupon_id]->amounts as $amount) {
					// Calculate the total from each service related to this coupon
					foreach ($service_ids as $service_id) {
						// Skip if service is not available or incorrect currency
						if (!isset($service_list[$service_id]) || ($amount->currency != $service_list[$service_id]->package_pricing->currency))
							continue;
						
						$line_item_quantity = $service_list[$service_id]->qty;
						$discount_amount = abs($amount->amount);
						
						// Set the discount amount based on percentage
						if ($amount->type == "percent") {
							$line_item_description = Language::_("Invoices.!line_item.coupon_line_item_description_percent", true, $coupons[$coupon_id]->code, $discount_amount);
							$discount_amount /= 100;
							$line_item_amount += -(abs($service_list[$service_id]->package_pricing->price)*$discount_amount);
						}
						// Set the discount amount based on amount
						else {
							$line_item_amount += -max(0, (abs($service_list[$service_id]->package_pricing->price) - $discount_amount) > 0 ? $discount_amount : $service_list[$service_id]->package_pricing->price);
							$line_item_description = Language::_("Invoices.!line_item.coupon_line_item_description_amount", true, $coupons[$coupon_id]->code);
						}
						
						$currency = $amount->currency;
					}
				}
				unset($amount);
			}
			// Inclusive coupons can only be added to all services together
			elseif ($coupons[$coupon_id]->type == "inclusive") {
				$service_total = 0;
				$matched_packages = array();
				
				// Check each coupon package correlates with a service package
				foreach ($coupons[$coupon_id]->packages as $package) {
					foreach ($service_ids as $service_id) {
						// Skip if service is not available
						if (!isset($service_list[$service_id]))
							break 2;
						
						// Save a list of matched packages and set the price of each
						if ($service_list[$service_id]->package_pricing->package_id == $package->package_id) {
							$matched_packages[$package->package_id] = $package->package_id;
							$service_total += $service_list[$service_id]->package_pricing->price;
						}
					}
				}
				
				// All service packages matched all coupon packages, this coupon can be applied
				if (count($matched_packages) == count($coupons[$coupon_id]->packages)) {
					// Calculate the amount, must be a percentage to be applied to all
					$percent = null;
					foreach ($coupons[$coupon_id]->amounts as $amount) {
						if ($amount->currency == $service_list[$service_id]->package_pricing->currency && $amount->type == "percent") {
							$percent = abs($amount->amount)/100;
							$currency = $amount->currency;
							break;
						}
					}
					unset($amount);
					
					if ($percent !== null) {
						$line_item_amount = -(abs($service_total)*$percent);
						$line_item_description = Language::_("Invoices.!line_item.coupon_line_item_description_percent", true, $coupons[$coupon_id]->code, ($percent*100));
					}
				}
			}
			
			// Create the line item
			if ($line_item_amount && $line_item_description && $currency) {
				// Convert the amount to the default currency for this client
				if ($currency != $default_currency)
					$line_item_amount = $this->Currencies->convert($line_item_amount, $currency, $default_currency, Configure::get("Blesta.company_id"));
				
				$line_items[] = array(
					'service_id' => null,
					'description' => $line_item_description,
					'qty' => $line_item_quantity,
					'amount' => $line_item_amount,
					'tax' => false
				);
			}
		}
		
		return $line_items;
	}
	
	/**
	 * Return all field data for the given service, decrypting fields where neccessary
	 *
	 * @param int $service_id The ID of the service to fetch fields for
	 * @return array An array of stdClass objects representing fields, containing:
	 * 	- key The service field name
	 * 	- value The value for this service field
	 * 	- encrypted Whether or not this field was originally encrypted (1 true, 0 false)
	 */
	protected function getFields($service_id) {
		$fields = $this->Record->select(array("key", "value", "serialized", "encrypted"))->
			from("service_fields")->where("service_id", "=", $service_id)->
			fetchAll();
		$num_fields = count($fields);
		for ($i=0; $i<$num_fields; $i++) {
			// If the field is encrypted, must decrypt the field
			if ($fields[$i]->encrypted)
				$fields[$i]->value = $this->systemDecrypt($fields[$i]->value);
			
			if ($fields[$i]->serialized)
				$fields[$i]->value = unserialize($fields[$i]->value);
		}
		
		return $fields;
	}
	
	/**
	 * Returns info regarding the module belonging to the given $package_pricing_id
	 *
	 * @param int $package_pricing_id The package pricing ID to fetch the module of
	 * @return mixed A stdClass object containing module info and the package ID belonging to the given $package_pricing_id, false if no such module exists
	 */
	private function getModuleClassByPricingId($package_pricing_id) {
		return $this->Record->select(array("modules.*", 'packages.id' => "package_id"))->from("package_pricing")->
			innerJoin("packages", "packages.id", "=", "package_pricing.package_id", false)->
			innerJoin("modules", "modules.id", "=", "packages.module_id", false)->
			where("package_pricing.id", "=", $package_pricing_id)->
			fetch();
	}
	
	/**
	 * Validates a service's 'status' field
	 *
	 * @param string $status The status type
	 * @return boolean True if $status is valid, false otherwise
	 */
	public function validateStatus($status) {
		$options = array_keys($this->getStatusTypes());
		return in_array($status, $options);
	}
	
	/**
	 * Validates whether to use a module when adding/editing a service
	 *
	 * @param string $use_module
	 * @return boolean True if validated, false otherwise
	 */
	public function validateUseModule($use_module) {
		$options = array("true", "false");
		return in_array($use_module, $options);
	}
	
	/**
	 * Validates a service field's 'encrypted' field
	 *
	 * @param string $encrypted Whether or not to encrypt
	 */
	public function validateEncrypted($encrypted) {
		$options = array(0, 1, "true", "false");
		return in_array($encrypted, $options);
	}
	
	/**
	 * Validates whether the given service has children NOT of the given status
	 *
	 * @param int $service_id The ID of the parent service to validate
	 * @param string $status The status of children services to ignore (e.g. "canceled") (optional, default null to not ignore any child services)
	 * @return boolean True if the service has children not of the given status, false otherwise
	 */
	public function validateHasChildren($service_id, $status=null) {
		$this->Record->select()->from("services")->
			where("parent_service_id", "=", $service_id);
		
		if ($status)
			$this->Record->where("status", "!=", $status);
		
		return ($this->Record->numResults() > 0);
	}
	
	/**
	 * Retrieves the rule set for adding/editing service fields
	 *
	 * @return array The rules
	 */
	public function getFieldRules() {
		$rules = array(
			'key' => array(
				'empty' => array(
					'rule' => "isEmpty",
					'negate' => true,
					'message' => $this->_("Services.!error.key.empty")
				)
			),
			'encrypted' => array(
				'format' => array(
					'if_set' => true,
					'rule' => array(array($this, "validateEncrypted")),
					'message' => $this->_("Services.!error.encrypted.format"),
					'post_format' => array(array($this, "boolToInt"))
				)
			)
		);
		return $rules;
	}
	
	/**
	 * Retrieves the rule set for adding/editing services
	 *
	 * @param array $vars An array of input fields
	 * @param boolean $edit Whether or not this is an edit request
	 * @return array The rules
	 */
	private function getRules($vars, $edit=false) {
		$rules = array(
			'parent_service_id' => array(
				'exists' => array(
					'if_set' => true,
					'rule' => array(array($this, "validateExists"), "id", "services"),
					'message' => $this->_("Services.!error.parent_service_id.exists")
				),
				'parent' => array(
					'if_set' => true,
					'rule' => array(array($this, "hasParent")),
					'negate' => true,
					'message' => $this->_("Services.!error.parent_service_id.parent")
				)
			),
			'package_group_id' => array(
				'exists' => array(
					'if_set' => true,
					'rule' => array(array($this, "validateExists"), "id", "package_groups"),
					'message' => $this->_("Services.!error.package_group_id.exists")
				)
			),
			'id_format' => array(
				'empty' => array(
					'if_set' => true,
					'rule' => "isEmpty",
					'negate' => true,
					'message' => $this->_("Services.!error.id_format.empty")
				),
				'length' => array(
					'if_set' => true,
					'rule' => array("maxLength", 64),
					'message' => $this->_("Services.!error.id_format.length")
				)
			),
			'id_value' => array(
				'valid' => array(
					'if_set' => true,
					'rule' => array(array($this, "isInstanceOf"), "Record"),
					'message' => $this->_("Services.!error.id_value.valid")
				)
			),
			'pricing_id' => array(
				'exists' => array(
					'rule' => array(array($this, "validateExists"), "id", "package_pricing"),
					'message' => $this->_("Services.!error.pricing_id.exists")
				)
			),
			'client_id' => array(
				'exists' => array(
					'rule' => array(array($this, "validateExists"), "id", "clients"),
					'message' => $this->_("Services.!error.client_id.exists")
				),
				'allowed' => array(
					'rule' => array(array($this, "validateAllowed"), isset($vars['pricing_id']) ? $vars['pricing_id'] : null),
					'message' => $this->_("Services.!error.client_id.allowed")
				)
			),
			'module_row_id' => array(
				'exists' => array(
					'if_set' => true,
					'rule' => array(array($this, "validateExists"), "id", "module_rows"),
					'message' => $this->_("Services.!error.module_row_id.exists")
				)
			),
			'coupon_id' => array(
				'valid' => array(
					'if_set' => true,
					'rule' => array(array($this, "validateCoupon"), isset($vars['coupon_packages']) ? $vars['coupon_packages'] : null),
					'message' => $this->_("Services.!error.coupon_id.valid")
				)
			),
			'qty' => array(
				'format' => array(
					'if_set' => true,
					'rule' => "is_numeric",
					'message' => $this->_("Services.!error.qty.format")
				),
				'length' => array(
					'if_set' => true,
					'rule' => array("maxLength", 10),
					'message' => $this->_("Services.!error.qty.length")
				),
				'available' => array(
					'if_set' => true,
					'rule' => array(array($this, "decrementQuantity"), isset($vars['pricing_id']) ? $vars['pricing_id'] : null, true, $edit && isset($vars['current_qty']) ? $vars['current_qty'] : null),
					'message' => $this->_("Services.!error.qty.available")
				)
			),
			'status' => array(
				'format' => array(
					'if_set' => true,
					'rule' => array(array($this, "validateStatus")),
					'message' => $this->_("Services.!error.status.format")
				)
			),
			'date_added' => array(
				'format' => array(
					'if_set' => true,
					'rule' => "isDate",
					'post_format'=>array(array($this, "dateToUtc")),
					'message' => $this->_("Services.!error.date_added.format")
				)
			),
			'date_renews' => array(
				'valid' => array(
					'if_set' => true,
					'rule' => array(array($this, "validateDateRenews"), isset($vars['date_last_renewed']) ? $vars['date_last_renewed'] : null),
					'message' => $this->_("Services.!error.date_renews.valid", isset($vars['date_last_renewed']) ? $this->Date->cast($vars['date_last_renewed'], "Y-m-d") : null)
				),
				'format' => array(
					'if_set' => true,
					'rule' => "isDate",
					'post_format'=>array(array($this, "dateToUtc")),
					'message' => $this->_("Services.!error.date_renews.format")
				)
			),
			'date_last_renewed' => array(
				'format' => array(
					'if_set' => true,
					'rule' => "isDate",
					'post_format'=>array(array($this, "dateToUtc")),
					'message' => $this->_("Services.!error.date_last_renewed.format")
				)
			),
			'date_suspended' => array(
				'format' => array(
					'if_set' => true,
					'rule' => "isDate",
					'post_format'=>array(array($this, "dateToUtc")),
					'message' => $this->_("Services.!error.date_suspended.format")
				)
			),
			'date_canceled' => array(
				'format' => array(
					'if_set' => true,
					'rule' => "isDate",
					'post_format'=>array(array($this, "dateToUtc")),
					'message' => $this->_("Services.!error.date_canceled.format")
				)
			),
			'use_module' => array(
				'format' => array(
					'if_set' => true,
					'rule' => array(array($this, "validateUseModule")),
					'message' => $this->_("Services.!error.use_module.format")
				)
			),
			'configoptions' => array(
				'valid' => array(
					'if_set' => true,
					'rule' => array(array($this, "validateConfigOptions"), isset($vars['pricing_id']) ? $vars['pricing_id'] : null),
					'message' => $this->_("Services.!error.configoptions.valid")
				)
			)
		);
		
		// Set rules for editing services
		if ($edit) {
			// Remove id_format and id_value, they cannot be updated
			unset($rules['id_format'], $rules['id_value'], $rules['client_id']['allowed']);
			
			$rules['pricing_id']['exists']['if_set'] = true;
			$rules['client_id']['exists']['if_set'] = true;
		}
		
		return $rules;
	}
	
	/**
	 * Checks if the given $field is a reference of $class
	 */
	public function isInstanceOf($field, $class) {
		return $field instanceof $class;
	}
	
	/**
	 * Performs all validation necessary before adding a service
	 *
	 * @param array $vars An array of service info including:
	 * 	- parent_service_id The ID of the service this service is a child of (optional)
	 * 	- package_group_id The ID of the package group this service was added from (optional)
	 * 	- pricing_id The package pricing schedule ID for this service
	 * 	- client_id The ID of the client to add the service under
	 * 	- module_row_id The module row to add the service under (optional, default is first available)
	 * 	- coupon_id The ID of the coupon used for this service (optional)
	 * 	- qty The quanity consumed by this service (optional, default 1)
	 * 	- status The status of this service ('active','canceled','pending','suspended', default 'pending')
	 * 	- date_added The date this service is added (default to today's date UTC)
	 * 	- date_renews The date the service renews (optional, default calculated by package term)
	 * 	- date_last_renewed The date the service last renewed (optional)
	 * 	- date_suspended The date the service was last suspended (optional)
	 * 	- date_canceled The date the service was last canceled (optional)
	 * 	- use_module Whether or not to use the module when creating the service ('true','false', default 'true')
	 * 	- configoptions An array of key/value pairs of package options where the key is the package option ID and the value is the option value (optional)
	 * 	- * Any other service field data to pass to the module
	 * @param array $packages A numerically indexed array of packages ordered along with this service to determine if the given coupon may be applied
	 * @return array $vars An array of $vars, modified by error checking
	 * @see Services::validateService()
	 */
	public function validate(array $vars, array $packages = null) {
		
		if (!isset($this->Packages))
			Loader::loadModels($this, array("Packages"));
		if (!isset($this->ModuleManager))
			Loader::loadModels($this, array("ModuleManager"));
		
		$vars['coupon_packages'] = $packages;
		
		if (!isset($vars['qty']))
			$vars['qty'] = 1;
		
		// Check basic rules
		$this->Input->setRules($this->getRules($vars, false));
		
		// Set date added if not given
		if (!isset($vars['date_added']))
			$vars['date_added'] = date("c");
		
		// Get the package
		if (isset($vars['pricing_id'])) {
			$package = $this->Packages->getByPricingId($vars['pricing_id']);
			
			// Set the next renew date based on the package pricing
			if ($package && empty($vars['date_renews'])) {
				foreach ($package->pricing as $pricing) {
					if ($pricing->id == $vars['pricing_id']) {
						// Set date renews
						$vars['date_renews'] = $this->getNextRenewDate($vars['date_added'], $pricing->term, $pricing->period, "c");
						break;
					}
				}
				unset($pricing);
			}
		}
		
		if ($this->Input->validates($vars)) {
			
			$module = $this->ModuleManager->initModule($package->module_id);
			
			if ($module) {
				$module->validateService($package, $vars);
				
				// If any errors encountered through the module, set errors
				if (($errors = $module->errors())) {
					$this->Input->setErrors($errors);
					return;
				}
			}
		}
		return $vars;
	}
	
	/**
	 * Validates service info, including module options, for creating a service. An alternative to Services::validate()
	 *
	 * @param stdClass $package A stdClass object representing the package for the service
	 * @param array $vars An array of values to be evaluated, including:
	 * 	- invoice_method The invoice method to use when creating the service, options are:
	 * 		- create Will create a new invoice when adding this service
	 * 		- append Will append this service to an existing invoice (see 'invoice_id')
	 * 		- none Will not create any invoice
	 * 	- invoice_id The ID of the invoice to append to if invoice_method is set to 'append'
	 * 	- pricing_id The ID of the package pricing to use for this service
	 * 	- * Any other service field data to pass to the module
	 * @see Services::validate()
	 */
	public function validateService($package, array $vars) {
		
		if (!isset($this->ModuleManager))
			Loader::loadModels($this, array("ModuleManager"));
		
		$rules = array(
			/*
			'client_id' => array(
				'exists' => array(
					'rule' => array(array($this, "validateExists"), "id", "clients"),
					'message' => $this->_("Services.!error.client_id.exists")
				)
			),
			*/
			'invoice_method' => array(
				'valid' => array(
					'if_set' => true,
					'rule' => array("in_array", array("create", "append", "none")),
					'message' => $this->_("Services.!error.invoice_method.valid")
				)
			),
			'pricing_id' => array(
				'valid' => array(
					'rule' => array(array($this, "validateExists"), "id", "package_pricing"),
					'message' => $this->_("Services.!error.pricing_id.valid")
				)
			),
			'configoptions' => array(
				'valid' => array(
					'if_set' => true,
					'rule' => array(array($this, "validateConfigOptions"), isset($vars['pricing_id']) ? $vars['pricing_id'] : null),
					'message' => $this->_("Services.!error.configoptions.valid")
				)
			)
			/*
			'status' => array(
				'format' => array(
					'if_set' => true,
					'rule' => array(array($this, "validateStatus")),
					'message' => $this->_("Services.!error.status.format")
				)
			),
			*/
		);
		
		$this->Input->setRules($rules);
		if ($this->Input->validates($vars)) {
			
			$module_data = $this->getModuleClassByPricingId($vars['pricing_id']);
			
			if ($module_data) {
				$module = $this->ModuleManager->initModule($module_data->id);
				
				if ($module && !$module->validateService($package, $vars))
					$this->Input->setErrors($module->errors());
					
			}
		}
	}
	
	/**
	 * Verifies if the given coupon ID can be applied to the requested packages
	 *
	 * @param int $coupon_id The ID of the coupon to validate
	 * @param array An array of pacakges to confirm the coupon can be applied
	 * @return boolean True if the coupon can be applied, false otherwise
	 */
	public function validateCoupon($coupon_id, array $packages=null) {
		if (!isset($this->Coupons))
			Loader::loadModels($this, array("Coupons"));
			
		return (boolean)$this->Coupons->getForPackages(null, $coupon_id, $packages);
	}
	
	/**
	 * Verifies that the given date value is valid for a cancel date
	 *
	 * @param string $date The date to cancel a service or "end_of_term" to cancel at the end of the term
	 * @return boolean True if $date is valid, false otherwise
	 */
	public function validateDateCanceled($date) {
		return ($this->Input->isDate($date) || strtolower($date) == "end_of_term");
	}
	
	/**
	 * Verifies that the given renew date is greater than the last renew date (if available)
	 *
	 * @param string $renew_date The date a service should renew
	 * @param string $last_renew_date The date a service last renewed
	 * @return boolean True if renew date is valid, false otherwise
	 */
	public function validateDateRenews($renew_date, $last_renew_date=null) {
		if ($last_renew_date)
			return $this->dateToUtc($renew_date) > $this->dateToUtc($last_renew_date);
		return true;
	}
	
	/**
	 * Verifies that the client has access to the package for the given pricing ID
	 *
	 * @param int $client_id The ID of the client
	 * @param int $pricing_id The ID of the package pricing
	 * @return boolean True if the client can add the package, false otherwise
	 */
	public function validateAllowed($client_id, $pricing_id) {
		if ($pricing_id == null)
			return true;
		return (boolean)$this->Record->select(array("packages.id"))->from("package_pricing")->
			innerJoin("packages", "packages.id", "=", "package_pricing.package_id", false)->
			on("client_packages.client_id", "=", $client_id)->
			leftJoin("client_packages", "client_packages.package_id", "=", "packages.id", false)->
			where("package_pricing.id", "=", $pricing_id)->
			open()->
				where("packages.status", "=", "active")->
				open()->
					orWhere("packages.status", "=", "restricted")->
					Where("client_packages.client_id", "=", $client_id)->
				close()->
			close()->
			fetch();
	}
	
	/**
	 * Verifies that the givne package options are valid
	 *
	 * @param array $config_options An array of key/value pairs where each key is the package option ID and each value is the option value
	 * @param int $pricing_id The package pricing ID
	 * @return boolean True if valid, false otherwise
	 */
	public function validateConfigOptions($config_options, $pricing_id) {
		if (!isset($this->PackageOptions))
			Loader::loadModels($this, array("PackageOptions"));
		
		foreach ($config_options as $option_id => $value) {
			
			$result = $this->Record->select(array("package_option_values.*"))->from("package_pricing")->
				innerJoin("package_option", "package_pricing.package_id", "=", "package_option.package_id", false)->
				innerJoin("package_option_group", "package_option_group.option_group_id", "=", "package_option.option_group_id", false)->
				innerJoin("package_options", "package_options.id", "=", "package_option_group.option_id", false)->
				innerJoin("package_option_values", "package_option_values.option_id", "=", "package_options.id", false)->
				where("package_options.id", "=", $option_id)->
				where("package_pricing.id", "=", $pricing_id)->
				open()->
					where("package_option_values.value", "=", $value)->
					orWhere("package_options.type", "=", "quantity")->
				close()->fetch();
			
			if (!$result)
				return false;
			
			// Check quantities
			if ($result->min != null && $result->min > $value)
				return false;
			if ($result->max != null && $result->max < $value)
				return false;
			if ($result->step != null && $value != $result->max && ($value - (int)$result->min)%$result->step !== 0)
				return false;
		}
		return true;
	}
	
	/**
	 * Decrements the package quantity if $check_only is false, otherwise only validates
	 * the quantity could be decremented.
	 *
	 * @param int $quantity The quantity requested
	 * @param int $pricing_id The pricing ID
	 * @param boolean $check_only True to only verify the quantity could be decremented, false otherwise
	 * @param mixed $current_qty The currenty quantity being consumed by the service
	 * @return boolean true if the quantity could be (not necessarily has been) consumed, false otherwise
	 */
	public function decrementQuantity($quantity, $pricing_id, $check_only=true, $current_qty=null) {

		// Check if quantity can be deductable
		$consumable = (boolean)$this->Record->select()->from("package_pricing")->
			innerJoin("packages", "package_pricing.package_id", "=", "packages.id", false)->
			where("package_pricing.id", "=", $pricing_id)->
			open()->
				where("packages.qty", ">=", $quantity-(int)$current_qty)->
				orWhere("packages.qty", "=", null)->
			close()->
			fetch();

		if ($consumable && !$check_only) {
			
			$this->Record->set("packages.qty", "packages.qty-?", false)->
				appendValues(array($quantity-(int)$current_qty))->
				innerJoin("package_pricing", "package_pricing.package_id", "=", "packages.id", false)->
				where("package_pricing.id", "=", $pricing_id)->
				where("packages.qty", ">", 0)->
				update("packages");
		}
		return $consumable;
	}
}
?>