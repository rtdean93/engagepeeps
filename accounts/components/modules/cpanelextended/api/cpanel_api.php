<?php

/**
 * A class that handle connections to cPanel API
 * It's also checking if the JSON response is valid
 * and returns status message
 *
 * @author Dominik Gacek
 * @version 1.0
 */

class cPanelApi 
{
    /**
     * Hostname where the cPanel is installed
     * 
     * @var string
     */
    
    protected $hostname = "localhost";
    
    /**
     * IP address to the hostname where the cPanel is located
     * 
     * @var string 
     */
    
    protected $ip = "127.0.0.1";
    
    /**
     * The port at wich cPanel is located
     * by default it's 2086
     * 
     * @var integer
     */
    
    protected $port = 2087;
    
    /*
     * A URL address where the requests go to
     * 
     * @var string
     */
    
    protected $url;
    
    /**
     * A URL Query that combine the data from user
     * 
     * @var string
     */
    
    protected $query;
    
    /**
     * Path to the API action
     * 
     * @var string
     */
    
    protected $path;
    
    /**
     * A reselt of working function _curlExec
     * with the data retrieved from API or an error code
     * 
     * @var string
     */
    
    protected $response;
    
    /**
     * A message with a status code "success" or
     * error message if something went wrong
     * 
     * @var string
     */
    
    protected $statusmsg;
    
    /**
     * A current username
     * 
     * @var string
     */
    
    protected $username = 'root';
    
    /**
     * A server username that we conenct with
     * 
     * @var string 
     */
    
    protected $serverusername = 'root';
    
    /**
     * A server password that we can connect with
     * 
     * @var string
     */
    
    protected $serverpassword = '';
    
    /**
     * An access key, that we can use instead of password
     * 
     * @var string
     */
    
    protected $key = '';
    
    /**
     * Wheather SSL connect is enabled or disabled (default enabled)
     * 
     * @var boolean
     */
    
    protected $usessl = true;
    

    /**
     * Create a new instance and set the data from global params
     * 
     * @param array $params
     */
    
    public function __construct($hostname, $username = 'root', $password = '', $port = 2087, $usessl = true, $key = '', $currentUsername = '')
    {
        $this->hostname         = $hostname;
        //$this->ip               = $ip;
        $this->serverusername   = $username;
        $this->serverpassword   = $password;
        $this->username         = $currentUsername;
        $this->port             = $port;
        $this->usessl           = $usessl;
        $this->key              = $key;
        $this->url              = $this->buildUrl();
    }
    
    /**
     * A magic method that can handle any request to API 
     */
    
    public function __call($method, $arguments = array())
    {
        return $this->sendRequest('json-api/'.$method, isset($arguments[0]) ? $arguments[0] : array());
    }
    
    /**
     * Create an user account
     * 
     * @param array $params
     */
    
    public function createacct($params)
    {
        return $this->sendRequest('json-api/createacct', $params);
    }
    
    /**
     * Suspend an user account
     * 
     * @param array $params
     */
    
    public function suspendacct($params)
    {
        return $this->sendRequest('json-api/suspendacct', $params);
    }
    
    /**
     * Change an user package
     * 
     * @param array $params
     */
    
    public function changepackage($params)
    {
        return $this->sendRequest('json-api/changepackage', $params);
    }
    
    /**
     * Show account summary
     */
    
    public function accountsummary()
    {
        if (empty($this->username)) {
            return false;
        }

        return $this->sendRequest('json-api/accountsummary', array('user' => $this->username));
    }
    
    /**
     * Modify an user account
     */
    
    public function modifyacct($params)
    {
        return $this->sendRequest('json-api/modifyacct', $params);
    }
    
    /**
     * Get response is typecasted to string
     */
    
    public function __toString() 
    {
        $response = $this->getCleanResponse();

        if(is_string($response))
        {
            return $response;
        }
        else
        {
            return '';
        }
    }
    
    /**
     * Method wich creating a correct URL for requests from params
     * 
     * @return string
     */
    
    public function buildUrl()
    {
        return "http". ($this->usessl ? "s" : "") ."://".$this->hostname.":".$this->port."/";
    }
    
    /**
     * Prepare a request to API
     * 
     * @param string $path
     * @param array $data
     * @return \cPanelApi
     */
    
    public function sendRequest($path, $data = array())
    {
        $this->path  = $path;
        $this->query = "?".http_build_query($data);
        $this->response = '';
            
        if($this->_curlExecute())
        {
            $this->checkAndGetJson();
        }

        return $this;
    }
    
    /**
     * Checks whether connection is successfull
     * 
     * @return boolean true or false
     */
    
    public function checkConnection()
    {
        if(empty($this->hostname) or empty($this->serverusername) or (empty($this->serverpassword) and empty($this->key))) return false;
        
        $request = $this->sendRequest('/json-api/version');
        $response = $request->getResponse();
        
        if(isset($response->version)) 
            return true;
        
        return false;
    }
    
    /**
     * Prepare a request to API2 version via sendRequest method
     * 
     * @param string $module
     * @param string $action
     * @param array $data
     * @return \cPanelApi
     */
    
    public function sendApi2Request($module, $action, $data = array())
    { 
        $data = array_merge(array(
            "cpanel_jsonapi_user"       => $this->username,
            "cpanel_jsonapi_module"     => urlencode($module),
            "cpanel_jsonapi_func"       => urlencode($action),
            "cpanel_jsonapi_apiversion"    => 2
        ), $data);

        return $this->sendRequest('/json-api/cpanel', $data);
    }
    
