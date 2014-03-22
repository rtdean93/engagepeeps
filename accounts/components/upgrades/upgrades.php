<?php
Loader::load(dirname(__FILE__) . DS . "lib" . DS . "upgrade_util.php");
/**
 * Handles the upgrade process to bring the current database up to the
 * requirements of the installed files.
 *
 * @package blesta
 * @subpackage blesta.components.upgrades
 * @copyright Copyright (c) 2010, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class Upgrades extends Model {
	
	/**
	 * Setup
	 */
	public function __construct($db_info = null) {
		if ($db_info !== null)
			Configure::set("Blesta.database_info", $db_info);

		parent::__construct($db_info);
		
		Loader::loadComponents($this, array("Input"));
	}
	
	/**
	 * Returns all upgrade mappings
	 *
	 * @return array An array in key/value pairs where each key is the version from and each value is the version to
	 */
	public function getMappings() {
		Configure::load("mappings", dirname(__FILE__) . DS . "tasks" . DS);
		
		return Configure::get("Upgrade.mappings");
	}
	
	/**
	 * Starts the upgrade process
	 *
	 * @param string $from The version to start the upgrade from
	 * @param string $to The version to upgrade to, null to upgrade to latest version
	 * @param callback $callback The callback to execute after each task in the upgrade process (for each version)
	 */
	public function start($from, $to, $callback = null) {
		Loader::loadModels($this, array("Settings"));
		
		// Ensure config is writable
		$config_file = CONFIGDIR . "blesta.php";
		if (file_exists($config_file) && !is_writable($config_file)) {
			$this->Input->setErrors(array('config' => array('permission' => "Config file must be writable: " . $config_file)));
			return;
		}
		
		$upgrades = $this->getUpgrades($from, $to);
		$mappings = $this->getMappings();
		
		// Process each upgrade in the order given
		foreach ($upgrades as $from => $filename) {
			$class_name = Loader::toCamelCase(substr($filename, 0, -4));

			Loader::load(dirname(__FILE__) . DS . "tasks" . DS . $filename);
			
			$upgrade = new $class_name();
			$this->processObject($upgrade, $callback);
			
			if (($errors = $this->Input->errors()))
				return;
			
			// Update the stored database version to this version
			$this->Settings->setSetting("database_version", $mappings[$from]);
		}
	}
	
	/**
	 * Generates a mapping of all files
	 *
	 * @param string $from The version to start the upgrade from
	 * @param string $to The version to upgrade to, null to upgrade to latest version
	 * @throws Exception Thrown if the required upgrade files are not present
	 */
	public function getUpgrades($from, $to) {
		$mappings = $this->getMappings();
		
		$upgrades = array();
		foreach ($mappings as $start => $end) {
			if (version_compare($from, $start, "<=") && ($to === null || version_compare($to, $end, ">="))) {
				$filename = "upgrade" . str_replace(array(".", "-"), "_", $mappings[$from]);
				$upgrades[$from] = Loader::fromCamelCase($filename) . ".php";
				
				if (!file_exists(dirname(__FILE__) . DS . "tasks" . DS . $upgrades[$from]))
					throw new Exception("Missing upgrade file: " . $filename);
					
				$from = $end;
			}
		}
		return $upgrades;
	}
	
	/**
	 * Processes the given object, passes the callback to the object
	 * by passing the current task count being executed and the total number
	 * of tasks to be executed for that object.
	 *
	 * @param string $obj The full path to the SQL file to execute
	 * @param callback $callback The callback to execute after each task in the upgrade process
	 */
	public function processObject($obj, $callback = null) {
		
		$total_tasks = count($obj->tasks());
		
		$i=0;
		foreach ($obj->tasks() as $task) {
			if ($callback)
				call_user_func($callback, $i, $total_tasks);
			$obj->process($task);
			
			if (($errors = $obj->errors())) {
				$obj->rollback();
				$this->Input->setErrors($errors);
				return;
			}
			$i++;
		}
		
		// Finished
		if ($callback)
			call_user_func($callback, $i, $total_tasks);
	}
	
	/**
	 * Processes the given SQL file, executes the given callback after each query
	 * by passing the current query number being executed and the total number
	 * of queries to be executed for that file.
	 *
	 * @param string $file The full path to the SQL file to execute
	 * @param callback $callback The callback to execute after each query
	 * @throws PDOExcetion if any query fails
	 */
	public function processSql($file, $callback = null) {
		
		$queries = explode(";\n", file_get_contents($file));
		$query_count = count($queries);
		
		$i=0;
		foreach ($queries as $query) {
			if ($callback)
				call_user_func($callback, $i, $query_count);
			
			// conserve memory
			array_shift($queries);
			
			if (trim($query) != "") {
				$this->query($query);
			}
			
			$i++;
		}
		
		// Finished
		if ($callback)
			call_user_func($callback, $i, $query_count);
	}
	
	/**
	 * Return all errors
	 *
	 * @return array An array of errors
	 */
	public function errors() {
		return $this->Input->errors();
	}
}
?>