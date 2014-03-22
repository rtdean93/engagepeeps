<?php
Loader::load(VENDORDIR . "amazons3" . DS . "S3.php");
/**
 * Amazon S3 component that backs up file data.
 * 
 * @package blesta
 * @subpackage blesta.components.net.amazon_s3
 * @copyright Copyright (c) 2010, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class AmazonS3 extends S3 {
	
	/**
	 * Constructs a new AmazonS3 component, setting the credentials
	 *
	 * @param string $access_key The access key
	 * @param string $secret_key The secret key
	 * @param boolean $use_ssl Whether or not to use SSL when communicating
	 */
	public function __construct($access_key, $secret_key, $use_ssl=true) {
		parent::__construct($access_key, $secret_key, $use_ssl);
	}
	
	/**
	 * Uploads a file to Amazon S3
	 *
	 * @param string $file The full path of the file on the local system to upload
	 * @param string $bucket The name of the bucket to upload to
	 * @param string $remote_file_name The name of the file on the S3 server, null will default to the same file name as the local file
	 * @return boolean True if the file was successfully uploaded, false otherwise
	 */
	public function upload($file, $bucket, $remote_file_name=null) {
		if (!file_exists($file))
			return false;
		
		if ($remote_file_name === null)
			$remote_file_name = baseName($file);
			
		if ($this->putObjectFile($file, $bucket, $remote_file_name))
			return true;
		return false;
	}
}
?>