<?php
/**
 * Upgrade Utility that all upgrade objects must extend
 * 
 * @package blesta
 * @subpackage blesta.components.upgrades
 * @copyright Copyright (c) 2010, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
abstract class UpgradeUtil {
	/**
	 * Returns a numerically indexed array of tasks to execute for the upgrade process
	 *
	 * @retrun array A numerically indexed array of tasks to execute for the upgrade process
	 */
	abstract function tasks();
	
	/**
	 * Processes the given task
	 *
	 * @param string $task The task to process
	 */
	abstract function process($task);
	
	/**
	 * Rolls back all tasks completed for the upgrade process
	 */
	abstract function rollback();
	
	/**
	 * Return all validation errors encountered
	 *
	 * @return mixed Boolean false if no errors encountered, an array of errors otherwise
	 */
	public function errors() {
		if (isset($this->Input) && is_object($this->Input) && $this->Input instanceof Input)
			return $this->Input->errors();
	}
}
?>