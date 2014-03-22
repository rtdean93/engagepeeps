<?php
/**
 * Client portal pay controller
 *
 * @package blesta
 * @subpackage blesta.app.controllers
 * @copyright Copyright (c) 2010, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class ClientPay extends AppController {

	/**
	 * Pre-action
	 */
	public function preAction() {
		
		if (strtolower($this->action) == "received") {
			// Disable automatic CSRF check for callback action
			Configure::set("Blesta.verify_csrf_token", false);
		}
		
		parent::preAction();
		
		$this->uses(array("Accounts", "Clients", "Contacts", "Currencies", "Invoices", "Services", "Transactions"));
		
		// If hash access requested, verify that it is correct
		if ($this->action == "method" && isset($this->get['sid'])) {
			$params = array();
			$temp = explode("|", $this->Invoices->systemDecrypt($this->get['sid']));
			
			if (count($temp) <= 1)
				$this->redirect($this->base_uri . "login/");
			
			foreach ($temp as $field) {
				$field = explode("=", $field, 2);
				$params[$field[0]] = $field[1];
			}
			
			// Verify hash matches
			if (!$this->Invoices->verifyPayHash($params['c'], (isset($this->get[0]) ? $this->get[0] : null), $params['h']))
				$this->redirect($this->base_uri . "login/");
				
			// Fetch the client record being processed
			$this->client = $this->Clients->get($params['c']);
		}
		
		// Get the logged-in client ID
		$blesta_client_id = $this->Session->read("blesta_client_id");
		
		// Require login if not on payment received screen or making payment
		$payment = $this->Session->read("payment");
		if (!isset($this->client) && empty($payment) && $this->action != "received")
			$this->requireLogin();
		// Fetch the client from this payment session
		elseif (empty($blesta_client_id) && isset($payment['client_id']))
			$this->client = $this->Clients->get($payment['client_id']);
		elseif (isset($this->get['client_id']))
			$this->client = $this->Clients->get($this->get['client_id']);
			
		Language::loadLang("client_pay");

		if (!isset($this->client))
			$this->client = $this->Clients->get($blesta_client_id);
		
		// Attempt to set the page title language
		if ($this->client) {
			try {
				$language = Language::_("ClientPay." . Loader::fromCamelCase($this->action ? $this->action : "index") . ".page_title", true, $this->client->id_code);
				$this->structure->set("page_title", $language);
			}
			catch(Exception $e) {
				// Attempting to set the page title language has failed, likely due to
				// the language definition requiring multiple parameters.
				// Fallback to index. Assume the specific page will set its own page title otherwise.
				$this->structure->set("page_title", Language::_("ClientPay.index.page_title", true), $this->client->id_code);
			}
		}
		else
			$this->redirect($this->base_uri);
	}
	
	/**
	 * Step 1 - select invoices to pay
	 */
	public function index() {
		// Check if payment data has already been set
		$payment = $this->Session->read("payment");
		// Set payment data for updating
		if (!empty($payment['currency'])) {
			// Default to the current payment
			if (!isset($this->get[0]) || $this->get[0] == $payment['currency']) {
				$currency = $this->Currencies->get($payment['currency'], $this->company_id);
				$vars = new stdClass();
				$vars->credit = (isset($payment['credit']) ? $payment['credit'] : "");
				
				// Set the selected payment amounts
				if (isset($payment['amounts'])) {
					$vars->applyamount = array();
					$vars->invoice_id = array();
					foreach ($payment['amounts'] as $amount) {
						$vars->applyamount[$amount['invoice_id']] = $amount['amount'];
						$vars->invoice_id[] = $amount['invoice_id'];
					}
					unset($amount);
				}
			}
		}
		
		// Ensure a valid currency was given
		if (isset($this->get[0]) && !($currency = $this->Currencies->get($this->get[0], $this->company_id)))
			$this->redirect($this->base_uri);
		
		// Get the client settings
		$client_settings = $this->client->settings;
		
		// Use the default currency
		if (empty($currency))
			$currency = $this->Currencies->get($client_settings['default_currency'], $this->company_id);
		
		if (!empty($this->post)) {
			// Set the invoices selected to be paid
			$invoice_ids = (isset($this->post['invoice_id']) ? $this->post['invoice_id'] : null);
			if (isset($invoice_ids[0]) && $invoice_ids[0] == "all")
				unset($invoice_ids[0]);
			
			// Check for invalid credit amounts
			$errors = array();
			$credit = $this->CurrencyFormat->cast((isset($this->post['credit']) ? $this->post['credit'] : ""), $currency->code);
			if ($credit < 0)
				$errors = array(array('invalid_credit' => Language::_("ClientPay.!error.invalid_amount", true)));
			
			// Check that either invoices were given to be paid, or a credit was
			if (empty($invoice_ids) && empty($this->post['credit']))
				$errors[] = array('payment_amounts' => Language::_("ClientPay.!error.payment_amounts", true));
			
			// Verify payment amounts, ensure that amounts entered do no exceed total due on invoice
			if (isset($this->post['invoice_id']) || isset($this->post['credit'])) {
				$apply_amounts = array(
					'amounts' => array(),
					'currency' => $currency->code,
					'credit' => (isset($this->post['credit']) ? $this->post['credit'] : 0),
					'client_id' => (isset($payment['client_id']) ? $payment['client_id'] : null)
				);
				
				$transaction_errors = array();
				if (isset($this->post['invoice_id'])) {
					foreach ($this->post['invoice_id'] as $inv_id) {
						if (isset($this->post['applyamount'][$inv_id])) {
							$apply_amounts['amounts'][] = array(
								'invoice_id' => $inv_id,
								'amount' => $this->CurrencyFormat->cast($this->post['applyamount'][$inv_id], $currency->code)
							);
						}
					}
					
					$this->Transactions->verifyApply($apply_amounts, false);
					$transaction_errors = $this->Transactions->errors();
				}
				$errors = array_merge(($transaction_errors ? $transaction_errors : array()), $errors);
			}
			
			if ($errors) {
				$vars = (object)$this->post;
				$this->setMessage("error", $errors);
			}
			else {
				// Save the payment amounts
				$this->Session->write("payment", $apply_amounts);
				$this->redirect($this->base_uri . "pay/method/");
			}
		}
		
		if (!isset($vars))
			$vars = new stdClass();
		
		// Default select all invoices
		if (empty($vars->invoice_id) && empty($errors))
			$vars->invoice_id = array("all");
		
		// Get all invoices open for this client (to be paid)
		$invoice_list = (isset($invoice) ? array($invoice) : $this->Invoices->getAll($this->client->id, "open", array('date_due'=>"ASC"), $currency->code));
		
		// Check for different amounts due and disable toggle link
		$toggle_amounts = true;
		foreach ($invoice_list as $inv) {
			if (isset($vars->applyamount[$inv->id]) && $vars->applyamount[$inv->id] != $this->CurrencyFormat->cast($inv->due, $currency->code)) {
				$toggle_amounts = false;
				break;
			}
		}
		
		$this->set("vars", $vars);
		$this->set("invoice_info", $this->partial("client_pay_multiple_invoices", array('vars'=>(isset($vars) ? $vars : new stdClass()), 'invoices'=>$invoice_list, 'toggle_amounts'=>$toggle_amounts)));
	}
	
	/**
	 * Step 2 - select payment method
	 */
	public function method() {
		// Pay a single invoice
		if (isset($this->get[0]) && ($invoice = $this->Invoices->get($this->get[0])) && $invoice->client_id == $this->client->id) {
			// Format the fields and save the info
			$invoices = array(
				'amounts' => array(array('invoice_id' => $invoice->id, 'amount' => $this->CurrencyFormat->cast($invoice->due, $invoice->currency))),
				'currency' => $invoice->currency,
				'credit' => "",
				'client_id' => $this->client->id
			);
			$this->Session->write("payment", $invoices);
		}
		else {
			// Ensure some invoices exist from the first step
			$invoices = $this->Session->read("payment");
			if ((!empty($invoice) && $invoice && $invoice->client_id != $this->client->id) || empty($invoices) || empty($invoices['currency']) || (empty($invoices['amounts']) && empty($invoices['credit']))) {
				$this->Session->clear("payment");
				$this->redirect($this->base_uri);
			}
		}
		
		// Get all non-merchant gateways
		$this->uses(array("GatewayManager"));
		$nm_gateways = $this->GatewayManager->getAllInstalledNonmerchant($this->company_id, $invoices['currency']);
		
		if (!empty($this->post)) {
			$errors = array();
			$this->post['pay_with'] = isset($this->post['pay_with']) ? $this->post['pay_with'] : null;
			
			if ($this->post['pay_with'] == "details") {
				// Fetch the contact we're about to set the payment account for
				$this->post['contact_id'] = (isset($this->post['contact_id']) ? $this->post['contact_id'] : 0);
				$contact = $this->Contacts->get($this->post['contact_id']);
				
				if ($this->post['contact_id'] == "none" || !$contact || ($contact->client_id != $this->client->id))
					$this->post['contact_id'] = $this->client->contact_id;
				
				// Attempt to save the account, then set it as the account to use
				if (isset($this->post['save_details']) && $this->post['save_details'] == "true") {
					if ($this->post['payment_type'] == "ach") {
						$account_id = $this->Accounts->addAch($this->post);
						
						// Assign the newly created payment account as the account to use for this payment
						if ($account_id) {
							$this->post['payment_account'] = "ach_" . $account_id;
							$this->post['pay_with'] = "account";
						}
					}
					elseif ($this->post['payment_type'] == "cc") {
						$this->post['expiration'] = (isset($this->post['expiration_year']) ? $this->post['expiration_year'] : "") . (isset($this->post['expiration_month']) ? $this->post['expiration_month'] : "");
						// Remove type, it will be automatically determined
						unset($this->post['type']);
						$account_id = $this->Accounts->addCc($this->post);
						
						// Assign the newly created payment account as the account to use for this payment
						if ($account_id) {
							$this->post['payment_account'] = "cc_" . $account_id;
							$this->post['pay_with'] = "account";
						}
					}
				}
				// Verify the payment account details entered were correct, since we're not storing them
				else {
					$vars_arr = $this->post;
					if ($this->post['payment_type'] == "ach")
						$this->Accounts->verifyAch($vars_arr);
					elseif ($this->post['payment_type'] == "cc") {
						$this->post['expiration'] = (isset($this->post['expiration_year']) ? $this->post['expiration_year'] : "") . (isset($this->post['expiration_month']) ? $this->post['expiration_month'] : "");
						// Remove type, it will be automatically determined
						unset($this->post['type']);
						$vars_arr = $this->post;
						$this->Accounts->verifyCc($vars_arr);
					}
					
					if (isset($vars_arr['type']))
						$this->post['type'] = $vars_arr['type'];
					unset($vars_arr);
				}
				
				$errors = $this->Accounts->errors();
			}
			elseif ($this->post['pay_with'] != "details" && $this->post['pay_with'] != "account") {
				// Non-merchant gateway selected, make sure it's valid
				$errors = array(
					array(
						'invalid_gateway' => Language::_("ClientPay.!error.invalid_gateway", true)
					)
				);
				foreach ($nm_gateways as $gateway) {
					if ($this->post['pay_with'] == $gateway->id) {
						$errors = array();
						break;
					}
				}
			}
			
			if ($errors) {
				$this->setMessage("error", $errors);
				$vars = (object)$this->post;
			}
			else {
				// Save the payment method
				$this->Session->write("payment", array_merge($invoices, array('method' => $this->post)));
				$this->redirect($this->base_uri . "pay/confirm/");
			}
		}
		
		// Set initial vars
		if (empty($vars))
			$vars = new stdClass();
		
		
		// Fetch the auto-debit payment account (if set), so we can identify it
		$autodebit = $this->Clients->getDebitAccount($this->client->id);
		
		// Get ACH payment types
		$ach_types = $this->Accounts->getAchTypes();
		// Get CC payment types
		$cc_types = $this->Accounts->getCcTypes();
		
		// Set the payment types allowed
		$transaction_types = $this->Transactions->transactionTypeNames();
		$payment_types = array();
		if ($this->client->settings['payments_allowed_ach'] == "true")
			$payment_types['ach'] = $transaction_types['ach'];
		if ($this->client->settings['payments_allowed_cc'] == "true")
			$payment_types['cc'] = $transaction_types['cc'];
		
		// Set non-merchant gateway payment types
		$this->set("nm_gateways", $nm_gateways);
		
		// Set available payment accounts
		$payment_accounts = array();
		
		// Only allow CC payment accounts if enabled
		if (isset($payment_types['cc'])) {
			$cc = $this->Accounts->getAllCcByClient($this->client->id);
			
			$temp_cc_accounts = array();
			foreach ($cc as $account) {
				// Get the merchant gateway that can be used for this payment and this payment account
				$merchant_gateway = $this->GatewayManager->getInstalledMerchant($this->company_id, $invoices['currency'], $account->gateway_id, true);
				
				// Skip this payment account if it is expecting a different
				// merchant gateway, one is not available, or the payment
				// method is not supported by the gateway
				if (!$merchant_gateway ||
					($merchant_gateway &&
						(
							($account->gateway_id && $account->gateway_id != $merchant_gateway->id) ||
							($account->reference_id && !in_array("MerchantCcOffsite", $merchant_gateway->info['interfaces'])) ||
							(!$account->reference_id && !in_array("MerchantCc", $merchant_gateway->info['interfaces']))
						)
					))
					continue;
				
				$is_autodebit = false;
				if ($autodebit && $autodebit->type == "cc" && $autodebit->account_id == $account->id) {
					$is_autodebit = true;
					$vars->payment_account = "cc_" . $account->id;
				}
				$lang_define = ($is_autodebit ? "ClientPay.method.field_paymentaccount_autodebit" : "ClientPay.method.field_paymentaccount");
				$temp_cc_accounts["cc_" . $account->id] = Language::_($lang_define, true, $account->first_name, $account->last_name, $cc_types[$account->type], $account->last4);
			}
			
			// Add the CC payment accounts that can be used for this payment
			if (!empty($temp_cc_accounts)) {
				$payment_accounts[] = array('value'=>"optgroup", 'name'=>Language::_("ClientPay.method.field_paymentaccount_cc", true));
				$payment_accounts = array_merge($payment_accounts, $temp_cc_accounts);
			}
			unset($temp_cc_accounts);
		}
		
		// Only allow ACH payment accounts if enabled
		if (isset($payment_types['ach'])) {
			$ach = $this->Accounts->getAllAchByClient($this->client->id);
			
			$temp_ach_accounts = array();
			foreach ($ach as $account) {
				// Get the merchant gateway that can be used for this payment and this payment account
				$merchant_gateway = $this->GatewayManager->getInstalledMerchant($this->company_id, $invoices['currency'], $account->gateway_id, true);
				
				// Skip this payment account if it is expecting a different
				// merchant gateway, one is not available, or the payment
				// method is not supported by the gateway
				if (!$merchant_gateway ||
					($merchant_gateway &&
						(
							($account->gateway_id && $account->gateway_id != $merchant_gateway->id) ||
							($account->reference_id && !in_array("MerchantAchOffsite", $merchant_gateway->info['interfaces'])) ||
							(!$account->reference_id && !in_array("MerchantAch", $merchant_gateway->info['interfaces']))
						)
					))
					continue;
				
				$is_autodebit = false;
				if ($autodebit && $autodebit->type == "ach" && $autodebit->account_id == $account->id) {
					$is_autodebit = true;
					$vars->payment_account = "ach_" . $account->id;
				}
				$lang_define = ($is_autodebit ? "ClientPay.method.field_paymentaccount_autodebit" : "ClientPay.method.field_paymentaccount");
				$temp_ach_accounts["ach_" . $account->id] = Language::_($lang_define, true, $account->first_name, $account->last_name, $ach_types[$account->type], $account->last4);
			}
			
			// Add the ACH payment accounts that can be used for this payment
			if (!empty($temp_ach_accounts)) {
				$payment_accounts[] = array('value'=>"optgroup", 'name'=>Language::_("ClientPay.method.field_paymentaccount_ach", true));
				$payment_accounts = array_merge($payment_accounts, $temp_ach_accounts);
			}
			unset($temp_ach_accounts);
		}
		
		$this->set("payment_accounts", $payment_accounts);
		
		// Set the country
		$vars->country = (!empty($this->client->settings['country']) ? $this->client->settings['country'] : "");
		
		// Set the contact info partial to the view
		$this->setContactView($vars);
		// Set the CC info partial to the view
		$this->setCcView($vars, false, true);
		// Set the ACH info partial to the view
		$this->setAchView($vars, false, true);
		
		$this->set("payment_types", $payment_types);
		$this->set("vars", $vars);
	}
	
	/**
	 * Step 3 - confirm and make payment
	 */
	public function confirm() {
		// Ensure some invoices exist from the first step
		$payment = $this->Session->read("payment");
		if (empty($payment) || empty($payment['method']) || (empty($payment['amounts']) && empty($payment['credit'])) ||
			empty($payment['currency']) || !isset($payment['credit']))
			$this->redirect($this->base_uri);
		
		// Get the credit amount
		$total = $this->CurrencyFormat->cast($payment['credit'], $payment['currency']);
		$invoices = array();
		$apply_amounts = array();
		
		// Calculate the total to pay for each invoice
		foreach ($payment['amounts'] as $invoice) {
			$apply_amounts[$invoice['invoice_id']] = $this->CurrencyFormat->cast($invoice['amount'], $payment['currency']);
			$total += $apply_amounts[$invoice['invoice_id']];
			
			$invoice = $this->Invoices->get($invoice['invoice_id']);
			if ($invoice && $invoice->client_id == $this->client->id)
				$invoices[] = $invoice;
		}
		
		// Execute payment
		if (!empty($this->post)) {
			$this->uses(array("Payments"));
			
			$options = array(
				'invoices'=>$apply_amounts,
				'staff_id'=>null,
				'email_receipt'=>true
			);
			
			// Pay via existing CC/ACH account
			if ($payment['method']['pay_with'] == "account") {
				$account_info = null;
				list($type, $account_id) = explode("_", $payment['method']['payment_account'], 2);
			}
			// Pay one-time with the given details
			elseif ($payment['method']['pay_with'] == "details") {
				$type = $payment['method']['payment_type'];
				$account_id = null;
				$account_info = array(
					'first_name' => $payment['method']['first_name'],
					'last_name' => $payment['method']['last_name'],
					'address1' => $payment['method']['address1'],
					'address2' => $payment['method']['address2'],
					'city' => $payment['method']['city'],
					'state' => $payment['method']['state'],
					'country' => $payment['method']['country'],
					'zip' => $payment['method']['zip']
				);
				
				// Set ACH/CC-specific fields
				if ($type == "ach") {
					$account_info['account_number'] = $payment['method']['account'];
					$account_info['routing_number'] = $payment['method']['routing'];
					$account_info['type'] = $payment['method']['type'];
				}
				elseif ($type == "cc") {
					$account_info['card_number'] = $payment['method']['number'];
					$account_info['card_exp'] = $payment['method']['expiration_year'] . $payment['method']['expiration_month'];
					$account_info['card_security_code'] = $payment['method']['security_code'];
				}
			}
			
			// Process the payment (not non-merchant gateway payments)
			if ($payment['method']['pay_with'] == "account" || $payment['method']['pay_with'] == "details") {
				$transaction = $this->Payments->processPayment($this->client->id, $type, $total, $payment['currency'], $account_info, $account_id, $options);
				
				if (($errors = $this->Payments->errors())) {
					// Error
					$this->setMessage("error", $errors);
				}
				else {
					// Success, remove the payment data
					$this->Session->clear("payment");
					
					$this->flashMessage("message", Language::_("ClientPay.!success.payment_processed", true, $this->CurrencyFormat->format($transaction->amount, $transaction->currency), $transaction->transaction_id));
					$this->redirect($this->base_uri);
				}
			}
		}
		
		// Set the payment account being used if one exists
		if ($payment['method']['pay_with'] == "account") {
			// Set the account to use
			list($type, $account_id) = explode("_", $payment['method']['payment_account'], 2);
			
			if ($type == "cc")
				$this->set("account", $this->Accounts->getCc($account_id));
			elseif ($type == "ach")
				$this->set("account", $this->Accounts->getAch($account_id));
			
			$this->set("account_type", $type);
			$this->set("account_id", $account_id);
		}
		elseif ($payment['method']['pay_with'] == "details") {
			// Set the last 4
			if ($payment['method']['payment_type'] == "ach")
				$payment['method']['last4'] = substr($payment['method']['account'], -4);
			elseif ($payment['method']['payment_type'] == "cc")
				$payment['method']['last4'] = substr($payment['method']['number'], -4);
			
			$this->set("account_type", $payment['method']['payment_type']);
			$this->set("account", (object)$payment['method']);
		}
		else {
			// Non-merchant gateway
			$this->uses(array("Countries", "Payments", "States"));
			
			// Fetch this contact
			$contact = $this->Contacts->get($this->client->contact_id);
			
			$contact_info = array(
				'id' => $this->client->contact_id,
				'client_id' => $this->client->id,
				'user_id' => $this->client->user_id,
				'contact_type' => $contact->contact_type_name,
				'contact_type_id' => $contact->contact_type_id,
				'first_name' => $this->client->first_name,
				'last_name' => $this->client->last_name,
				'title' => $contact->title,
				'company' => $this->client->company,
				'address1' => $this->client->address1,
				'address2' => $this->client->address2,
				'city' => $this->client->city,
				'zip' => $this->client->zip,
				'country' => (array)$this->Countries->get($this->client->country),
				'state' => (array)$this->States->get($this->client->country, $this->client->state)
			);
			
			$options = array();
			$allow_recur = true;
			
			// Set the description for this payment
			$description = Language::_("ClientPay.confirm.description_credit", true);
			foreach ($invoices as $index => $invoice) {
				if ($index == 0)
					$description = Language::_("ClientPay.confirm.description_invoice", true, $invoice->id_code);
				else
					$description .= Language::_("ClientPay.confirm.description_invoice_separator", true) . " " . Language::_("ClientPay.confirm.description_invoice_number", true, $invoice->id_code);
					
				// Check for recurring info
				if ($allow_recur && ($recur = $this->getRecurInfo($invoice))) {
					// Only keep recurring info if none exists or is the same term and period as the existing
					if (!isset($options['recur']) || ($options['recur']['term'] == $recur['term'] && $options['recur']['period'] == $recur['period'])) {
						if (!isset($options['recur']))
							$options['recur'] = $recur;
						// Sum recurring amounts
						else
							$options['recur']['amount'] += $recur['amount'];
					}
					else {
						unset($options['recur']);
						$allow_recur = false;
					}
				}
			}
			
			$options['description'] = $description;
			$options['return_url'] = rtrim($this->base_url, "/");
			
			// Get all non-merchant gateways
			$this->uses(array("GatewayManager"));
			$nm_gateways = $this->GatewayManager->getAllInstalledNonmerchant($this->company_id, $payment['currency']);
			
			foreach ($nm_gateways as $gateway) {
				if ($gateway->id == $payment['method']['pay_with']) {
					$this->set("gateway_name", $gateway->name);
					$options['return_url'] .= $this->client_uri . "pay/received/" . $gateway->class . "/" . $this->client->id . "/";
					break;
				}
			}
			
			$this->set("client", $this->client);
			$this->set("gateway_buttons", $this->Payments->getBuildProcess($contact_info, $total, $payment['currency'], $apply_amounts, $options, $payment['method']['pay_with']));
		}
		
		$this->set("invoices", $invoices);
		$this->set("apply_amounts", $apply_amounts);
		$this->set("total", $total);
		$this->set("account_types", $this->Accounts->getTypes());
		$this->set("ach_types", $this->Accounts->getAchTypes());
		$this->set("cc_types", $this->Accounts->getCcTypes());
		$this->set("currency", $payment['currency']);
	}
	
	/**
	 * Nonmerchant gateway payment received callback
	 */
	public function received() {
		$this->components(array("GatewayPayments"));
		
		$gateway_name = isset($this->get[0]) ? $this->get[0] : null;
		$this->get['client_id'] = isset($this->get[1]) ? $this->get[1] : null;
		
		$trans_data = $this->GatewayPayments->processReceived($gateway_name, $this->get, $this->post);
		
		if (($errors = $this->GatewayPayments->errors()))
			$this->setMessage("error", $errors);
		else
			$this->set("trans_data", $trans_data);
	}
	
	/**
	 * Sets the contact partial view
	 * @see ClientPay::index()
	 *
	 * @param stdClass $vars The input vars object for use in the view
	 * @param boolean $edit True if this is an edit, false otherwise
	 */
	private function setContactView(stdClass $vars, $edit=false) {
		$this->uses(array("Countries", "States"));
		
		$contacts = array();
		
		if (!$edit) {
			// Set an option for no contact
			$no_contact = array(
				(object)array(
					'id'=>"none",
					'first_name'=>Language::_("ClientPay.setcontactview.text_none", true),
					'last_name'=>""
				)
			);
			
			// Set all contacts whose info can be prepopulated (primary or billing only)
			$contacts = array_merge($this->Contacts->getAll($this->client->id, "primary"), $this->Contacts->getAll($this->client->id, "billing"));
			$contacts = array_merge($no_contact, $contacts);
		}
		
		// Set partial for contact info
		$contact_info = array(
			'js_contacts' => $this->Json->encode($contacts),
			'contacts' => $this->Form->collapseObjectArray($contacts, array("first_name", "last_name"), "id", " "),
			'countries' => $this->Form->collapseObjectArray($this->Countries->getList(), array("name", "alt_name"), "alpha2", " - "),
			'states' => $this->Form->collapseObjectArray($this->States->getList($vars->country), "name", "code"),
			'vars' => $vars,
			'edit' => $edit,
			'first_heading' => false
		);
		
		// Load language for partial
		Language::loadLang("client_contacts");
		$this->set("contact_info", $this->partial("client_contacts_contact_info", $contact_info));
	}
	
	/**
	 * Sets the ACH partial view
	 * @see ClientPay::index()
	 *
	 * @param stdClass $vars The input vars object for use in the view
	 * @param boolean $edit True if this is an edit, false otherwise
	 * @param boolean $save_account True to offer an option to save these payment details, false otherwise
	 */
	private function setAchView(stdClass $vars, $edit=false, $save_account=false) {
		// Set partial for ACH info
		$ach_info = array(
			'types' => $this->Accounts->getAchTypes(),
			'vars' => $vars,
			'edit' => $edit,
			'client' => $this->client,
			'save_account' => $save_account
		);
		
		// Load language for partial
		Language::loadLang("client_accounts");
		$this->set("ach_info", $this->partial("client_accounts_ach_info", $ach_info));
	}
	
	/**
	 * Sets the CC partial view
	 * @see ClientPay::index()
	 *
	 * @param stdClass $vars The input vars object for use in the view
	 * @param boolean $edit True if this is an edit, false otherwise
	 * @param boolean $save_account True to offer an option to save these payment details, false otherwise
	 */
	private function setCcView(stdClass $vars, $edit=false, $save_account=false) {
		// Set available credit card expiration dates
		$expiration = array(
			// Get months with full name (e.g. "January")
			'months' => $this->Date->getMonths(1, 12, "m", "F"),
			// Sets years from the current year to 10 years in the future
			'years' => $this->Date->getYears(date("Y"), date("Y") + 10, "Y", "Y")
		);
		
		// Set partial for CC info
		$cc_info = array(
			'expiration' => $expiration,
			'vars' => $vars,
			'edit' => $edit,
			'client' => $this->client,
			'save_account' => $save_account
		);
		
		// Load language for partial
		Language::loadLang("client_accounts");
		$this->set("cc_info", $this->partial("client_accounts_cc_info", $cc_info));
	}
	
	/**
	 * Evaluates the given invoice, performs necessary looks ups to determine if
	 * the invoice is for a recurring invoice or service. Returns the term and
	 * period for the recurring invoice or service.
	 *
	 * @param stdClass $invoice A stdClass object representing the invoice
	 * @return mixed Boolean false if the invoice is not for a recurring service or invoice, otherwise an array of recurring info including:
	 * 	- amount The amount to recur
	 * 	- term The term to recur
	 * 	- period The recurring period (day, week, month, year, onetime) used in conjunction with term in order to determine the next recurring payment
	 */
	private function getRecurInfo(stdClass $invoice) {
		$recurring_invoice = $this->Invoices->getRecurringFromInvoices($invoice->id);
		
		if ($recurring_invoice) {
			return array(
				'amount' => $recurring_invoice->total,
				'term' => $recurring_invoice->term,
				'period' => $recurring_invoice->period
			);
		}
		else {
			$service_found = false;
			$recur = array();
			
			foreach ($invoice->line_items as $line) {
				// All line items must be a service in order to recur
				if ($line->service_id == "")
					return false;
				
				$service = $this->Services->get($line->service_id);
				
				if ($service) {
					$service_found = true;
					
					if (empty($recur)) {
						$recur = array(
							'amount' => $service->package_pricing->price,
							'term' => $service->package_pricing->term,
							'period' => $service->package_pricing->period,
						);
					}
					elseif ($recur['term'] == $service->package_pricing->term && $recur['period'] == $service->package_pricing->period)
						$recur['amount'] += $service->package_pricing->price;
					// Can't recur due to multiple services at difference terms and periods
					else
						return false;
				}
			}
			
			if ($service_found)
				return $recur;
			return false;
		}
	}
}
?>