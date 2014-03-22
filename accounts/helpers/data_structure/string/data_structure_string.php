<?php
/**
 * String Data Structure helper
 *
 * Provides utility methods to assist in manipulating strings.
 *
 * @package blesta
 * @subpackage blesta.helpers.data_structure.string
 * @copyright Copyright (c) 2010, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class DataStructureString {
	
	/**
	 * Generates a random string from a list of characters
	 *
	 * @param int $length The length of the random string
	 * @param string $pool The pool of characters to include in the random string, defaults to alpha numeric characters. Can be configured further in $options['types']
	 * @param array $options An array of options including:
	 * 	- types A numerically indexed-array of character-types that may be used to generate the random string (i.e. "alpha", "alpha_lower", "alpha_upper", and/or "numeric") (optional)
	 * @return string A randomly generated word with the given length
	 */
	public function random($length=8, $pool=null, array $options=array()) {
		$alpha_lower = "abcdefghijklmnopqrstuvwxyz";
		$alpha_upper = "ABCDEFGHIJKLMNOPQRSTUVWXYZ";
		$numeric = "0123456789";
		
		if (!$pool) {
			
			// Set the valid characters to use in the random word
			if (!isset($options['types']) || !is_array($options['types']))
				$pool .= $alpha_lower . $alpha_upper . $numeric;
			else {
				// Filter out the given character types
				foreach ($options['types'] as $character_type) {
					switch($character_type) {
						case "alpha_lower":
							$pool .= $alpha_lower;
							break;
						case "alpha_upper":
							$pool .= $alpha_upper;
							break;
						case "alpha":
							$pool .= $alpha_lower . $alpha_upper;
							break;
						case "numeric":
							$pool .= $numeric;
							break;
					}
				}
			}
		}
		
		$str = "";
		$max_index = strlen($pool)-1;

		if ($max_index >= 0) {
			for ($i=0; $i<$length; $i++)
				$str .= $pool[mt_rand(0, $max_index)];
		}
		
		return $str;
	}
}
?>