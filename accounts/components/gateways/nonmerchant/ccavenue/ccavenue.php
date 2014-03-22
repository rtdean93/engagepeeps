<?php
/**
 * CCAvenue Payment Gateway
 *
 * @package blesta
 * @subpackage blesta.components.gateways.ccavenue
 * @author Phillips Data, Inc.
 * @author Nirays Technologies
 * @copyright Copyright (c) 2013, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 * @link http://nirays.com/ Nirays
 */
define("CCAVENUE_TEST_STRING", "SUB-MERCHANT TEST");
class Ccavenue extends NonmerchantGateway {

    /**
     * @var string The version of this gateway
     */
    private static $version = "1.0.1";
    /**
     * @var string The authors of this gateway
     */
    private static $authors = array(
		array('name' => "Phillips Data, Inc.", 'url' => "http://www.blesta.com"),
		array('name' => "Nirays Technologies.", 'url' => "http://nirays.com")
	);
	
    /**
     * @var array An array of meta data for this gateway
     */
    private $meta;

    /**
     * @var string The URL to post payments to
     */
    private $ccavenue_url = "https://www.ccavenue.com/shopzone/cc_details.jsp";
    /**
     * Construct a new merchant gateway
     */
    public function __construct() {

        // Load components required by this gateway
        Loader::loadComponents($this, array("Input"));

        // Load components required by this gateway
        Loader::loadModels($this, array("Clients","Contacts","Transactions","Companies"));
        // Load the language required by this gateway
        Language::loadLang("ccavenue", null, dirname(__FILE__) . DS . "language" . DS);
    }

