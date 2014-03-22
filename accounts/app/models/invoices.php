<?php
/**
 * Invoice management
 * 
 * @package blesta
 * @subpackage blesta.app.models
 * @copyright Copyright (c) 2010, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class Invoices extends AppModel {
	
	/**
	 * Initialize Invoices
	 */
	public function __construct() {
		parent::__construct();
		Language::loadLang(array("invoices"));
	}
	
	/**
	 * Creates a new invoice using the given data
	 *
	 * @param array $vars An array of invoice data including:
	 * 	- client_id The client ID the invoice belongs to
	 * 	- date_billed The date the invoice goes into effect
	 * 	- date_due The date the invoice is due
	 * 	- date_closed The date the invoice was closed
	 * 	- date_autodebit The date the invoice should be autodebited
	 * 	- status 'active','draft', or 'void'
	 * 	- currency The ISO 4217 3-character currency code of the invoice
	 * 	- note_public Notes visible to the client
	 * 	- note_private Notes visible only to staff members
	 * 	- lines A numerically indexed array of line item info including:
	 * 		- service_id The service ID attached to this line item (optional)
	 * 		- description The line item description
	 * 		- qty The quantity for this line item (min. 1)
	 * 		- amount The unit cost (cost per quantity) for this line item
	 * 		- tax Whether or not to tax the line item
	 *	- term The term for the recurring invoice as an integer 1-65535, if blank will not be considered for a recurring invoice
	 *	- period The period for the recurring invoice ('day', 'week', 'month', 'year')
	 *	- duration The duration of the recurring invoice ('indefinitely' for forever or 'times' for a set number of times)
	 *	- duration_time The number of times an invoice should recur
	 *	- recur_date_billed The date the next invoice will be created
	 * 	- delivery A numerically indexed array of delivery methods
	 * @return int The invoice ID, void on error
	 */
	public function add(array $vars) {
		// Fetch client settings on invoices
		Loader::loadComponents($this, array("SettingsCollection"));
		$client_settings = $this->SettingsCollection->fetchClientSettings($vars['client_id']);
		
		$vars = $this->getNextInvoiceVars($vars, $client_settings);
		
		// Copy record so that it is not overwritten during validation
		$record = clone $this->Record;		
		$this->Record->reset();
		
		// Note: there must be at least 1 line item
		$this->Input->setRules($this->getRules($vars));
		
		// Start the transaction
		$this->Record->begin();
		
		if ($this->Input->validates($vars)) {
			// Set the record back
			$this->Record = $record;			
			unset($record);
			
			// Assign subquery values to this record component
			$this->Record->appendValues($vars['id_value']->values);
			// Ensure the subquery value is set first because its the first value
			$vars = array_merge(array('id_value'=>null), $vars);
			// Add invoice
			$fields = array("id_value", "id_format", "client_id", "date_billed", "date_due", "date_closed",
				"date_autodebit", "status", "previous_due", "currency", "note_public", "note_private"
			);
			
			$this->Record->insert("invoices", $vars, $fields);
			
			$invoice_id = $this->Record->lastInsertId();
			
			// Get tax rules for this client
			$tax_rules = $this->getTaxRules($vars['client_id']);
			$num_taxes = count($tax_rules);
			
			// Add invoice line items
			$fields = array("invoice_id", "service_id", "description", "qty", "amount", "order");
			foreach ($vars['lines'] as $i => $line) {
				$line['invoice_id'] = $invoice_id;
				$line['order'] = $i;
				
				// Add invoice line item
				$this->Record->insert("invoice_lines", $line, $fields);
				
				$line_item_id = $this->Record->lastInsertId();
				
				// Add line item taxes, if set to taxable IFF tax is enabled
				if ($client_settings['enable_tax'] == "true" && isset($line['tax']) && $line['tax']) {
					for ($j=0; $j<$num_taxes; $j++)
						$this->addLineTax($line_item_id, $tax_rules[$j]->id, $client_settings['cascade_tax'] == "true");
				}
			}
			
			// Add invoice delivery methods
			if (!empty($vars['delivery'])) {
				foreach ($vars['delivery'] as $key => $value)
					$this->addDelivery($invoice_id, array('method'=>$value), $vars['client_id']);
			}
			
			// Save recurring invoice info
			if (isset($vars['term']) && !empty($vars['term'])) {
				// If a draft, serialize and store as meta data for future editing
				if (isset($vars['status']) && $vars['status'] == "draft") {
					$this->setMeta($invoice_id, "recur",
						array(
							'term'=>$vars['term'],
							'period'=>$vars['period'],
							'duration'=>$vars['duration'],
							'duration_time'=>$vars['duration_time'],
							'recur_date_billed'=>$vars['recur_date_billed']
						)
					);
				}
				// If not a draft, attempt to save as recurring
				else {
					$vars['duration'] = ($vars['duration'] == "indefinitely" ? null : $vars['duration_time']);
					$vars['date_renews'] = $vars['recur_date_billed'];
					$this->addRecurring($vars);
				}
			}
			
			// Commit if no errors when adding
			if (!$this->Input->errors()) {
				// Set totals
				$this->updateTotals($invoice_id);
				
				$this->Record->commit();
				
				$this->Events->register("Invoices.add", array("EventsInvoicesCallback", "add"));
				$this->Events->trigger(new EventObject("Invoices.add", compact("invoice_id")));
				
				return $invoice_id;
			}
		}
		
		// Rollback, something went wrong
		$this->Record->rollBack();
	}
	
	/**
	 * Creates a new recurring invoice using the given data
	 *
	 * @param array $vars An array of invoice data including:
	 * 	- client_id The client ID the invoice belongs to
	 * 	- term The term as an integer 1-65535 (optional, default 1)
	 * 	- period The period, 'day', 'week', 'month', 'year'
	 * 	- duration The number of times this invoice will recur or null to recur indefinitely
	 * 	- date_renews The date the next invoice will be created
	 * 	- currency The currency this invoice is created in
	 * 	- note_public Notes visible to the client
	 * 	- note_private Notes visible only to staff members
	 * 	- lines A numerically indexed array of line item info including:
	 * 		- description The line item description
	 * 		- qty The quantity for this line item (min. 1)
	 * 		- amount The unit cost (cost per quantity) for this line item
	 * 		- tax Whether or not to tax the line item
	 * 	- delivery A numerically indexed array of delivery methods
	 * @return int The recurring invoice ID, void on error
	 */
	public function addRecurring(array $vars) {
		
		// Set the rules for adding recurring invoices
		$this->Input->setRules($this->getRecurringRules($vars));
		
		if ($this->Input->validates($vars)) {
		
			// Add recurring invoice
			$fields = array("client_id", "term", "period", "duration", "currency",
				"date_renews", "date_last_renewed", "note_public", "note_private"
			);
			$this->Record->insert('invoices_recur', $vars, $fields);
			
			$invoice_recur_id = $this->Record->lastInsertId();
			
			// Add line items
			$fields = array("invoice_recur_id", "description", "qty", "amount", "taxable", "order");
			foreach ($vars['lines'] as $i => $line) {
				$line['invoice_recur_id'] = $invoice_recur_id;
				$line['order'] = $i;
				
				if (isset($line['tax']))
					$line['taxable'] = $line['tax'];
				
				// Add invoice line item
				$this->Record->insert("invoice_recur_lines", $line, $fields);
			}
			
			// Add invoice delivery methods
			if (!empty($vars['delivery'])) {
				foreach ($vars['delivery'] as $key => $value)
					$this->addRecurringDelivery($invoice_recur_id, array('method'=>$value), $vars['client_id']);
			}
			
			return $invoice_recur_id;
		}
	}
	
	/**
	 * Sets meta data for the given invoice
	 *
	 * @param int $invoice_id The ID of the invoice to set meta data for
	 * @param string $key The key of the invoice meta data
	 * @param mixed $value The value to store for this meta field
	 */
	private function setMeta($invoice_id, $key, $value) {
		// Delete all old meta data for this invoice and key
		$this->Record->from("invoice_meta")->
			where("invoice_id", "=", $invoice_id)->where("key", "=", $key)->delete();
		
		// Add the net meta data
		$this->Record->insert("invoice_meta", array('invoice_id'=>$invoice_id,'key'=>$key,'value'=>base64_encode(serialize($value))));
	}
	
	/**
	 * Deletes any meta on the given invoice ID
	 *
	 * @param int $invoice_id The invoice ID to unset meta data for
	 * @param string $key The key to unset, null will unset all keys
	 */
	private function unsetMeta($invoice_id, $key=null) {
		$this->Record->from("invoice_meta")->where("invoice_id", "=", $invoice_id);
		
		if ($key !== null)
			$this->Record->where("key", "=", $key);
			
		$this->Record->delete();
	}
	
	/**
	 * Fetches the meta fields for this invoice.
	 *
	 * @param int $invoice_id The invoice ID to fetch meta data for
	 * @param string $key The key to fetch if fetching only a single meta field, null to fetch all meta fields
	 * @return mixed An array of stdClass objects if fetching all meta data, a stdClass object if fetching a specific meta field, boolean false if fetching a specific meta field that does not exist
	 */
	private function getMeta($invoice_id, $key=null) {
		$this->Record->select()->from("invoice_meta")->where("invoice_id", "=", $invoice_id);
		
		if ($key !== null)
			return $this->Record->where("key", "=", $key)->fetch();
			
		return $this->Record->fetchAll();
	}
	
	/**
	 * Adds a line item to an existing invoice
	 *
	 * @param int $invoice_id The ID of the invoice to add a line item to
	 * @param array $vars A list of line item vars including:
	 * 	- service_id The service ID attached to this line item
	 * 	- description The line item description
	 * 	- qty The quantity for this line item (min. 1)
	 * 	- amount The unit cost (cost per quantity) for this line item
	 * 	- tax Whether or not to tax the line item
	 * 	- order The order number of the line item (optional, default is the last)
	 * @return int The ID of the line item created
	 */
	private function addLine($invoice_id, array $vars) {
		$line = $vars;
		$line['invoice_id'] = $invoice_id;
		
		// Calculate the next line item order off of this invoice
		if (!isset($vars['order'])) {
			$order = $this->Record->select(array('MAX(order)' => "order"))->
				from("invoice_lines")->
				where("invoice_id", "=", $invoice_id)->
				fetch();
			
			if (isset($order->order))
				$line['order'] = $order->order + 1;
		}
		
		// Insert a new line item
		$fields = array("invoice_id", "service_id", "description", "qty", "amount", "order");
		$this->Record->insert("invoice_lines", $line, $fields);
		
		return $this->Record->lastInsertId();
	}
	
	/**
	 * Updates an invoice using the given data. If a new line item is added, or
	 * the quantity, unit cost, or tax status of an item is updated the
	 * latest tax rules will be applied to this invoice.
	 *
	 * @param int $invoice_id The ID of the invoice to update
	 * @param array $vars An array of invoice data (all optional unless noted otherwise) including:
	 * 	- client_id The client ID the invoice belongs to (required)
	 * 	- date_billed The date the invoice goes into effect
	 * 	- date_due The date the invoice is due
	 * 	- date_closed The date the invoice was closed
	 * 	- date_autodebit The date the invoice should be autodebited
	 * 	- status 'active','draft', or 'void'
	 * 	- currency The ISO 4217 3-character currency code of the invoice
	 * 	- note_public Notes visible to the client
	 * 	- note_private Notes visible only to staff members
	 * 	- lines A numerically indexed array of line item info including:
	 * 		- id The ID for this line item (required to update, else will add as new)
	 * 		- service_id The service ID attached to this line item
	 * 		- description The line item description (if empty, along with amount, will delete line item)
	 * 		- qty The quantity for this line item (min. 1)
	 * 		- amount The unit cost (cost per quantity) for this line item (if empty, along with description, will delete line item)
	 * 		- tax Whether or not to tax the line item
	 *	- term If editing a draft, the term for the recurring invoice as an integer 1-65535, if blank will not be considered for a recurring invoice
	 *	- period If editing a draft, the period for the recurring invoice ('day', 'week', 'month', 'year')
	 *	- duration If editing a draft, the duration of the recurring invoice ('indefinitely' for forever or 'times' for a set number of times)
	 *	- duration_time If editing a draft, the number of times an invoice should recur
	 *	- recur_date_billed If editing a draft, the date the next invoice will be created
	 * 	- delivery A numerically indexed array of delivery methods
	 * @return int The invoice ID, void on error
	 */
	public function edit($invoice_id, array $vars) {
		// Fetch client settings on invoices
		Loader::loadComponents($this, array("SettingsCollection"));
		$client_settings = $this->SettingsCollection->fetchClientSettings($vars['client_id']);
		
		$vars = $this->getNextInvoiceVars($vars, $client_settings);
		
		// Copy record so that it is not overwritten during validation
		$record = clone $this->Record;		
		$this->Record->reset();
		
		// Pull out line items that should be deleted
		$delete_items = array();
		// Check we have a numerically indexed line item array
		if (isset($vars['lines']) && (array_values($vars['lines']) === $vars['lines'])) {
			foreach ($vars['lines'] as $i => &$line) {
				if (isset($line['id']) && !empty($line['id'])) {
					$amount = trim(isset($line['amount']) ? $line['amount'] : "");
					$description = trim(isset($line['description']) ? $line['description'] : "");
					
					// Set this item to be deleted, and remove it from validation check
					// if amount and description are both empty
					if (empty($description) && empty($amount)) {
						$delete_items[] = $line;
						unset($vars['lines'][$i]);
					}
				}
			}
			unset($line);
			
			// Re-index array
			if (!empty($delete_items))
				$vars['lines'] = array_values($vars['lines']);
		}
		
		$vars['id'] = $invoice_id;
		
		$rules = $this->getRules($vars);
		$line_rules = array(
			'lines[][id]' => array(
				'exists' => array(
					'if_set' => true,
					'rule' => array(array($this, "validateExists"), "id", "invoice_lines", false),
					'message' => $this->_("Invoices.!error.lines[][id].exists")
				)
			),
			// Ensure no payments have been applied to the invoice
			'id' => array(
				'amount_applied' => array(
					'if_set' => true,
					'rule' => array(array($this, "validateAmountApplied")),
					'negate' => true,
					'message' => $this->_("Invoices.!error.id.amount_applied")
				)
			)
		);
		
		// Set other rules to optional
		$rules['date_billed']['format']['if_set'] = true;
		$rules['date_due']['format']['if_set'] = true;
		$rules['date_due']['after_billed']['if_set'] = true;
		
		$rules = array_merge($rules, $line_rules);

		// Get this current invoice
		$invoice = $this->get($invoice_id);
		
		// If the invoice wasn't already a draft or we're not moving from a draft
		// then we can't update the id_format or id_value
		if ($invoice->status != "draft" || ($invoice->status == "draft" && $vars['status'] == "draft")) {
			// Do not evaluate rules for id_format and id_value because they can not be changed
			unset($rules['id_format']);
			unset($rules['id_value']);
		}
		
		$this->Input->setRules($rules);
		
		// Edit the invoice
		if ($this->Input->validates($vars)) {
			
			if (isset($rules['id_value'])) {
			
				// Set the record back
				$this->Record = $record;			
				unset($record);
			
				// Assign subquery values to this record component
				$this->Record->appendValues($vars['id_value']->values);
				// Ensure the subquery value is set first because its the first value
				$vars = array_merge(array('id_value'=>null), $vars);
			}
			
			// Update invoice
			$fields = array(
				"client_id", "date_billed", "date_due", "date_closed", "date_autodebit",
				"status", "previous_due", "currency", "note_public", "note_private"
			);
			if (isset($rules['id_format']))
				$fields[] = "id_format";
			if (isset($rules['id_value']))
				$fields[] = "id_value";
				
			$this->Record->where("id", "=", $invoice_id)->update("invoices", $vars, $fields);
			
			// Delete existing unsent invoice delivery methods and insert new
			$this->Record->from("invoice_delivery")->where("invoice_id", "=", $invoice_id)->
				where("date_sent", "=", null)->delete();
			
			if (!empty($vars['delivery']) && ($num_methods = count($vars['delivery'])) > 0) {
				for ($i=0; $i<$num_methods; $i++)
					$this->addDelivery($invoice_id, array('method'=>$vars['delivery'][$i]), $vars['client_id']);
			}
			
			// Get the tax rules
			$tax_rules = $this->getTaxRules($invoice->client_id);
			
			// Flag whether or not the invoice has been updated in such a way to
			// warrant updating the tax rules applied to the invoice
			$tax_change = $this->taxUpdateRequired($invoice_id, $vars['lines'], $delete_items);
			
			// Delete any line items set to be deleted
			for ($i=0, $num_items=count($delete_items); $i<$num_items; $i++)
				$this->deleteLine($delete_items[$i]['id']);
			
			// Insert and update line items and taxes
			foreach ($vars['lines'] as $i => $line) {				
				$line['invoice_id'] = $invoice_id;
				
				// Add or update a line item
				if (isset($line['id']) && !empty($line['id'])) {
					$line_item_id = $line['id'];
					$line['order'] = $i;
					
					// Update a line item
					$fields = array("service_id", "description", "qty", "amount", "order");
					$this->Record->where("id", "=", $line_item_id)->update("invoice_lines", $line, $fields);
					
					if ($tax_change) {
						// Delete the current line item tax rule
						$this->deleteLineTax($line_item_id);
					}
				}
				else {
					// Create a new line item
					$line_item_id = $this->addLine($invoice_id, $line);
				}
				
				if ($tax_change) {
					// Add line item taxes, if set to taxable IFF tax is enabled
					if ($client_settings['enable_tax'] == "true" && isset($line['tax']) && $line['tax']) {
						for ($j=0, $num_taxes = count($tax_rules); $j<$num_taxes; $j++)
							$this->addLineTax($line_item_id, $tax_rules[$j]->id, $client_settings['cascade_tax'] == "true");
					}
				}
			}
			
			
			// If invoice was a draft save recurring invoice info
			if ($invoice->status == "draft") {
				if (isset($vars['term']) && !empty($vars['term'])) {
					// If a draft, serialize and store as meta data for future editing
					if (isset($vars['status']) && $vars['status'] == "draft") {
						$this->setMeta($invoice_id, "recur",
							array(
								'term'=>$vars['term'],
								'period'=>$vars['period'],
								'duration'=>$vars['duration'],
								'duration_time'=>$vars['duration_time'],
								'recur_date_billed'=>$vars['recur_date_billed']
							)
						);
					}
					// If not a draft, attempt to save as recurring
					else {
						$vars['duration'] = ($vars['duration'] == "indefinitely" ? null : $vars['duration_time']);
						$vars['date_renews'] = $vars['recur_date_billed'];
						$this->addRecurring($vars);
						
						// Remove any existing meta data, no longer needed
						$this->unsetMeta($invoice_id);
					}
				}
				// Remove any existing meta data, no longer needed
				else
					$this->unsetMeta($invoice_id);
			}
			
			// Update totals
			$this->updateTotals($invoice_id);
			
			$this->Events->register("Invoices.edit", array("EventsInvoicesCallback", "edit"));
			$this->Events->trigger(new EventObject("Invoices.edit", compact("invoice_id")));
			
			return $invoice_id;
		}
	}
	
	/**
	 * Updates a recurring invoice using the given data. If a new line item is added, or
	 * the quantity, unit cost, or tax status of an item is updated the
	 * latest tax rules will be applied to this invoice.
	 *
	 * @param int $invoice_recur_id The ID of the recurring invoice to update
	 * @param array $vars An array of invoice data (all optional) including:
	 * 	- client_id The client ID the recurring invoice belongs to
	 * 	- term The term as an integer 1-65535 (optional, default 1)
	 * 	- period The period, 'day', 'week', 'month', 'year'
	 * 	- duration The number of times this invoice will recur or null to recur indefinitely
	 * 	- date_renews The date the next invoice will be created
	 * 	- date_last_renewed The date the last invoice was created (optional) - not recommended to overwrite this value
	 * 	- currency The currency this invoice is created in
	 * 	- note_public Notes visible to the client
	 * 	- note_private Notes visible only to staff members
	 * 	- lines A numerically indexed array of line item info including:
	 * 		- id The ID for this line item (required to update, else will add as new)
	 * 		- description The line item description (if empty, along with amount, will delete line item)
	 * 		- qty The quantity for this line item (min. 1)
	 * 		- amount The unit cost (cost per quantity) for this line item (if empty, along with description, will delete line item)
	 * 		- tax Whether or not to tax the line item
	 * 	- delivery A numerically indexed array of delivery methods
	 * @return int The recurring invoice ID, void on error
	 */
	public function editRecurring($invoice_recur_id, array $vars) {
		
		// Pull out line items that should be deleted
		$delete_items = array();
		// Check we have a numerically indexed line item array
		if (isset($vars['lines']) && (array_values($vars['lines']) === $vars['lines'])) {
			foreach ($vars['lines'] as $i => &$line) {
				if (isset($line['id']) && !empty($line['id'])) {
					$amount = trim(isset($line['amount']) ? $line['amount'] : "");
					$description = trim(isset($line['description']) ? $line['description'] : "");
					
					// Set this item to be deleted, and remove it from validation check
					// if amount and description are both empty
					if (empty($description) && empty($amount)) {
						$delete_items[] = $line;
						unset($vars['lines'][$i]);
					}
				}
			}
			unset($line);
			
			// Re-index array
			if (!empty($delete_items))
				$vars['lines'] = array_values($vars['lines']);
		}
		
		$this->Input->setRules($this->getRecurringRules($vars));
		
		if ($this->Input->validates($vars)) {
			
			// Update recurring invoice
			$fields = array(
				'client_id','term','period','duration','date_renews',
				'date_last_renewed','note_public','note_private'
			);
			$this->Record->where("id", "=", $invoice_recur_id)->update("invoices_recur", $vars, $fields);
			
			// Delete any line items set to be deleted
			for ($i=0, $num_items=count($delete_items); $i<$num_items; $i++)
				$this->deleteRecurringLine($delete_items[$i]['id']);
			
			// Insert and update line items and taxes
			foreach ($vars['lines'] as $i => $line) {
				$line['invoice_recur_id'] = $invoice_recur_id;
				$line['order'] = $i;
				
				if (isset($line['tax']))
					$line['taxable'] = $line['tax'];
				
				// Add or update a line item
				if (isset($line['id']) && !empty($line['id']) && $this->validateExists($line['id'], "id", "invoice_recur_lines", false)) {
					$line_item_id = $line['id'];
					
					// Update a line item
					$fields = array("description", "qty", "amount", "taxable", "order");
					$this->Record->where("id", "=", $line_item_id)->update("invoice_recur_lines", $line, $fields);
				}
				else {
					// Insert a new line item
					$fields = array("invoice_recur_id", "description", "qty", "amount", "taxable", "order");
					$this->Record->insert("invoice_recur_lines", $line, $fields);
				}
			}
			
			// Delete existing invoice delivery methods and insert new
			$this->Record->from("invoice_recur_delivery")->where("invoice_recur_id", "=", $invoice_recur_id)->delete();
			
			if (!empty($vars['delivery']) && ($num_methods = count($vars['delivery'])) > 0) {
				for ($i=0; $i<$num_methods; $i++)
					$this->addRecurringDelivery($invoice_recur_id, array('method'=>$vars['delivery'][$i]), $vars['client_id']);
			}
			
			return $invoice_recur_id;
		}		
	}
	
	/**
	 * Creates a new invoice if the given recurring invoice is set to be renewed
	 *
	 * @param int $invoice_recur_id The recurring invoice ID
	 * @param array $client_settings A list of client settings belonging to this invoice's client (optional)
	 * @return boolean True if any invoices were created from this recurring invoice, false otherwise
	 */
	public function addFromRecurring($invoice_recur_id, array $client_settings=null) {
		$invoice = $this->getRecurring($invoice_recur_id);
		$created_invoice = false;
		
		if ($invoice) {
			// Fetch the client associated with this invoice
			Loader::loadModels($this, array("Clients", "Companies"));
			$client = $this->Clients->get($invoice->client_id, false);
			// Get the date format for invoice descriptions
			$date_format = $this->Companies->getSetting($client->company_id, "date_format")->value;
			$date = clone $this->Date;
			$date->setTimezone("UTC", Configure::get("Blesta.company_timezone"));
			
			// Get the client settings
			if (!isset($client_settings['inv_days_before_renewal']) || !isset($client_settings['timezone'])) {
				Loader::loadComponents($this, array("SettingsCollection"));
				$client_settings = $this->SettingsCollection->fetchClientSettings($invoice->client_id);
			}
			$invoice_days_before_renewal = abs((int)$client_settings['inv_days_before_renewal']);
			
			// Encompass the entire day
			$today_timestamp = $this->Date->toTime($this->dateToUtc($this->Date->format("Y-m-d") . " 23:59:59", "c"));
			
			// Set the next renew date
			$next_renew_date = $invoice->date_renews . "Z";
			
			$invoice_day_timestamp = strtotime($next_renew_date . " -" . $invoice_days_before_renewal . " days");
			$invoice_day = date("c", $invoice_day_timestamp);
			
			$fields = array("date_renews", "date_last_renewed");
			
			// Set invoice delivery methods
			$delivery_methods = array();
			foreach ($invoice->delivery as $delivery)
				$delivery_methods[] = $delivery->method;
			
			// Renew the invoice, possibly many times if it needs to be caught up
			while (($invoice->duration == null || $invoice->count < $invoice->duration) &&
				   ($invoice_day_timestamp <= $today_timestamp)) {
				
				// Convert line items to arrays
				$start_period = $next_renew_date;
				$end_period = date("c", strtotime($start_period . " +" . abs((int)$invoice->term) . " " . $invoice->period));
				$line_items = array();
				foreach ($invoice->line_items as $line) {
					// Update the line item description to include the recurring period
					$line_item = (array)$line;
					$line_item['description'] = Language::_("Invoices.!line_item.recurring_renew_description", true, $line->description, $date->cast($start_period, $date_format), $date->cast($end_period, $date_format));
					$line_items[] = $line_item;
				}
				unset($start_period, $end_period);
				
				// Adjust date_due to match today if billing in the past
				$date_billed = date("c");
				$date_due = $next_renew_date;
				if (strtotime($next_renew_date) < strtotime(date("c")))
					$date_due = date("c");
				
				$vars = array(
					'client_id' => $invoice->client_id,
					'date_billed' => $date_billed,
					'date_due' => $date_due,
					'status' => "active",
					'currency' => $invoice->currency,
					'lines' => $line_items,
					'delivery' => $delivery_methods
				);
				
				// Create a new invoice
				$invoice_id = $this->add($vars);
				
				// Set the next renew date for any subsequent invoice
				$next_renew_date = date("c", strtotime($next_renew_date . " +" . abs((int)$invoice->term) . " " . $invoice->period));
				$invoice_day_timestamp = strtotime($next_renew_date . " -" . $invoice_days_before_renewal . " days");
				$invoice_day = date("c", $invoice_day_timestamp);
				
				if (!$this->errors()) {
					// Update the recurring invoice renew dates
					$this->Record->where("id", "=", $invoice_recur_id)->
						update("invoices_recur", array('date_renews' => $this->dateToUtc($next_renew_date), 'date_last_renewed' => $this->dateToUtc($invoice->date_renews)), $fields);
					
					// Set a recurring invoice was created
					$this->Record->insert("invoices_recur_created", array('invoice_recur_id' => $invoice_recur_id, 'invoice_id' => $invoice_id));
					
					$created_invoice = true;
				}
				else
					break;
				
				// Fetch the recurring invoice again for the next iteration
				$invoice = $this->getRecurring($invoice_recur_id);
			}
		}
		
		return $created_invoice;
	}
	
	/**
	 * Permanently deletes a draft invoice
	 *
	 * @param int $invoice_id The invoice ID of the draft invoice to delete
	 */
	public function deleteDraft($invoice_id) {
		$invoice_id = (int)$invoice_id;
		
		$rules = array(
			'invoice_id' => array(
				'draft' => array(
					'rule' => array(array($this, "validateIsDraft")),
					'message' => $this->_("Invoices.!error.invoice_id.draft")
				)
			)
		);
		
		$this->Input->setRules($rules);
		
		// Set invoice ID for validation
		$vars = array('invoice_id'=>$invoice_id);
		
		if ($this->Input->validates($vars)) {
			// Delete the given invoice iff it's a draft invoice
			$this->unsetMeta($invoice_id);
			$this->Record->from("invoice_delivery")->where("invoice_id", "=", $invoice_id)->delete();
			$this->Record->from("invoice_lines")->
				leftJoin("invoice_line_taxes", "invoice_line_taxes.line_id", "=", "invoice_lines.id", false)->
				where("invoice_lines.invoice_id", "=", $invoice_id)->delete(array("invoice_line_taxes.*", "invoice_lines.*"));
			$this->Record->from("invoices")->where("id", "=", $invoice_id)->delete();
		}
	}
	
	/**
	 * Permanently removes a recurring invoice from the system
	 *
	 * @param int $invoice_recur_id The ID of the recurring invoice to delete
	 */
	public function deleteRecurring($invoice_recur_id) {
		// No harm, no foul. We can delete recurring invoices outright, since there are no side-effects to doing so
		$this->Record->from("invoices_recur")->where("id", "=", $invoice_recur_id)->delete();
		$this->Record->from("invoice_recur_delivery")->where("invoice_recur_id", "=", $invoice_recur_id)->delete();
		$this->Record->from("invoice_recur_lines")->where("invoice_recur_id", "=", $invoice_recur_id)->delete();
		$this->Record->from("invoice_recur_values")->where("invoice_recur_id", "=", $invoice_recur_id)->delete();
	}
	
	/**
	 * Permanently removes an invoice line item and its corresponding line item taxes
	 *
	 * @param int $line_id The line item ID
	 */
	private function deleteLine($line_id) {
		// Delete line item
		$this->Record->from("invoice_lines")->where("id", "=", $line_id)->delete();
		
		// Delete line item taxes
		$this->deleteLineTax($line_id);
	}
	
	/**
	 * Permanently removes a recurring invoice line item
	 *
	 * @param int $line_id The line item ID
	 */
	private function deleteRecurringLine($line_id) {
		// Delete line item
		$this->Record->from("invoice_recur_lines")->where("id", "=", $line_id)->delete();
	}
	
	/**
	 * Adds a new line item tax
	 *
	 * @param int $line_id The line item ID
	 * @param int $tax_id The tax ID
	 * @param boolean $cascade Whether or not this tax rule should cascade over other rules
	 */
	private function addLineTax($line_id, $tax_id, $cascade=false) {
		$this->Record->insert("invoice_line_taxes", array('line_id'=>$line_id, 'tax_id'=>$tax_id, 'cascade'=>($cascade ? 1 : 0)));
	}
	
	/**
	 * Permanently removes an invoice line item's tax rule
	 *
	 * @param int $line_id The line item ID
	 */
	private function deleteLineTax($line_id) {
		// Delete line item taxes
		$this->Record->from("invoice_line_taxes")->where("line_id", "=", $line_id)->delete();
	}
	
	/**
	 * Creates an invoice from a set of services
	 *
	 * @param int $client_id The ID of the client to create the invoice for
	 * @param int $service_ids An array of service IDs to generate line items from
	 * @param string $currency The currency code to use to generate the invoice
	 * @param string $due_date The date the invoice is to be due
	 * @return int $invoice_id The ID of the invoice generated
	 */
	public function createFromServices($client_id, $service_ids, $currency, $due_date) {
		if (!isset($this->Coupons))
			Loader::loadModels($this, array("Coupons"));
		if (!isset($this->Clients))
			Loader::loadModels($this, array("Clients"));
		
		// Set the delivery method for the client
		$delivery_method = $this->Clients->getSetting($client_id, "inv_method");
		$delivery_method = (isset($delivery_method->value) ? $delivery_method->value : "email");
		
		// Determine whether setup fees can be taxed
		$setup_fee_tax = $this->Clients->getSetting($client_id, "setup_fee_tax");
		$setup_fee_tax = ("true" == (isset($setup_fee_tax->value) ? $setup_fee_tax->value : "false"));
		
		$coupons = array();
		$line_items = $this->getLinesForServices($service_ids, $currency, $coupons, $setup_fee_tax);
		
		// Adjust date_due to match today if billing in the past
		$date_billed = date("c");
		if (strtotime($due_date) < strtotime(date("c")))
			$due_date = date("c");
		
		// Create the invoice
		$vars = array(
			'client_id' => $client_id,
			'date_billed' => $date_billed,
			'date_due' => $due_date,
			'status' => "active",
			'currency' => $currency,
			'delivery' => array($delivery_method),
			'lines' => $line_items
		);
		
		// Create the invoice
		$invoice_id = $this->add($vars);
		
		if ($this->Input->errors())
			return;
		
		// Increment the used quantity for all coupons used
		foreach ($coupons as $coupon_id => $coupon)
			$this->Coupons->incrementUsage($coupon_id);
		
		return $invoice_id;
	}
	
	/**
	 * Edits an invoice to append a set of service IDs as line items
	 *
	 * @param int $invoice_id The ID of the invoice to append to
	 * @param int $service_ids An array of service IDs to generate line items from
	 * @return int $invoice_id The ID of the invoice updated
	 */
	public function appendServices($invoice_id, $service_ids) {
		if (!isset($this->Services))
			Loader::loadModels($this, array("Services"));
		if (!isset($this->Coupons))
			Loader::loadModels($this, array("Coupons"));
		if (!isset($this->Clients))
			Loader::loadModels($this, array("Clients"));
		
		if (($invoice = $this->get($invoice_id))) {
			$coupons = array();
			
			// Fetch client settings
			Loader::loadComponents($this, array("SettingsCollection"));
			$client_settings = $this->SettingsCollection->fetchClientSettings($invoice->client_id);
			
			// Determine whether setup fees can be taxed
			$setup_fee_tax = (isset($client_settings['setup_fee_tax']) && $client_settings['setup_fee_tax'] == "true");
			
			$line_items = $this->getLinesForServices($service_ids, $invoice->currency, $coupons, $setup_fee_tax);
			
			// Get the tax rules
			$tax_rules = $this->getTaxRules($invoice->client_id);
			
			foreach ($line_items as $line) {
				$line_item_id = $this->addLine($invoice_id, $line);
				
				// Add line item taxes, if set to taxable IFF tax is enabled
				if (isset($client_settings['enable_tax']) && $client_settings['enable_tax'] == "true" && isset($line['tax']) && $line['tax']) {
					for ($j=0, $num_taxes = count($tax_rules); $j<$num_taxes; $j++)
						$this->addLineTax($line_item_id, $tax_rules[$j]->id, (isset($client_settings['cascade_tax']) && $client_settings['cascade_tax'] == "true"));
				}
			}
			
			if ($this->Input->errors())
				return;
			
			// Increment the used quantity for all coupons used
			foreach ($coupons as $coupon_id => $coupon)
				$this->Coupons->incrementUsage($coupon_id);
			
			// Update invoice totals
			$this->updateTotals($invoice_id);
		}
		
		return $invoice_id;
	}
	
	/**
	 * Generates the line items for a given set of service IDs
	 *
	 * @param array int $service_ids A numerical array of service IDs
	 * @param string $currency The ISO 4217 3-character currency code of the invoice
	 * @param array An array of stdClass object, each representing a coupon
	 * @param boolean $setup_fee_tax True to tax setup fees, false otherwise
	 */
	private function getLinesForServices($service_ids, $currency, &$coupons, $setup_fee_tax) {
		if (!isset($this->Services))
			Loader::loadModels($this, array("Services"));
		if (!isset($this->Companies))
			Loader::loadModels($this, array("Companies"));
		if (!isset($this->PackageOptions))
			Loader::loadModels($this, array("PackageOptions"));
			
		$date = clone $this->Date;
		$date->setTimezone("UTC", Configure::get("Blesta.company_timezone"));
		
		$date_format = null;
		
		$line_items = array();
		$services = array();
		foreach ($service_ids as $service_id) {
			$service = $this->Services->get($service_id);
			
			if (!$service)
				continue;
			
			if ($date_format === null)
				$date_format = $this->Companies->getSetting(Configure::get("Blesta.company_id"), "date_format")->value;
			
			// Fetch the pricing info for this service with amounts converted to the given currency
			$pricing_info = $this->Services->getPricingInfo($service->id, $currency);
			
			// Set the line item description
			$line_item_description = Language::_("Invoices.!line_item.service_created_description", true, (isset($pricing_info->package_name) ? $pricing_info->package_name : ""), (isset($pricing_info->name) ? $pricing_info->name : ""));
			
			// Recurring service, set the date range into the description
			if ($service->package_pricing->period != "onetime" && !empty($service->date_renews)) {
				// Determine the next renew date
				$start_term_date = ($service->date_last_renewed ? $service->date_last_renewed : $service->date_added);
				$line_item_description = Language::_("Invoices.!line_item.service_renew_description", true, (isset($pricing_info->package_name) ? $pricing_info->package_name : ""), (isset($pricing_info->name) ? $pricing_info->name : ""), $date->cast($start_term_date, $date_format), $date->cast($service->date_renews, $date_format));
			}
			
			// Add a line item
			$line_items[] = array(
				'service_id' => $service->id,
				'description' => $line_item_description,
				'qty' => $service->qty,
				'amount' => (isset($pricing_info->price) ? $pricing_info->price : $service->price),
				'tax' => (isset($pricing_info->tax) ? $pricing_info->tax : false)
			);
			
			// Set setup fee line item
			if ($pricing_info && $pricing_info->setup_fee > 0) {
				$line_items[] = array(
					'service_id' => $service->id,
					'description' => Language::_("Invoices.!line_item.service_setup_fee_description", true, (isset($pricing_info->package_name) ? $pricing_info->package_name : ""), (isset($pricing_info->name) ? $pricing_info->name : "")),
					'qty' => 1,
					'amount' => $pricing_info->setup_fee,
					'tax' => ($setup_fee_tax ? $pricing_info->tax : false)
				);
			}
			
			// Set each service configurable option line item
			$service_options = $this->Services->getOptions($service->id);
			foreach ($service_options as $service_option) {
				$package_option = $this->PackageOptions->getByPricingId($service_option->option_pricing_id);
				
				if ($package_option && property_exists($package_option, "value") && property_exists($package_option->value, "pricing") && $package_option->value->pricing) {
					// Get the amount converted to the given currency
					$amount = $this->PackageOptions->getValuePrice($package_option->value->id, $package_option->value->pricing->term, $package_option->value->pricing->period, (isset($pricing_info->currency) ? $pricing_info->currency : ""), $currency);
					
					if ($amount) {
						// Create a line item for this option
						$line_items[] = array(
							'service_id' => $service->id,
							'description' => Language::_("Invoices.!line_item.service_option_renew_description", true, $package_option->label, $package_option->value->name),
							'qty' => $service_option->qty,
							'amount' => $amount->price,
							'tax' => (isset($pricing_info->tax) ? $pricing_info->tax : false)
						);
						
						// Set setup fee line item
						if ($amount->setup_fee > 0) {
							$line_items[] = array(
								'service_id' => $service->id,
								'description' => Language::_("Invoices.!line_item.service_option_setup_fee_description", true, $package_option->label, $package_option->value->name),
								'qty' => 1,
								'amount' => $amount->setup_fee,
								'tax' => ($setup_fee_tax && isset($pricing_info->tax) ? $pricing_info->tax : false)
							);
						}
					}
				}
			}
			
			$services[] = $service;
		}
		
		// Add in any coupon line item discounts
		$coupons = array();
		return array_merge($line_items, $this->Services->buildServiceCouponLineItems($services, $currency, $coupons));
	}
	
	/**
	 * Sets the invoice to closed if the invoice has been paid in full, otherwise
	 * removes any closed status previously set on the invoice. Only invoices with
	 * status of 'active' can be closed.
	 *
	 * @param int $invoice_id The ID of the invoice to close or unclose
	 * @return boolean True if the invoice was closed, false otherwise
	 */
	public function setClosed($invoice_id) {
		// Update totals
		$this->updateTotals($invoice_id);
		
		$invoice = $this->get($invoice_id);
		
		if ($invoice) {
			
			// If rounding is enabled, round the total due to ensure it best matches
			// currency formatting input values (e.g. total due of 2.033 becomes 2.03,
			// thus closing an invoice with payment of 2.03)
			if (Configure::get("Blesta.transactions_validate_apply_round")) {
				$invoice->total = round($invoice->total, 2);
				$invoice->paid = round($invoice->paid, 2);
			}
			
			// Mark as closed if it is an active invoice that was paid in full and has not already
			// been marked as closed
			if ($invoice->paid >= $invoice->total && $invoice->status == "active") {
				$this->Record->where("id", "=", $invoice_id)->where("date_closed", "=", null)->
					update("invoices", array('date_closed'=>$this->dateToUtc(date("c"))));
				
				$this->Events->register("Invoices.setClosed", array("EventsInvoicesCallback", "setClosed"));
				$this->Events->trigger(new EventObject("Invoices.setClosed", compact("invoice_id")));
				
				return true;
			}
			// If not paid in full or not active, remove closed status
			else
				$this->Record->where('id', "=", $invoice_id)->update("invoices", array('date_closed'=>null));
		}
		return false;
	}
	
	/**
	 * Calculates and updates the stored subtotal, total, and amount paid values for the given invoice
	 *
	 * @param int $invoice_id The ID of the invoice to update totals for
	 */
	private function updateTotals($invoice_id) {
		// Fetch current totals
		$subtotal = $this->getSubtotal($invoice_id);
		$total = $this->getTotal($invoice_id);
		$paid = $this->getPaid($invoice_id);
		
		// Update totals
		$this->Record->where("id", "=", $invoice_id)->
			update("invoices", array('subtotal' => $subtotal, 'total' => $total, 'paid' => $paid));
	}
	
	/**
	 * Fetches the given invoice
	 *
	 * @param int $invoice_id The ID of the invoice to fetch
	 * @return mixed A stdClass object containing invoice information, false if no such invoice exists
	 */
	public function get($invoice_id) {
		$this->Record = $this->getInvoice($invoice_id);
		$invoice = $this->Record->fetch();
		
		if ($invoice) {
			$invoice->line_items = $this->getLineItems($invoice_id);
			$invoice->delivery = $this->getDelivery($invoice_id);
			$invoice->meta = $this->getMeta($invoice_id);
			
			$invoice->tax_subtotal = 0;
			$invoice->tax_total = 0;
			$invoice->taxes = array();
			
			// Tally up taxes across all line items
			foreach ($invoice->line_items as $line) {

				// All inclusive tax totals
				$invoice->tax_subtotal += $line->tax_subtotal;
				// All inclusive and exclusive tax totals
				$invoice->tax_total += $line->tax_total;
				// All tax rules applied to the invoice
				foreach ($line->taxes as $tax) {
					if (!isset($invoice->taxes[$tax->level-1])) {
						$invoice->taxes[$tax->level-1] = $tax;
						$invoice->taxes[$tax->level-1]->tax_total = $line->taxes_applied[$tax->level-1]['amount'];
					}
					else
						$invoice->taxes[$tax->level-1]->tax_total += $line->taxes_applied[$tax->level-1]['amount'];
				}
			}
		}
		return $invoice;
	}
	
	/**
	 * Fetches the given recurring invoice
	 *
	 * @param int $invoice_recur_id The ID of the recurring invoice to fetch
	 * @return mixed A stdClass object containing recurring invoice information, false if no such recurring invoice exists
	 */
	public function getRecurring($invoice_recur_id) {
		$this->Record = $this->getRecurringInvoice($invoice_recur_id);
		$invoice = $this->Record->fetch();
		
		if ($invoice) {
			$invoice->line_items = $this->getRecurringLineItems($invoice_recur_id);
			$invoice->delivery = $this->getRecurringDelivery($invoice_recur_id);
			
			$vars = array('currency'=>$invoice->currency,'lines'=>array());
			foreach ($invoice->line_items as $line) {
				$line->tax = $line->taxable ? "true" : "false";
				$vars['lines'][] = (array)$line;
			}
			$totals = $this->calcLineTotals($invoice->client_id, $vars);
			
			$invoice->total = isset($totals['total']) ? $totals['total']['amount'] : 0;
			
			if (isset($totals['tax'])) {
				foreach ($totals['tax'] as $level => $tax) {
					$invoice->taxes[$level] = (object)$tax;
					$invoice->taxes[$level]->tax_total = $tax['amount'];
				}
			}
		}
		return $invoice;
	}
	
	/**
	 * Fetches the recurring invoice record that produced the given invoice ID
	 *
	 * @param int $invoice_id The ID of the invoice created by a recurring invoice
	 * @return mixed A stdClass object representing the recurring invoice, false if no such recurring
	 * invoice exists or the invoice was not created from a recurring invoice
	 */
	public function getRecurringFromInvoices($invoice_id) {
		$invoice = $this->Record->select(array("invoices_recur_created.invoice_recur_id"))->
			from("invoices_recur_created")->
			where("invoice_id", "=", $invoice_id)->fetch();
		
		if ($invoice)
			return $this->getRecurring($invoice->invoice_recur_id);
		return false;
	}
	
	/**
	 * Calculates the amount of tax for each tax rule given that applies to the given line sub total (which is unit cost * quantity).
	 * Also returns the line total including inclusive tax rules as well as the total with all tax rules
	 *
	 * @param float $line_subtotal The subtotal (quanity * unit cost) for the line item
	 * @param array $taxes An array of stdClass objects each representing a tax rule to be applied to the line subtotal
	 * @return array An array containing the following:
	 * 	- tax An array of tax rule applied amounts
	 * 	- tax_subtotal The tax subtotal (all inclusive taxes applied)
	 * 	- tax_total All taxes applied (inclusive and exclusive)
	 * 	- line_total The total for the line including inclusive taxes
	 * 	- line_total_w_tax The total for the line including all taxes (inclusive and exclusive)
	 */
	public function getTaxTotals($line_subtotal, $taxes) {
		$tax = array();
		$tax_subtotal = 0;
		$tax_total = 0;
		
		foreach ($taxes as $tax_rule) {
			$level_index = ($tax_rule->level-1);
			
			// If cascading tax is enabled, and this tax rule level is > 1 apply this tax to the line item including tax level below it
			if ($tax_rule->cascade > 0 && $tax_rule->level > 1 && isset($tax[$level_index-1]))
				$tax_amount = round($tax_rule->amount*($line_subtotal+$tax[$level_index-1]['amount'])/100,2);
			// This is a normal tax, which does not apply to the tax rule below it
			else
				$tax_amount = round($tax_rule->amount*$line_subtotal/100, 2);
			
			// If the tax rule is inclusive, it belongs to the total
			if ($tax_rule->type == "inclusive")
				$tax_subtotal += $tax_amount;
			$tax_total += $tax_amount;
			
			// If a tax is already defined at this level, increment the values
			if (isset($tax[$level_index]))
				$tax_amount += $tax[$level_index]['amount'];
				
			$tax[$level_index] = array('id'=>$tax_rule->id, 'name'=>$tax_rule->name, 'percentage'=>$tax_rule->amount, 'amount'=>$tax_amount);
		}
		unset($tax_rule);
		
		return array('tax'=>$tax, 'tax_subtotal'=>$tax_subtotal, 'tax_total'=>$tax_total, 'line_total'=>$line_subtotal + $tax_subtotal, 'line_total_w_tax'=>$line_subtotal + $tax_total);
	}
	
	/**
	 * Fetches all line items belonging to the given invoice
	 *
	 * @param int $invoice_id The ID of the invoice to fetch line items for
	 * @return array An array of stdClass objects each representing a line item
	 */
	public function getLineItems($invoice_id) {
		$fields = array("id","invoice_id","service_id","description","qty","amount",'qty*amount'=>"subtotal");
		// Fetch all line items belonging to the given invoice
		$lines = $this->Record->select($fields)->from("invoice_lines")->
			where("invoice_lines.invoice_id", "=", $invoice_id)->order(array('order'=>"ASC"))->fetchAll();
		
		// Fetch tax rules for each line item
		foreach ($lines as $i => &$line) {
			$line->taxes = $this->getLineTaxes($line->id);
			
			// calculate the total due for each line item with tax (we already have it without tax)
			$tax_amounts = $this->getTaxTotals($line->subtotal, $line->taxes);
			// Amount of each tax rule applied to the line item
			$line->taxes_applied = $tax_amounts['tax'];
			// All inclusive tax totals
			$line->tax_subtotal = $tax_amounts['tax_subtotal'];
			// All inclusive and exclusive tax totals
			$line->tax_total = $tax_amounts['tax_total'];
			// Total include only inclusive tax rules
			$line->total = $tax_amounts['line_total'];
			// Total including all taxes (inclusive and exclusive)
			$line->total_w_tax = $tax_amounts['line_total_w_tax'];
		}
		return $lines;
	}
	
	/**
	 * Fetches all line items belonging to the given recurring invoice
	 *
	 * @param int $invoice_recur_id The ID of the recurring invoice to fetch line items for
	 * @return array An array of stdClass objects each representing a line item
	 */
	public function getRecurringLineItems($invoice_recur_id) {
		$fields = array("id","invoice_recur_id","description","qty","amount",'qty*amount'=>"subtotal","taxable");
		// Fetch all line items belonging to the given invoice
		return $this->Record->select($fields)->from("invoice_recur_lines")->
			where("invoice_recur_lines.invoice_recur_id", "=", $invoice_recur_id)->order(array('order'=>"ASC"))->fetchAll();
	}
	
	/**
	 * Fetches all tax info attached to the line item
	 *
	 * @param int $invoice_line_id The ID of the invoice line item to fetch tax info for
	 * @return array An array of stdClass objects each representing a tax rule
	 * @see Taxes::getAll()
	 */
	private function getLineTaxes($invoice_line_id) {
		$fields = array("taxes.*", "invoice_line_taxes.cascade");
		return $this->Record->select($fields)->from("invoice_line_taxes")->
			innerJoin("taxes", "invoice_line_taxes.tax_id", "=", "taxes.id", false)->
			where("invoice_line_taxes.line_id", "=", $invoice_line_id)->fetchAll();
	}
	
	/**
	 * Fetches a list of invoices for a client
	 *
	 * @param int $client_id The client ID (optional, default null to get invoices for all clients)
	 * @param string $status The status type of the invoices to fetch (optional, default 'open') one of the following:
	 * 	- open Fetches all active open invoices
	 * 	- closed Fetches all closed invoices
	 * 	- past_due Fetches all active past due invoices
	 * 	- draft Fetches all invoices with a status of "draft"
	 * 	- void Fetches all invoices with a status of "void"
	 * 	- active Fetches all invoices with a status of "active"
	 * 	- to_autodebit Fetches all invoices that are ready to be autodebited now, and which can be with an active client and payment account to do so
	 * 	- pending_autodebit Fetches all invoice that are set to be autodebited in the future, and which have an active client and payment account to do so with
	 * 	- to_print Fetches all paper invoices set to be printed
	 * 	- printed Fetches all paper invoices that have been set as printed
	 * 	- pending Fetches all active invoices that have not been billed for yet
	 * 	- to_deliver Fetches all invoices set to be delivered by a method other than paper (i.e. deliverable invoices not in the list of those "to_print")
	 * 	- all Fetches all invoices
	 * @param int $page The page to return results for (optional, default 1)
	 * @param array $order_by The sort and order conditions (e.g. array('sort_field'=>"ASC"), optional)
	 * @return array An array of stdClass objects containing invoice information, or false if no invoices exist
	 */
	public function getList($client_id=null, $status="open", $page=1, $order_by=array('date_due'=>"ASC")) {
		// If sorting by ID code, use id code sort mode
		if (isset($order_by['id_code']) && Configure::get("Blesta.id_code_sort_mode")) {
			$temp = $order_by['id_code'];
			unset($order_by['id_code']);
			
			foreach ((array)Configure::get("Blesta.id_code_sort_mode") as $key) {
				$order_by[$key] = $temp;
			}
		}
		
		$this->Record = $this->getInvoices($client_id, $status);
		
		// Return the results
		return $this->Record->order($order_by)->
			limit($this->getPerPage(), (max(1, $page) - 1)*$this->getPerPage())->fetchAll();
	}
		
	/**
	 * Returns the total number of invoices returned from Invoices::getClientList(), useful
	 * in constructing pagination for the getList() method.
	 *
	 * @param int $client_id The client ID (optional, default null to get invoice count for all clients)
	 * @param string $status The status type of the invoices to fetch (optional, default 'open') one of the following:
	 * 	- open Fetches all active open invoices
	 * 	- closed Fetches all closed invoices
	 * 	- past_due Fetches all active past due invoices
	 * 	- draft Fetches all invoices with a status of "draft"
	 * 	- void Fetches all invoices with a status of "void"
	 * 	- active Fetches all invoices with a status of "active"
	 * 	- to_autodebit Fetches all invoices that are ready to be autodebited now, and which can be with an active client and payment account to do so
	 * 	- pending_autodebit Fetches all invoice that are set to be autodebited in the future, and which have an active client and payment account to do so with
	 * 	- to_print Fetches all paper invoices set to be printed
	 * 	- printed Fetches all paper invoices that have been set as printed
	 * 	- pending Fetches all active invoices that have not been billed for yet
	 * 	- to_deliver Fetches all invoices set to be delivered by a method other than paper (i.e. deliverable invoices not in the list of those "to_print")
	 * 	- all Fetches all invoices
	 * @return int The total number of invoices
	 * @see Invoices::getList()
	 */
	public function getListCount($client_id=null, $status="open") {
		$this->Record = $this->getInvoices($client_id, $status);
		
		// Return the number of results
		return $this->Record->numResults();
	}
	
	/**
	 * Fetches all invoices for a client
	 *
	 * @param int $client_id The client ID (optional, default null to get invoices for all clients)
	 * @param string $status The status type of the invoices to fetch (optional, default 'open') one of the following:
	 * 	- open Fetches all active open invoices
	 * 	- closed Fetches all closed invoices
	 * 	- past_due Fetches all active past due invoices
	 * 	- draft Fetches all invoices with a status of "draft"
	 * 	- void Fetches all invoices with a status of "void"
	 * 	- active Fetches all invoices with a status of "active"
	 * 	- to_autodebit Fetches all invoices that are ready to be autodebited now, and which can be with an active client and payment account to do so
	 * 	- pending_autodebit Fetches all invoice that are set to be autodebited in the future, and which have an active client and payment account to do so with
	 * 	- to_print Fetches all paper invoices set to be printed
	 * 	- printed Fetches all paper invoices that have been set as printed
	 * 	- pending Fetches all active invoices that have not been billed for yet
	 * 	- to_deliver Fetches all invoices set to be delivered by a method other than paper (i.e. deliverable invoices not in the list of those "to_print")
	 * 	- all Fetches all invoices
	 * @param array $order_by The sort and order conditions (e.g. array('sort_field'=>"ASC"), optional)
	 * @param string $currency The currency code to limit results on (null = any currency)
	 * @return array An array of stdClass objects containing invoice information
	 */
	public function getAll($client_id=null, $status="open", $order_by=array('date_due'=>"ASC"), $currency=null) {
		$this->Record = $this->getInvoices($client_id, $status);
		
		if ($currency !== null)
			$this->Record->where("currency", "=", $currency);
		
		return $this->Record->order($order_by)->fetchAll();
	}
	
	/**
	 * Fetches all invoices for this company that are autodebitable by their respective clients
	 *
	 * @param int $client_group_id The client group ID
	 * @param boolean $pending True to fetch all invoices that will be ready to autodebit in the future, or false to fetch all invoices ready to be autodebited (optional, default false)
	 * @param string $days The number of days before invoices are to be autodebited
	 * 	- autodebit_days_before_due Use the autodebit days before due setting
	 * 	- notice_pending_autodebit Use the autodebit days before due setting plus the notice pending autodebit setting
	 * @return array An array of client IDs, each containing an array of stdClass objects representing invoice information
	 */
	public function getAllAutodebitableInvoices($client_group_id, $pending=false, $days="autodebit_days_before_due") {
		// Fetch all autodebitable open invoices for this company
		$type = "to_autodebit";
		if ($pending)
			$type = "pending_autodebit";
		
		Loader::loadModels($this, array("ClientGroups"));
		
		// Determine the number of days from invoice due date to fetch invoices for
		$options = array();
		$num_days = 0;
		switch ($days) {
			case "notice_pending_autodebit":
				$temp_days = $this->ClientGroups->getSetting($client_group_id, $days);
				// Valid integer given
				if ($temp_days && is_numeric($temp_days->value))
					$num_days += $temp_days->value;
				// no break, add both values up
			case "autodebit_days_before_due":
				$temp_days = $this->ClientGroups->getSetting($client_group_id, "autodebit_days_before_due");
				// Valid integer given
				if ($temp_days && is_numeric($temp_days->value))
					$num_days += $temp_days->value;
				break;
		}
		
		// Set option for autodebit date to be some number of days in the future
		$options = array('autodebit_date' => $this->dateToUtc(strtotime(date("c") . " +" . $num_days . " days")), 'client_group_id' => $client_group_id);
		
		$this->Record = $this->getInvoices(null, $type, $options);
		return $this->Record->order(array('invoices.client_id'=>"ASC"))->fetchAll();
	}
	
	/**
	 * Search invoices
	 *
	 * @param string $query The value to search invoices for
	 * @param int $page The page number of results to fetch (optional, default 1)
	 * @return array An array of invoices that match the search criteria
	 */
	public function search($query, $page=1) {
		$this->Record = $this->searchInvoices($query);
		
		// Set order by clause
		$order_by = array();
		if (Configure::get("Blesta.id_code_sort_mode")) {
			foreach ((array)Configure::get("Blesta.id_code_sort_mode") as $key) {
				$order_by[$key] = "ASC";
			}
		}
		else
			$order_by = array("date_due"=>"ASC");
		
		return $this->Record->order($order_by)->
			limit($this->getPerPage(), (max(1, $page) - 1)*$this->getPerPage())->
			fetchAll();
	}
	
	/**
	 * Return the total number of invoices returned from Invoices::search(), useful
	 * in constructing pagination
	 *
	 * @param string $query The value to search invoices for
	 * @see Invoices::search()
	 */
	public function getSearchCount($query) {
		$this->Record = $this->searchInvoices($query);
		return $this->Record->numResults();
	}
	
	/**
	 * Partially constructs the query for searching invoices
	 *
	 * @param string $query The value to search invoices for
	 * @return Record The partially constructed query Record object
	 * @see Invoices::search(), Invoices::getSearchCount()
	 */
	private function searchInvoices($query) {
		$this->Record = $this->getInvoices(null, "all");
		
		$sub_query_sql = $this->Record->get();
		$values = $this->Record->values;
		$this->Record->reset();
		
		$this->Record->select()->appendValues($values)->from(array($sub_query_sql => "temp"))->
			like("CONVERT(temp.id_code USING utf8)", "%" . $query . "%", true, false)->
			orLike("temp.client_id_code", "%" . $query . "%")->
			orLike("temp.client_company", "%" . $query . "%")->
			orLike("CONCAT_WS(' ', temp.client_first_name, temp.client_first_name)", "%" . $query . "%", true, false)->
			orLike("temp.client_address1", "%" . $query . "%")->
			orLike("temp.client_email", "%" . $query . "%");
		
		return $this->Record;
	}
	
	/**
	 * Fetches all recurring invoices for a client
	 *
	 * @param int $client_id The client ID (optional, default null to get recurring invoices for all clients)
	 * @return array An array of stdClass objects containing recurring invoice information
	 */
	public function getAllRecurring($client_id=null) {
		return $this->getRecurringInvoices($client_id)->fetchAll();
	}
	
	/**
	 * Fetches all renewing recurring invoices. That is, where the date_renews
	 * is <= current date + the maximum invoice days before renewal for the
	 * current client group and the recurring invoice has not already created all
	 * invoices to be created.
	 *
	 * @param int $client_group_id The ID of the client group whose renewing recurring invoices to fetch
	 * @return array An array of stdClass objects, each representing a recurring invoice
	 */
	public function getAllRenewingRecurring($client_group_id) {
		// Get the invoice days before renewal
		Loader::loadModels($this, array("ClientGroups"));
		$inv_days_before_renewal = $this->ClientGroups->getSetting($client_group_id, "inv_days_before_renewal");
		
		// Set the date at which invoices would be created based on the
		// renew date and invoice days before renewal, and encompass the entire day
		$invoice_date = date("Y-m-d 23:59:59", strtotime(date("c") . " +" . abs((int)$inv_days_before_renewal->value) . " days"));
		
		// Get all recurring invoices set to renew today
		$this->Record = $this->getRecurringInvoices(null, false)->
			where("client_groups.id", "=", $client_group_id)->
			where("invoices_recur.date_renews", "<=", $this->dateToUtc($invoice_date))->
			where("invoices_recur.term", ">", "0")->
			group("invoices_recur.id");
		
		$sub_query = $this->Record->get();
		$values = $this->Record->values;
		$this->Record->reset();
		
		// Filter for those that have not reached their recur limit up to this date
		$this->Record->select()->from(array($sub_query=>"ri"))->
			appendValues($values)->
			having("ri.duration", "=", null)->
			orHaving("ri.duration", ">", "IFNULL(ri.count,?)", false)->
			appendValues(array(0));
		
		return $this->Record->fetchAll();
	}
	
	/**
	 * Fetches a list of recurring invoices for a client
	 *
	 * @param int $client_id The client ID (optional, default null to get recurring invoices for all clients)
	 * @param int $page The page to return results for
	 * @param array $order The fields and direction to order by. Key/value pairs where key is the field and value is the direction (asc/desc)
	 * @return array An array of stdClass objects containing recurring invoice information
	 */
	public function getRecurringList($client_id=null, $page=1, array $order=array('id'=>"asc")) {
		$this->Record = $this->getRecurringInvoices($client_id);

		// If sorting by term, sort by both term and period
		if (isset($order['term'])) {
			$temp_order_by = $order;
			
			$order = array('period'=>$order['term'], 'term'=>$order['term']);
			
			// Sort by any other fields given as well
			foreach ($temp_order_by as $sort=>$ord) {
				if ($sort == "term")
					continue;
				
				$order[$sort] = $ord;
			}
		}
		
		// Return the results
		return $this->Record->order($order)->limit($this->getPerPage(), (max(1, $page) - 1)*$this->getPerPage())->fetchAll();
	}
	
	/**
	 * Return the total number of recurring invoices returned from Invoices::getRecurringList(), useful
	 * in constructing pagination for the getList() method.
	 *
	 * @param int $client_id The client ID
	 * @return int The total number of recurring invoices
	 * @see Invoices::getRecurringList()
	 */
	public function getRecurringListCount($client_id) {
		$this->Record = $this->getRecurringInvoices($client_id);
		
		// Return the number of results
		return $this->Record->numResults();
	}

	/**
	 * Retrieves a list of recurring invoice periods
	 *
	 * @return array Key=>value pairs of recurring invoice pricing periods
	 */
	public function getPricingPeriods() {
		return array(
			"day"=>$this->_("Invoices.getPricingPeriods.day"),
			"week"=>$this->_("Invoices.getPricingPeriods.week"),
			"month"=>$this->_("Invoices.getPricingPeriods.month"),
			"year"=>$this->_("Invoices.getPricingPeriods.year")
		);
	}
	
	/**
	 * Retrieves the date that the given invoice should be autodebited. This considers
	 * current client settings and autodebit accounts.
	 *
	 * @param int $invoice_id The ID of the invoice
	 * @return mixed A string representing the UTC date that this invoice will be autodebited, or false if the invoice cannot be autodebited
	 */
	public function getAutodebitDate($invoice_id) {
		// Check that the client has a CC or ACH account set for autodebit (only 1 could be)
		$invoice = $this->Record->select("invoices.*")->from("invoices")->
			innerJoin("client_settings", "client_settings.client_id", "=", "invoices.client_id", false)->
				on("ach_client_account.type", "=", "ach")->
			leftJoin(array("client_account"=>"ach_client_account"), "ach_client_account.client_id", "=", "invoices.client_id", false)->
				on("cc_client_account.type", "=", "cc")->
			leftJoin(array("client_account"=>"cc_client_account"), "cc_client_account.client_id", "=", "invoices.client_id", false)->
			// Check that the found CC or ACH account is active
			leftJoin("accounts_ach", "accounts_ach.id", "=", "ach_client_account.account_id", false)->
			leftJoin("accounts_cc", "accounts_cc.id", "=", "cc_client_account.account_id", false)->
			open()->
				where("accounts_ach.status", "=", "active")->
				orWhere("accounts_cc.status", "=", "active")->
			close()->
			// Require autodebit on client account
			where("client_settings.key", "=", "autodebit")->
			where("client_settings.value", "=", "true")->
			where("invoices.status", "=", "active")->
			where("invoices.id", "=", $invoice_id)->
			fetch();
		
		// Autodebit is enabled
		if ($invoice) {
			// An autodebit date is set on the invoice itself
			if ($invoice->date_autodebit)
				return $invoice->date_autodebit;
			
			// Get the autodebit days before due setting
			if (!isset($this->SettingsCollection))
				Loader::loadComponents($this, array("SettingsCollection"));
			
			$autodebit_days_before_due = $this->SettingsCollection->fetchClientSetting($invoice->client_id, null, "autodebit_days_before_due");
			
			if (isset($autodebit_days_before_due['value']) && is_numeric($autodebit_days_before_due['value']))
				return date("Y-m-d H:i:s", strtotime($invoice->date_due . " -" . $autodebit_days_before_due['value'] . " days"));
		}
		
		return false;
	}
	
	/**
	 * Partially constructs the query required by Invoices::get() and others
	 *
	 * @param int $invoice_id The ID of the invoice to fetch
	 * @return Record The partially constructed query Record object
	 */
	private function getInvoice($invoice_id) {
		
		$fields = array("invoices.*",
			"REPLACE(invoices.id_format, ?, invoices.id_value)" => "id_code",
			"invoice_delivery.date_sent" => "delivery_date_sent"
		);
		
		// Fetch the invoices along with total due and total paid, calculate total remaining on the fly
		$this->Record->select($fields)->select(array("invoices.total-IFNULL(invoices.paid,0)"=>"due"), false)->
			appendValues(array($this->replacement_keys['invoices']['ID_VALUE_TAG']))->
			from("invoices")->
			on("invoice_delivery.date_sent", "!=", null)->leftJoin("invoice_delivery", "invoice_delivery.invoice_id", "=", "invoices.id", false)->
			where("invoices.id", "=", $invoice_id)->
			group("invoices.id");
			
		return $this->Record;
	}
	
	/**
	 * Partially constructs the query required by Invoices::getList() and
	 * Invoices::getListCount()
	 *
	 * @param int $client_id The client ID (optional, default null to fetch invoices for all clients)
	 * @param string $status The status type of the invoices to fetch (optional, default 'open') one of the following:
	 * 	- open Fetches all active open invoices
	 * 	- closed Fetches all closed invoices
	 * 	- past_due Fetches all active past due invoices
	 * 	- draft Fetches all invoices with a status of "draft"
	 * 	- void Fetches all invoices with a status of "void"
	 * 	- active Fetches all invoices with a status of "active"
	 * 	- to_autodebit Fetches all invoices that are ready to be autodebited now, and which can be with an active client and payment account to do so
	 * 	- pending_autodebit Fetches all invoice that are set to be autodebited in the future, and which have an active client and payment account to do so with
	 * 	- to_print Fetches all paper invoices set to be printed
	 * 	- printed Fetches all paper invoices that have been set as printed
	 * 	- pending Fetches all active invoices that have not been billed for yet
	 * 	- to_deliver Fetches all invoices set to be delivered by a method other than paper (i.e. deliverable invoices not in the list of those "to_print")
	 * 	- all Fetches all invoices
	 * 	@param array $options A list of additional options
	 *	- autodebit_date The autodebit date to fetch invoices; for use with the "to_autodebit" or "pending_autodebit" statuses
	 *	- client_group_id The ID of the client group to filter invoices on
	 * @return Record The partially constructed query Record object
	 */
	private function getInvoices($client_id=null, $status="open", array $options=array()) {
		$fields = array("invoices.*",
			'REPLACE(invoices.id_format, ?, invoices.id_value)' => "id_code",
			'invoice_delivery.date_sent' => "delivery_date_sent",
			'REPLACE(clients.id_format, ?, clients.id_value)' => "client_id_code",
			'contacts.first_name'=>"client_first_name",
			'contacts.last_name'=>"client_last_name",
			'contacts.company'=>"client_company",
			'contacts.address1'=>"client_address1",
			'contacts.email'=>"client_email"
		);
		
		// Filter based on company ID
		$company_id = Configure::get("Blesta.company_id");
		
		// Fetch the invoices along with total due and total paid, calculate total remaining on the fly
		$this->Record->select($fields)->select(array("invoices.total-IFNULL(invoices.paid,0)" => "due"), false)->
			appendValues(array($this->replacement_keys['invoices']['ID_VALUE_TAG'],$this->replacement_keys['clients']['ID_VALUE_TAG']))->
			from("invoices")->
			innerJoin("clients", "clients.id", "=", "invoices.client_id", false)->
			innerJoin("client_groups", "client_groups.id", "=", "clients.client_group_id", false)->
			on("contacts.contact_type", "=", "primary")->
			innerJoin("contacts", "contacts.client_id", "=", "clients.id", false);

		// Require date_sent non-null if status is something other than "to_print" or "to_deliver"
		// so that we only fetch sent delivery data
		if ($status != "to_print" && $status != "to_deliver")
			$this->Record->on("invoice_delivery.date_sent", "!=", null);
		
		$this->Record->leftJoin("invoice_delivery", "invoice_delivery.invoice_id", "=", "invoices.id", false);
		
		// Negate for $status = 'open' or 'closed'
		$negate = false;

		switch($status) {
			case "closed":
				// Get closed invoices
				$negate = true;
			case "open":
				// Get open invoices
				
				// Check the date is open/closed
				$this->Record->where("invoices.date_closed", ($negate ? "!=" : "="), null)->
					where("invoices.status", "=", "active")->
					where("invoices.date_billed", "<=", $this->dateToUtc(date("c")));
				break;
			case "pending":
				// Get invoices pending date billed
				$this->Record->where("invoices.date_billed", ">", $this->dateToUtc(date("c")))->
					where("invoices.status", "=", "active");
				break;
			case "past_due":
				// Get past due invoices
				
				// Check date is past due and invoice is not closed
				$this->Record->where("invoices.date_due", "<", $this->dateToUtc(date("c")))->
					where("invoices.date_closed", "=", null)->
					where("invoices.status", "=", "active");					
				break;
			case "pending_autodebit":
				// Get all set to autodebit in the future
				$pending_autodebit = true;
			case "to_autodebit":
				// Get all invoices set to be autodebited (i.e. which are not set to be autodebited in the future)
				// and where the client is set to be autodebited and has a payment account set to do so with
				// and where the autodebit payment account is active
				
				$now = $this->dateToUtc(date("c"));
				// Set the autodebit date to use
				$autodebit_date = isset($options['autodebit_date']) ? $options['autodebit_date'] : $now;
				
				// Check that the client has a CC or ACH account set for autodebit (only 1 could be)
				$this->Record->innerJoin("client_settings", "client_settings.client_id", "=", "clients.id", false)->
						on("ach_client_account.type", "=", "ach")->
					leftJoin(array("client_account"=>"ach_client_account"), "ach_client_account.client_id", "=", "clients.id", false)->
						on("cc_client_account.type", "=", "cc")->
					leftJoin(array("client_account"=>"cc_client_account"), "cc_client_account.client_id", "=", "clients.id", false)->
					// Check that the found CC or ACH account is active
					leftJoin("accounts_ach", "accounts_ach.id", "=", "ach_client_account.account_id", false)->
					leftJoin("accounts_cc", "accounts_cc.id", "=", "cc_client_account.account_id", false)->
					open()->
						where("accounts_ach.status", "=", "active")->
						orWhere("accounts_cc.status", "=", "active")->
					close()->
					// Require autodebit on client account
					where("client_settings.key", "=", "autodebit")->
					where("client_settings.value", "=", "true")->
					// The invoice must be active and autodebit may not be set in the future (unless pending)
					where("invoices.date_closed", "=", null)->
					where("invoices.status", "=", "active")->
					where("invoices.date_billed", "<=", $now);
					
					if (isset($pending_autodebit) && $pending_autodebit) {
						// Filter on invoices that will be autodebited in the future
						$this->Record->
							open()->
								open()->
									like("invoices.date_due", date("Y-m-d%", strtotime($autodebit_date)))->
									where("invoices.date_autodebit", "=", null)->
								close()->
								orLike("invoices.date_autodebit", date("Y-m-d%", strtotime($autodebit_date)))->
							close();
					}
					else {
						// Filter on invoices that are ready to be autodebited now
						$this->Record->
							open()->
								like("invoices.date_autodebit", date("Y-m-d%", strtotime($autodebit_date)))->
								open()->
									orWhere("invoices.date_autodebit", "=", null)->
									where("invoices.date_due", "<=", date("Y-m-d 23:59:59", strtotime($autodebit_date)))->
								close()->
							close();
					}
					
				break;
			case "to_print":
				// Get invoices pending printing
				$this->Record->where("invoice_delivery.method", "=", "paper")->
					where("invoices.status", "=", "active")->
					where("invoices.date_billed", "<=", $this->dateToUtc(date("c")))->
					where("invoice_delivery.date_sent", "=", null);
				break;
			case "printed":
				// Get printed invoices
				$this->Record->where("invoice_delivery.method", "=", "paper");
				break;
			case "to_deliver":
				// Get invoices pending deliver
				$this->Record->where("invoice_delivery.method", "!=", "paper")->
					where("invoices.status", "=", "active")->
					where("invoices.date_billed", "<=", $this->dateToUtc(date("c")))->
					where("invoice_delivery.method", "!=", null)->
					where("invoice_delivery.date_sent", "=", null);
				break;
			case "all":
				// Do not filter on status
				break;
			default:
				// Get invoices by status (active, draft, void)
				$this->Record->where("invoices.status", "=", $status);				
				break;
		}
		
		// Filter by client group ID
		if (isset($options['client_group_id']))
			$this->Record->where("client_groups.id", "=", $options['client_group_id']);
		
		// Filter by company
		$this->Record->where("client_groups.company_id", "=", $company_id);
		
		// Get for a specific client
		if ($client_id != null)
			$this->Record->where("invoices.client_id", "=", $client_id);
			
		$this->Record->group("invoices.id");
		
		return $this->Record;
	}
	
	/**
	 * Partially constructs the query required by Invoices::getRecurring() and others
	 *
	 * @param int $invoice_recur_id The recurring invoice ID to fetch
	 * @return Record The partially constructed query Record object
	 */
	private function getRecurringInvoice($invoice_recur_id) {
		$count = new Record();
		$count->select(array("invoice_recur_id", "COUNT(*)"=>"count"))->from("invoices_recur_created")->
			group("invoices_recur_created.invoice_recur_id");
		$sub_query = $count->get();
		$values = $count->values;
		
		$this->Record->values = $values;
		
		$fields = array("invoices_recur.*", "IFNULL(temp_count.count,?)"=>"count", "SUM(invoice_recur_lines.amount*invoice_recur_lines.qty)"=>"subtotal",
			"MAX(invoice_recur_lines.taxable)"=>"taxable");
		
		$this->Record->select($fields)->appendValues(array(0))->from("invoices_recur")->
			leftJoin(array($sub_query=>"temp_count"), "temp_count.invoice_recur_id", "=", "invoices_recur.id", false)->
			leftJoin("invoice_recur_lines", "invoices_recur.id", "=", "invoice_recur_lines.invoice_recur_id", false)->
			where("invoices_recur.id", "=", $invoice_recur_id)->group("invoices_recur.id");
			
		return $this->Record;
	}
	
	/**
	 * Partially constructs the query required by both Invoices::getRecurringList() and
	 * Invoices::getRecurringListCount()
	 *
	 * @param int $client_id The client ID
	 * @param boolean $group True to group the query as required, false to not group at all (grouping should still be done)
	 * @return Record The partially constructed query Record object
	 */
	private function getRecurringInvoices($client_id=null, $group=true) {
		$count = new Record();
		$count->select(array("invoice_recur_id", "COUNT(*)"=>"count"))->from("invoices_recur_created")->
			group("invoices_recur_created.invoice_recur_id");
		$sub_query = $count->get();
		$values = $count->values;
		
		$this->Record->values = $values;
		
		$fields = array("invoices_recur.*", "IFNULL(temp_count.count,?)"=>"count", "SUM(invoice_recur_lines.amount*invoice_recur_lines.qty)"=>"subtotal",
			"MAX(invoice_recur_lines.taxable)"=>"taxable",
			'REPLACE(clients.id_format, ?, clients.id_value)' => "client_id_code",
			'contacts.first_name'=>"client_first_name",
			'contacts.last_name'=>"client_last_name",
			'contacts.company'=>"client_company");
		
		// Filter based on company ID
		$company_id = Configure::get("Blesta.company_id");
		
		$this->Record->select($fields)->appendValues(array(0, $this->replacement_keys['clients']['ID_VALUE_TAG']))->
			from("invoices_recur")->
			leftJoin(array($sub_query=>"temp_count"), "temp_count.invoice_recur_id", "=", "invoices_recur.id", false)->
			leftJoin("invoice_recur_lines", "invoices_recur.id", "=", "invoice_recur_lines.invoice_recur_id", false)->
			innerJoin("clients", "clients.id", "=", "invoices_recur.client_id", false)->
			innerJoin("client_groups", "client_groups.id", "=", "clients.client_group_id", false)->
			on("contacts.contact_type", "=", "primary")->
			innerJoin("contacts", "contacts.client_id", "=", "clients.id", false)->
			where("client_groups.company_id", "=", $company_id);
			
		if ($client_id != null)
			$this->Record->where("invoices_recur.client_id", "=", $client_id);
		
		if ($group)
			$this->Record->group("invoices_recur.id");
		
		return $this->Record;
	}
	
	/**
	 * Retrieves the previous due amount for the given client in the given currency
	 *
	 * @param int $client_id The client ID
	 * @param string $currency The ISO 4217 3-character currency code
	 * @return float The previous amount due for this client
	 */
	private function getPreviousDue($client_id, $currency) {
		// Get sum of all open invoices
		$total_due = $this->amountDue($client_id, $currency);
		
		// Get sum of all transactions applied for all invoices
		$amount_applied = $this->Record->select(array("SUM(transaction_applied.amount)"=>"total"))->from("transaction_applied")->
			innerJoin("invoices", "invoices.id", "=", "transaction_applied.invoice_id", false)->
			where("invoices.status", "=", "active")->where("invoices.currency", "=", $currency)->
			where("invoices.client_id", "=", $client_id)->where("invoices.date_closed", "=", null)->
			where("invoices.date_billed", "<=", $this->dateToUtc(date("c")))->group("invoices.client_id")->fetch();
		
		if ($amount_applied)
			return max(0, ($total_due - $amount_applied->total));
		return max(0, $total_due);

	}
	
	/**
	 * Retrieves the number of invoices given an invoice status for the given client
	 *
	 * @param int $client_id The client ID (optional, default null to get invoice count for company)
	 * @param string $status The status type of the invoices to fetch (optional, default 'open') one of the following:
	 * 	- open Fetches all active open invoices
	 * 	- closed Fetches all closed invoices
	 * 	- past_due Fetches all active past due invoices
	 * 	- draft Fetches all invoices with a status of "draft"
	 * 	- void Fetches all invoices with a status of "void"
	 * 	- active Fetches all invoices with a status of "active"
	 * 	- to_print Fetches all paper invoices set to be printed
	 * 	- printed Fetches all paper invoices that have been set as printed
	 * 	- pending Fetches all active invoices that have not been billed for yet
	 * 	- to_deliver Fetches all invoices set to be delivered by a method other than paper (i.e. deliverable invoices not in the list of those "to_print")
	 * @return int The number of invoices of type $status for $client_id
	 */
	public function getStatusCount($client_id=null, $status="open") {
		
		$this->Record->select(array("COUNT(invoices.id)"=>"status"))->from("invoices")->
			innerJoin("clients", "clients.id", "=", "invoices.client_id", false)->
			innerJoin("client_groups", "client_groups.id", "=", "clients.client_group_id", false);
			
		// Negate for $status = 'open' or 'closed'
		$negate = false;
		
		switch($status) {
			case "closed":
				// Get closed invoices
				$negate = true;
			case "open":
				// Get open invoices
				
				// Check the date is open/closed
				$this->Record->where("invoices.date_closed", ($negate ? "!=" : "="), null)->
					where("invoices.status", "=", "active")->
					where("invoices.date_billed", "<=", $this->dateToUtc(date("c")));
				break;
			case "pending":
				// Get invoices pending date billed
				$this->Record->where("invoices.date_billed", ">", $this->dateToUtc(date("c")))->
					where("invoices.status", "=", "active");
				break;
			case "past_due":
				// Get past due invoices
				
				// Check date is past due and invoice is not closed
				$cur_date = date("Y-m-d H:i:s");
				$this->Record->where("invoices.date_due", "<", $cur_date)->
					where("invoices.date_closed", "=", null)->
					where("invoices.status", "=", "active");					
				break;
			case "to_print";
				// Get invoices pending printing
				$this->Record->innerJoin("invoice_delivery", "invoice_delivery.invoice_id", "=", "invoices.id", false)->
					where("invoice_delivery.method", "=", "paper")->
					where("invoices.status", "=", "active")->
					where("invoices.date_billed", "<=", $this->dateToUtc(date("c")))->
					where("invoice_delivery.date_sent", "=", null);
				break;
			case "printed":
				// Get printed invoices
				$this->Record->on("invoice_delivery.date_sent", "!=", null)->
					innerJoin("invoice_delivery", "invoice_delivery.invoice_id", "=", "invoices.id", false)->
					where("invoice_delivery.method", "=", "paper");
				break;
			case "to_deliver":
				// Get invoices pending deliver
				$this->Record->innerJoin("invoice_delivery", "invoice_delivery.invoice_id", "=", "invoices.id", false)->
					where("invoices.status", "=", "active")->
					where("invoices.date_billed", "<=", $this->dateToUtc(date("c")))->
					where("invoice_delivery.method", "!=", "paper")->
					where("invoice_delivery.method", "!=", null)->
					where("invoice_delivery.date_sent", "=", null);
				break;
			default:
				// Get invoices by status (active, draft, void)
				$this->Record->where("invoices.status", "=", $status);				
				break;
		}
		
		// Set company ID
		$this->Record->where("client_groups.company_id", "=", Configure::get("Blesta.company_id"));
		
		// Get a specific client
		if ($client_id != null)
			$this->Record->where("invoices.client_id", "=", $client_id);
		
		$count = $this->Record->fetch();
		if ($count)
			return $count->status;
		return 0;
	}
	
	/**
	 * Retrieves the number of recurring invoices for the given client
	 *
	 * @param int $client_id The client ID (optional, default null to get recurring invoice count for company)
	 * @return int The number of recurring invoices for $client_id
	 */
	public function getRecurringCount($client_id=null) {
		$this->Record->select(array("COUNT(invoices_recur.id)"=>"recur_count"))->from("invoices_recur")->
			innerJoin("clients", "clients.id", "=", "invoices_recur.client_id", false)->
			innerJoin("client_groups", "client_groups.id", "=", "clients.client_group_id", false)->
			where("client_groups.company_id", "=", Configure::get("Blesta.company_id"));
			
		// Get a specific client
		if ($client_id != null)
			$this->Record->where("invoices_recur.client_id", "=", $client_id);
			
		$count = $this->Record->fetch();
		if ($count)
			return $count->recur_count;
		return 0;
	}
	
	/**
	 * Updates $vars with the subqueries to properly set the previous_due, id_format, and id_value fields
	 * when creating an invoice or converting a draft to an active invoice
	 *
	 * @param array $vars An array of invoice data from Invoices::add() or Invoices::edit()
	 * @param array $client_settings An array of client settings
	 * @return array An array of invoice data now including the proper subqueries for setting the previous_due, id_format and id_value fields
	 */
	private function getNextInvoiceVars(array $vars, array $client_settings) {
		
		// Get the previous amount due
		$vars['previous_due'] = $this->getPreviousDue($vars['client_id'], $vars['currency']);
		// Set the id format accordingly (for drafts or regular invoices), also replace the {year} tag with the appropriate year
		// to ensure the id_value is calculated appropriately on a year-by-year basis
		$vars['id_format'] = str_ireplace("{year}", $this->Date->format("Y"), (isset($vars['status']) && $vars['status'] == "draft" ? $client_settings['inv_draft_format'] : $client_settings['inv_format']));
		
		// Creates subquery to calculate the next invoice ID value on the fly
		$sub_query = new Record();

		/*
		$values = array($client_settings['inv_start'], $client_settings['inv_increment'],
			$client_settings['inv_start'], $client_settings['inv_increment'],
			$client_settings['inv_start'], $client_settings['inv_pad_size'], $client_settings['inv_pad_str']);
		*/
		$values = array($client_settings['inv_start'], $client_settings['inv_increment'],
			$client_settings['inv_start']);
		
		//$sub_query->select(array("LPAD(IFNULL(GREATEST(MAX(t1.id_value),?)+?,?), " .
		//	"GREATEST(CHAR_LENGTH(IFNULL(MAX(t1.id_value)+?,?)),?),?)"), false)->
		$sub_query->select(array("IFNULL(GREATEST(MAX(t1.id_value),?)+?,?)"), false)->
			appendValues($values)->
			from(array("invoices"=>"t1"))->
			innerJoin("clients", "clients.id", "=", "t1.client_id", false)->
			innerJoin("client_groups", "client_groups.id", "=", "clients.client_group_id", false)->
			where("client_groups.company_id", "=", Configure::get("Blesta.company_id"))->
			where("t1.id_format", "=", $vars['id_format']);
		// run get on the query so $sub_query->values are built
		$sub_query_string = $sub_query->get();
		
		// Convert subquery into sub-sub query to force MySQL to create a temporary table
		// to avoid conflicts with reading/writing from the "invoices" table simultaneously
		$query = new Record();
		$query->values = $sub_query->values;
		$query->select("t11.*")->from(array($sub_query_string=>"t11"));
		
		// id_value will be calculated on the fly using a subquery
		$vars['id_value'] = $query;
		
		return $vars;
	}
	
	/**
	 * Fetches all invoice delivery methods this invoice is assigned
	 *
	 * @param int $invoice_id The ID of the invoice
	 * @param boolean $sent True to get only invoice delivery records that have been sent, or false to get only delivery records that have not been sent (optional, defaults to fetch all)
	 * @return array An array of stdClass objects containing invoice delivery log information
	 */
	public function getDelivery($invoice_id, $sent=null) {
		$this->Record->select()->from("invoice_delivery")->
			where("invoice_id", "=", $invoice_id);
		
		// Filter on whether the invoice has been delivered
		if ($sent)
			$this->Record->where("date_sent", "!=", null);
		elseif ($sent === false)
			$this->Record->where("date_sent", "=", null);
		
		return $this->Record->fetchAll();
	}
	
	/**
	 * Fetches all invoice delivery records assigned to each of the given invoice IDs
	 *
	 * @param array $invoice_ids A list of invoice IDs (optional)
	 * @param string $delivery_method The delivery method to filter by (e.g. "email"), (optional)
	 * @param string $status The delivery status, either "all" for all, "unsent" for deliveries not marked sent, or "sent" for deliveries marked sent (optional, default "all")
	 * @return array An array of stdClass objects containing invoice delivery log information
	 */
	public function getAllDelivery($invoice_ids=null, $delivery_method=null, $status="all") {
		$this->Record->select()->from("invoice_delivery");
		
		if ($invoice_ids && is_array($invoice_ids))
			$this->Record->where("invoice_id", "in", $invoice_ids);
		
		if ($delivery_method)
			$this->Record->where("method", "=", $delivery_method);
		
		// Filter on whether the delivery has been sent already
		if (in_array($status, array("unsent", "sent"))) {
			$operator = ($status == "unsent" ? "=" : "!=");
			$this->Record->where("date_sent", $operator, null);
		}
		
		return $this->Record->fetchAll();
	}
	
	/**
	 * Fetches all invoice delivery methods this recurring invoice is assigned
	 *
	 * @param int $invoice_recur_id The ID of the recurring invoice
	 * @return array An array of stdClass objects containing invoice delivery log information
	 */
	public function getRecurringDelivery($invoice_recur_id) {
		return $this->Record->select(array("id","invoice_recur_id","method"))->
			from("invoice_recur_delivery")->where("invoice_recur_id", "=", $invoice_recur_id)->fetchAll();
	}
	
	/**
	 * Adds the invoice delivery status for the given invoice
	 *
	 * @param int $invoice_id The ID of the invoice to update delivery status for
	 * @param array $vars An array of invoice delivery information including:
	 * 	- method The delivery method
	 * @param int $client_id The ID of the client to add the delivery method under
	 * @return int The invoice delivery ID, void on error
	 */
	public function addDelivery($invoice_id, array $vars, $client_id) {
		$delivery_methods = $this->getDeliveryMethods($client_id);
		$rules = array(
			'invoice_id' => array(
				'exists' => array(
					'rule' => array(array($this, "validateExists"), "id", "invoices"),
					'message' => $this->_("Invoices.!error.invoice_id.exists")
				)
			),
			'method' => array(
				'exists' => array(
					'rule' => array("array_key_exists", $delivery_methods),
					'message' => $this->_("Invoices.!error.method.exists")
				)
			)
		);
		
		$this->Input->setRules($rules);
		
		$vars['invoice_id'] = $invoice_id;
		
		if ($this->Input->validates($vars)) {
			$fields = array("invoice_id", "method");
			$this->Record->insert("invoice_delivery", $vars, $fields);
			return $this->Record->lastInsertId();
		}
	}
	
	
	/**
	 * Adds the invoice delivery status for the given recurring invoice
	 *
	 * @param int $invoice_recur_id The ID of the recurring invoice to update delivery status for
	 * @param array $vars An array of invoice delivery information including:
	 * 	- method The delivery method
	 * @param int $client_id The ID of the client to add the delivery method under
	 * @return int The recurring invoice delivery ID, void on error
	 */
	public function addRecurringDelivery($invoice_recur_id, array $vars, $client_id) {
		$delivery_methods = $this->getDeliveryMethods($client_id);
		$rules = array(
			'invoice_recur_id' => array(
				'exists' => array(
					'rule' => array(array($this, "validateExists"), "id", "invoices_recur"),
					'message' => $this->_("Invoices.!error.invoice_recur_id.exists")
				)
			),
			'method[]' => array(
				'exists' => array(
					'rule' => array("in_array", $delivery_methods),
					'message' => $this->_("Invoices.!error.method.exists")
				)
			)
		);
		
		$this->Input->setRules($rules);
		
		$vars['invoice_recur_id'] = $invoice_recur_id;
		
		if ($this->Input->validates($vars)) {
			$fields = array("invoice_recur_id", "method");
			$this->Record->insert("invoice_recur_delivery", $vars, $fields);
			return $this->Record->lastInsertId();
		}
	}
	
	/**
	 * Fetches a list of invoice deliveries for the currently active company
	 *
	 * @param string $method The delivery method to filter by (optional, default null for all)
	 * @param int $page The page to return results for (optional, default 1)
	 * @param array $order_by The sort and order conditions (e.g. array('sort_field'=>"ASC"), optional)
	 */
	public function getDeliveryList($method=null, $page=1, array $order_by = array('date_sent' => "DESC")) {
		$this->Record = $this->getInvoiceDeliveries($method);
		
		// If sorting by ID code, use id code sort mode
		if (isset($order_by['id_code']) && Configure::get("Blesta.id_code_sort_mode")) {
			$temp = $order_by['id_code'];
			unset($order_by['id_code']);
			
			foreach ((array)Configure::get("Blesta.id_code_sort_mode") as $key) {
				$order_by[$key] = $temp;
			}
		}
		
		if ($order_by)
			$this->Record->order($order_by);
		
		return $this->Record->limit($this->getPerPage(), (max(1, $page) - 1)*$this->getPerPage())->fetchAll();
	}
	
	/**
	 * Retrieves the total number of invoice deliveries for the currently active company
	 *
	 * @param string $method The delivery method to filter by (optional, default null for all)
	 * @return int The total number of invoice deliveries
	 */
	public function getDeliveryListCount($method=null) {
		return $this->getInvoiceDeliveries($method)->numResults();
	}
	
	/**
	 * Partially constructs a Record object for fetching invoice deliveries
	 *
	 * @return Record A partially-constructed Record object for fetching invoice deliveries
	 */
	private function getInvoiceDeliveries($method=null) {
		$fields = array("invoice_delivery.*", "contacts.first_name", "contacts.last_name",
			'contacts.company', 'clients.id' => "client_id",
			'REPLACE(invoices.id_format, ?, invoices.id_value)' => "invoice_id_code",
			'REPLACE(clients.id_format, ?, clients.id_value)' => "client_id_code",
		);
		
		$this->Record->select($fields)->from("invoice_delivery")->
			appendValues(array($this->replacement_keys['invoices']['ID_VALUE_TAG'], $this->replacement_keys['clients']['ID_VALUE_TAG']))->
			innerJoin("invoices", "invoices.id", "=", "invoice_delivery.invoice_id", false)->
			innerJoin("clients", "clients.id", "=", "invoices.client_id", false)->
				on("contacts.contact_type", "=", "primary")->
			innerJoin("contacts", "contacts.client_id", "=", "clients.id", false)->
				on("client_groups.company_id", "=", Configure::get("Blesta.company_id"))->
			innerJoin("client_groups", "client_groups.id", "=", "clients.client_group_id", false);
		
		if ($method)
			$this->Record->where("invoice_delivery.method", "=", $method);
		return $this->Record;
	}
	
	/**
	 * Fetches all invoice delivery methods that are supported or enabled for this company
	 *
	 * @param int $client_id The ID of the client to fetch the delivery methods for
	 * @param int $client_group_id The ID of the client group to fetch the delivery methods for if $client_id is not given
	 * @param boolean $enabled If true, will only return delivery methods that are enabled for this company, else all supported methods are returned
	 * @return array An array of delivery methods in key/value pairs
	 */
	public function getDeliveryMethods($client_id=null, $client_group_id=null, $enabled=true) {
		$company_id = Configure::get("Blesta.company_id");
		$methods = array(
			'email'=>$this->_("Invoices.getDeliveryMethods.email"),
			'paper'=>$this->_("Invoices.getDeliveryMethods.paper"),
			'interfax'=>$this->_("Invoices.getDeliveryMethods.interfax"),
			'postalmethods'=>$this->_("Invoices.getDeliveryMethods.postalmethods")
		);
		
		if ($enabled) {
			if (!isset($this->SettingsCollection))
				Loader::loadComponents($this, array("SettingsCollection"));
			
			// If no client ID given, fetch from the company setting
			if ($client_id != null)
				$delivery_methods = $this->SettingsCollection->fetchClientSetting($client_id, null, "delivery_methods");
			elseif ($client_group_id != null)
				$delivery_methods = $this->SettingsCollection->fetchClientGroupSetting($client_group_id, null, "delivery_methods");
			else
				$delivery_methods = $this->SettingsCollection->fetchSetting(null, $company_id, "delivery_methods");
			
			if ($delivery_methods && isset($delivery_methods['value'])) {
				$delivery_methods = unserialize(base64_decode($delivery_methods['value']));
				
				if (is_array($delivery_methods)) {
					// array_fill_keys()
					$delivery_methods = array_combine($delivery_methods,array_fill(0,count($delivery_methods), true));
					return array_intersect_key($methods, $delivery_methods);
				}
			}
			return array();
		}
		return $methods;
	}
	
	/**
	 * Marks the delivery status as sent
	 *
	 * @param int $invoice_delivery_id The ID of the delivery item to mark as sent
	 */
	public function delivered($invoice_delivery_id) {
		$this->Record->set("date_sent", $this->dateToUtc(date("c")))->
			where("id", "=", $invoice_delivery_id)->update("invoice_delivery");
	}
	
	/**
	 * Removes the invoice delivery record
	 *
	 * @param int $invoice_delivery_id The ID of the delivery item to delete
	 */
	public function deleteDelivery($invoice_delivery_id) {
		$this->Record->from("invoice_delivery")->
			where("id", "=", $invoice_delivery_id)->delete();
	}
	
	/**
	 * Removes the recurring invoice delivery record
	 *
	 * @param int $invoice_delivery_id The ID of the recurring delivery item to delete
	 */
	public function deleteRecurringDelivery($invoice_delivery_id) {
		$this->Record->from("invoice_recurring_delivery")->
			where("id", "=", $invoice_delivery_id)->delete();
	}
	
	/**
	 * Calculate the subtotal, tax, and total of a set of line items
	 *
	 * @param int $client_id The ID of the client to calculate the line totals for
	 * @param array $vars An array of invoice info including:
	 * 	- lines A numerically indexed array of line items including:
	 * 		- qty The quantity of each line item
	 * 		- amount The unit cost per quantity
	 * 		- tax Whether or not this line items is taxable
	 * 	- currency The currency to use (optional, defaults to the client's default currency)
	 * @param array A numerically indexed array of stdClass objects each representing a tax rule to apply to this client or client group. Must be provided if $client_id not specified
	 * @param int $client_group_id The ID of the client group to calculate line totals for
	 * @return array An array containing the following keys, void on error:
	 * 	- subtotal
	 * 	- total
	 * 	- total_w_tax
	 * 	- tax
	 * 	- total_due Optionally set if $vars['amount_paid'] given and > 0
	 */
	public function calcLineTotals($client_id, array $vars, array $tax_rules = null, $client_group_id = null) {
		Loader::loadHelpers($this, array("CurrencyFormat"=>array(Configure::get("Blesta.company_id"))));
		Loader::loadComponents($this, array("SettingsCollection"));
		
		if ($client_id)
			$client_settings = $this->SettingsCollection->fetchClientSettings($client_id);
		else
			$client_settings = $this->SettingsCollection->fetchClientGroupSettings($client_group_id);		

		// Use default currency for this client if not set
		if (!isset($vars['currency']))
			$vars['currency'] = $client_settings['default_currency'];
		
		// Use our rules to format the data (note: these don't validate the data because
		// there's really no reason to, we're just calculating totals and want to permit
		// values to be blank)
		$rules = array(
			'lines[][amount]' => array(
				'format' => array(
					'if_set' => true,
					'pre_format'=>array(array($this, "currencyToDecimal"), $vars['currency'], 4),
					'rule' => "is_scalar",
					'message' => $this->_("Invoices.!error.lines[][amount].format")
				)
			),
			'lines[][tax]' => array(
				'format' => array(
					'if_set' => true,
					'pre_format'=>array(array($this, "strToBool")),
					'rule' => "is_bool",
					'message' => $this->_("Invoices.!error.lines[][tax].format")
				)
			),
			'lines[][qty]' => array(
				'minimum' => array(
					'pre_format'=>array(array($this, "primeQuantity")),
					'if_set' => true,
					'rule' => "is_scalar",
					'message' => $this->_("Invoices.!error.lines[][qty].minimum")
				)
			)
		);
		$this->Input->setRules($rules);
		
		if ($this->Input->validates($vars)) {
			$totals = array();
		
			// Subtotal sum
			$subtotal = 0;
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
			
			// Fetch all tax rules that apply to this client
			if (!$tax_rules)
				$tax_rules = $this->getTaxRules($client_id);
			
			// Set cascade tax setting
			foreach ($tax_rules as &$tax_rule) {
				$tax_rule->cascade = $client_settings['cascade_tax'] == "true" ? 1 : 0;
			}
			unset($tax_rule);
			
			// Calculate line totals
			foreach ($vars['lines'] as $i => $line) {
				
				// Skip calculations on any lines that are blank
				if ($line['qty'] <= 0)
					continue;
				
				$line_total = $line['qty'] * $line['amount'];
				$subtotal += $line_total;
				
				// Calculate tax for each line item that is taxable IFF tax is enabled
				if ($client_settings['tax_exempt'] != "true" && $client_settings['enable_tax'] == "true" && isset($line['tax']) && $line['tax']) {
					$tax_totals = $this->getTaxTotals($line_total, $tax_rules);
					
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
						$tax_rule['amount_formatted'] = $this->CurrencyFormat->format($tax_rule['amount'], $vars['currency']);
						$tax[$level_index] = $tax_rule;
					}
				}
			}
			unset($tax_rules);
			
			$total = $subtotal + $tax_subtotal;
			$total_w_tax = $subtotal + $tax_total;
			
			$totals = array(
				'subtotal'=>array('amount'=>$subtotal,'amount_formatted'=>$this->CurrencyFormat->format($subtotal, $vars['currency'])),
				'total'=>array('amount'=>$total,'amount_formatted'=>$this->CurrencyFormat->format($total, $vars['currency'])),
				'total_w_tax'=>array('amount'=>$total_w_tax,'amount_formatted'=>$this->CurrencyFormat->format($total_w_tax, $vars['currency'])),
				'tax'=>$tax
			);
			
			// If amount paid was given, return total due
			if (isset($vars['amount_paid']) && $vars['amount_paid'] > 0) {
				$total_due = $total_w_tax - $vars['amount_paid'];
				$totals['total_due'] = array('amount'=>$total_due,'amount_formatted'=>$this->CurrencyFormat->format($total_due, $vars['currency']));
			}
			
			return $totals;
		}
	}
	
	/**
	 * Calculates the client's amount due in the given currency. This sums all
	 * existing open invoices for the given currency.
	 *
	 * @param int $client_id The client ID to calculate on
	 * @param string $currency The ISO 4217 3-character currency code
	 * @return double The amount due
	 */
	public function amountDue($client_id, $currency) {

		$this->Record = $this->getInvoices($client_id, "open");
		$inv_subquery = $this->Record->where("invoices.currency", "=", $currency)->get();
		$inv_values = $this->Record->values;
		$this->Record->reset();

		$fields = array("SUM(IFNULL(open_invoices.due,0))" => "total");
		$amount = $this->Record->appendValues($inv_values)->select($fields, false)->from(array($inv_subquery => "open_invoices"))->fetch();
		
		return ($amount && $amount->total !== null ? $amount->total : 0);
	}
	
	/**
	 * Returns an array of all currency the given client has been invoiced in
	 *
	 * @param int $client_id
	 * @param string $status The status type of the invoices to fetch (optional, default 'active') - ['open','closed','past_due','draft','void','active'] (or 'all' for all active/draft/void)
	 * @return array An array of stdClass objects, each representing a currency in use
	 */
	public function invoicedCurrencies($client_id, $status="active") {
		
		$this->Record->select(array("invoices.currency"))->from("invoices")->
			where("client_id", "=", $client_id)->group("invoices.currency");
		
		switch($status) {
			case "closed":
				// Get closed invoices
				$negate = true;
			case "open":
				// Get open invoices
				
				// Check the date is open/closed
				$this->Record->where("invoices.date_closed", ($negate ? "!=" : "="), null)->
					where("invoices.status", "=", "active");					
				break;
			case "past_due":
				// Get past due invoices
				
				// Check date is past due and invoice is not closed
				$this->Record->where("invoices.date_due", "<", $this->dateToUtc(date("c")))->
					where("invoices.date_closed", "=", null)->
					where("invoices.status", "=", "active");					
				break;
			case "all":
				// Do not filter on status
				break;
			default:
				// Get invoices by status (active, draft, void)
				$this->Record->where("invoices.status", "=", $status);				
				break;
		}
		
		return $this->Record->fetchAll();
	}
	
	/**
	 * Calculates the subtotal of the given invoice ID
	 *
	 * @param int $invoice_id The ID of the invoice to calculate the subtotal of
	 * @return float The subtotal of the invoice
	 */
	public function getSubtotal($invoice_id) {
		$subtotal = 0;
		$sub = $this->Record->select(array("SUM(IFNULL(invoice_lines.amount*invoice_lines.qty,0))" => "subtotal"), false)->
			from("invoice_lines")->where("invoice_lines.invoice_id", "=", $invoice_id)->fetch();
			
		if ($sub)
			$subtotal = $sub->subtotal;
			
		return $subtotal;
	}
	
	/**
	 * Calculates the total (subtotal + tax) of the given invoice ID
	 *
	 * @param int $invoice_id The ID of the invoice to calculate the total of
	 * @return float The total of the invoice
	 */
	public function getTotal($invoice_id) {
		$total = $this->getSubtotal($invoice_id);

		$fields = array(
			"invoice_lines.*",
			"invoice_line_taxes.cascade",
			'taxes_1.level' => "taxes_1_level",
			'taxes_1.amount' => "taxes_1_amount",
			'taxes_2.level' => "taxes_2_level",
			'taxes_2.amount' => "taxes_2_amount",
		);
		
		$lines = $this->Record->select($fields)->
			from("invoice_lines")->
			leftJoin("invoice_line_taxes", "invoice_line_taxes.line_id", "=", "invoice_lines.id", false)->
			on("taxes_1.level", "=", 1)->
			leftJoin(array('taxes'=>"taxes_1"), "taxes_1.id", "=", "invoice_line_taxes.tax_id", false)->
			on("taxes_2.level", "=", 2)->
			leftJoin(array('taxes'=>"taxes_2"), "taxes_2.id", "=", "invoice_line_taxes.tax_id", false)->
			where("invoice_lines.invoice_id", "=", $invoice_id)->fetchAll();
			
		$tax = array();
		$total_tax = 0;
		foreach ($lines as $line) {
			$line_subtotal = $line->amount * $line->qty;
			
			if ($line->taxes_1_level)
				$tax[$line->id] = $tax_amount = round($line->taxes_1_amount*$line_subtotal/100, 2);
			else {	
				// If cascading tax is enabled, and this tax rule level is > 1 apply this tax to the line item including tax level below it
				if ($line->cascade > 0 && $line->taxes_2_level && isset($tax[$line->id]))
					$tax_amount = round($line->taxes_2_amount*($line_subtotal+$tax[$line->id])/100,2);
				// This is a normal tax, which does not apply to the tax rule below it
				else
					$tax_amount = round($line->taxes_2_amount*$line_subtotal/100, 2);
			}
			
			$total_tax += $tax_amount;
		}
		
		return $total + $total_tax;
	}
	
	/**
	 * Calculates the total paid on the given invoice ID
	 *
	 * @param int $invoice_id The ID of the invoice to calculate the total paid on
	 * @return float The total paid on the invoice
	 */
	public function getPaid($invoice_id) {
		$total_paid = 0;
		
		$paid = $this->Record->select(array("SUM(IFNULL(transaction_applied.amount,0))" => "total"), false)->
			from("transaction_applied")->
			innerJoin("transactions", "transaction_applied.transaction_id", "=", "transactions.id", false)->
			where("transaction_applied.invoice_id", "=", $invoice_id)->
			where("transactions.status", "=", "approved")->
			group("transaction_applied.invoice_id")->fetch();
		if ($paid)
			$total_paid = $paid->total;
		return $total_paid;
	}
	
	/**
	 * Retrieves a list of invoice statuses and language
	 *
	 * @return array A key/value array of statuses and their language
	 */
	public function getStatuses() {
		return array(
			'active' => Language::_("Invoices.status.active", true),
			'draft' => Language::_("Invoices.status.draft", true),
			'void' => Language::_("Invoices.status.void", true)
		);
	}
	
	/**
	 * Validates the invoice 'status' field
	 *
	 * @param string $status The status to check
	 * @return boolean True if validated, false otherwise
	 */
	public function validateStatus($status) {
		switch ($status) {
			case "active":
			case "draft":
			case "void":
				return true;
		}
		return false;
	}
	
	/**
	 * Validates that the given invoice is a draft invoice
	 *
	 * @param int $invoice_id The invoice ID
	 * @return boolean True if the given invoice is a draft, and false otherwise
	 */
	public function validateIsDraft($invoice_id) {
		$count = $this->Record->select("id")->from("invoices")->where("id", "=", $invoice_id)->
			where("status", "=", "draft")->numResults();
		
		return ($count > 0);
	}
	
	/**
	 * Validates that the delivery options match the available set
	 *
	 * @param array $methods A key=>value array of delivery methods (e.g. "email"=>true)
	 * @return boolean True if at least one delivery method was given, false otherwise
	 */
	public function validateDeliveryMethods(array $methods=null) {
		$all_methods = array("email", "paper", "interfax", "postalmethods");
		
		if (!empty($methods)) {
			foreach ($methods as $key => $value) {
				// If a method was given that doesn't match, the value is invalid
				if (!in_array($value, $all_methods))
					return false;
			}
			return true;
		}
		return false;
	}
	
	/**
	 * Checks if the given invoice has any payments applied to it
	 *
	 * @param int $invoice_id The invoice ID to check
	 * @return boolean True if the invoice has payments applied to it, false otherwise
	 */
	public function validateAmountApplied($invoice_id) {
		$num_payments = $this->Record->select("transaction_id")->from("transaction_applied")->
			where("invoice_id", "=", $invoice_id)->numResults();
		
		if ($num_payments > 0)
			return true;
		return false;
	}
	
	/**
	 * Validates that the given date due is on or after the date billed
	 *
	 * @param string $date_due The date the invoice is due
	 * @param string $date_billed The date the invoice is billed
	 * @return boolean True if the date due is on or after the date billed, false otherwise
	 */
	public function validateDateDueAfterDateBilled($date_due, $date_billed) {
		if (!empty($date_due) && !empty($date_billed)) {
			if (strtotime($date_due) < strtotime($date_billed))
				return false;
		}
		return true;
	}
	
	/**
	 * Returns the rule set for adding/editing invoices
	 *
	 * @param array $vars The input vars
	 * @return array Invoice rules
	 */
	private function getRules(array $vars) {
		$rules = array(
			// Invoice rules
			'id_format' => array(
				'empty' => array(
					'rule' => "isEmpty",
					'negate' => true,
					'message' => $this->_("Invoices.!error.id_format.empty")
				),
				'length' => array(
					'rule' => array("maxLength", 64),
					'message' => $this->_("Invoices.!error.id_format.length")
				)
			),
			'id_value' => array(
				'valid' => array(
					'rule' => array(array($this, "isInstanceOf"), "Record"),
					'message' => $this->_("Invoices.!error.id_value.valid")
				)
			),
			'client_id' => array(
				'exists' => array(
					'rule' => array(array($this, "validateExists"), "id", "clients"),
					'message' => $this->_("Invoices.!error.client_id.exists")
				)
			),
			'date_billed' => array(
				'format' => array(
					'rule' => "isDate",
					'message' => $this->_("Invoices.!error.date_billed.format"),
					'post_format'=>array(array($this, "dateToUtc"))
				)
			),
			'date_due' => array(
				'format' => array(
					'rule' => "isDate",
					'message' => $this->_("Invoices.!error.date_due.format")
				),
				'after_billed' => array(
					'rule' => array(array($this, "validateDateDueAfterDateBilled"), $this->ifSet($vars['date_billed'])),
					'message' => $this->_("Invoices.!error.date_due.after_billed"),
					'post_format'=>array(array($this, "dateToUtc"))
				)
			),
			'date_closed' => array(
				'format' => array(
					'if_set' => true,
					'rule' => "isDate",
					'message' => $this->_("Invoices.!error.date_closed.format"),
					'post_format'=>array(array($this, "dateToUtc"))
				)
			),
			'date_autodebit' => array(
				'format' => array(
					'if_set' => true,
					'rule' => "isDate",
					'message' => $this->_("Invoices.!error.date_autodebit.format"),
					'post_format'=>array(array($this, "dateToUtc"))
				)
			),
			'status' => array(
				'format' => array(
					'if_set' => true,
					'rule' => array(array($this, "validateStatus")),
					'message' => $this->_("Invoices.!error.status.format")
				)
			),
			'currency' => array(
				'length' => array(
					//'if_set' => true,
					'rule' => array("matches", "/^[A-Z]{3}$/"),
					'message' => $this->_("Invoices.!error.currency.length")
				)
			),
			// Invoice line item rules
			'lines[][service_id]' => array(
				'exists' => array(
					'if_set' => true,
					'rule' => array(array($this, "validateExists"), "id", "services"),
					'message' => $this->_("Invoices.!error.lines[][service_id].exists")
				)
			),
			'lines[][description]' => array(
				'empty' => array(
					'rule' => "isEmpty",
					'negate' => true,
					'message' => $this->_("Invoices.!error.lines[][description].empty")
				)
			),
			'lines[][qty]' => array(
				/* unnecessary error
				'format' => array(
					'if_set' => true,
					'rule' => "is_numeric",
					'message' => $this->_("Invoices.!error.lines[][qty].format")
				),
				*/
				'minimum' => array(
					'pre_format'=>array(array($this, "primeQuantity")),
					'if_set' => true,
					'rule' => array("compares", ">", 0),
					'message' => $this->_("Invoices.!error.lines[][qty].minimum")
				)
			),
			'lines[][amount]' => array(
				'format' => array(
					'if_set' => true,
					'pre_format'=>array(array($this, "currencyToDecimal"), $vars['currency'], 4),
					'rule' => "is_numeric",
					'message' => $this->_("Invoices.!error.lines[][amount].format")
				)
			),
			'lines[][tax]' => array(
				'format' => array(
					'if_set' => true,
					'pre_format'=>array(array($this, "strToBool")),
					'rule' => "is_bool",
					'message' => $this->_("Invoices.!error.lines[][tax].format")
				)
			),
			// Invoice delivery rules
			'delivery' => array(
				'exists' => array(
					'if_set' => true,
					'rule' => array(array($this, "validateDeliveryMethods")),
					'message' => $this->_("Invoices.!error.delivery.exists")
				)
			)
		);
		return $rules;
	}
	
	/**
	 * Returns the rule set for adding/editing recurring invoices
	 *
	 * @param array $vars The input vars
	 * @return array Invoice rules
	 */
	private function getRecurringRules(array $vars) {
		$rules = array(
			'client_id' => array(
				'exists' => array(
					'rule' => array(array($this, "validateExists"), "id", "clients"),
					'message' => $this->_("Invoices.!error.client_id.exists")
				)
			),
			'term' => array(
				'format' => array(
					'if_set' => true,
					'rule' => "is_numeric",
					'message' => $this->_("Invoices.!error.term.format")
				),
				'bounds' => array(
					'if_set' => true,
					'rule' => array("between", 1, 65535),
					'message' => $this->_("Invoices.!error.term.bounds")
				)
			),
			'period' => array(
				'format' => array(
					'if_set' => true,
					'rule' => array(array($this, "validatePeriod")),
					'message' => $this->_("Invoices.!error.period.format")
				)
			),
			'duration' => array(
				'format' => array(
					'if_set' => true,
					'rule' => array(array($this, "validateDuration")),
					'message' => $this->_("Invoices.!error.duration.format")
				)
			),
			'currency' => array(
				'length' => array(
					'rule' => array("matches", "/^[A-Z]{3}$/"),
					'message' => $this->_("Invoices.!error.currency.length")
				)
			),
			'date_renews' => array(
				'format' => array(
					'if_set' => true,
					'rule' => "isDate",
					'message' => $this->_("Invoices.!error.date_renews.format"),
					'post_format'=>array(array($this, "dateToUtc"))
				)
			),
			'date_last_renewed' => array(
				'format' => array(
					'if_set' => true,
					'rule' => "isDate",
					'message' => $this->_("Invoices.!error.date_last_renewed.format"),
					'post_format'=>array(array($this, "dateToUtc"))
				)
			),
			'lines[][description]' => array(
				'empty' => array(
					'rule' => "isEmpty",
					'negate' => true,
					'message' => $this->_("Invoices.!error.lines[][description].empty")
				)
			),
			'lines[][qty]' => array(
				'minimum' => array(
					'pre_format'=>array(array($this, "primeQuantity")),
					'if_set' => true,
					'rule' => array("compares", ">", 0),
					'message' => $this->_("Invoices.!error.lines[][qty].minimum")
				)
			),
			'lines[][amount]' => array(
				'format' => array(
					'if_set' => true,
					'pre_format'=>array(array($this, "currencyToDecimal"), $vars['currency'], 4),
					'rule' => "is_numeric",
					'message' => $this->_("Invoices.!error.lines[][amount].format")
				)
			),
			'lines[][tax]' => array(
				'format' => array(
					'if_set' => true,
					'pre_format'=>array(array($this, "strToBool")),
					'rule' => "is_bool",
					'message' => $this->_("Invoices.!error.lines[][tax].format")
				)
			),
			// Invoice delivery rules
			'delivery' => array(
				'exists' => array(
					'if_set' => true,
					'rule' => array(array($this, "validateDeliveryMethods")),
					'message' => $this->_("Invoices.!error.delivery.exists")
				)
			)
		);
		return $rules;
	}
	
	/**
	 * Validates the recurring invoice duration
	 *
	 * @param mixed $duration An integer idenfying the number of the times the recurring invoice should recur, null for indefinitely
	 * @return boolean True if the duration is valid, false otherwise
	 */
	public function validateDuration($duration) {
		if ($duration == "") 
			return true;
		if (is_numeric($duration) && $duration >= 1 && $duration <= 65535)
			return true;
		return false;
	}
	
	/**
	 * Validates the recurring invoice period
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
	 * Checks if the given $field is a reference of $class
	 */
	public function isInstanceOf($field, $class) {
		return $field instanceof $class;
	}
	
	/**
	 * Converts quantity to a float and ensures it is greater than 0, if no qty is set
	 * or if quanity is less than or equal to 0, quanity will be set at 1
	 *
	 * @param mixed $qty The quantity to be primed, may be an integer, float, or fractional string
	 * @return float The quanity rounded to 4 decimal places
	 */
	public function primeQuantity($qty) {
		$qty = trim($qty);
		
		// If qty is not a float or int, process as a fraction string
		if ((string)(float)$qty != $qty) {
			$parts = explode(" ", $qty, 2);
			$f = 0; // The index of the fractional portion of the string in $parts
			// Evaluate whole and fractional portions
			if (count($parts) > 1)
				$f = 1;
			
			// Parse the fraction into its parts	
			$fract = explode("/", $parts[$f], 2);
			$decimal = 0;
			
			if (count($fract) == 2)
				$decimal = (int)$fract[0] / max(1, (int)$fract[1]);
			
			$qty = ($f > 0 ? (int)$parts[0] : 0) + $decimal;
		}
		
		$qty = (float)max(0, $qty);
		
		return $qty > 0 ? sprintf("%.4f", $qty) : 1;
	}
	
	/**
	 * Retrieves all active tax rules that apply to the given client
	 *
	 * @param int $client_id The client ID
	 * @return array A numerically indexed array of stdClass objects each representing a tax rule to apply to this client
	 */
	public function getTaxRules($client_id) {
		
		$fields = array("taxes.id", "taxes.level", "taxes.name", "taxes.amount", "taxes.type", "taxes.country", "taxes.state");
		
		$tax_rules = $this->Record->select($fields)->from("clients")->
			innerJoin("client_groups", "clients.client_group_id", "=", "client_groups.id", false)->
			on("contacts.client_id", "=", "clients.id", false)->on("contacts.contact_type", "=", "primary")->innerJoin("contacts")->
			innerJoin("taxes", "taxes.company_id", "=", "client_groups.company_id", false)->
			open()->
				open()->
					where("taxes.country", "=", "contacts.country", false)->
					where("taxes.state", "=", "contacts.state", false)->
				close()->
				open()->
					orWhere("taxes.country", "=", "contacts.country", false)->
					where("taxes.state", "=", null)->
				close()->
				open()->
					orWhere("taxes.country", "=", null)->
					where("taxes.state", "=", null)->
				close()->
			close()->
			where("clients.id", "=", $client_id)->where("taxes.status", "=", "active")->
			order(array('level'=>"ASC"))->
			group("taxes.level")->fetchAll();
		
		return $tax_rules;
	}
	
	/**
	 * Retrieves all active tax rules that apply to the given company and location
	 *
	 * @param int $company_id The ID of the company to fetch tax rules on
	 * @param string $country The ISO 3166-1 alpha2 country code to fetch tax rules on
	 * @param string $state 3166-2 alpha-numeric subdivision code to fetch tax rules on
	 * @return array A numerically indexed array of stdClass objects each representing a tax rule to apply to this company and location
	 */
	public function getTaxRulesByLocation($company_id, $country, $state) {
		
		$fields = array("taxes.id", "taxes.level", "taxes.name", "taxes.amount", "taxes.type", "taxes.country", "taxes.state");
		
		$tax_rules = $this->Record->select($fields)->from("taxes")->
			open()->
				open()->
					where("taxes.country", "=", $country)->
					where("taxes.state", "=", $state)->
				close()->
				open()->
					orWhere("taxes.country", "=", $country)->
					where("taxes.state", "=", null)->
				close()->
				open()->
					orWhere("taxes.country", "=", null)->
					where("taxes.state", "=", null)->
				close()->
			close()->
			where("taxes.company_id", "=", $company_id)->where("taxes.status", "=", "active")->
			order(array('level'=>"ASC"))->
			group("taxes.level")->fetchAll();
		
		return $tax_rules;
	}
	
	/**
	 * Identifies whether or not the given invoice with its updated line items and deleted items
	 * requires tax rules to be updated when saved. This method doesn't check whether the tax
	 * rules have been updated, just whether the invoice has been changed such that the updated
	 * tax rules would need to be updated. There's no consequence in updating tax when
	 * the tax rules have not changed.
	 *
	 * @param int $invoice_id The ID of the invoice to evaluate
	 * @param array $lines An array of line items including:
	 * 	- id The ID of the line item (if available)
	 * 	- tax Whether or not the line items is taxable (true/false)
	 * 	- amount The amount per quantity for the line item
	 * 	- qty The quantity of the line item
	 * @param array $delete_items An array of items to be deleted from the invoice
	 * @return boolean True if the invoice has been modified in such a way to warrant updating the tax rules applied, false otherwise
	 * @see Invoices::edit()
	 */
	private function taxUpdateRequired($invoice_id, $lines, $delete_items) {
		$tax_change = false;
		
		$invoice = $this->get($invoice_id);
		$num_lines = count($lines);
		$num_delete = count($delete_items);
		
		// Ensure the invoice exists
		if (!$invoice)
			return $tax_change;
		
		// If any new items added or any items removed, taxes must be updated
		if (count($invoice->line_items) != $num_lines || $num_delete > 0)
			$tax_change = true;
		// Ensure that quanity, unit cost, and tax status remain unchanged
		else {
			for ($i=0; $i<$num_lines; $i++) {
				if (isset($lines[$i]['id'])) {
					for ($j=0; $j<$num_lines; $j++) {
						// Ensure tax status remains unchanged
						if ($invoice->line_items[$j]->id == $lines[$i]['id']) {
							if ((!$lines[$i]['tax'] && !empty($invoice->line_items[$j]->taxes)) ||
								($lines[$i]['tax'] && empty($invoice->line_items[$j]->taxes))) {
								$tax_change = true;
								break 2;
							}
							
							// Ensure amount and quantity remain unchanged
							if ($lines[$i]['amount'] != $invoice->line_items[$j]->amount ||
								$lines[$i]['qty'] != $invoice->line_items[$j]->qty) {
								$tax_change = true;
								break 2;
							}
						}
					}
				}
			}
		}
		
		return $tax_change;
	}

	/**
	 * Creates a Payment Hash that may be used to temporarily authenticate a
	 * user's access to pay an invoice, or invoices
	 *
	 * @param int $client_id The client ID to create the hash for
	 * @param int $invoice_id The ID of the invoice to create the hash for (if null will allow the hash to work for any invoice belonging to the client)
	 * @return string A hash built based upon the parameters provided
	 */	
	public function createPayHash($client_id, $invoice_id) {
		return substr($this->systemHash('c=' . $client_id . '|i=' . $invoice_id), -16);
	}
	
	/**
	 * Verifies the Payment Hash is valid
	 *
	 * @param int $client_id The client ID to verify the hash for
	 * @param int $invoice_id The ID of the invoice to verify the hash for
	 * @param string $hash The original hash to verify against
	 * @return boolean True if the hash is valid, false otherwise
	 */
	public function verifyPayHash($client_id, $invoice_id, $hash) {
		$h = $this->systemHash('c=' . $client_id . '|i=' . $invoice_id);
		return substr($h, -16) == $hash;
	}
}
?>