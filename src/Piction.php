<?php

namespace Imamuseum\PictionClient;

use Exception;

class Piction
{
    /*
    Some remarks about Piction API and how this client works with it.
    - The REST API is a wrap of the existing SOAP webservice.
    - Calling methods in the API are done by GET
    - Piction has a particular URL structure to specify which method to call and which arguments to use.
      the following URL structure shows you what is the basic Piction REST API URL structure:
      http://piction.host.com/r/st/[method]/surl/[auth_token](/[param_name]/[param_value]/..)
    - The REST API can return return XML and JSON. By default it returns XML.

    NOTE: [ undocumented alternative way to access Piction ]

    Is possible access Piction using !soap.jsonget it basically is a
    wrap of the same SOAP webservice but it return JSON and instead of using
    a structure URL it use normal GET parameters. e.g.
    http://piction.host.com/r/!soap.jsonget?n=[method]&surl=[auth_token](&[param_name]=[param_value]/..)
    */

    public function __construct()
    {
        $dotenv = new \Dotenv\Dotenv(__DIR__.'/..');
        $dotenv->load();

        $this->endpoint = getenv('PICTION_ENDPOINT_URL');
        $this->username = getenv('PICTION_USERNAME');
        $this->password = getenv('PICTION_PASSWORD');
        $this->format   = getenv('PICTION_FORMAT');
        $this->surl     = getenv('PICTION_SURL');

        // Check if surl is null and if so lets get a new one to store.
        if ($this->surl === "null" || $this->surl === "") {
            $this->surl = $this->authenticate();
        }
    }

    public function authenticate()
    {
        $url = $this->endpoint .
            'piction_login/USERNAME/' . $this->username .
            '/PASSWORD/' . $this->password .
            '/' . $this->format . '/TRUE';

        $response = $this->_curlCall($url, $piction_method="", $params=[]);
        $response = $this->_to_json($response);
        $this->saveToken($response);

        return $response->SURL;
    }

    public function saveToken($response)
    {
        // get current .env and its contents
        $file = '.env';
        $fileContent = file_get_contents($file);

        // set term to search and use regular expression to match the whole line
        $searchTerm = "PICTION_SURL";
        $pattern = "/^.*$searchTerm.*\$/m";

        // store any matching occurences in $matches
        if(preg_match_all($pattern, $fileContent, $matches)){
           $oldSurl = implode("\n", $matches[0]);
        }

        // build new line to add to file and replace old one in file
        $newSurl = $searchTerm .'=' . $response->SURL;
        $newFileContent = str_replace($oldSurl, $newSurl, $fileContent);
        file_put_contents($file, $newFileContent);
    }

    private function _buildURL($piction_method, $params)
    {
        // Convert parameters to Piction URL structure
        // http://piction.host.com/r/st/[method]/surl/[auth_token](/[param_name]/[param_value]/..)
        $url = "";

        // Format a pair key-value list
        foreach ($params as $key => $value) {
            $url .= strtoupper($key) . '/' . $this->_prepareValue($value) . '/';
        }

        // Add format to url if the correct parameters exist
        if (isset($this->format) && !is_null($this->format) && !isset($params['format'])) {
            $url .= $this->format . '/TRUE/';
        } elseif (isset($params['format'])) {
            $url .= $params['format'] . '/TRUE/';
        }

        // Build the url to request
        $url = $this->endpoint . $piction_method . '/surl/' . $this->surl . '(/' . $url . ')';

        return $url;
    }

    private function _prepareValue($value)
    {
        /*
        Convert values to Piction values
        */
        if ($value != "") {
            // Replace spaces with %20 to send params correctly
            $value = str_replace(' ', '%20', $value);
        }

        // Check if the value is a boolean and set to correct type of string
        if ($this->_is_bool($value)) {
            if (($value != "") && ($value !== FALSE)) {
                $value = 'TRUE';
            } else {
                $value = 'FALSE';
            }
        }

        return $value;
    }

    private function _request($piction_method, $params)
    {
        /*
        Make a request to Piction and return response in the format requested.
        */

        // We don't send parameters as ?key=value because Piction uses a specific URL structure for it.
        // The following method will created that url
        $url = $this->_buildURL($piction_method, $params);

        // Call Piction
        $response = $this->_curlCall($url, $piction_method, $params);

        return $response;
    }

    private function _curlCall($url, $piction_method, $params)
    {
        //print($url);
        // Get cURL resource
        $curl = curl_init();
        // Set some options - we are passing in a useragent too here
        curl_setopt_array($curl, array(
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_URL => $url
        ));

        if(curl_exec($curl) === false) {
            echo 'Curl error: ' . curl_error($curl);
        } else {
            // Send the request & save response to $response
            $response = curl_exec($curl);
        }

        // Close request to clear up some resources
        curl_close($curl);

        // Check the response to see if a surl validation is thrown
        $response = $this->_checkResponse($response, $piction_method, $params);

        return $response;
    }

    private function _checkResponse($response, $piction_method, $params)
    {
        // If surl validation fails, get a new surl and run the request again
        if (strlen(strstr($response,'SURL failed validation')) > 0){
            $this->surl = $this->authenticate();
            $response = $this->_request($piction_method, $params);
        }

        return $response;
    }

    public function call($piction_method, $params, $follow_pagination=False)
    {
        /*
        Call a piction method and return response in the format requested.
        This method injects the authentication token in all calls.
        */

        $response = $this->_request($piction_method, $params);

        return $response;
    }

    /*************************
        Helper Functions
    *************************/

    // Test for boolean variable where is_bool() returns false positive;
    private function _is_bool($var)
    {
        if (!is_string($var))
            return (bool) $var;

        switch (strtolower($var)) {
            case '1':
            case 'true':
            case 'on':
            case 'yes':
            case 'y':
                return true;
        default:
            return false;
        }
    }

    // FORMAT RESPONSES
    // raw
    private function _to_raw($response)
    {
        return string($response);
    }

    // json
    private function _to_json($response)
    {
        try {
            return json_decode($response);
        }
        catch (Exception $e) {
            echo 'Caught exception: ', $e->getMessage(), '\n';
        }
    }

    // xml
    private function _to_xml($response)
    {
        try {
            $xml = simplexml_load_string($response);
        }
        catch (Exception $e) {
            echo 'Caught exception: ', $e->getMessage(), '\n';
        }
        return $xml->asXML();
    }
}
