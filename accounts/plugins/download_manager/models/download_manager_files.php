<?php
/**
 * Download Manager Files
 *
 * Manages file downloads
 *
 * @package blesta
 * @subpackage blesta.plugins.download_manager.models
 * @copyright Copyright (c) 2010, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */

class DownloadManagerFiles extends DownloadManagerModel {
	
	/**
	 * Initialize
	 */
	public function __construct() {
		parent::__construct();
		
		Loader::loadComponents($this, array("SettingsCollection", "Upload"));
		
		Language::loadLang("download_manager_files", null, PLUGINDIR . "download_manager" . DS . "language" . DS);
	}
	
	/**
	 * Create a new file
	 *
	 * @param array $vars A list of file input vars, including:
	 * 	- category_id The ID of the category to which this file belongs (optional)
	 * 	- name The name of the file
	 * 	- file_name The absolute path to the file on the server, or null if the file is being uploaded in $files
	 * 	- public Whether or not this file is publically-available (0 or 1, default 0)
	 * 	- permit_client_groups Whether or not this file is available to permitted client groups (0 or 1, default 0)
	 * 	- file_groups A list of client group IDs (optional)
	 * 	- permit_packages Whether or not this file is available to permitted packgaes (0 or 1, default 0)
	 * 	- file_packages A list of package IDs (optional)
	 * @param array $files A list of files in the format of post data $_FILES
	 * @return stdClass An stdClass object representing this file, or void on error
	 */
	public function add(array $vars, array $files) {
		$this->Input->setRules($this->getRules($vars, $files));
		
		if ($this->Input->validates($vars)) {
			// Begin a transaction
			$this->Record->begin();
			
			// Handle file upload
			if (!empty($files['file'])) {
				// Set the uploads directory
				$temp = $this->SettingsCollection->fetchSetting(null, Configure::get("Blesta.company_id"), "uploads_dir");
				$upload_path = $temp['value'] . Configure::get("Blesta.company_id") . DS . "download_files" . DS;
				
				$this->Upload->setFiles($files);
				$this->Upload->setUploadPath($upload_path);
				$file_name = $this->makeFileName($files['file']['name']);
				
				if (!($errors = $this->Upload->errors())) {
					// Will not overwrite existing file
					$this->Upload->writeFile("file", false, $file_name);
					$data = $this->Upload->getUploadData();
					
					// Set the file name of the file that was uploaded
					if (isset($data['file']['full_path']))
						$vars['file_name'] = $data['file']['full_path'];
					
					$errors = $this->Upload->errors();
				}
				
				// Error, could not upload the file
				if ($errors) {
					$this->Input->setErrors($errors);
					// Attempt to remove the file if it was somehow written
					@unlink($upload_path . $file_name);
					return;
				}
			}
			
			$fields = array("category_id", "company_id", "name", "file_name", "public", "permit_client_groups", "permit_packages");
			$this->Record->insert("download_files", $vars, $fields);
			$file_id = $this->Record->lastInsertId();
			
			// Add client groups
			if ($this->ifSet($vars['permit_client_groups'], "0") == "1" && !empty($vars['file_groups']))
				$this->addFileGroups($file_id, $vars['file_groups']);
			
			// Add package groups
			if ($this->ifSet($vars['permit_packages'], "0") == "1" && !empty($vars['file_packages']))
				$this->addFilePackages($file_id, $vars['file_packages']);
			
			// Commit the transaction
			$this->Record->commit();
		}
	}
	