    /**
     * Prepare a request to API1 version via sendRequest method
     * 
     * @param string $module
     * @param string $action
     * @param array $data
     * @return \cPanelApi
     */
    
    public function sendApi1Request($module, $action, $data = array())
    { 
        $args = array();
        $argcount = 0;
        
        foreach($data as $key => $value)
        {
            $args['arg-'.$argcount] = urlencode($value);
            $argcount++;
        }
        
        $data = array_merge(array(
            "cpanel_jsonapi_user"       => $this->username,
            "cpanel_jsonapi_module"     => urlencode($module),
            "cpanel_jsonapi_func"       => urlencode($action),
            "cpanel_jsonapi_apiversion"    => 1,
        ), $args);        

        return $this->sendRequest('/json-api/cpanel', $data);
    }
    
    
    /**
     * Returns status message of the operation
     * 
     * @return void
     */
    
    public function checkAndGetJson()
    {
        $result = json_decode($this->response);

        if(! $result)
        {
            $this->statusmsg = 'Invalid data format, or internal API error';
        }
        if (isset($result->status) && $result->status == 0) {
                $this->statusmsg = $result->statusmsg;
                return false;
        }
        elseif (isset($result->result) && is_array($result->result) && isset($result->result[0]->status) && $result->result[0]->status == 0) {
                $this->statusmsg = $result->result[0]->statusmsg;
                return false;
        }
        elseif (isset($result->cpanelresult) && !empty($result->cpanelresult->error)) {
                $this->statusmsg = (isset($result->cpanelresult->data->reason) ? $result->cpanelresult->data->reason : $result->cpanelresult->error);
                return false;
        }
        elseif (isset($result->cpanelresult->data[0]->status) && $result->cpanelresult->data[0]->status === 0) {
                $this->statusmsg = (isset($result->cpanelresult->data[0]->statusmsg) ? $result->cpanelresult->data[0]->statusmsg : $result->cpanelresult->error);
                return false;
        }
        elseif (isset($result->cpanelresult) && !empty($result->cpanelresult->error)) {
                $this->statusmsg = (isset($result->cpanelresult->data->reason) ? $result->cpanelresult->data->reason : $result->cpanelresult->error);
                return false;
        }
        
        $this->statusmsg =  'success';
        return true;
        
        /*
        if(is_object($json) and isset($json->cpanelresult))
        {
            if(isset($json->cpanelresult->error))
            {
                $this->statusmsg = $json->cpanelresult->error;
            }
            elseif(isset($json->cpanelresult->data->statusmsg))
            {
                $this->statusmsg = $json->cpanelresult->data->statusmsg;
            }
            elseif(isset($json->cpanelresult->data[0]->statusmsg))
            {
                $this->statusmsg = $json->cpanelresult->data[0]->statusmsg;
            }

            $this->statusmsg = "success";
        }
        else 
        {
            $this->statusmsg = "Unknown error. Please try again";
        }*/
    }
    
    /**
     * Returns status message
     * 
     * @return string
     */
    
    public function getResultMessage()
    {
        return ! empty($this->statusmsg) ? $this->statusmsg : "Unknown error";
    }
    
    /**
     * Check weather request was successfull
     * 
     * @return boolean
     */
    
    public function isSuccess()
    {
        return ($this->statusmsg == 'success');
    }
    
    /**
     * Decode, and generate an array or object from response
     * 
     * @param type $toArray
     * @return array or object
     */
    
    public function getResponse($toArray = false)
    {
        return json_decode($this->response, $toArray);
    }
    
    /**
     * Get clean JSON encoded response
     * 
     * @return string
     */
    
    public function getCleanResponse()
    {
        return $this->response;
    }
    
    /**
     * Make a request via Curl to API
     * 
     * @return boolean
     */
    
    protected function _curlExecute()
    {
        $curl = curl_init();		
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);	
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0); 	
        curl_setopt($curl, CURLOPT_HEADER, 0);			
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_PORT, $this->port);
        //curl_setopt($curl, CURLOPT_SSLVERSION, 3);
        curl_setopt($curl, CURLOPT_TIMEOUT, 90);

        if(! empty($this->serverpassword))
        {
            curl_setopt($curl, CURLOPT_HTTPHEADER, array("Authorization: Basic " . base64_encode($this->serverusername.":".$this->serverpassword) . "\n\r"));
        }
        else
        {
            curl_setopt($curl, CURLOPT_HTTPHEADER, array("Authorization: WHM $this->serverusername:" . preg_replace("'(\r|\n)'", "", $this->key)));
        }

        //curl_setopt($curl, CURLOPT_BUFFERSIZE, 131072);
        //curl_setopt($curl, CURLOPT_USERAGENT, "Mozilla/4.0 (compatible; MSIE 5.01; Windows NT 5.0)");
        //curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($curl, CURLOPT_URL, $this->url.$this->path.$this->query);	
        $result = curl_exec($curl);
        $errorscount = curl_errno($curl);
        $error = curl_error($curl);
        
        curl_close($curl);
        
        if($errorscount > 0)
        {
            $this->statusmessage = $error;
   
            return false;
        }
        else
        {
            $this->response = $result;
            
            return true;
        }
        
    }
}

?>
