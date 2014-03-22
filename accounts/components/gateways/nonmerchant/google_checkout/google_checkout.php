<?php
/**
 * Google Checkout
 *
 * The Google Checkout API can be found at: https://developers.google.com/checkout/developer/Google_Checkout_XML_API
 *
 * @package blesta
 * @subpackage blesta.components.gateways.google_checkout
 * @copyright Copyright (c) 2010, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class GoogleCheckout extends NonmerchantGateway {
	/**
	 * @var string The version of this gateway
	 */
	private static $version = "1.0.0";
	/**
	 * @var string The authors of this gateway
	 */
	private static $authors = array(array('name'=>"Phillips Data, Inc.",'url'=>"http://www.blesta.com"));
	/**
	 * @var array An array of meta data for this gateway
	 */
	private $meta;
	/**
	 * @var string The ISO 4217 currency code
	 */
	private $currency;
	
	/**
	 * Construct a new non-merchant gateway
	 */
	public function __construct() {
		// Load components required by this gateway
		Loader::loadComponents($this, array("Input"));
		
		// Load the language required by this gateway
		Language::loadLang("google_checkout", null, dirname(__FILE__) . DS . "language" . DS);
	}
	
	/**
	 * Returns the name of this gateway
	 *
	 * @return string The common name of this gateway
	 */
	public function getName() {
		return Language::_("GoogleCheckout.name", true);
	}
	
	/**
	 * Returns the version of this gateway
	 *
	 * @return string The current version of this gateway
	 */
	public function getVersion() {
		return self::$version;
	}

	/**
	 * Returns the name and URL for the authors of this gateway
	 *
	 * @return array The name and URL of the authors of this gateway
	 */
	public function getAuthors() {
		return self::$authors;
	}
	
	/**
	 * Return all currencies supported by this gateway
	 *
	 * @return array A numerically indexed array containing all currency codes (ISO 4217 format) this gateway supports
	 */
	public function getCurrencies() {
		return array("GBP", "USD");
	}
	
	/**
	 * Sets the currency code to be used for all subsequent payments
	 *
	 * @param string $currency The ISO 4217 currency code to be used for subsequent payments
	 */
	public function setCurrency($currency) {
		$this->currency = $currency;
	}
	
	/**
	 * Create and return the view content required to modify the settings of this gateway
	 *
	 * @param array $meta An array of meta (settings) data belonging to this gateway
	 * @return string HTML content containing the fields to update the meta data for this gateway
	 */
	public function getSettings(array $meta=null) {
		$this->view = $this->makeView("settings", "default", str_replace(ROOTWEBDIR, "", dirname(__FILE__) . DS));

		// Load the helpers required for this view
		Loader::loadHelpers($this, array("Form", "Html"));

		$this->view->set("meta", $meta);
		$this->view->set("callback_url", Configure::get("Blesta.gw_callback_url") . Configure::get("Blesta.company_id") . "/google_checkout/");
		
		return $this->view->fetch();
	}
	
	/**
	 * Validates the given meta (settings) data to be updated for this gateway
	 *
	 * @param array $meta An array of meta (settings) data to be updated for this gateway
	 * @return array The meta data to be updated in the database for this gateway, or reset into the form on failure
	 */
	public function editSettings(array $meta) {
		// Verify meta data is valid
		$rules = array(
			'merchant_id' => array(
				'empty' => array(
					'rule' => "isEmpty",
					'negate' => true,
					'message' => Language::_("GoogleCheckout.!error.merchant_id.empty", true)
				)
			),
			'merchant_key' => array(
				'empty' => array(
					'rule' => "isEmpty",
					'negate' => true,
					'message' => Language::_("GoogleCheckout.!error.merchant_key.empty", true)
				)
			),
			'callback_key' => array(
				'empty' => array(
					'rule' => "isEmpty",
					'negate' => true,
					'message' => Language::_("GoogleCheckout.!error.callback_key.empty", true)
				)
			),
			'dev_mode' => array(
				'valid' => array(
					'if_set' => true,
					'rule' => array("in_array", array("true", "false")),
					'message' => Language::_("GoogleCheckout.!error.dev_mode.valid", true)
				)
			)
		);

		// Set checkbox if not set
		if (!isset($meta['dev_mode']))
			$meta['dev_mode'] = "false";
		
		$this->Input->setRules($rules);
		
		// Validate the given meta data to ensure it meets the requirements
		$this->Input->validates($meta);
		// Return the meta data, no changes required regardless of success or failure for this gateway
		return $meta;
	}
	
	/**
	 * Returns an array of all fields to encrypt when storing in the database
	 *
	 * @return array An array of the field names to encrypt when storing in the database
	 */
	public function encryptableFields() {
		return array("merchant_id", "merchant_key", "callback_key");
	}
	
	/**
	 * Sets the meta data for this particular gateway
	 *
	 * @param array $meta An array of meta data to set for this gateway
	 */
	public function setMeta(array $meta=null) {
		$this->meta = $meta;
	}
	
	/**
	 * Returns all HTML markup required to render an authorization and capture payment form
	 *
	 * @param array $contact_info An array of contact info including:
	 * 	- id The contact ID
	 * 	- client_id The ID of the client this contact belongs to
	 * 	- user_id The user ID this contact belongs to (if any)
	 * 	- contact_type The type of contact
	 * 	- contact_type_id The ID of the contact type
	 * 	- first_name The first name on the contact
	 * 	- last_name The last name on the contact
	 * 	- title The title of the contact
	 * 	- company The company name of the contact
	 * 	- address1 The address 1 line of the contact
	 * 	- address2 The address 2 line of the contact
	 * 	- city The city of the contact
	 * 	- state An array of state info including:
	 * 		- code The 2 or 3-character state code
	 * 		- name The local name of the country
	 * 	- country An array of country info including:
	 * 		- alpha2 The 2-character country code
	 * 		- alpha3 The 3-cahracter country code
	 * 		- name The english name of the country
	 * 		- alt_name The local name of the country
	 * 	- zip The zip/postal code of the contact
	 * @param float $amount The amount to charge this contact
	 * @param array $invoice_amounts An array of invoices, each containing:
	 * 	- id The ID of the invoice being processed
	 * 	- amount The amount being processed for this invoice (which is included in $amount)
	 * @param array $options An array of options including:
	 * 	- description The Description of the charge
	 * 	- recur An array of recurring info including:
	 * 		- amount The amount to recur
	 * 		- term The term to recur
	 * 		- period The recurring period (day, week, month, year, onetime) used in conjunction with term in order to determine the next recurring payment
	 * @return mixed A string of HTML markup required to render an authorization and capture payment form, or an array of HTML markup
	 */
	public function buildProcess(array $contact_info, $amount, array $invoice_amounts=null, array $options=null) {
		$this->loadApi();
		
		// Build the cart
		$account_type = ($this->meta['dev_mode'] == "false" ? "production" : "sandbox");
		$cart = new GoogleCart($this->meta['merchant_id'], $this->meta['merchant_key'], $account_type, $this->currency);
		
		// Add private data
		$data = array(
			'client_id' => $this->ifSet($contact_info['client_id']),
			'invoices' => base64_encode(serialize($invoice_amounts))
		);
		$cart->SetMerchantPrivateData(new MerchantPrivateData($data));
		
		// Add a single item to cover the entire cost
		$item = new GoogleItem($this->ifSet($options['description']), "", 1, $amount);
		$cart->AddItem($item);
		
		// Display Google Checkout button
		return $cart->CheckoutButtonCode("large", true, "en_US", false, "trans");
	}
	
	/**
	 * Refund a payment
	 *
	 * @param string $reference_id The reference ID for the previously submitted transaction
	 * @param string $transaction_id The transaction ID for the previously submitted transaction
	 * @param float $amount The amount to refund this transaction
	 * @param string $notes Notes about the refund that may be sent to the client by the gateway
	 * @return array An array of transaction data including:
	 * 	- status The status of the transaction (approved, declined, void, pending, reconciled, refunded, returned)
	 * 	- reference_id The reference ID for gateway-only use with this transaction (optional)
	 * 	- transaction_id The ID returned by the remote gateway to identify this transaction
	 * 	- message The message to be displayed in the interface in addition to the standard message for this transaction status (optional)
	 */
	public function refund($reference_id, $transaction_id, $amount, $notes=null) {
		$this->loadApi();
		
		// Build the refund request
		$account_type = ($this->meta['dev_mode'] == "false" ? "production" : "sandbox");
		$request = new GoogleRequest($this->meta['merchant_id'], $this->meta['merchant_key'], $account_type, $this->currency);
		
		// Log the refund params to be sent
		$params = array(
			'merchant_id' => $this->meta['merchant_id'],
			'merchant_key' => $this->meta['merchant_key'],
			'dev_mode' => $account_type,
			'amount' => $amount,
			'transaction_id' => $transaction_id,
			'notes' => $notes,
			'reason' => Language::_("GoogleCheckout.refund.notes_reason", true)
		);
		$this->log($request->GetRequestUrl(), serialize($this->maskData($params, array("merchant_id", "merchant_key"))), "input", true);
		
		// Refund
		$response = $request->SendRefundOrder($transaction_id, $amount, $params['reason'], $notes);
		
		if (isset($response[0]) && $response[0] == 200) {
			// Parse the XML response
			if (isset($response[1])) {
				$response = $this->parseXml($response[1]);
				
				if (isset($response['request-received'])) {
					// Log the successful response
					$this->log($request->GetRequestUrl(), serialize($response), "output", true);
					
					// Set error since the refund did not go through yet; we need to wait for a confirmation
					// from the gateway. @see GoogleCheckout::validate()
					$this->Input->setErrors(array('processing' => array('refund' => Language::_("GoogleCheckout.!error.processing.refund", true))));
					return;
				}
			}
		}
		$this->Input->setErrors($this->getCommonError("general"));
		
		// Log the unsuccessful response
		$this->log($request->GetRequestUrl(), serialize($response), "output", false);
	}
	
	/**
	 * Void a payment or authorization
	 *
	 * @param string $reference_id The reference ID for the previously submitted transaction
	 * @param string $transaction_id The transaction ID for the previously submitted transaction
	 * @param string $notes Notes about the void that may be sent to the client by the gateway
	 * @return array An array of transaction data including:
	 * 	- status The status of the transaction (approved, declined, void, pending, reconciled, refunded, returned)
	 * 	- reference_id The reference ID for gateway-only use with this transaction (optional)
	 * 	- transaction_id The ID returned by the remote gateway to identify this transaction
	 * 	- message The message to be displayed in the interface in addition to the standard message for this transaction status (optional)
	 */
	public function void($reference_id, $transaction_id, $notes=null) {
		$this->loadApi();
		
		// Build the refund request
		$account_type = ($this->meta['dev_mode'] == "false" ? "production" : "sandbox");
		$request = new GoogleRequest($this->meta['merchant_id'], $this->meta['merchant_key'], $account_type, $this->currency);
		
		// Log the refund params to be sent
		$params = array(
			'merchant_id' => $this->meta['merchant_id'],
			'merchant_key' => $this->meta['merchant_key'],
			'dev_mode' => $account_type,
			'transaction_id' => $transaction_id,
			'notes' => $notes,
			'reason' => Language::_("GoogleCheckout.void.notes_reason", true)
		);
		$this->log($request->GetRequestUrl(), serialize($this->maskData($params, array("merchant_id", "merchant_key"))), "input", true);
		
		// Cancel
		$response = $request->SendCancelOrder($transaction_id, $params['reason'], $notes);
		
		if (isset($response[0]) && $response[0] == 200) {
			// Parse the XML response
			if (isset($response[1])) {
				$response = $this->parseXml($response[1]);
				
				if (isset($response['request-received'])) {
					// Log the successful response
					$this->log($request->GetRequestUrl(), serialize($response), "output", true);
					
					// Set error since the void did not go through yet; we need to wait for a confirmation
					// from the gateway. @see GoogleCheckout::validate()
					$this->Input->setErrors(array('processing' => array('void' => Language::_("GoogleCheckout.!error.processing.void", true))));
					return;
				}
			}
		}
		elseif (isset($response[0]) && $response[0] == 400) {
			// Error, the gateway requires this payment first be refunded
			if (isset($response[1])) {
				$this->Input->setErrors(array('refund' => array('required' => Language::_("GoogleCheckout.!error.refund.required", true))));
				$this->log($request->GetRequestUrl(), serialize($response), "output", false);
				return;
			}
		}
		
		$this->Input->setErrors($this->getCommonError("general"));
		
		// Log the unsuccessful response
		$this->log($request->GetRequestUrl(), serialize($response), "output", false);
	}
	
	/**
	 * Returns data regarding a success transaction. This method is invoked when
	 * a client returns from the non-merchant gateway's web site back to Blesta.
	 *
	 * @param array $get The GET data for this request
	 * @param array $post The POST data for this request
	 * @return array An array of transaction data, may set errors using Input if the data appears invalid
	 *  - client_id The ID of the client that attempted the payment
	 *  - amount The amount of the payment
	 *  - currency The currency of the payment
	 *  - invoices An array of invoices and the amount the payment should be applied to (if any) including:
	 *  	- id The ID of the invoice to apply to
	 *  	- amount The amount to apply to the invoice
	 * 	- status The status of the transaction (approved, declined, void, pending, reconciled, refunded, returned)
	 * 	- transaction_id The ID returned by the gateway to identify this transaction
	 * 	- parent_transaction_id The ID returned by the gateway to identify this transaction's original transaction
	 */
	public function success(array $get, array $post) {
		// Get private data
		$private_data = $this->getPrivateData($post);
		
		return array(
			'client_id' => $private_data['client_id'],
			'invoices' => $private_data['invoices'],
			'amount' => $this->ifSet($post['order-total']),
			'currency' => $this->ifSet($post['order-total_currency']),
			'status' => "approved",
			'transaction_id' => $this->ifSet($post['google-order-number']),
			'parent_transaction_id' => null
		);
	}
	
	/**
	 * Validates the incoming POST/GET response from the gateway to ensure it is
	 * legitimate and can be trusted.
	 *
	 * @param array $get The GET data for this request
	 * @param array $post The POST data for this request
	 * @return array An array of transaction data, sets any errors using Input if the data fails to validate
	 *  - client_id The ID of the client that attempted the payment
	 *  - amount The amount of the payment
	 *  - currency The currency of the payment
	 *  - invoices An array of invoices and the amount the payment should be applied to (if any) including:
	 *  	- id The ID of the invoice to apply to
	 *  	- amount The amount to apply to the invoice
	 * 	- status The status of the transaction (approved, declined, void, pending, reconciled, refunded, returned)
	 * 	- reference_id The reference ID for gateway-only use with this transaction (optional)
	 * 	- transaction_id The ID returned by the gateway to identify this transaction
	 * 	- parent_transaction_id The ID returned by the gateway to identify this transaction's original transaction (in the case of refunds)
	 */
	public function validate(array $get, array $post) {
		// Log request received
		$this->log($this->ifSet($_SERVER['REQUEST_URI']), serialize($post), "output", true);
		
		// Get private data
		$private_data = $this->getPrivateData($post);
		
		// Set transaction data
		$data = array(
			'client_id' => $private_data['client_id'],
			'invoices' => $private_data['invoices'],
			'amount' => $this->ifSet($post['order-total']),
			'currency' => $this->ifSet($post['order-total_currency']),
			'transaction_id' => $this->ifSet($post['google-order-number']),
			'reference_id' => $this->ifSet($post['serial-number']),
			'parent_transaction_id' => null
		);
		
		// Set the transaction status
		if ($this->ifSet($post['_type']) == "new-order-notification") {
			$data['status'] = "pending";
		}
		elseif ($this->ifSet($post['_type']) == "refund-amount-notification") {
			// Handle a confirmed refund response by setting required refund data
			$data['status'] = "refunded";
			$data['amount'] = $this->ifSet($post['latest-refund-amount'], 0);
			$data['currency'] = $this->ifSet($post['latest-refund-amount_currency']);
			$data['parent_transaction_id'] = $data['transaction_id'];
		}
		else {
			
			// Set the transaction status
			switch (strtolower($this->ifSet($post['new-financial-order-state']))) {
				case "charged":
					$data['status'] = "approved";
					
					// Save the original order number as the parent transaction ID for refunds
					$data['parent_transaction_id'] = $data['transaction_id'];
					break;
				case "payment_declined":
					$data['status'] = "declined";
					break;
				case "cancelled":
					$data['status'] = "void";
					break;
				case "cancelled_by_google":
					$data['status'] = "void";
					break;
				default:
					$this->Input->setErrors(array('unmodifiable' => array('response' => Language::_("GoogleCheckout.!error.unmodifiable.response", true))));
					return;
			}
			
			$this->loadApi();
			
			// Fetch the new-order-notification which contains our private information
			$account_type = ($this->meta['dev_mode'] == "false" ? "production" : "sandbox");
			
			$history_request = new GoogleNotificationHistoryRequest($this->meta['merchant_id'], $this->meta['merchant_key'], $account_type);
			$history_response = $history_request->SendNotificationHistoryRequest(null, null, array($data['transaction_id']), array("new-order"));
			
			// Log request received
			$this->log($this->ifSet($_SERVER['REQUEST_URI']), serialize($history_response), "output", true);
			
			// Get private data
			if (isset($history_response[1])) {
				$response = $this->parseXml($history_response[1]);
				
				if (isset($response['notification-history-response']['notifications']['new-order-notification']['shopping-cart']['merchant-private-data'])) {
					$private_data = $this->getPrivateData($response['notification-history-response']['notifications']['new-order-notification']['shopping-cart']['merchant-private-data'], true);
					
					// Update the transaction with the private data
					$data['client_id'] = $private_data['client_id'];
					$data['invoices'] = $private_data['invoices'];
					
					// Set the order total and currency
					$data['amount'] = $this->ifSet($response['notification-history-response']['notifications']['new-order-notification']['order-total']['VALUE']);
					$data['currency'] = $this->ifSet($response['notification-history-response']['notifications']['new-order-notification']['order-total']['currency']);
				}
			}
		}
		
		return $data;
	}
	
	/**
	 * Retrieves a list of private order data that is returned from the gateway
	 *
	 * @param array $response The response from the gateway
	 * @return array A list of private order data including:
	 * 	- client_id The ID of the client
	 * 	- invoices An array of invoices which includes
	 * 		- id The invoice ID
	 * 		- amount The invoice amount
	 */
	private function getPrivateData($response, $notification_history_response = false) {
		$client_id = null;
		$invoices = array();
		
		// Get private data
		if (!$notification_history_response) {
			$private_data = $this->ifSet($response['shopping-cart_merchant-private-data']);
			
			if (!empty($private_data)) {
				// Set the private data
				preg_match("/<client_id>([0-9]+)<\/client_id>/", $private_data, $client);
				preg_match("/<invoices>(.+)<\/invoices>/", $private_data, $invoices);
				$client_id = $this->ifSet($client[1]);
				$invoice = $this->ifSet($invoices[1]);
				if (!empty($invoice))
					$invoices = unserialize(base64_decode($invoices[1]));
			}
		}
		else {
			// Get private data from a notification history parsed response
			if (isset($response['client_id']['VALUE']))
				$client_id = $response['client_id']['VALUE'];
			if (isset($response['invoices']['VALUE']))
				$invoices = unserialize(base64_decode($response['invoices']['VALUE']));
		}
		
		return array(
			'client_id' => $client_id,
			'invoices' => $invoices
		);
	}
	
	/**
	 * Parses the XML response, returns an array
	 *
	 * @return array An array representing the parsed XML response
	 */
	private function parseXml($response) {
		Loader::load(dirname(__FILE__) . DS . "api" . DS . "xml-processing" . DS . "gc_xmlparser.php");
		
		// Parse
		try {
			$xml_parser = new gc_XmlParser($response);
			return $xml_parser->GetData();
		}
		catch (Exception $e) {
			// Error parsing XML
		}
		
		return array();
	}
	
	/**
	 * Loads the API if not already loaded
	 */
	private function loadApi() {
		Loader::load(dirname(__FILE__) . DS . "api" . DS . "googlecart.php");
		Loader::load(dirname(__FILE__) . DS . "api" . DS . "googleitem.php");
		Loader::load(dirname(__FILE__) . DS . "api" . DS . "googlerequest.php");
		Loader::load(dirname(__FILE__) . DS . "api" . DS . "googlenotificationhistory.php");
	}
}
?>