	/**
	 * Updates a file
	 *
	 * @param int $file_id The ID of the file to update
	 * @param array $vars A list of file input vars, including:
	 * 	- category_id The ID of the category to which this file belongs
	 * 	- name The name of the file
	 * 	- file_name The absolute path to the file on the server, or null if the file is being uploaded in $files
	 * 	- public Whether or not this file is publically-available (0 or 1, default 0)
	 * 	- permit_client_groups Whether or not this file is available to permitted client groups (0 or 1, default 0)
	 * 	- file_groups A list of client group IDs (optional)
	 * 	- permit_packages Whether or not this file is available to permitted packgaes (0 or 1, default 0)
	 * 	- file_packages A list of package IDs (optional)
	 * @param array $files A list of files in the format of post data $_FILES
	 * @return stdClass An stdClass object representing this file
	 */
	public function edit($file_id, array $vars, array $files) {
		$vars['file_id'] = $file_id;
		$this->Input->setRules($this->getRules($vars, $files, true));
		$orig_file = $this->get($file_id);
		
		if ($this->Input->validates($vars)) {
			// Begin a transaction
			$this->Record->begin();
			
			// Handle file upload
			if (isset($files['file']['size']) && $files['file']['size'] != 0) {
				// Set the uploads directory
				$temp = $this->SettingsCollection->fetchSetting(null, Configure::get("Blesta.company_id"), "uploads_dir");
				$upload_path = $temp['value'] . Configure::get("Blesta.company_id") . DS . "download_files" . DS;
				
				$this->Upload->setFiles($files);
				$this->Upload->setUploadPath($upload_path);
				$file_name = $this->makeFileName($files['file']['name']);
				
				if (!($errors = $this->Upload->errors())) {
					// Will not overwrite existing file
					$this->Upload->writeFile("file", false, $file_name);
					$data = $this->Upload->getUploadData();
					
					// Set the file name of the file that was uploaded
					if (isset($data['file']))
						$vars['file_name'] = $data['file']['full_path'];
					
					$errors = $this->Upload->errors();
				}
				
				// Error, could not upload the file
				if ($errors) {
					$this->Input->setErrors($errors);
					// Attempt to remove the file if it was somehow written
					@unlink($upload_path . $file_name);
					return;
				}
			}
			
			$fields = array("category_id", "company_id", "name", "file_name", "public", "permit_client_groups", "permit_packages");
			$this->Record->where("id", "=", $file_id)->update("download_files", $vars, $fields);
			
			// Add client groups
			$this->deleteFileGroups($file_id);
			if ($this->ifSet($vars['permit_client_groups'], "0") == "1" && !empty($vars['file_groups']))
				$this->addFileGroups($file_id, $vars['file_groups']);
			
			// Add package groups
			$this->deleteFilePackages($file_id);
			if ($this->ifSet($vars['permit_packages'], "0") == "1" && !empty($vars['file_packages']))
				$this->addFilePackages($file_id, $vars['file_packages']);
			
			// Commit the transaction
			$this->Record->commit();
			
			// Remove the existing file since a new one has been uploaded
			if (isset($errors) && empty($errors) && isset($file_name) && $file_name != $orig_file->file_name)
				@unlink($orig_file->file_name);
		}
	}
	
		
	/**
	 * Deletes a file
	 *
	 * @param int $file_id The ID of the file to delete
	 */
	public function delete($file_id) {
		$file = $this->get($file_id);
		
		if ($file) {
			// Begin a transaction
			$this->Record->begin();
			
			// Delete all groups/packages related to the file
			$this->deleteFileGroups($file_id);
			$this->deleteFilePackages($file_id);
			
			// Delete the file
			$this->Record->from("download_files")->where("id", "=", $file_id)->delete();
			
			// Commit the changes
			$this->Record->commit();
			
			// Remove the file from disk
			@unlink($file->file_name);
		}
	}
	
	/**
	 * Fetches the file
	 *
	 * @param int $file_id The ID of the file to fetch
	 * @return mixed An stdClass object representing the file, or false if none exist
	 */ 
	public function get($file_id) {
		$file = $this->Record->select()->from("download_files")->where("id", "=", $file_id)->fetch();
		
		if ($file) {
			// Get client groups and packages
			$file->packages = $this->getFilePackages($file_id);
			$file->client_groups = $this->getFileGroups($file_id);
		}
		
		return $file;
	}
	
	/**
	 * Fetches all files within a specific category
	 *
	 * @param int $company_id The ID of the company from which to fetch files
	 * @param int $category_id The ID of the category whose files to fetch (optional, default null for uncategorized)
	 * @return array A list of stdClass objects representing files
	 */
	public function getAll($company_id, $category_id = null) {
		return $this->Record->select()->from("download_files")->
			where("category_id", "=", $category_id)->fetchAll();
	}
	
	/**
	 * Retrieves a list of files available to a client, filtered by category
	 *
	 * @param int $client_id The ID of the client
	 * @param int $company_id The ID of the company this client belongs to
	 * @param int $category_id The ID of the download category whose files to fetch
	 * @return array A list of files available to the client in the given category
	 */
	public function getAllAvailable($company_id, $client_id = null, $category_id = null) {
		return $this->getFilesAvailable($company_id, $client_id, $category_id)->
			group("temp.id")->fetchAll();
	}
	
