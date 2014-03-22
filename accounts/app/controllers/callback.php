<?php
/**
 * Callback controller, handles all callback requests
 *
 * @package blesta
 * @subpackage blesta.app.controllers
 * @copyright Copyright (c) 2010, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class Callback extends AppController {
	
	/**
	 * Setup
	 */
	public function preAction() {
		// Disable automatic CSRF check
		Configure::set("Blesta.verify_csrf_token", false);
		
		parent::preAction();
	}
	
	/**
	 * Handle backward compatible gateway callback requests
	 */
	public function index() {
		
		// Backward compatible gateway callback requests (Blesta version 2.x)
		if (isset($this->get['gw']) || isset($this->post['gw'])) {
			if (isset($this->get['gw']))
				$gateway_name = $this->get['gw'];
			if (isset($this->post['gw']))
				$gateway_name = $this->post['gw'];
			
			$gateways = array(
				'_2checkout' => "_2checkout",
				'googlecheckout' => "google_checkout",
				'paypal' => "paypal_payments_standard",
				'paypalsub' => "paypal_payments_standard"
			);
			
			if (isset($this->get['uid'])) {
				$this->components(array("Record"));

				$client = $this->Record->select()->from("clients")->
					innerJoin("client_groups", "client_groups.id", "=", "clients.client_group_id", false)->
					where("client_groups.company_id", "=", Configure::get("Blesta.company_id"))->
					where("clients.id_value", "=", $this->get['uid'])->fetch();

				if ($client)
					$this->get['client_id'] = $client->id;
			}
			
			if (array_key_exists($gateway_name, $gateways)) {
				$this->gw($gateways[$gateway_name]);
			}
		}

		// Nothing here, redirect back to landing page
		$this->redirect();
	}
	
	/**
	 * Handle gateway callbacks
	 */
	public function gw($gateway_name=null) {

		$this->components(array("GatewayPayments"));
		
		// Company ID is in the 1st parameter
		if (isset($this->get[0])) {
			$this->company_id = $this->get[0];
			Configure::set("Blesta.company_id", $this->company_id);
		}

		// Gateway name in the 2nd parameter
		if ($gateway_name === null && isset($this->get[1]))
			$gateway_name = $this->get[1];
		
		// Process the payment notification from the gateway
		$this->GatewayPayments->processNotification($gateway_name, $this->get, $this->post);
		
		// Redirect any client back to the payment received page
		if ($this->Session->read("blesta_client_id") || $this->Session->read("payment"))
			$this->redirect($this->client_uri . "pay/received/" . $gateway_name);
		
		return false;
	}
}
?>