<?php
/**
 * Authorize.net CIM (Customer Information Manager) API
 *
 * An API for performing remote requests to Authorize.net using the CIM API.
 * Documentation on the CIM (Customer Information Manager) API can be found at: http://www.authorize.net/support/CIM_XML_guide.pdf
 *
 * This API requires the SimpleXMLElement extension
 *
 * @package blesta
 * @subpackage blesta.components.gateways.authorize_net.apis
 * @copyright Copyright (c) 2010, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class AuthorizeNetCim {
	/**
	 * @var string The CIM test URL
	 */	
	private static $cim_test_url = "https://apitest.authorize.net/xml/v1/request.api";
	/**
	 * @var string The CIM live URL
	 */	
	private static $cim_live_url = "https://api.authorize.net/xml/v1/request.api";
	/**
	 * @var string The authorize.net login ID
	 */
	private $login_id;
	/**
	 * @var string The Authorize.net transaction key
	 */
	private $trans_key;
	/**
	 * @var boolean Whether or not to submit payments using the test URL
	 */
	private $dev_mode = false;
	/**
	 * @var array A set of responses from the gateway for the request
	 */
	private $last_response = array();
	/**
	 * @var array A set of raw responses from the gateway for the request
	 */
	private $raw_response = array();
	/**
	 * @var array A multi-dimensional array of parameters from the last request
	 */
	private $last_params = array();
	/**
	 * @var array A set of URLs from the last request
	 */
	private $last_url = array();
	/**
	 * @var string The validation mode to use for CC accounts (options include: null, "none", "testMode" and "liveMode")
	 */
	private $validation_mode = null;
	/**
	 * @var string The currency to use
	 */
	private $currency;
	
	/**
	 * Initializes the request parameter
	 *
	 * @param string $login_id The Authorize.net login ID
	 * @param string $trans_key The Autnorize.net transaction key
	 * @param boolean $dev_mode If true, will submit to the AIM test URL rather than the live URL
	 * @param string $validation_mode The validation mode to use for CC accounts (null, "none", "testMode", "liveMode")
	 */
	public function __construct($login_id, $trans_key, $dev_mode=false, $validation_mode=null) {
		$this->login_id = $login_id;
		$this->trans_key = $trans_key;
		$this->dev_mode = $dev_mode;
		$this->validation_mode = $validation_mode;
	}
	
	/**
	 * Sets the currency code to be used for all subsequent requests
	 *
	 * @param string $currency The ISO 4217 currency code to be used for subsequent requests
	 */
	public function setCurrency($currency) {
		$this->currency = $currency;
	}
	
	/**
	 * Creates the customer profile if required, then creates the customer payment profile.
	 *
	 * @param string $type The type of payment account to be stored (ach, cc)
	 * @param array $account_info Information about the payment account including:
	 * 	-first_name The first name on the account
	 * 	-last_name The last name on the account
	 * 	-account_number The bank account number (if ach)
	 * 	-routing_number The bank account routing number (if ach)
	 * 	-type The bank account type (checking, savings, business_checking) (if ach)
	 * 	-card_number The credit card number (if cc)
	 * 	-card_exp The card expiration date in yyyymm format (if cc)
	 * 	-card_security_code The card security code (if cc)
	 * 	-address1 The address 1 line of the account holder
	 * 	-address2 The address 2 line of the account holder
	 * 	-city The city of the account holder
	 * 	-state An array of state info including:
	 * 		-code The 2 or 3-character state code
	 * 		-name The local name of the country
	 * 	-country An array of country info including:
	 * 		-alpha2 The 2-character country code
	 * 		-alpha3 The 3-character country code
	 * 		-name The english name of the country
	 * 		-alt_name The local name of the country
	 * 	-zip The zip/postal code of the account holder
	 * @param array $customer_info An array of customer info, required if $profile_id is null, includes:
	 * 	-customer_id The Merchant assigned ID for the customer, required only if nothing set for description and email
	 * 	-email The email address associated with the customer profile, required only if customer_id not set
	 * 	-description Description of the customer, required only if customer_id not set
	 * @param string $profile_id The customer profile ID, required if $customer_info is null
	 * @return array An array on success (void on error) containing:
	 * 	-profile_id
	 * 	-payment_profile_id
	 */
	public function store($type, array $account_info, array $customer_info=null, $profile_id=null) {
		$payment_profile_id =  null;
		
		// Create the customer profile if not given
		if ($profile_id == null)
			$profile_id = $this->createProfile($customer_info);
		
		if ($profile_id) {
			// Create the payment profile
			$payment_profile_id = $this->createPaymentProfile($profile_id, $type, $account_info);
		}
		
		if ($payment_profile_id) {
			return array(
				'profile_id'=>$profile_id,
				'payment_profile_id'=>$payment_profile_id
			);
		}
	}
	
	/**
	 * Updates the customer profile and payment profile with the given information
	 *
	 * @param string $type The type of payment account to be stored (ach, cc)
	 * @param array $account_info Information about the payment account including:
	 * 	-first_name The first name on the account
	 * 	-last_name The last name on the account
	 * 	-account_number The bank account number (if ach)
	 * 	-routing_number The bank account routing number (if ach)
	 * 	-type The bank account type (checking, savings, business_checking) (if ach)
	 * 	-card_number The credit card number (if cc)
	 * 	-card_exp The card expiration date in yyyymm format (if cc)
	 * 	-card_security_code The card security code (if cc)
	 * 	-address1 The address 1 line of the account holder
	 * 	-address2 The address 2 line of the account holder
	 * 	-city The city of the account holder
	 * 	-state An array of state info including:
	 * 		-code The 2 or 3-character state code
	 * 		-name The local name of the country
	 * 	-country An array of country info including:
	 * 		-alpha2 The 2-character country code
	 * 		-alpha3 The 3-character country code
	 * 		-name The english name of the country
	 * 		-alt_name The local name of the country
	 * 	-zip The zip/postal code of the account holder
	 * 	-account_changed True if the account details (bank account or card number, etc.) have been updated, false otherwise
	 * @param array $customer_info An array of customer info includes:
	 * 	-customer_id The Merchant assigned ID for the customer, required only if nothing set for description and email
	 * 	-email The email address associated with the customer profile, required only if customer_id not set
	 * 	-description Description of the customer, required only if customer_id not set
	 * @param int $profile_id The profile ID for this account
	 * @param int $payment_profile_id The payment profile ID for this account
	 * @return array An array on success (void on error) containing:
	 * 	-profile_id
	 * 	-payment_profile_id
	 */
	public function update($type, array $account_info, array $customer_info, $profile_id, $payment_profile_id) {

		// Update the customer profile
		if ($this->updateProfile($profile_id, $customer_info) &&
			// Update payment profile
			$this->updatePaymentProfile($profile_id, $payment_profile_id, $type, $account_info)) {
			return array(
				'profile_id'=>$profile_id,
				'payment_profile_id'=>$payment_profile_id
			);
		}
	}
	
	/**
	 * Removes the payment profile
	 * 
	 * @param int $profile_id The profile ID for this account
	 * @param int $payment_profile_id The payment profile ID for this account
	 * @return array An array on success (void on error) containing:
	 * 	-profile_id
	 * 	-payment_profile_id
	 */
	public function delete($profile_id, $payment_profile_id) {
		
		$data = array(
			'customerProfileId'=>$profile_id,
			'customerPaymentProfileId'=>$payment_profile_id
		);
		$response_obj = $this->submit($data, "deleteCustomerPaymentProfileRequest");
		
		if (isset($response_obj->messages->resultCode) &&
			strtolower($response_obj->messages->resultCode) == "ok") {
			return array(
				'profile_id'=>$profile_id,
				'payment_profile_id'=>$payment_profile_id
			);
		}
	}
	
	/**
	 * Authorizes payment in the given amount from the given payment account
	 *
	 * @param int $profile_id The customer profile ID
	 * @param int $payment_profile_id The payment profile ID to authorize payment on
	 * @param float $amount The amount to authorize
	 * @param array $invoice_amounts An array of invoices, each containing:
	 * 	-id The ID of the invoice being processed
	 * 	-amount The amount being processed for this invoice (which is included in $amount)
	 * @return array The response from the gateway
	 */
	public function auth($profile_id, $payment_profile_id, $amount, array $invoice_amounts=null) {
		return $this->createProfileTransaction("profileTransAuthOnly", $profile_id, $payment_profile_id, $amount, null, $invoice_amounts);
	}

	/**
	 * Captures payment for the given amount from the given payment account and previous authorization
	 *
	 * @param int $profile_id The customer profile ID
	 * @param int $payment_profile_id The payment profile ID to capture payment on
	 * @param int $transaction_id The previous authorization transaction ID
	 * @param float $amount The amount to capture
	 * @param array $invoice_amounts An array of invoices, each containing:
	 * 	-id The ID of the invoice being processed
	 * 	-amount The amount being processed for this invoice (which is included in $amount)
	 * @return array The response from the gateway
	 */	
	public function capture($profile_id, $payment_profile_id, $transaction_id, $amount, array $invoice_amounts=null) {
		return $this->createProfileTransaction("profileTransPriorAuthCapture", $profile_id, $payment_profile_id, $amount, $transaction_id, $invoice_amounts);
	}
	
	/**
	 * Authorizes and captures payment in the given amount from the given payment account
	 *
	 * @param int $profile_id The customer profile ID
	 * @param int $payment_profile_id The payment profile ID to authorize and capture payment on
	 * @param float $amount The amount to authorize and capture
	 * @param array $invoice_amounts An array of invoices, each containing:
	 * 	-id The ID of the invoice being processed
	 * 	-amount The amount being processed for this invoice (which is included in $amount)
	 * @return array The response from the gateway
	 */
	public function authCapture($profile_id, $payment_profile_id, $amount, array $invoice_amounts=null) {
		return $this->createProfileTransaction("profileTransAuthCapture", $profile_id, $payment_profile_id, $amount, null, $invoice_amounts);
	}
	
	/**
	 * Voids payment for the given payment account
	 *
	 * @param int $profile_id The customer profile ID
	 * @param int $payment_profile_id The payment profile ID to void payment on
	 * @param int $transaction_id The transaction ID to void
	 * @return array The response from the gateway
	 */
	public function void($profile_id, $payment_profile_id, $transaction_id) {
		return $this->createProfileTransaction("profileTransVoid", $profile_id, $payment_profile_id, null, $transaction_id);
	}
	
	/**
	 * Refunds payment in the given amount for the given payment account
	 *
	 * @param int $profile_id The customer profile ID
	 * @param int $payment_profile_id The payment profile ID to authorize payment on
	 * @param int $transaction_id The transaction ID to refund
	 * @param float $amount The amount to refund
	 * @return array The response from the gateway
	 */
	public function refund($profile_id, $payment_profile_id, $transaction_id, $amount) {
		return $this->createProfileTransaction("profileTransRefund", $profile_id, $payment_profile_id, $amount, $transaction_id);
	}	
	
	/**
	 * Creates a customer profile with the gateway
	 *
	 * @param array $customer_info Customer info including:
	 * 	-customer_id The Merchant assigned ID for the customer, required only if nothing set for description and email
	 * 	-email The email address associated with the customer profile, required only if customer_id not set
	 * 	-description Description of the customer, required only if customer_id not set
	 * @return string The customer profile ID returned by the gateway on success, null otherwise
	 */
	private function createProfile(array $customer_info) {
		$data = array(
			'profile'=>array()
		);
		
		// Only set 1 of either 'merchantCustomerId', 'email', or 'description'
		if ($this->ifSet($customer_info['customer_id']) != "")
			$data['profile']['merchantCustomerId'] = $customer_info['customer_id'];
		elseif ($this->ifSet($customer_info['email']) != "")
			$data['profile']['email'] = $customer_info['email'];
		elseif ($this->ifSet($customer_info['description']) != "")
			$data['profile']['description'] = $customer_info['description'];
		
		$response_obj = $this->submit($data, "createCustomerProfileRequest");
		
		if (isset($response_obj->messages->resultCode) &&
			strtolower($response_obj->messages->resultCode) == "ok") {
			return (string)$response_obj->customerProfileId;
		}
		return null;
	}
	
	/**
	 * Creates a customer payment profile with the gateway
	 *
	 * @param string $profile_id The customer profile ID
	 * @param string $type The type of payment account (ach or cc)
	 * @param array $account_info An array of account info including:
	 * 	-first_name The first name on the account
	 * 	-last_name The last name on the account
	 * 	-account_number The bank account number (if ach)
	 * 	-routing_number The bank account routing number (if ach)
	 * 	-type The bank account type (checking, savings, business_checking) (if ach)
	 * 	-card_number The credit card number (if cc)
	 * 	-card_exp The card expiration date in yyyymm format (if cc)
	 * 	-card_security_code The card security code (if cc)
	 * 	-address1 The address 1 line of the account holder
	 * 	-address2 The address 2 line of the account holder
	 * 	-city The city of the account holder
	 * 	-state An array of state info including:
	 * 		-code The 2 or 3-character state code
	 * 		-name The local name of the country
	 * 	-country An array of country info including:
	 * 		-alpha2 The 2-character country code
	 * 		-alpha3 The 3-character country code
	 * 		-name The english name of the country
	 * 		-alt_name The local name of the country
	 * 	-zip The zip/postal code of the account holder
	 * @return string The customer payment profile ID returned by the gateway on success, null otherwise
	 */
	private function createPaymentProfile($profile_id, $type, array $account_info) {
		
		$payment = array();
		switch ($type) {
			case "ach":
				
				// Determine ACH type
				$account_type = "";
				switch ($this->ifSet($account_info['type'])) {
					default:
					case "checking":
						$account_type = "checking";
						break;
					case "savings":
						$account_type = "savings";
						break;
					case "business_checking":
						$account_type = "businessChecking";
						break;
				}
				
				$payment = array(
					'bankAccount'=>array(
						'accountType'=>$account_type,
						'routingNumber'=>$this->ifSet($account_info['routing_number']),
						'accountNumber'=>$this->ifSet($account_info['account_number']),
						'nameOnAccount'=>$this->ifSet($account_info['first_name']) . " " . $this->ifSet($account_info['last_name']),
						'echeckType'=>"WEB"
					)
				);
				break;
			case "cc":
				$payment = array(
					'creditCard'=>array(
						'cardNumber'=>$this->ifSet($account_info['card_number']),
						'expirationDate'=>substr($this->ifSet($account_info['card_exp']), 0, 4) . "-" . substr($this->ifSet($account_info['card_exp']), -2) // From YYYYMM to YYYY-MM
					)
				);
				
				// Only set card security code if given
				if ($this->ifSet($account_info['card_security_code']))
					$payment['creditCard']['cardCode'] = $account_info['card_security_code'];
				break;
		}
		
		$data = array(
			'customerProfileId'=>$profile_id,
			'paymentProfile'=>array(
				'billTo'=>array(
					'firstName'=>$this->ifSet($account_info['first_name']),
					'lastName'=>$this->ifSet($account_info['last_name']),
					'address'=>$this->ifSet($account_info['address1']),
					'city'=>$this->ifSet($account_info['city']),
					'state'=>$this->ifSet($account_info['state']['code']),
					'zip'=>$this->ifSet($account_info['zip']),
					'country'=>$this->ifSet($account_info['country']['alpha2'])
				),
				'payment'=>$payment
			)
		);
		
		if ($this->validation_mode)
			$data['validationMode'] = $this->validation_mode;
		
		$response_obj = $this->submit($data, "createCustomerPaymentProfileRequest");
		
		if (isset($response_obj->messages->resultCode) &&
			strtolower($response_obj->messages->resultCode) == "ok") {
			return (string)$response_obj->customerPaymentProfileId;
		}
		return null;
	}
	
	/**
	 * Processes the requested profile transaction using the supplied details
	 *
	 * @param string $trans_type The type of transaction to perform (profileTransAuthOnly, profileTransAuthCapture, profileTransPriorAuthCapture, profileTransRefund, profileTransVoid)
	 * @param int $profile_id The profile ID for this customer profile
	 * @param int $payment_profile_id The payment profile ID for this customer profile account
	 * @param float $amount The amount to of the transaction (not required for profileTransVoid, optional for some others, consult CIM docs for more info)
	 * @param int $trans_id The transaction ID this transaction relates to (needed for prior auth captures, refunds and voids)
	 * @param array $invoice_amounts An array of invoices, each containing:
	 * 	-id The ID of the invoice being processed
	 * 	-amount The amount being processed for this invoice (which is included in $amount)
	 * @return mixed SimpleXMLElement object on success, null otherwise
	 */
	private function createProfileTransaction($trans_type, $profile_id, $payment_profile_id, $amount=null, $trans_id=null, array $invoice_amounts=null) {

		$data = array(
			'transaction'=>array(
				$trans_type => array()
			)
		);
		
		// If the trans type is anything other than a void, we must supply the amount
		if ($trans_type != "profileTransVoid")
			$data['transaction'][$trans_type]['amount'] = $amount;
			
		$data['transaction'][$trans_type]['customerProfileId'] = $profile_id;
		$data['transaction'][$trans_type]['customerPaymentProfileId'] = $payment_profile_id;
		
		// Set the original transaction ID to capture (only required for PriorAuthCapture, refund, and void transactions)
		if ($trans_id !== null)
			$data['transaction'][$trans_type]['transId'] = $trans_id;
			
		// If invoices given, pass along the 1st (since the gateway limits us
		// to 20 chars, we pretty much can only send one ID)
		if ($invoice_amounts)
			$data['transaction'][$trans_type]['order']['invoiceNumber'] = $this->ifSet($invoice_amounts[0]['invoice_id']);
		
		$response_obj = $this->submit($data, "createCustomerProfileTransactionRequest");
		
		if (isset($response_obj->directResponse))
			return $this->parseTransResponse($response_obj->directResponse);
		return null;
	}
	
	/**
	 * Updates a customer profile with the gateway
	 *
	 * @param int $profile_id The customer profile ID
	 * @param array $customer_info Customer info including:
	 * 	-customer_id The Merchant assigned ID for the customer, required only if nothing set for description and email
	 * 	-email The email address associated with the customer profile, required only if customer_id not set
	 * 	-description Description of the customer, required only if customer_id not set
	 * @return boolean True on success, false otherwise
	 */
	private function updateProfile($profile_id, array $customer_info) {
		$data = array(
			'profile'=>array()
		);
		
		// Only set 1 of either 'merchantCustomerId', 'email', or 'description'
		if ($this->ifSet($customer_info['customer_id']) != "")
			$data['profile']['merchantCustomerId'] = $customer_info['customer_id'];
		elseif ($this->ifSet($customer_info['email']) != "")
			$data['profile']['email'] = $customer_info['email'];
		elseif ($this->ifSet($customer_info['description']) != "")
			$data['profile']['description'] = $customer_info['description'];
			
		$data['profile']['customerProfileId'] = $profile_id;
		
		$response_obj = $this->submit($data, "updateCustomerProfileRequest");
		
		if (isset($response_obj->messages->resultCode) &&
			strtolower($response_obj->messages->resultCode) == "ok") {
			return true;
		}
		return false;
	}
	
	/**
	 * Updates the payment profile ID with the given info. If $account_info['account_changed']
	 * identifies the account details as not being changed the existing payment profile will
	 * be fetched so that the remaining account information can be updated while maintaining
	 * existing data
	 * 
	 * @param int $profile_id The customer profile ID
	 * @param int $payment_profile_id The payment profile ID
	 * @param string $type The type of payment account (ach or cc)
	 * @param array $account_info An array of account info including:
	 * 	-first_name The first name on the account
	 * 	-last_name The last name on the account
	 * 	-account_number The bank account number (if ach)
	 * 	-routing_number The bank account routing number (if ach)
	 * 	-type The bank account type (checking, savings, business_checking) (if ach)
	 * 	-card_number The credit card number (if cc)
	 * 	-card_exp The card expiration date in yyyymm format (if cc)
	 * 	-card_security_code The card security code (if cc)
	 * 	-address1 The address 1 line of the account holder
	 * 	-address2 The address 2 line of the account holder
	 * 	-city The city of the account holder
	 * 	-state An array of state info including:
	 * 		-code The 2 or 3-character state code
	 * 		-name The local name of the country
	 * 	-country An array of country info including:
	 * 		-alpha2 The 2-character country code
	 * 		-alpha3 The 3-character country code
	 * 		-name The english name of the country
	 * 		-alt_name The local name of the country
	 * 	-zip The zip/postal code of the account holder
	 * 	-account_changed True if the account details (bank account or card number, etc.) have been updated, false otherwise
	 * @return boolean True on success, false otherwise
	 */
	private function updatePaymentProfile($profile_id, $payment_profile_id, $type, array $account_info) {
		
		// Fetch the existing payment profile as some details may not have changed
		$payment_profile = (!$account_info['account_changed']) ? $this->getPaymentProfile($profile_id, $payment_profile_id) : array();
		
		$payment = array();
		// Only set account details if they've changed, otherwise merge with existing details (see AuthorizeNetCim::getPaymentProfile())
		if ($account_info['account_changed']) {
			switch ($type) {
				case "ach":
					
					// Determine ACH type
					$account_type = "";
					switch ($this->ifSet($account_info['type'])) {
						default:
						case "checking":
							$account_type = "checking";
							break;
						case "savings":
							$account_type = "savings";
							break;
						case "business_checking":
							$account_type = "businessChecking";
							break;
					}
					
					$payment = array(
						'bankAccount'=>array(
							'accountType'=>$account_type,
							'routingNumber'=>$this->ifSet($account_info['routing_number']),
							'accountNumber'=>$this->ifSet($account_info['account_number']),
							'nameOnAccount'=>$this->ifSet($account_info['first_name']) . " " . $this->ifSet($account_info['last_name']),
							'echeckType'=>"WEB"
						)
					);
					break;
				case "cc":
					$payment = array(
						'creditCard'=>array(
							'cardNumber'=>$this->ifSet($account_info['card_number']),
							'expirationDate'=>substr($this->ifSet($account_info['card_exp']), 0, 4) . "-" . substr($this->ifSet($account_info['card_exp']), -2) // From YYYYMM to YYYY-MM
						)
					);
					
					// Only set card security code if given
					if ($this->ifSet($account_info['card_security_code']))
						$payment['creditCard']['cardCode'] = $account_info['card_security_code'];
					break;
			}
		}
		
		$data = array(
			'customerProfileId'=>$profile_id,
			'paymentProfile'=>array(
				'billTo'=>array(
					'firstName'=>$this->ifSet($account_info['first_name']),
					'lastName'=>$this->ifSet($account_info['last_name']),
					'address'=>$this->ifSet($account_info['address1']),
					'city'=>$this->ifSet($account_info['city']),
					'state'=>$this->ifSet($account_info['state']['code']),
					'zip'=>$this->ifSet($account_info['zip']),
					'country'=>$this->ifSet($account_info['country']['alpha2'])
				),
				'payment'=>array_merge($payment_profile, $payment),
				'customerPaymentProfileId'=>$payment_profile_id
			)
		);
		
		if ($this->validation_mode)
			$data['validationMode'] = $this->validation_mode;
		
		$response_obj = $this->submit($data, "updateCustomerPaymentProfileRequest");
		
		if (isset($response_obj->messages->resultCode) &&
			strtolower($response_obj->messages->resultCode) == "ok") {
			return true;
		}
		return false;
	}
	
	/**
	 * Fetches the payment account info for the given payment account. This is used in
	 * conjunction with AuthorizeNetCim::updatePaymentProfile() to set fields that
	 * are not being updated but are still required in order to maintain their
	 * existing values.
	 *
	 * @param int $profile_id The customer profile ID
	 * @param int $payment_profile_id The payment profile ID
	 * @return an array containing the payment account fields for the payment account to be replaced in paymentProfile->payment when making the API request
	 * @see AuthorizeNetCim::updatePaymentProfile()
	 */
	private function getPaymentProfile($profile_id, $payment_profile_id) {
		$result = array();
		
		$data = array(
			'customerProfileId'=>$profile_id,
			'customerPaymentProfileId'=>$payment_profile_id
		);
		$response_obj = $this->submit($data, "getCustomerPaymentProfileRequest");
		
		if (isset($response_obj->messages->resultCode) &&
			strtolower($response_obj->messages->resultCode) == "ok") {
			
			if (isset($response_obj->paymentProfile->payment->creditCard)) {
				$result['creditCard'] = array(
					'cardNumber'=>(string)$this->ifSet($response_obj->paymentProfile->payment->creditCard->cardNumber),
					'expirationDate'=>(string)$this->ifSet($response_obj->paymentProfile->payment->creditCard->expirationDate)
				);
			}
			elseif (isset($response_obj->paymentProfile->payment->bankAccount)) {
				$result['bankAccount'] = array(
					'accountType'=>(string)$this->ifSet($response_obj->paymentProfile->payment->bankAccount->accountType),
					'routingNumber'=>(string)$this->ifSet($response_obj->paymentProfile->payment->bankAccount->routingNumber),
					'accountNumber'=>(string)$this->ifSet($response_obj->paymentProfile->payment->bankAccount->accountNumber),
					'nameOnAccount'=>(string)$this->ifSet($response_obj->paymentProfile->payment->bankAccount->nameOnAccount),
					'echeckType'=>(string)$this->ifSet($response_obj->paymentProfile->payment->bankAccount->echeckType)
				);				
			}
		}
		return $result;
	}

	/**
	 * Returns the URL used in the last requests
	 *
	 * @return string The URL of the last requests
	 */
	public function getUrl() {
		return $this->last_url;
	}

	/**
	 * Returns the parameters sent to the gateway during the last request
	 *
	 * @return array An array of parameters from the last request
	 */
	public function getParams() {
		return $this->last_params;
	}

	/**
	 * Returns the parsed response of the last request
	 *
	 * @return array An array of response fields.
	 */
	public function getResponse() {
		return $this->last_response;
	}
	
	/**
	 * Returns the raw response of the last request
	 *
	 * @return string The last response (in raw format) from the gateway
	 */
	public function getRawResponse() {
		return $this->raw_response;
	}

	/**
	 * Makes a remote request to the gateway using the provided parameters.
	 * Will automatically append the authorization parameters and contact the
	 * appropriate URL based on the test mode setting.
	 *
	 * When the request is submitted, sets both the parameters sent to $this->last_params
	 * and response to $this->last_response
	 *
	 * @param array $data The data to submit to the gateway
	 * @param string $action The action to perform on the gateway
	 * @param string $name_space The name space to submit the XML data using
	 * @return array The parsed response from the gateway
	 */
	private function submit(array $data, $action, $name_space="AnetApi/xml/v1/schema/AnetApiSchema.xsd") {
		
		// Load the XML helper if not already loaded
		if (!isset($this->Xml))
			Loader::loadHelpers($this, array("Xml"));
		
		// Prepend authentication details
		$auth = array(
			'name' => $this->login_id,
			'transactionKey' => $this->trans_key
		);
		$data = array_merge(array('merchantAuthentication'=>$auth), $data);
		unset($auth);
		
		// Wrap action around data, we'll add the namespace below
		$data = array(
			$action => $data
		);
		
		// We'll hold the parameters as an array since it's easier to filter when we log it
		$this->last_params[] = $data;
		
		// Convert data to XML string
		$xml = $this->Xml->makeXml($data);
		
		// Add the namespace in there
		$xml = str_replace("<" . $action . ">", "<" . $action . " xmlns=\"" . $name_space . "\">", $xml);
		
		// Load the HTTP component, if not already loaded
		if (!isset($this->Http)) {
			Loader::loadComponents($this, array("Net"));
			$this->Http = $this->Net->create("Http");
		}
		
		// Set the URL to submit to
		$this->last_url[] = $url = ($this->dev_mode ? self::$cim_test_url : self::$cim_live_url);
		
		// Send text/xml
		$this->Http->setHeader("Content-Type: text/xml");
		
		// Submit the requests
		$this->raw_response[] = $raw_response = $this->Http->post($url, $xml);
		$this->last_response[] = $response = $this->parseResponse($raw_response);
		return $response;
	}
	
	/**
	 * Parse the response from the gateway into an XML object
	 *
	 * @param string $response An XML string response
	 * @return SimpleXMLElement A SimpleXMLElement representation of the XML response
	 */
	private function parseResponse($response) {
		return simplexml_load_string($response, "SimpleXMLElement", LIBXML_NOWARNING);
	}
	
	/**
	 * Parse the transactions response string from the gateway into an array
	 *
	 * @param string $response The response string to parse from the gateway
	 * @return array The response string parsed into an array
	 */
	private function parseTransResponse($response) {
		
		if ($response == "")
			return $response;
		
		$authnet_array = explode(",", $response);
		$authnet_results = array();
		
		try {
			return array(
				'x_response_code'=> $authnet_array[0],
				'x_response_subcode'=> $authnet_array[1],
				'x_response_reason_code'=> $authnet_array[2],
				'x_response_reason_text'=> $authnet_array[3],
				'x_auth_code'=> $authnet_array[4],
				'x_avs_code'=> $authnet_array[5],
				'x_trans_id'=> $authnet_array[6],
				'x_invoice_num'=> $authnet_array[7],
				'x_description'=> $authnet_array[8],
				'x_amount'=> $authnet_array[9],
				'x_method'=> $authnet_array[10],
				'x_type'=> $authnet_array[11],
				'x_cust_id'=> $authnet_array[12],
				'x_first_name'=> $authnet_array[13],
				'x_last_name'=> $authnet_array[14],
				'x_company'=> $authnet_array[15],
				'x_address'=> $authnet_array[16],
				'x_city'=> $authnet_array[17],
				'x_state'=> $authnet_array[18],
				'x_zip'=> $authnet_array[19],
				'x_country'=> $authnet_array[20],
				'x_phone'=> $authnet_array[21],
				'x_fax'=> $authnet_array[22],
				'x_email'=> $authnet_array[23],
				'x_ship_to_first_name'=> $authnet_array[24],
				'x_ship_to_last_name'=> $authnet_array[25],
				'x_ship_to_company'=> $authnet_array[26],
				'x_ship_to_address'=> $authnet_array[27],
				'x_ship_to_city'=> $authnet_array[28],
				'x_ship_to_state'=> $authnet_array[29],
				'x_ship_to_zip'=> $authnet_array[30],
				'x_ship_to_country'=> $authnet_array[31],
				'x_tax'=> $authnet_array[32],
				'x_duty'=> $authnet_array[33],
				'x_freight'=> $authnet_array[34],
				'x_tax_exempt'=> $authnet_array[35],
				'x_po_num'=> $authnet_array[36],
				'x_md5_hash'=> $authnet_array[37],
				'x_cvv2_resp_code'=> $authnet_array[38]
			);
		}
		catch (Exception $e) {
			// invalid response from gateway
		}
		
		return null;
	}
	
	/**
	 * Returns $value if $value isset, otherwise returns $alt
	 *
	 * @param mixed $value The value to return if $value isset
	 * @param mixed $alt The value to return if $value is not set
	 * @return mixed Either $value or $alt
	 */
	protected function ifSet(&$value, $alt=null) {
		if (isset($value))
			return $value;
		return $alt;
	}
}
?>