	/**
	 * Checks whether the given client has access to the given file
	 *
	 * @param int $client_id The ID of the client
	 * @param int $company_id The ID of the company that the client belongs to
	 * @param int $file_id The ID of the file to check
	 * @return boolean True if the client has access to the file, false otherwise
	 */
	public function hasAccessToFile($file_id, $company_id, $client_id = null) {
		// Fetch the files without filtering on category
		$count = $this->getFilesAvailable($company_id, $client_id, false)->
			where("temp.id", "=", $file_id)->group("temp.id")->numResults();
		
		if ($count > 0)
			return true;
		return false;
	}
	
	/**
	 * Partially constructs a Record object for fetching files available to a client
	 *
	 * @param int $company_id The ID of the company this client belongs to
	 * @param int $client_id The ID of the client
	 * @param mixed $category_id The ID of the download category whose files to fetch (optional, null for root directory, or false to not check category, default null)
	 * @return Record A partially-constructed Record object
	 */
	private function getFilesAvailable($company_id, $client_id = null, $category_id = null) {
		// Get all public files
		$alias = "download_files";
		
		// Set table to 'temp' for consistency
		if (!$client_id) {
			$alias = "temp";
			$this->Record->select($alias . ".*")->
				from(array('download_files' => "temp"));
		}
		else
			$this->Record->select("download_files.*")->from("download_files");
			
		$this->Record->where($alias . ".public", "=", "1")->
			where($alias . ".company_id", "=", $company_id);
		
		// Filter on category
		if ($category_id !== false)
			$this->Record->where($alias . ".category_id", "=", $category_id);
	
		// No client was given, so only public files are available, return just those
		if (!$client_id)
			return $this->Record;
		
		// Use the previous query as subquery to fetch other files as well
		$subquery_public = $this->Record->get();
		$values = $this->Record->values;
		$this->Record->reset();
		$this->Record->values = $values;
		
		// Get all files based on client group
		$this->Record->select("download_files.*")->from("download_files")->
			innerJoin("download_file_groups", "download_file_groups.file_id", "=", "download_files.id", false)->
			innerJoin("client_groups", "client_groups.id", "=", "download_file_groups.client_group_id", false)->
			innerJoin("clients", "clients.client_group_id", "=", "client_groups.id", false)->
			where("download_files.company_id", "=", $company_id)->
			where("clients.id", "=", $client_id)->
			where("download_files.permit_client_groups", "=", "1");
		
		if ($category_id !== false)
			$this->Record->where("download_files.category_id", "=", $category_id);
		
		$subquery_group = $this->Record->get();
		$values = $this->Record->values;
		$this->Record->reset();
		$this->Record->values = $values;
		
		// Get all files based on packages
		$this->Record->select("download_files.*")->from("download_files")->
			innerJoin("download_file_packages", "download_file_packages.file_id", "=", "download_files.id", false)->
			innerJoin("package_pricing", "package_pricing.package_id", "=", "download_file_packages.package_id", false)->
			innerJoin("services", "services.pricing_id", "=", "package_pricing.id", false)->
			where("services.status", "=", "active")->
			where("services.client_id", "=", $client_id)->
			where("download_files.company_id", "=", $company_id)->
			where("download_files.permit_packages", "=", "1");
		
		if ($category_id !== false)
			$this->Record->where("download_files.category_id", "=", $category_id);
		
		$subquery_package = $this->Record->get();
		$values = $this->Record->values;
		$this->Record->reset();
		$this->Record->values = $values;
		
		return $this->Record->select("temp.*")->from(array("((" . $subquery_public . ") UNION (" . $subquery_group . ") UNION (" . $subquery_package . "))" => "temp"));
	}
	
	/**
	 * Attaches a list of client groups to a file
	 *
	 * @param int $file_id The ID of the file the client groups should be attached to
	 * @param array $client_group_ids An array of client group IDs
	 */
	private function addFileGroups($file_id, $client_group_ids) {
		// Set all file client groups
		foreach ($client_group_ids as $client_group_id)
			$this->Record->insert("download_file_groups", array('file_id'=>$file_id, 'client_group_id'=>$client_group_id));
	}
	
