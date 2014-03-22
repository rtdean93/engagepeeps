<?php
/**
 * Client portal main controller
 *
 * @package blesta
 * @subpackage blesta.app.controllers
 * @copyright Copyright (c) 2010, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class ClientMain extends AppController {
	
	/**
	 * @var string The custom field prefix used in form names to keep them unique and easily referenced
	 */
	private $custom_field_prefix = "c_f";
	/**
	 * @var array A list of client editable settings
	 */
	private $editable_settings = array();
	
	/**
	 * Main pre-action
	 */
	public function preAction() {
		parent::preAction();
		
		// Allow states to be fetched without login
		if (strtolower($this->action) == "getstates")
			return;
		
		// Require login
		$this->requireLogin();
		
		// Load models, language
		$this->uses(array("Clients", "Contacts"));
		Language::loadLang(array("client_main"));
		
		$this->client = $this->Clients->get($this->Session->read("blesta_client_id"));
		
		// Attempt to set the page title language
		if ($this->client) {
			try {
				$language = Language::_("ClientMain." . Loader::fromCamelCase($this->action ? $this->action : "index") . ".page_title", true, $this->client->id_code);
				$this->structure->set("page_title", $language);
			}
			catch(Exception $e) {
				// Attempting to set the page title language has failed, likely due to
				// the language definition requiring multiple parameters.
				// Fallback to index. Assume the specific page will set its own page title otherwise.
				$this->structure->set("page_title", Language::_("ClientMain.index.page_title", true), $this->client->id_code);
			}
		}
		else {
			$this->Session->clear("blesta_client_id");
			$this->redirect($this->base_uri);
		}
		
		// Set left client info section
		$this->setMyInfo();
		
		// Set editable client settings
		$client_settings = $this->client->settings;
		$this->editable_settings = array(
			'autodebit' => true,
			'tax_id' => true,
			'inv_address_to' => true,
			'default_currency' => (isset($client_settings['client_set_currency']) ? ($client_settings['client_set_currency'] == "true") : false),
			'inv_method' => (isset($client_settings['client_set_invoice']) ? ($client_settings['client_set_invoice'] == "true") : false),
			'language' => (isset($client_settings['client_set_lang']) ? ($client_settings['client_set_lang'] == "true") : false)
		);
	}
	
	/**
	 * Client Profile
	 */
	public function index() {

		// Get all client currencies that there may be amounts due in
		$currencies = $this->Invoices->invoicedCurrencies($this->client->id);
		
		// Set a message for all currencies that have an amount due
		$amount_due_message = null;
		$max_due = 0;
		$max_due_currency = null;
		$currencies_owed = 0;
		foreach ($currencies as $currency) {
			$total_due = $this->Invoices->amountDue($this->client->id, $currency->currency);

			if ($total_due > $max_due) {
				$max_due_currency = $currency->currency;
				$max_due = $total_due;
				$amount_due_message = Language::_("ClientMain.!info.invoice_due_text", true, $this->CurrencyFormat->format($total_due, $currency->currency));
				$currencies_owed++;
			}
		}
		
		if ($amount_due_message) {
			$message = array('amount_due' => array($amount_due_message));
			if ($currencies_owed > 1)
				$message['amount_due'][] = Language::_("ClientMain.!info.invoice_due_other_currencies", true);
				
			$this->setMessage("info", $message, false, array(
				'info_title' => Language::_("ClientMain.!info.invoice_due_title", true, $this->client->first_name),
				'info_buttons' => array(
					array(
						'class' => "btn",
						'url' => $this->Html->safe($this->base_uri . "pay/index/" . $max_due_currency . "/"),
						'label' => Language::_("ClientMain.!info.invoice_due_button", true)
					)
				)
			));
		}
		
		$this->set("client", $this->client);
	}
	
	/**
	 * Edit the client
	 */
	public function edit() {
		$this->uses(array("Currencies", "Languages", "Users"));
		$this->ArrayHelper = $this->DataStructure->create("Array");
		
		$vars = array();
		
		// Update the client
		if (!empty($this->post)) {
			// Set the client settings to update
			$new_client_settings = array();
			foreach ($this->editable_settings as $setting=>$enabled) {
				if (isset($this->post[$setting]) && $enabled)
					$new_client_settings[$setting] = $this->post[$setting];
			}
			
			// Begin a new transaction
			$this->Clients->begin();
			
			// Update the password if given
			$user_errors = array();
			if (!empty($this->post['new_password'])) {
				// Create a new user
				$user_vars = array(
					'new_password' => $this->post['new_password'],
					'confirm_password' => $this->post['confirm_password'],
					'current_password' => $this->post['current_password']
				);
				
				$this->Users->edit($this->client->user_id, $user_vars, true);
				$user_errors = $this->Users->errors();
			}
			
			// Update the client
			$this->post['id_code'] = $this->client->id_code;
			$this->post['user_id'] = $this->client->user_id;
			$this->Clients->edit($this->client->id, $this->post);
			$client_errors = $this->Clients->errors();
			
			// Update the client custom fields
			$custom_field_errors = $this->addCustomFields($this->post);
			
			// Update client settings
			$this->Clients->setClientSettings($this->client->id, $new_client_settings);
			$client_settings_errors = $this->Clients->errors();
			
			// Update the phone numbers
			$vars = $this->post;
			// Format the phone numbers
			$vars['numbers'] = $this->ArrayHelper->keyToNumeric($this->post['numbers']);
			
			// Update the contact
			unset($vars['user_id']);
			$this->Contacts->edit($this->client->contact_id, $vars);
			$contact_errors = $this->Contacts->errors();
			
			// Combine any errors
			$errors = array_merge(($client_errors ? $client_errors : array()), ($contact_errors ? $contact_errors : array()), ($client_settings_errors ? $client_settings_errors : array()), ($custom_field_errors ? $custom_field_errors : array()), ($user_errors ? $user_errors : array()));
			
			if (!empty($errors)) {
				// Error, rollback
				$this->Clients->rollBack();
				
				$this->setMessage("error", $errors);
				$vars = (object)$this->post;
			}
			else {
				// Success, commit
				$this->Clients->commit();
				
				$this->flashMessage("message", Language::_("ClientMain.!success.client_updated", true));
				$this->redirect($this->base_uri);
			}
		}
		
		// Set the initial client data
		if (empty($vars)) {
			$vars = $this->client;
			
			// Set contact phone numbers formatted for HTML
			$vars->numbers = $this->ArrayHelper->numericToKey($vars->numbers);
			
			// Set client custom field values
			$field_values = $this->Clients->getCustomFieldValues($this->client->id);
			foreach ($field_values as $field) {
				$vars->{$this->custom_field_prefix . $field->id} = $field->value;
			}
		}
		
		// Set whether to show additional settings section
		$show_additional_settings = false;
		if ($this->editable_settings['language'] || 0 < count($custom_fields = $this->Clients->getCustomFields($this->client->company_id, $this->client->client_group_id, array('show_client' => 1))))
			$show_additional_settings = true;
		
		// Get all client contacts for which to make invoices addressable to (primary and billing contacts)
		$contacts = array_merge($this->Contacts->getAll($this->client->id, "primary"), $this->Contacts->getAll($this->client->id, "billing"));
		
		$this->set("username", $this->client->username);
		$this->set("contacts", $this->Form->collapseObjectArray($contacts, array("first_name", "last_name"), "id", " "));
		$this->set("currencies", $this->Form->collapseObjectArray($this->Currencies->getAll($this->client->company_id), "code", "code"));
		$this->set("languages", $this->Form->collapseObjectArray($this->Languages->getAll($this->client->company_id), "name", "code"));
		$this->set("enabled_fields", $this->editable_settings);
		$this->set("show_additional_settings", $show_additional_settings);
		$this->set("vars", $vars);
		
		// Set partials to view
		$this->setContactView($vars);
		$this->setPhoneView($vars);
		$this->setCustomFieldView($vars);
	}
	
	/**
	 * Edit client's invoice method
	 */
	public function invoiceMethod() {
		// Get available delivery methods
		$delivery_methods = $this->Invoices->getDeliveryMethods($this->client->id);
		
		$vars = array();
		
		if (!empty($this->post)) {
			// Only update the invoice method setting from this page
			$vars = array('inv_method' => (isset($this->post['inv_method']) ? $this->post['inv_method'] : ""));
			$this->Clients->setClientSettings($this->client->id, $vars);
			
			if (($errors = $this->Clients->errors())) {
				// Error, reset vars
				$vars = (object)$this->post;
				$this->setMessage("error", $errors);
			}
			else {
				// Success
				$new_invoice_method = isset($delivery_methods[$vars['inv_method']]) ? $delivery_methods[$vars['inv_method']] : "";
				$this->flashMessage("message", Language::_("ClientMain.!success.invoice_method_updated", true, $new_invoice_method));
				$this->redirect($this->base_uri);
			}
		}
		
		// Set the invoice method, or reset when setting is disabled
		if (empty($vars) || !$this->editable_settings['inv_method'])
			$vars = (object)array('inv_method' => $this->client->settings['inv_method']);
		
		// Set message descriptions for each delivery method available
		$delivery_method_language = array();
		foreach ($delivery_methods as $method => $name)
			$delivery_method_language[] = Language::_("ClientMain.!info.invoice_method_" . $method, true, $name);
		
		$messages = array('inv_method' => array_merge(array(Language::_("ClientMain.!info.invoice_method_current", true, (isset($delivery_methods[$this->client->settings['inv_method']]) ? $delivery_methods[$this->client->settings['inv_method']] : ""))), $delivery_method_language));
		$this->setMessage("info", $messages);
		
		$this->set("vars", $vars);
		$this->set("enabled", $this->editable_settings['inv_method']);
		$this->set("delivery_methods", $delivery_methods);
	}
	
	/**
	 * Attempts to add custom fields to a client
	 *
	 * @param int $client_id The client ID to add custom fields for
	 * @param array $vars The post data, containing custom fields
	 * @return mixed An array of errors, or false if none exist
	 * @see Clients::add(), Clients::edit()
	 */
	private function addCustomFields(array $vars=array()) {
		$client_id = $this->client->id;
		
		// Get the client's current custom fields
		$client_custom_fields = $this->Clients->getCustomFieldValues($client_id);
		
		// Create a list of custom field IDs to update
		$custom_fields = $this->Clients->getCustomFields($this->client->company_id, $this->client->client_group_id);
		$custom_field_ids = array();
		foreach ($custom_fields as $field)
			$custom_field_ids[] = $field;
		unset($field);
		
		// Build a list of given custom fields to update
		$custom_fields_set = array();
		foreach ($vars as $field => $value) {
			// Get the custom field ID from the name
			$field_id = preg_replace("/" . $this->custom_field_prefix . "/", "", $field, 1);
			
			// Set the custom field
			if ($field_id != $field)
				$custom_fields_set[$field_id] = $value;
		}
		unset($field, $value);
		
		// Set every custom field available, even if it's not given, for validation
		$deletable_fields = array();
		foreach ($custom_field_ids as $field) {
			if (!isset($custom_fields_set[$field->id])) {
				// Only set custom field to validate if it is not read only
				if ($field->read_only != "1") {
					// Set a temp value for validation purposes
					$custom_fields_set[$field->id] = "";
					// Set this field to be deleted
					$deletable_fields[] = $field->id;
				}
			}
		}
		unset($field_id);
		
		// Attempt to add/update each custom field
		$temp_field_errors = array();
		foreach ($custom_fields_set as $field_id => $value) {
			$this->Clients->setCustomField($field_id, $client_id, $value);
			$temp_field_errors[] = $this->Clients->errors();
		}
		unset($field_id, $value);
		
		// Delete the fields that were not given
		foreach ($deletable_fields as $field_id)
			$this->Clients->deleteCustomFieldValue($field_id, $client_id);
		
		// Combine multiple custom field errors together
		$custom_field_errors = array();
		for ($i=0, $num_errors=count($temp_field_errors); $i<$num_errors; $i++) {
			// Skip any "error" that is not an array already
			if (!is_array($temp_field_errors[$i]))
				continue;
			
			// Change the keys of each custom field error so we can display all of them at once
			$error_keys = array_keys($temp_field_errors[$i]);
			$temp_error = array();
			
			foreach ($error_keys as $key)
				$temp_error[$key . $i] = $temp_field_errors[$i][$key];
			
			$custom_field_errors = array_merge($custom_field_errors, $temp_error);
		}
		
		return (empty($custom_field_errors) ? false : $custom_field_errors);
	}
	
	/**
	 * Sets the contact partial view
	 * @see ClientMain::edit()
	 *
	 * @param stdClass $vars The input vars object for use in the view
	 */
	private function setContactView(stdClass $vars) {
		$this->uses(array("Countries", "States"));
		
		$contacts = array();
		
		// Set partial for contact info
		$contact_info = array(
			'js_contacts' => $this->Json->encode($contacts),
			'contacts' => $this->Form->collapseObjectArray($contacts, array("first_name", "last_name"), "id", " "),
			'countries' => $this->Form->collapseObjectArray($this->Countries->getList(), array("name", "alt_name"), "alpha2", " - "),
			'states' => $this->Form->collapseObjectArray($this->States->getList($vars->country), "name", "code"),
			'vars' => $vars,
			'edit' => true,
			'show_email' => true
		);
		
		// Load language for partial
		Language::loadLang("client_contacts");
		$this->set("contact_info", $this->partial("client_contacts_contact_info", $contact_info));
	}
	
	/**
	 * Sets the contact phone number partial view
	 * @see ClientMain::edit()
	 *
	 * @param stdClass $vars The input vars object for use in the view
	 */
	private function setPhoneView(stdClass $vars) {
		// Set partial for phone numbers
		$partial_vars = array(
			'numbers'=>(isset($vars->numbers) ? $vars->numbers : array()),
			'number_types'=>$this->Contacts->getNumberTypes(),
			'number_locations'=>$this->Contacts->getNumberLocations()
		);
		$this->set("phone_numbers", $this->partial("client_contacts_phone_numbers", $partial_vars));
	}
	
	/**
	 * Sets the custom fields partial view
	 * @see ClientMain::edit()
	 *
	 * @param stdClass $vars An stdClass object representing the client vars
	 */
	private function setCustomFieldView(stdClass $vars) {
		// Set partial for custom fields
		$custom_fields = $this->Clients->getCustomFields($this->client->company_id, $this->client->client_group_id);
		$custom_field_values = null;
		
		// Swap key/value pairs for "Select" option custom fields (to display)
		foreach ($custom_fields as &$field) {
			// Swap select values
			if ($field->type == "select" && is_array($field->values))
				$field->values = array_flip($field->values);
			
			// Re-set any missing custom field values (e.g. in the case of errors) for read-only vars
			if ($field->read_only == "1" && !isset($vars->{$this->custom_field_prefix . $field->id})) {
				// Fetch the custom field values for this client
				if ($custom_field_values === null)
					$custom_field_values = $this->Clients->getCustomFieldValues($this->client->id);
				
				// Set this custom field value to the client's value
				foreach ($custom_field_values as $custom_field) {
					if ($custom_field->id == $field->id) {
						$vars->{$this->custom_field_prefix . $field->id} = $custom_field->value;
						break;
					}
				}
			}
		}
		
		$partial_vars = array(
			'vars' => $vars,
			'custom_fields' => $custom_fields,
			'custom_field_prefix' => $this->custom_field_prefix
		);
		$this->set("custom_fields", $this->partial("client_main_custom_fields", $partial_vars));
	}
	
	/**
	 * Sets a partial view that contains all left-column client info
	 */
	private function setMyInfo() {
		$this->uses(array("Accounts", "Invoices"));
		
		$client = $this->client;
		// Get client contact numbers
		$client->numbers = $this->Contacts->getNumbers($client->contact_id);
		
		// Get available invoice delivery methods and set language for the one set for this client
		$invoice_delivery_methods = $this->Invoices->getDeliveryMethods($client->id, $client->client_group_id, true);
		$invoice_method_language = (isset($invoice_delivery_methods[$client->settings['inv_method']]) ? $invoice_delivery_methods[$client->settings['inv_method']] : "");
		
		$myinfo_settings = array(
			'invoice' => array(
				'enabled' => ("true" == $client->settings['client_set_invoice']),
				'description' => Language::_("ClientMain.myinfo.setting_invoices", true, $invoice_method_language)
			),
			'autodebit' => array(
				'enabled' => true,
				'description' => $this->getAutodebitDescription()
			)
		);
		
		$this->set("myinfo", $this->partial("client_main_myinfo", compact("client", "myinfo_settings")));
	}
	
	/**
	 * AJAX Fetches the currency amounts for the my info sidebar
	 */
	public function getCurrencyAmounts() {
		// Ensure a valid client was given
		if (!$this->isAjax()) {
			header($this->server_protocol . " 401 Unauthorized");
			exit();
		}
		
		$this->uses(array("Currencies", "Transactions"));
		
		$currency_code = $this->client->settings['default_currency'];
		if (isset($this->get[0]) && ($currency = $this->Currencies->get($this->get[0], $this->company_id)))
			$currency_code = $currency->code;
		
		// Fetch the amounts
		$amounts = array(
			'total_credit' => array(
				'lang' => Language::_("ClientMain.getcurrencyamounts.text_total_credits", true),
				'amount' => $this->CurrencyFormat->format($this->Transactions->getTotalCredit($this->client->id, $currency_code), $currency_code)
			)
		);
		
		// Build the vars
		$vars = array(
			'selected_currency' => $currency_code,
			'currencies' => array_unique(array_merge($this->Clients->usedCurrencies($this->client->id), array($this->client->settings['default_currency']))),
			'amounts' => $amounts
		);
		
		// Set the partial for currency amounts
		$response = $this->partial("client_main_getcurrencyamounts", $vars);
		
		// JSON encode the AJAX response
		$this->outputAsJson($response);
		return false;
	}
	
	/**
	 * AJAX Fetch all states belonging to a given country (json encoded ajax request)
	 */
	public function getStates() {
		$this->uses(array("States"));
		// Prepend "all" option to state listing
		$states = array();
		if (isset($this->get[0]))
			$states = (array)$this->Form->collapseObjectArray($this->States->getList($this->get[0]), "name", "code");
		
		echo $this->Json->encode($states);
		return false;
	}
	
	/**
	 * Retrieves the autodebit language description based on the payment account settings
	 *
	 * @return string The autodebit language description
	 */
	private function getAutodebitDescription() {
		$client = $this->client;
		#
		# TODO: Clean this up... -- BEGIN
		#
		#
		// Set autodebit/invoice language based on settings
		$autodebit_description = Language::_("ClientMain.myinfo.setting_autodebit_disabled", true);
		if (("true" == $client->settings['autodebit']) && ($debit_account = $this->Clients->getDebitAccount($client->id))) {
			$autodebit_days_before_due = $client->settings['autodebit_days_before_due'];
			$autodebit_description = Language::_("ClientMain.myinfo.setting_autodebit_enabled", true);
			$autodebit_account_description = "";
			
			// Set autodebit language based on account
			switch($debit_account->type) {
				case "cc":
					if (($autodebit_account = $this->Accounts->getCc($debit_account->account_id))) {
						$card_types = $this->Accounts->getCcTypes();
						$card_type = (isset($card_types[$autodebit_account->type]) ? $card_types[$autodebit_account->type] : "");
						
						// Set the language based on how many days before due. Zero, one, or more
						if ($autodebit_days_before_due == 0)
							$autodebit_account_description = Language::_("ClientMain.myinfo.setting_autodebit_cc_zero_days", true, $card_type, $autodebit_account->last4);
						elseif ($autodebit_days_before_due == 1)
							$autodebit_account_description = Language::_("ClientMain.myinfo.setting_autodebit_cc_one_day", true, $card_type, $autodebit_account->last4);
						else
							$autodebit_account_description = Language::_("ClientMain.myinfo.setting_autodebit_cc_days", true, $card_type, $autodebit_account->last4, $autodebit_days_before_due);
					}
					break;
				case "ach":
					if (($autodebit_account = $this->Accounts->getAch($debit_account->account_id))) {
						$account_types = $this->Accounts->getAchTypes();
						$account_type = (isset($account_types[$autodebit_account->type]) ? $account_types[$autodebit_account->type] : "");
						
						if ($autodebit_days_before_due == 0)
							$autodebit_account_description =  Language::_("ClientMain.myinfo.setting_autodebit_ach_zero_days", true, $account_type, $autodebit_account->last4);
						elseif ($autodebit_days_before_due == 1)
							$autodebit_account_description =  Language::_("ClientMain.myinfo.setting_autodebit_ach_one_day", true, $account_type, $autodebit_account->last4);
						else
							$autodebit_account_description =  Language::_("ClientMain.myinfo.setting_autodebit_ach_days", true, $account_type, $autodebit_account->last4, $autodebit_days_before_due);
					}
					break;
			}
			
			// Combine the autodebit descriptions
			$autodebit_description = $this->Html->concat(" ", $autodebit_description, $autodebit_account_description);
		}
		#
		# TODO: Clean this up... -- END
		#
		#
		return $autodebit_description;
	}
}
?>