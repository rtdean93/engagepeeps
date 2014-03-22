<?php
/**
 * Client portal services controller
 *
 * @package blesta
 * @subpackage blesta.app.controllers
 * @copyright Copyright (c) 2010, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class ClientServices extends AppController {
	
	/**
	 * Pre-action
	 */
	public function preAction() {
		parent::preAction();
		
		// Require login
		$this->requireLogin();
		
		// Load models, language
		$this->uses(array("Clients", "Packages", "Services"));
		Language::loadLang("client_services");
		
		$this->client = $this->Clients->get($this->Session->read("blesta_client_id"));
		
		// Attempt to set the page title language
		if ($this->client) {
			try {
				$language = Language::_("ClientServices." . Loader::fromCamelCase($this->action ? $this->action : "index") . ".page_title", true, $this->client->id_code);
				$this->structure->set("page_title", $language);
			}
			catch(Exception $e) {
				// Attempting to set the page title language has failed, likely due to
				// the language definition requiring multiple parameters.
				// Fallback to index. Assume the specific page will set its own page title otherwise.
				$this->structure->set("page_title", Language::_("ClientServices.index.page_title", true), $this->client->id_code);
			}
		}
		else
			$this->redirect($this->base_uri);
	}
	
	/**
	 * List services
	 */
	public function index() {
		$status = (isset($this->get[0]) ? $this->get[0] : "active");
		$page = (isset($this->get[1]) ? (int)$this->get[1] : 1);
		$sort = (isset($this->get['sort']) ? $this->get['sort'] : "date_added");
		$order = (isset($this->get['order']) ? $this->get['order'] : "desc");
		
		$services = $this->Services->getList($this->client->id, $status, $page, array($sort => $order), false);
		$total_results = $this->Services->getListCount($this->client->id, $status, false);
		
		// Set the number of services of each type, not including children
		$status_count = array(
			'active' => $this->Services->getStatusCount($this->client->id, "active", false),
			'canceled' => $this->Services->getStatusCount($this->client->id, "canceled", false),
			'pending' => $this->Services->getStatusCount($this->client->id, "pending", false),
			'suspended' => $this->Services->getStatusCount($this->client->id, "suspended", false),
		);
		
		// Set language for periods
		$periods = $this->Packages->getPricingPeriods();
		foreach ($this->Packages->getPricingPeriods(true) as $period=>$lang)
			$periods[$period . "_plural"] = $lang;
		
		$this->set("periods", $periods);
		$this->set("client", $this->client);
		$this->set("status", $status);
		$this->set("services", $services);
		$this->set("status_count", $status_count);
		$this->set("widget_state", isset($this->widgets_state['services']) ? $this->widgets_state['services'] : null);
		$this->set("sort", $sort);
		$this->set("order", $order);
		$this->set("negate_order", ($order == "asc" ? "desc" : "asc"));
		
		// Overwrite default pagination settings
		$settings = array_merge(Configure::get("Blesta.pagination"), array(
				'total_results' => $total_results,
				'uri'=>$this->Html->safe($this->base_uri . "services/index/" . $status . "/[p]/"),
				'params'=>array('sort'=>$sort,'order'=>$order)
			)
		);
		$this->helpers(array("Pagination"=>array($this->get, $settings)));
		$this->Pagination->setSettings(Configure::get("Blesta.pagination_ajax"));
		
		if ($this->isAjax())
			return $this->renderAjaxWidgetIfAsync(isset($this->get['whole_widget']) ? null : (isset($this->get[1]) || isset($this->get['sort'])));
	}
	
	/**
	 * Manage service
	 */
	public function manage() {
		
		$this->uses(array("ModuleManager"));
		
		// Ensure we have a service
		if (!($service = $this->Services->get((int)$this->get[0])) || $service->client_id != $this->client->id)
			$this->redirect($this->base_uri);
		
		$package = $this->Packages->get($service->package->id);
		$module = $this->ModuleManager->initModule($service->package->module_id);
		$module->base_uri = $this->base_uri;
		
		$method = isset($this->get[1]) ? $this->get[1] : null;

		// Get tabs
		$client_tabs = $module->getClientTabs($package);
		
		$tab_view = null;
		// Load/process the tab request
		if ($method && key_exists($method, $client_tabs) && is_callable(array($module, $method))) {
			// Set the module row used for this service
			$module->setModuleRow($module->getModuleRow($service->module_row_id));
			
		 	$tab_view = $module->{$method}($package, $service, $this->get, $this->post, $this->files);
			
			if (($errors = $module->errors()))
				$this->setMessage("error", $errors);
			elseif (!empty($this->post))
				$this->setMessage("success", Language::_("ClientServices.!success.manage.tab_updated", true));
		}

		$this->set("tab_view", $tab_view);
		$tabs = array(
			array(
				'name' => Language::_("ClientServices.manage.tab_service_info", true),
				'attributes' => array('href' => $this->base_uri . "services/manage/" . $service->id . "/", 'class' => "ajax"),
				'current' => empty($method)
			)
		);
		foreach ($client_tabs as $action => $name) {
			$tabs[] = array(
				'name' => $name,
				'attributes' => array('href' => $this->base_uri . "services/manage/" . $service->id . "/" . $action . "/", 'class' => "ajax"),
				'current' => strtolower($action) == strtolower($method)
			);
		}
		
		// Set language for periods
		$periods = $this->Packages->getPricingPeriods();
		foreach ($this->Packages->getPricingPeriods(true) as $period=>$lang)
			$periods[$period . "_plural"] = $lang;
		
		// Set partial for the service information box
		$this->set("service_infobox", $this->partial("client_services_service_infobox", array('periods' => $periods, 'service' => $service)));
		
		$this->set("service", $service);
		$this->set("package", $package);
		$this->set("tabs", $tabs);
		
		// Set whether the client can cancel a service
		// First check whether the service is already canceled
		if (!$service->date_canceled || ($service->date_canceled && strtotime($this->Date->cast($service->date_canceled, "date_time")) > strtotime(date("c")))) {
			// Service has not already been canceled, check whether the setting is enabled for clients to cancel services
			$client_cancel_service = $this->client->settings['clients_cancel_services'] == "true";
		}
		else {
			// Service is already canceled, can't cancel it again
			$client_cancel_service = false;
		}
		
		$this->set("client_cancel_service", $client_cancel_service);
		
		if ($this->isAjax())
			return $this->renderAjaxWidgetIfAsync(isset($this->get['whole_widget']) ? null : false);
	}
	
	/**
	 * Cancel Service
	 */
	public function cancel() {
		$this->uses(array("Currencies"));
		
		// Set whether the client can cancel a service
		$this->components(array("SettingsCollection"));
		$client_can_cancel_service = $this->SettingsCollection->fetchSetting(null, $this->company_id, "clients_cancel_services");
		$client_can_cancel_service = (isset($client_can_cancel_service['value']) && $client_can_cancel_service['value'] == "true");
		
		// Ensure we have a service
		if (!$client_can_cancel_service || !($service = $this->Services->get((int)$this->get[0])) || $service->client_id != $this->client->id)
			$this->redirect($this->base_uri);
		
		if (!empty($this->post['cancel'])) {
			$data = $this->post;
			
			// Cancel the service
			switch ($this->post['cancel']) {
				case "now":
					// Nothing to do
					break;
				case "term":
				default:
					// Cancel at end of service term
					$data['date_canceled'] = "end_of_term";
					break;
			}
			
			// Cancel the service
			$this->Services->cancel($service->id, $data);
			
			if (($errors = $this->Services->errors())) {
				// Error, reset vars
				$vars = (object)$this->post;
				$this->setMessage("error", $errors);
			}
			else {
				// Success
				$this->flashMessage("message", Language::_("ClientServices.!success.service_" . ($this->post['cancel'] == "term" ? "schedule_" : "") . "canceled", true));
				$this->redirect($this->base_uri . "services/manage/" . $service->id . "/");
			}
		}
		
		// Set language for periods
		$periods = $this->Packages->getPricingPeriods();
		foreach ($this->Packages->getPricingPeriods(true) as $period=>$lang)
			$periods[$period . "_plural"] = $lang;
		
		// Set partial for the service information box
		$this->set("service_infobox", $this->partial("client_services_service_infobox", array('periods' => $periods, 'service' => $service)));
		
		// Set the cancellation to be at the end of the term
		if (!isset($vars))
			$vars = (object)array('cancel' => "term");
		
		// Set the confirmation message for canceling the service
		$cancel_messages = array(
			'now' => Language::_("ClientServices.cancel.confirm_cancel_now", true),
			'term' => Language::_("ClientServices.cancel.confirm_cancel", true)
		);
		if (isset($service->package_pricing->cancel_fee) && $service->package_pricing->cancel_fee > 0) {
			// Get the client settings
			$client_settings = $this->SettingsCollection->fetchClientSettings($service->client_id);
			
			// Get the pricing info
			if ($client_settings['default_currency'] != $service->package_pricing->currency)
				$pricing_info = $this->Services->getPricingInfo($service->id, $client_settings['default_currency']);
			else
				$pricing_info = $this->Services->getPricingInfo($service->id);
			
			// Set the formatted cancellation fee and confirmation message
			if ($pricing_info) {
				$cancellation_fee = $this->Currencies->toCurrency($pricing_info->cancel_fee, $pricing_info->currency, $this->company_id);
				
				$cancel_messages['now'] = Language::_("ClientServices.cancel.confirm_cancel_now", true) . " " . Language::_("ClientServices.cancel.confirm_cancel_now_fee", true, $cancellation_fee);
				
				if ($pricing_info->tax)
					$cancel_messages['now'] = Language::_("ClientServices.cancel.confirm_cancel_now", true) . " " . Language::_("ClientServices.cancel.confirm_cancel_now_fee_tax", true, $cancellation_fee);
			}
		}
		
		$this->set("confirm_cancel_messages", $cancel_messages);
		$this->set("service", $service);
		$this->set("package", $this->Packages->get($service->package->id));
		$this->set("vars", $vars);
	}
	
	/**
	 * Service Info
	 */
	public function serviceInfo() {
		
		$this->uses(array("ModuleManager"));
			
		// Ensure we have a service
		if (!($service = $this->Services->get((int)$this->get[0])) || $service->client_id != $this->client->id)
			$this->redirect($this->base_uri);
		
		$package = $this->Packages->get($service->package->id);
		$module = $this->ModuleManager->initModule($service->package->module_id);
		
		if ($module) {
			$module->base_uri = $this->base_uri;
			$module->setModuleRow($module->getModuleRow($service->module_row_id));
			$this->set("content", $module->getClientServiceInfo($service, $package));
		}
		
		// Set any addon services
		$this->set("services", $this->Services->getAllChildren($service->id));
		
		// Set language for periods
		$periods = $this->Packages->getPricingPeriods();
		foreach ($this->Packages->getPricingPeriods(true) as $period=>$lang)
			$periods[$period . "_plural"] = $lang;
		$this->set("periods", $periods);
		$this->set("statuses", $this->Services->getStatusTypes());

		echo $this->outputAsJson($this->view->fetch("client_services_serviceinfo"));
		return false;
	}
}
?>