	/**
	 * Deletes all client groups attached to this file
	 *
	 * @param int $file_id The ID of the file whose client groups to remove
	 */
	private function deleteFileGroups($file_id) {
		$this->Record->from("download_file_groups")->where("file_id", "=", $file_id)->delete();
	}
	
	/**
	 * Fetches all of the client groups that this file belongs to
	 *
	 * @param int $file_id The ID of the file whose client groups to fetch
	 * @return array A list of client group IDs that are assigned to the given file
	 */
	public function getFileGroups($file_id) {
		return $this->Record->select("client_group_id")->from("download_file_groups")->
			where("file_id", "=", $file_id)->fetchAll();
	}
	
	/**
	 * Fetches all of the client groups that this file belongs to
	 *
	 * @param int $file_id The ID of the file whose client groups to fetch
	 * @return array A list of client group IDs that are assigned to the given file
	 */
	public function getFilePackages($file_id) {
		return $this->Record->select("package_id")->from("download_file_packages")->
			where("file_id", "=", $file_id)->fetchAll();
	}
	
	/**
	 * Attaches a list of packages to a file
	 *
	 * @param int $file_id The ID of the file the packages should be attached to
	 * @param array $package_ids An array of packages IDs
	 */
	private function addFilePackages($file_id, $package_ids) {
		// Set all file packages
		foreach ($package_ids as $package_id)
			$this->Record->insert("download_file_packages", array('file_id'=>$file_id, 'package_id'=>$package_id));
	}
	
	/**
	 * Deletes all packages attached to this file
	 *
	 * @param int $file_id The ID of the file whose packages to remove
	 */
	private function deleteFilePackages($file_id) {
		$this->Record->from("download_file_packages")->where("file_id", "=", $file_id)->delete();
	}
	
	/**
	 * Converts the given file name into an appropriate file name to store to disk
	 *
	 * @param string $file_name The name of the file to rename
	 * @return string The rewritten file name in the format of YmdTHisO_[hash][ext] (e.g. 20121009T154802+0000_1f3870be274f6c49b3e31a0c6728957f.txt)
	 */
	public function makeFileName($file_name) {
		$ext = strrchr($file_name, ".");
		$file_name = md5($file_name . uniqid()) . $ext;
		
		return $this->dateToUtc(date("c"), "Ymd\THisO") . "_" . $file_name;
	}
	
	/**
	 * Validates that the given client groups may be added for this file
	 *
	 * @param array $client_group_ids A list of client group IDs
	 * @param int $permit_client_groups Whether or not client groups are permitted (0 or 1)
	 * @return boolean True if the client groups are valid, false otherwise
	 */
	public function validateGroups($client_group_ids, $permit_client_groups) {
		// Client groups are not permitted, don't bother checking them
		if ($permit_client_groups == "0")
			return true;
		
		// Make sure all client groups being added actually exist
		if ($client_group_ids) {
			foreach ($client_group_ids as $client_group_id) {
				if (!$this->validateExists($client_group_id, "id", "client_groups"))
					return false;
			}
		}
		
		return true;
	}
	
	/**
	 * Validates that the given packages may be added for this file
	 *
	 * @param array $package_ids A list of package IDs
	 * @param int $permit_packages Whether or not packages are permitted (0 or 1)
	 * @return boolean True if the packages are valid, false otherwise
	 */
	public function validatePackages($package_ids, $permit_packages) {
		// Packages are not permitted, don't bother checking them
		if ($permit_packages == "0")
			return true;
		
		// Make sure all packages being added actually exist
		if ($package_ids) {
			foreach ($package_ids as $package_id) {
				if (!$this->validateExists($package_id, "id", "packages"))
					return false;
			}
		}
		
		return true;
	}
	
	/**
	 * Validates that the file or file name provided is valid
	 *
	 * @param string $file_name The name of the file
	 * @param array $files A list of files in the format of post data $_FILES
	 * @return boolean True if the file exists or has been uploaded, false otherwise
	 */
	public function validateFile($file_name, array $files) {
		// If the file name is provided, it must be an absolute path to the file
		if ($file_name !== null) {
			// Validate absolute path to the file
			if (file_exists($file_name))
				return true;
		}
		// Require an uploaded 'file' be set. Errors with actually uploading the file
		// will be set by DownloadManagerFiles::add() and DownloadManagerFiles::edit()
		elseif (isset($files['file']['size']) && $files['file']['size'] != 0)
			return true;
		
		return false;
	}
	
