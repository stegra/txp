<?php

class HTTPRequest
{	
	var $useTable = false;
	var $connection_timeout = '30';
    
    /**
     * Set this to active if security is very important for your ssl connection, however I had
     * (valid) certification that would not pass the curl libraries ssl checks.
     */
	var $ssl_strict = false;	

// -------------------------------------------------------------------------------------
 	function httpPost($url, $vars = null, $headers = null, $cookie_file = null, $timeout = null)
    {
    	$vars = $this->__toUrlData($vars);
		
		$ch = curl_init();
    	if (! $ch)
    	{			
    		return false;
    	}
    	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    	
    	// Don't check certifications that closley if not required, fixed some issues for me before
    	if ($this->ssl_strict==false)
    	{
        	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        	curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);    	
    	}
    	
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $vars);        	

        if (empty($timeout))
            $timeout = $this->connection_timeout;    
        
        curl_setopt($ch,CURLOPT_TIMEOUT, $timeout);
    	curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION,1); // follow redirects recursively    	
        
        if (!empty($headers))
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
                        
        if (!empty($cookie_file))
        {
            curl_setopt($ch, CURLOPT_COOKIEJAR, $cookie_file);
            curl_setopt($ch, CURLOPT_COOKIEFILE, $cookie_file);
        }
        
       	$response = curl_exec($ch);
		curl_close($ch);           	
			    
		return $response;        
    }
 
 // -------------------------------------------------------------------------------------

    function httpGet($url, $vars = null, $headers = null, $cookie_file = null, $timeout = null)
    {   
    	if (!empty($vars))
            $url = $url.'?'.$this->__toUrlData($vars);

		$ch = curl_init();
        
    	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);    	
    	
    	// Don't check certifications that closley if not required, fixed some issues for me before
    	if ($this->ssl_strict==false)
    	{
        	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        	curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);    	
    	}    	
    	        
        curl_setopt($ch, CURLOPT_URL, $url);
        
        if (empty($timeout))
            $timeout = $this->connection_timeout;    
        
        curl_setopt($ch,CURLOPT_TIMEOUT, $timeout);        
        
        if (!empty($headers))
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
                        
        if (!empty($cookie_file))
        {
            curl_setopt($ch, CURLOPT_COOKIEJAR, $cookie_file);
            curl_setopt($ch, CURLOPT_COOKIEFILE, $cookie_file);
        }
                       
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, strtoupper('get'));
        curl_setopt($ch, CURLOPT_VERBOSE, 1); ########### debug
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION,1); // follow redirects recursively
    
        $ret = curl_exec($ch);
        
        curl_close($ch);
        
        
        return $ret;   
    }  

// -------------------------------------------------------------------------------------
    
    function __toUrlData($arrayData)
    {
        $postData = array();
        
        foreach ($arrayData as $key => $val)
        {
            array_push($postData, $key.'='.urlencode($val));
        }
        
        return join('&', $postData);
    }            
}

?>