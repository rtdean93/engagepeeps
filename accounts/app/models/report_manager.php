<?php
/**
 * Report Manager
 *
 * @package blesta
 * @subpackage blesta.app.models
 * @copyright Copyright (c) 2013, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class ReportManager extends AppModel {
	
	/**
	 * @var The path to the temp directory
	 */
	private $temp_dir = null;
	/**
	 * @var The company ID for this report
	 */
	private $company_id = null;
	
	/**
	 * Load language
	 *
	 * @param int $company_id The company ID 
	 */
	public function __construct($company_id = null) {
		parent::__construct();
		Language::loadLang(array("report_manager"));
		Loader::loadComponents($this, array("Download", "SettingsCollection"));
		
		// Set the date formats
		if (!isset($this->Companies))
			Loader::loadModels($this, array("Companies"));
		
		$this->company_id = ($company_id ? $company_id : Configure::get("Blesta.company_id"));
		$this->Date->setTimezone("UTC", $this->Companies->getSetting($this->company_id, "timezone")->value);
		$this->Date->setFormats(array(
			'date'=>$this->Companies->getSetting($this->company_id, "date_format")->value,
			'date_time'=>$this->Companies->getSetting($this->company_id, "datetime_format")->value
		));
		
		// Set the temp directory
		$temp_dir = $this->SettingsCollection->fetchSystemSetting(null, "temp_dir");
		if (isset($temp_dir['value']))
			$this->temp_dir = $temp_dir['value'];
	}
	
	/**
	 * Instantiates the given report and returns its instance
	 *
	 * @param string $class The name of the class in file_case to load
	 * @return An instance of the report specified
	 */
	private function loadReport($class) {
		// Load the report factory if not already loaded
		if (!isset($this->Reports))
			Loader::loadComponents($this, array("Reports"));
		
		// Instantiate the module and return the instance
		return $this->Reports->create($class);
	}
	
	/**
	 * Retrieves a list of report formats
	 *
	 * @return array A list of report formats and their language
	 */
	public function getFormats() {
		return array(
			'csv' => $this->_("ReportManager.getformats.csv")
		);
	}
	
	/**
	 * Retrieves the name for the given report type
	 *
	 * @param string $type The type of report to fetch the name for
	 * @return string The name of the report
	 */
	public function getName($type) {
		$this->Input->setRules($this->getRules());
		$params = array('type' => $type);
		
		if ($this->Input->validates($params)) {
			// Instantiate the report
			$report = $this->loadReport($type);
			
			return $report->getName();
		}
	}
	
	/**
	 * Retrieves a list of all available reports (those that exist on the file system)
	 *
	 * @return array An array representing each report and its name
	 */
	public function getAvailable() {
		$reports = array();
		
		$dir = opendir(COMPONENTDIR . "reports");
		while (false !== ($report = readdir($dir))) {
			// If the file is not a hidden file, and is a directory, accept it
			if (substr($report, 0, 1) != "." && is_dir(COMPONENTDIR . "reports" . DS . $report)) {
				
				try {
					$rep = $this->loadReport($report);
					$reports[$report] = $rep->getName();
				}
				catch (Exception $e) {
					// The report could not be loaded, try the next
					continue;
				}
			}
		}
		return $reports;
	}
	
	/**
	 * Retrieves the options for the given report type. Sets Input errors on failure
	 *
	 * @param string $type The type of report to fetch the options for
	 * @param array $vars A list of option values to pass to the report (optional)
	 * @return string The options as a view
	 */
	public function getOptions($type, array $vars=array()) {
		$this->Input->setRules($this->getRules());
		$params = array('type' => $type);
		
		if ($this->Input->validates($params)) {
			// Instantiate the report
			$report = $this->loadReport($type);
			
			return $report->getOptions($this->company_id, $vars);
		}
	}
	
	/**
	 * Generates the report type with the given vars. Sets Input errors on failure
	 *
	 * @param string $type The type of report to fetch
	 * @param array $vars A list of option values to pass to the report
	 * @param string $csv The format of the report to generate (optional, default csv)
	 * @param string $return (optional, default "download") One of the following:
	 * 	- download To build and send the report to the browser to prompt for download; returns null
	 * 	- false To build and send the report to the browser to prompt for download; returns null
	 * 	- object To return a PDOStatement object representing the report data; returns PDOStatement
	 * 	- true To return a PDOStatement object representing the report data; returns PDOStatement
	 * 	- file To build the report and store it on the file system; returns the path to the file
	 * @return mixed A PDOStatement, string, or void based on the $return parameter
	 */
	public function fetchAll($type, array $vars, $format = "csv", $return = "download") {
		// Accept boolean return value for backward compatibility
		// Convert return to one of the 3 accepted types: download, object, file
		if ($return === true || $return == "true")
			$return = "object";
		elseif ($return === false || $return == "false")
			$return = "download";
		
		// Default to download
		$return = (in_array($return, array("download", "object", "file")) ? $return : "download");
		
		// Validate the report type/format are valid
		$rules = array(
			'format' => array(
				'valid' => array(
					'rule' => array("array_key_exists", $this->getFormats()),
					'message' => $this->_("ReportManager.!error.format.valid", true)
				)
			)
		);
		
		$params = array('type' => $type, 'format' => $format);
		$this->Input->setRules(array_merge($this->getRules(), $rules));
		
		if ($this->Input->validates($params)) {
			// Instantiate the report
			$report = $this->loadReport($type);
			
			// Build the report data
			$pdo_stmt = $report->fetchAll($this->company_id, $vars);
			
			// Return the PDOStatement object
			if ($return == "object")
				return $pdo_stmt;
			
			// Create the file
			$path_to_file = rtrim($this->temp_dir, DS) . DS . $this->makeFileName($format);
			
			if (empty($this->temp_dir) || !is_dir($this->temp_dir) || (file_put_contents($path_to_file, "") === false) || !is_writable($path_to_file)) {
				$this->Input->setErrors(array('temp_dir' => array('writable' => $this->_("ReportManager.!error.temp_dir.writable", true))));
				return;
			}
			
			// Build the report and send it to the browser
			$headings = $report->getColumns();
			
			$heading_names = array();
			$heading_format = array();
			$heading_options = array();
			foreach ($headings as $key => $value) {
				// Set name
				if (isset($value['name']))
					$heading_names[] = $value['name'];
				// Set any formatting
				if (isset($value['format'])) {
					$heading_format[$key] = $value['format'];
					
					// Set any options
					if (isset($value['options']))
						$heading_options[$key] = $value['options'];
				}
			}
			
			// Add the data to a temp file
			$content = $this->buildCsvRow($heading_names);
			// Create the file
			file_put_contents($path_to_file, $content);
			
			// Add row data
			while (($fields = $pdo_stmt->fetch())) {
				$row = array();
				// Build each cell value
				foreach ($headings as $key => $value) {
					$cell = (property_exists($fields, $key) ? $fields->{$key} : "");
					$formatting = (array_key_exists($key, $heading_format) ? $heading_format[$key] : null);
					$options = (array_key_exists($key, $heading_options) ? $heading_options[$key] : null);
					
					// Add a date format to the cell
					if ($formatting == "date" && !empty($cell))
						$cell = $this->Date->cast($cell, "date_time");
					// Replace the value with one of the options provided
					elseif ($formatting == "replace" && !empty($options) && is_array($options))
						$cell = (array_key_exists($cell, $options) ? $options[$cell] : $cell);
					
					$row[] = $cell;
				}
				
				// Add the row to the file
				file_put_contents($path_to_file, $this->buildCsvRow($row), FILE_APPEND);
			}
			
			// Return the path to the file on the file system
			if ($return == "file")
				return $path_to_file;
			
			// Download the data
			$new_file_name = "report-" . $type . "-" . $this->Date->cast(date("c"), "Y-m-d") . "." . $format;
			$this->Download->setContentType("text/" . $format);
			
			// Download from temp file
			$this->Download->downloadFile($path_to_file, $new_file_name);
			@unlink($path_to_file);
			exit();
		}
	}
	
	/**
	 * Creates a temporary file name to store to disk
	 *
	 * @param string $ext The file extension
	 * @return string The rewritten file name in the format of YmdTHisO_[hash].[ext] (e.g. 20121009T154802+0000_1f3870be274f6c49b3e31a0c6728957f.txt)
	 */
	private function makeFileName($ext) {
		$file_name = md5(uniqid()) . $ext;
		
		return $this->Date->format("Ymd\THisO", date("c")) . "_" . $file_name;
	}
	
	/**
	 * Uses Excel-style formatting for CSV fields (individual cells)
	 *
	 * @param mixed $field A single string of data representing a cell, or an array of fields representing a row
	 * @return mixed An escaped and formatted single cell or array of fields as given
	 */
	protected function formatCsv($field) {
		if (is_array($field)) {
			foreach ($field as &$cell)
				$cell = "\"" . str_replace('"', '""', $cell) . "\"";
			
			return $field;
		}
		return "\"" . str_replace('"', '""', $field) . "\"";
	}
	
	/**
	 * Builds a CSV row
	 *
	 * @param array $fields A list of data to place in each cell
	 * @return string A CSV row containing the field data
	 */
	protected function buildCsvRow(array $fields) {
		$row = "";
		$formatted_fields = $this->formatCsv($fields);
		$num_fields = count($fields);
		
		$i = 0;
		foreach ($fields as $key => $value)
			$row .= $formatted_fields[$key] . (++$i == $num_fields ? "\n" : ",");
		
		return $row;
	}
	
	/**
	 * Validates that the given report type exists
	 *
	 * @param string $type The report type
	 * @return boolean True if the report type exists, false otherwise
	 */
	public function validateType($type) {
		$reports = $this->getAvailable();
		
		return array_key_exists($type, $reports);
	}
	
	/**
	 * Returns the rules to validate the report type
	 *
	 * @return array A list of rules
	 */
	private function getRules() {
		return array(
			'type' => array(
				'valid' => array(
					'rule' => array(array($this, "validateType")),
					'message' => $this->_("ReportManager.!error.type.valid", true)
				)
			)
		);
	}
}
?>