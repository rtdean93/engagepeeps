<?php
/**
 * Marketplace
 *
 * @package blesta
 * @subpackage blesta.app.models
 * @copyright Copyright (c) 2010, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class Marketplace extends AppModel {
	
	/**
	 * Fetches a list of extensions
	 *
	 * @return mixed An array of extensions, null if the marketplace is not available
	 */
	public function getList($category=null, $page=1) {
		return $this->fetch(Configure::get("Blesta.marketplace_url"), compact("category", "page"));
	}
	
	/**
	 * Fetches 
	 */
	public function search($query, $page=1) {
		#
		# TODO: Implement searching capabilities when available
		#
	}
	
	/**
	 * Fetches a specific extension from the marketplace
	 *
	 * @param int $id the ID of the extension to fetch
	 * @return mixed An array of extension information, null if the store or extension is not available
	 */
	public function getExtension($id) {
		return $this->fetch(Configure::get("Blesta.marketplace_url"), compact("id"));
	}
	
	/**
	 * Makes a remote request to fetch the given resource
	 *
	 * @param string $url The URL to request
	 * @param array $field An array of parameters to submit with the requested URL
	 * @return mixed An array representing the requested resource, null if the resource could not be found or could not be parsed
	 */
	private function fetch($url, array $fields=array()) {
		
		if (!isset($this->Net))
			Loader::loadComponents($this, array("Net"));
		if (!isset($this->Json))
			Loader::loadComponents($this, array("Json"));
			
		$http = $this->Net->create("Http");
		
		// Timeout after 10 seconds
		$http->setTimeout(10);
		
		$result = $http->get($url, $fields);
		
		try {
			if ($http->responseCode() == "200")
				return $this->Json->decode($result);
		}
		catch (Exception $e) {
			// An exception occured... nothing we need to do about it
			echo $e->getMessage();
		}
		return null;
	}

}
?>