    /**
     * Returns the name of this gateway
     *
     * @return string The common name of this gateway
     */
    public function getName() {
        return Language::_("Ccavenue.name", true);
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
        return array("INR");
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
                'valid' => array(
                    'rule' => "isEmpty",
                    'negate' => true,
                    'message' => Language::_("Ccavenue.!error.merchant_id.valid", true)
                )
            ),
            'working_key' => array(
                'valid' => array(
                    'rule' => "isEmpty",
                    'negate' => true,
                    'message' => Language::_("Ccavenue.!error.working_key.valid", true)
                )
            )
        );
        // Set checkbox if not set
        if (!isset($meta['encrypt_mode']))
            $meta['encrypt_mode'] = "false";

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
        return array("working_key");
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
     * 	- return_url The URL to redirect users to after a successful payment
     * 	- recur An array of recurring info including:
     * 		- start_date The date/time in UTC that the recurring payment begins
     * 		- amount The amount to recur
     * 		- term The term to recur
     * 		- period The recurring period (day, week, month, year, onetime) used in conjunction with term in order to determine the next recurring payment
     * @return mixed A string of HTML markup required to render an authorization and capture payment form, or an array of HTML markup
     */
    public function buildProcess(array $contact_info, $amount, array $invoice_amounts=null, array $options=null) {
        $client = $this->Clients->get($contact_info['client_id']);

        Loader::load(dirname(__FILE__) . DS . "lib" . DS . "lib_functions.php");
        $util = new LibFunctions();
        $company = $this->Companies->get($client->company_id);

        // Get the company hostname
        $hostname = isset($company->hostname) ? $company->hostname : "";
        // Force 2-decimal places only
        $amount = round($amount, 2);

        //redirection URL
        $redirect_url = Configure::get("Blesta.gw_callback_url") . Configure::get("Blesta.company_id") . "/ccavenue/".$this->ifSet($contact_info['client_id']);

        $order_id = $this->ifSet($contact_info['client_id']) . "-" . time();
        $Merchant_Id =  $this->ifSet($this->meta['merchant_id']);
        $working_key = $this->ifSet($this->meta['working_key']);

        // Filling the response parameters
        $checksum = $util->getChecksum(
            $Merchant_Id,
            $order_id,
            $amount,
            $redirect_url,
            $working_key);
        $fields = array(
                'Merchant_Id' => $Merchant_Id,
                'Amount' => $amount,
                'Order_Id' => $order_id,
                'Redirect_Url' => $redirect_url,
                'Checksum' => $checksum,
                'billing_cust_name' => $this->clean($this->ifSet($contact_info['first_name']) .' '.  $this->ifSet($contact_info['last_name'])),
                'billing_cust_address' => $this->clean($this->ifSet($contact_info['address1']) . ' ' . $this->ifSet($contact_info['address2'])),
                'billing_cust_country' => $this->ifSet(trim($contact_info['country']['name'])),
                'billing_cust_state' => $this->ifSet($contact_info['state']['name']),
                'billing_cust_city' =>  $this->ifSet($contact_info['city']),
                'billing_zip' => $this->ifSet($contact_info['zip']),
                'billing_cust_tel' => $this->clean($this->ifSet($this->getContact($client))),
                'billing_cust_email' => $this->ifSet($client->email),
                'delivery_cust_name' => $this->clean($this->ifSet($contact_info['first_name']) .' '.  $this->ifSet($contact_info['last_name'])),
                'delivery_cust_address' => $this->clean($this->ifSet($contact_info['address1']) . ' ' . $this->ifSet($contact_info['address2'])),
                'delivery_cust_country' => $this->ifSet(trim($contact_info['country']['name'])),
                'delivery_cust_state' => $this->ifSet($contact_info['state']['name']),
                'delivery_cust_tel' => $this->clean($this->ifSet($this->getContact($client))),
                'delivery_cust_notes' => '',
                'Merchant_Param' =>  $this->serializeInvoices($invoice_amounts),
                'billing_zip_code' => $this->ifSet($contact_info['zip']),
                'delivery_cust_city' => $this->ifSet($contact_info['city']),
                'delivery_zip_code' =>$this->ifSet($contact_info['zip']));

            $fields['billing_cust_notes'] =  "";

        $this->view = $this->makeView("process", "default", str_replace(ROOTWEBDIR, "", dirname(__FILE__) . DS));

        $merchant_data="";
        foreach ($fields as $Key => $Value)
            $merchant_data .= $Key . '=' . $Value .'&';
        rtrim($merchant_data, "&");

        // Load the helpers required for this view
        Loader::loadHelpers($this, array("Form", "Html"));

        $this->view->set("post_to", $this->ccavenue_url);

        if ($this->ifSet($this->meta['encrypt_mode']) == "true") {
            $encRequest=$util->encrypt($merchant_data,$working_key); // Method for encrypting the data.
            $this->view->set("encRequest", $encRequest);
            $this->view->set("Merchant_Id",$Merchant_Id );
        }
        else {
            $this->view->set("fields", $fields);
        }

        // Log request received
        return $this->view->fetch();

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

        $working_key = $this->ifSet($this->meta['working_key']) ; //put in the 32 bit working key in the quotes provided here
        $enc_response = $this->ifSet($post["encResponse"]);			//This is the response sent by the CCAvenue Server
        Loader::load(dirname(__FILE__) . DS . "lib" . DS . "lib_functions.php");
        $util = new LibFunctions();
        if($enc_response) {
            $rec_string = $util->decrypt($enc_response,$working_key);		//AES Decryption used as per the specified working key.
            $all_params = explode('&', $rec_string);
        }
        else {
            $all_params=$post;
        }

        $merchant_id = "";
        $order_id = "";
        $amount = "";
        $auth_desc = "";
        $avn_checksum = "";
        // Filling the response
        if(array_key_exists("Order_Id",$all_params)) $order_id = $all_params["Order_Id"];
        if(array_key_exists("Amount",$all_params)) $amount = $all_params["Amount"];
        if(array_key_exists("Merchant_Id",$all_params)) $merchant_id = $all_params["Merchant_Id"];
        if(array_key_exists("AuthDesc",$all_params)) $auth_desc = $all_params["AuthDesc"];
        if(array_key_exists("Checksum",$all_params)) $avn_checksum = $all_params["Checksum"];
        if(array_key_exists("Merchant_Param",$all_params)) $billing = $all_params["Merchant_Param"];
        if(array_key_exists("nb_bid",$all_params)) $bid = $all_params["nb_bid"];
        if(array_key_exists("bankRespMsg",$all_params)) $bank_resp_msg = $all_params["bankRespMsg"];
        if(array_key_exists("bankRespCode",$all_params)) $bank_resp_code = $all_params["bankRespCode"];
        if(array_key_exists("bank_name",$all_params)) $bank_name = $all_params["bank_name"];
        if(array_key_exists("card_category",$all_params)) $card_category = $all_params["card_category"];
        if(array_key_exists("nb_order_no",$all_params)) $nb_order_no = $all_params["nb_order_no"];
        $bankDetails= "nb_bid:" . $this->ifSet($bid) .
            " bankRespCode:" . $this->ifSet($bank_resp_code) .
            " bank_name:". $this->ifSet($bank_name) .
            " card_category:" . $this->ifSet($card_category) .
            " nb_order_no:" . $this->ifSet($nb_order_no) .
            " bankRespMsg:" . $this->ifSet($bank_resp_msg);

        $checksum = $util->verifyChecksum($merchant_id, $order_id, $amount, $auth_desc, $working_key, $avn_checksum);
        if($checksum && $auth_desc==="Y") {
            $status = "approved";
            $this->log($this->ifSet($_SERVER['REQUEST_URI'])," Bank Response:" . $this->ifSet($bankDetails), "output", true);
        }
        else if($checksum && $auth_desc==="B") {
            $status = "pending";
            $this->Input->setErrors(array('authentication' => array('response' => Language::_("Ccavenue.!error.delay.response", true))));
            $this->log($this->ifSet($_SERVER['REQUEST_URI']), Language::_("Ccavenue.!error.pending.log", true) . " Bank Response:" . $this->ifSet($bankDetails), "output", true);
            // "Batch Processing"
            //indicates that the transaction is in batch processing mode and the authorisation
            // status can only be determined at a later point in time. This happens only in very
            // rare cases if any of the Gateway servers is down and we opt to process orders offline. In the
            // case of these transactions the authorisation status is available only after 5-6 hours by
            // mail from CCAvenue and at the "Pending Orders” section.
            //Here you need to put in the routines/e-mail for a  "Batch Processing" order
            //This is only if payment for this transaction has been made by an American Express Card or by any netbank and status is not known is real time  the authorisation status will be  available only after 5-6 hours  at the "View Pending Orders" or you may do an order status query to fetch the status . Refer inetegrtaion document for order status tracker documentation"
        }
        else if($checksum && $auth_desc==="N") {
            $this->log($this->ifSet($_SERVER['REQUEST_URI']),
                Language::_("Ccavenue.!error.authentication.log", true) . " Bank Response:" . $this->ifSet($bankDetails), "output", false);
            $this->Input->setErrors(
                array(
                    'authentication' => array(
                        'response' => Language::_("Ccavenue.!error.authentication.log", true)
                    )
                )
            );
            $status = "declined";
        }
        else {
            $this->Input->setErrors(array('security' => array('response' => Language::_("Ccavenue.!error.security.response", true))));
            $this->log($this->ifSet($_SERVER['REQUEST_URI']),
                Language::_("Ccavenue.!error.security.log", true) . " Bank Response:" . $this->ifSet($bankDetails), "output", false);
            $status = "declined";
            //Here you need to check for the checksum, the checksum did not match hence the error.
        }

        $order_id_time = $this->ifSet($order_id);

        return  array(
            'client_id' => $this->ifSet($get[2]),
            'amount' => $amount,
            'currency' => "INR",
            'invoices' => $this->unserializeInvoices($this->ifSet($billing)),
            'status' => $status,
            'reference_id' => $this->ifSet($nb_order_no),
            'transaction_id' => $order_id_time,
            'parent_transaction_id' => null
        );
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
        return array(
            'client_id' => $this->ifSet($post['client_id']),
            'amount' => $this->ifSet($post['total']),
            'currency' => $this->ifSet($post['currency_code']),
            'invoices' => $this->unserializeInvoices($this->ifSet($post['invoices'])),
            'status' => "approved",
            'transaction_id' => $this->ifSet($post['order_number']),
            'parent_transaction_id' => null
        );
    }

    /**
     * Serializes an array of invoice info into a string
     *
     * @param array A numerically indexed array invoices info including:
     *  - id The ID of the invoice
     *  - amount The amount relating to the invoice
     * @return string A serialized string of invoice info in the format of key1=value1|key2=value2
     */
    private function serializeInvoices(array $invoices) {
        $str = "";
        foreach ($invoices as $i => $invoice)
            $str .= ($i > 0 ? "-" : "") . $invoice['id'] . "_" . $invoice['amount'];
        return $str;
    }

    /**
     * Unserializes a string of invoice info into an array
     *
     * @param string A serialized string of invoice info in the format of key1=value1|key2=value2
     * @return array A numerically indexed array invoices info including:
     *  - id The ID of the invoice
     *  - amount The amount relating to the invoice
     */
    private function unserializeInvoices($str) {
        $invoices = array();
        $temp = explode("-", $str);
        foreach ($temp as $pair) {
            $pairs = explode("_", $pair, 2);
            if (count($pairs) != 2)
                continue;
            $invoices[] = array('id' => $pairs[0], 'amount' => $pairs[1]);
        }
        return $invoices;
    }

    /**
     * Function to remove all special characters
     * @param $string
     * @return mixed
     */
    private function clean($string) {
        $string = str_replace(" ", "-", $string); // Replaces all spaces with hyphens.
        $string = preg_replace('/[^A-Za-z0-9\-]/', '', $string); // Removes special chars.

        $string = preg_replace('/-+/', '-', $string); // Replaces multiple hyphens with single one.
        return str_replace("-", " ", $string); // Replaces all spaces with hyphens.
    }

    /**
     * This function return contact number for the given client.
     * @param $client
     * @return string
     */
    private function getContact($client){
        // Get any phone/fax numbers
        $contact_numbers = $this->Contacts->getNumbers($client->contact_id);

        // Set any contact numbers (only the first of a specific type found)
        foreach ($contact_numbers as $contact_number) {
            switch ($contact_number->location) {
                case "home":
                    // Set home phone number
                    if (!isset($data) && $contact_number->type == "phone")
                        $data = $contact_number->number;
                    break;
                case "work":
                    // Set work phone/fax number
                    if (!isset($data) && $contact_number->type == "phone")
                        $data['office_tel'] = $contact_number->number;
                case "mobile":
                    // Set mobile phone number
                    if (!isset($data) && $contact_number->type == "phone")
                        $data = $contact_number->number;
                    break;
            }
        }
        if(trim($data)=="") {
            return "9391919191"; // dummy number
        }
        return $data;
    }
}
?>