	/**
	 * Validates that at least one of the given availability options is set, but not all
	 *
	 * @param int $permit_public Whether or not to permit the public to view this file (1 or 0. if 1, all others must be 0)
	 * @param int $permit_client_groups Whether or not to permit client groups to view this file (1 or 0)
	 * @param int $permit_packages Whether or not to permit certain packages to view this file (1 or 0)
	 * @return boolean True if the availability options validate, false otherwise
	 */
	public function validateFileAssignment($permit_public, $permit_client_groups, $permit_packages) {
		// Either public is selected, and nothing else, or at least one of the others is selected and not public
		if ($permit_public == "1" && $permit_client_groups == "0" && $permit_packages == "0")
			return true;
		elseif ($permit_public != "1" && ($permit_client_groups == "1" || $permit_packages == "1"))
			return true;
		return false;
	}
	
	/**
	 * Retrieves a list of rules to validate add/editing files
	 *
	 * @param array $vars A list of input vars to validate
	 * @param array $files A list of files in the format of post data $_FILES
	 * @param boolean $edit True to fetch the edit rules, false to fetch the add rules (optional, default false)
	 * @return array A list of rules
	 */
	private function getRules(array $vars, array $files, $edit = false) {
		$rules = array(
			'category_id' => array(
				'exists' => array(
					'if_set' => true,
					'rule' => array(array($this, "validateExists"), "id", "download_categories"),
					'message' => $this->_("DownloadManagerFiles.!error.category_id.exists")
				)
			),
			'company_id' => array(
				'exists' => array(
					'rule' => array(array($this, "validateExists"), "id", "companies"),
					'message' => $this->_("DownloadManagerFiles.!error.company_id.exists")
				)
			),
			'name' => array(
				'empty' => array(
					'rule' => "isEmpty",
					'negate' => true,
					'message' => $this->_("DownloadManagerFiles.!error.name.empty")
				)
			),
			'file_name' => array(
				'format' => array(
					'rule' => array(array($this, "validateFile"), $files),
					'message' => $this->_("DownloadManagerFiles.!error.file_name.format")
				)
			),
			'public' => array(
				'format' => array(
					'if_set' => true,
					'rule' => array("in_array", array("0", "1")),
					'message' => $this->_("DownloadManagerFiles.!error.public.format")
				),
				'assignment' => array(
					'rule' => array(array($this, "validateFileAssignment"), $this->ifSet($vars['permit_client_groups'], "0"), $this->ifSet($vars['permit_packages'], "0")),
					'message' => $this->_("DownloadManagerFiles.!error.public.assignment")
				)
			),
			'permit_client_groups' => array(
				'format' => array(
					'if_set' => true,
					'rule' => array("in_array", array("0", "1")),
					'message' => $this->_("DownloadManagerFiles.!error.permit_client_groups.format")
				)
			),
			'file_groups' => array(
				'format' => array(
					'if_set' => true,
					'rule' => array(array($this, "validateGroups"), $this->ifSet($vars['permit_client_groups'], "0")),
					'message' => $this->_("DownloadManagerFiles.!error.file_groups.format")
				)
			),
			'permit_packages' => array(
				'format' => array(
					'if_set' => true,
					'rule' => array("in_array", array("0", "1")),
					'message' => $this->_("DownloadManagerFiles.!error.permit_packages.format")
				)
			),
			'file_packages' => array(
				'format' => array(
					'if_set' => true,
					'rule' => array(array($this, "validatePackages"), $this->ifSet($vars['permit_packages'], "0")),
					'message' => $this->_("DownloadManagerFiles.!error.file_packages.format")
				)
			)
		);
		
		if ($edit) {
			// Update rules, check that the file exists
			$rules['file_id'] = array(
				'exists' => array(
					'rule' => array(array($this, "validateExists"), "id", "download_files"),
					'message' => $this->_("DownloadManagerFiles.!error.file_id.exists")
				)
			);
		}
		
		return $rules;
	}
}
?>