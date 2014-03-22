<?php
/**
 * Client portal contacts controller
 *
 * @package blesta
 * @subpackage blesta.app.controllers
 * @copyright Copyright (c) 2010, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class ClientContacts extends AppController {
	
	/**
	 * Pre action
	 */
	public function preAction() {
		parent::preAction();
		
		$this->uses(array("Clients", "Contacts"));
		Language::loadLang(array("client_contacts"));
		
		$this->client = $this->Clients->get($this->Session->read("blesta_client_id"));
		
		// Attempt to set the page title language
		if ($this->client) {
			try {
				$language = Language::_("ClientContacts." . Loader::fromCamelCase($this->action ? $this->action : "index") . ".page_title", true, $this->client->id_code);
				$this->structure->set("page_title", $language);
			}
			catch(Exception $e) {
				// Attempting to set the page title language has failed, likely due to
				// the language definition requiring multiple parameters.
				// Fallback to index. Assume the specific page will set its own page title otherwise.
				$this->structure->set("page_title", Language::_("ClientContacts.index.page_title", true), $this->client->id_code);
			}
		}
		else
			$this->redirect($this->base_uri);
	}
	
	/**
	 * List contacts
	 */
	public function index() {
		// Set sort and order
		$order = (isset($this->get['order']) ? $this->get['order'] : "asc");
		$sort = (isset($this->get['sort']) ? $this->get['sort'] : "first_name");
		
		// Get all contacts, not primary
		$contacts = $this->Contacts->getAll($this->client->id, null, array($sort=>$order));
		foreach ($contacts as $index=>$contact) {
			if ($contact->id == $this->client->contact_id) {
				unset($contacts[$index]);
				break;
			}
		}
		// Re-index the array
		$contacts = array_values($contacts);
		
		$this->set("contacts", $contacts);
		$this->set("contact_types", $this->getContactTypes());
		$this->set("sort", $sort);
		$this->set("order", $order);
		$this->set("negate_order", ($order == "asc" ? "desc" : "asc"));
		
		if ($this->isAjax())
			return $this->renderAjaxWidgetIfAsync(isset($this->get['whole_widget']) ? null : isset($this->get['sort']));
	}
	
	/**
	 * Create a contact
	 */
	public function add() {
		$this->ArrayHelper = $this->DataStructure->create("Array");
		
		$vars = new stdClass();
		
		// Set client settings
		$vars->country = $this->client->settings['country'];
		$vars->currency = $this->client->settings['default_currency'];
		$vars->language = $this->client->settings['language'];
		
		// Add contact
		if (!empty($this->post)) {
			$this->post['client_id'] = $this->client->id;
			
			$vars = $this->post;
			
			// Set contact type to 'other' if contact type id is given
			if (isset($this->post['contact_type']) && is_numeric($this->post['contact_type'])) {
				$vars['contact_type_id'] = $this->post['contact_type'];
				$vars['contact_type'] = "other";
			}
			else
				$vars['contact_type_id'] = null;
			
			// Remove contact ID so as to not interfere with rules since this field is for swapping contact data in the interface
			unset($vars['contact_id']);
			
			// Format any phone numbers
			$vars['numbers'] = $this->ArrayHelper->keyToNumeric($this->post['numbers']);
			
			// Create the contact
			$this->Contacts->add($vars);
			
			if (($errors = $this->Contacts->errors())) {
				// Error, reset vars
				$vars = (object)$this->post;
				$this->setMessage("error", $errors);
			}
			else {
				// Success
				$this->flashMessage("message", Language::_("ClientContacts.!success.contact_added", true));
				$this->redirect($this->base_uri . "contacts/");
			}
		}
		
		$this->set("contact_types", $this->getContactTypes());
		$this->set("vars", $vars);
		// Set partials
		$this->setContactView($vars);
		$this->setPhoneView($vars);
	}
	
	/**
	 * Edit a contact
	 */
	public function edit() {
		// Ensure a valid contact was given
		if (!isset($this->get[0]) || !($contact = $this->Contacts->get((int)$this->get[0])) ||
			($contact->client_id != $this->client->id))
			$this->redirect($this->base_uri . "contacts/");
		
		$this->ArrayHelper = $this->DataStructure->create("Array");
		
		$vars = array();
		
		if (!empty($this->post)) {
			$vars = $this->post;
			
			// Set contact type to 'other' if contact type id is given
			if (isset($this->post['contact_type']) && is_numeric($this->post['contact_type'])) {
				$vars['contact_type_id'] = $this->post['contact_type'];
				$vars['contact_type'] = "other";
			}
			else
				$vars['contact_type_id'] = null;
			
			// Format the phone numbers
			$vars['numbers'] = $this->ArrayHelper->keyToNumeric($this->post['numbers']);
			
			// Update the contact
			$this->Contacts->edit($contact->id, $vars);
			
			if (($errors = $this->Contacts->errors())) {
				// Error, reset vars
				$vars = (object)$this->post;
				$this->setMessage("error", $errors);
			}
			else {
				// Success
				$this->flashMessage("message", Language::_("ClientContacts.!success.contact_updated", true));
				$this->redirect($this->base_uri . "contacts/");
			}
		}
		
		// Set current contact
		if (empty($vars)) {
			$vars = $contact;
			
			// Set contact type if it is not a default type
			if (is_numeric($vars->contact_type_id))
				$vars->contact_type = $vars->contact_type_id;
			
			// Set contact phone numbers formatted for HTML
			$vars->numbers = $this->ArrayHelper->numericToKey($this->Contacts->getNumbers($contact->id));
		}
		
		$this->set("contact_types", $this->getContactTypes());
		$this->set("vars", $vars);
		// Set partials
		$this->setContactView($vars, true);
		$this->setPhoneView($vars);
	}
	
	/**
	 * Delete a contact
	 */
	public function delete() {
		// Ensure a valid contact was given
		if (!isset($this->get[0]) || !($contact = $this->Contacts->get((int)$this->get[0])) ||
			($contact->client_id != $this->client->id))
			$this->redirect($this->base_uri . "contacts/");
		
		// Attempt to delete the contact
		$this->Contacts->delete($contact->id);
		
		if (($errors = $this->Contacts->errors()))
			$this->flashMessage("error", $errors);
		else
			$this->flashMessage("message", Language::_("ClientContacts.!success.contact_deleted", true, $contact->first_name, $contact->last_name));
		
		$this->redirect($this->base_uri . "contacts/");
	}
	
	/**
	 * Sets the contact partial view
	 * @see ClientContacts::add(), ClientContacts::edit()
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
					'first_name'=>Language::_("ClientContacts.setcontactview.text_none", true),
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
			'show_email' => true
		);
		$this->set("contact_info", $this->partial("client_contacts_contact_info", $contact_info));
	}
	
	/**
	 * Sets the contact phone number partial view
	 * @see ClientContacts::add(), ClientContacts::edit()
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
	 * Retrieves a list of contact types. Useful for a drop-down list
	 * @see ClientContacts::index(), ClientContacts::add(), ClientContacts::edit()
	 *
	 * @return array A list of contact types
	 */
	private function getContactTypes() {
		// Set all contact types besides 'primary' and 'other'
		$contact_types = $this->Contacts->getContactTypes();
		$contact_type_ids = $this->Form->collapseObjectArray($this->Contacts->getTypes($this->client->company_id), "real_name", "id");
		unset($contact_types['primary'], $contact_types['other']);
		
		return $contact_types + $contact_type_ids;
	}
}
?>