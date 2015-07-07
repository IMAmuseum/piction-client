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

        $response = $this->_curlCall($url);

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

    private function _build_url($piction_method, $params)
    {
        # Convert parameters to Piction URL structure
        # http://piction.host.com/r/st/[method]/surl/[auth_token](/[param_name]/[param_value]/..)
        $url = "";

        # Format a pair key-value list
        foreach ($params as $key => $value) {
            $url .= strtoupper($key) . '/' . $this->_prepare_value($value) . '/';
        }

        if (isset($this->format) && !is_null($this->format)){
            $url .= $this->format . '/TRUE/';
        }

        $url = $this->endpoint . $piction_method . '/surl/' . $this->surl . '(/' . $url . ')';

        return $url;
    }

    private function _prepare_value($value)
    {
        /*
        Convert values to Piction values
        */
        if ($value != "") {
            $value = str_replace(' ', '%20', $value);
        }

        if (is_bool($value)) {
            if ( $value != ""){
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
        Make a request to Piction and return response in the formated requested.
        */

        // We don't send parameters as ?key=value because Piction uses a specific URL structure for it.
        // The following method will created that url
        $url = $this->_build_url($piction_method, $params);

        # Call Piction
        $response = $this->_curlCall($url);

        return $response;
    }

    private function _curlCall($url)
    {
        // Get cURL resource
        $curl = curl_init();
        // Set some options - we are passing in a useragent too here
        curl_setopt_array($curl, array(
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_URL => $url
        ));

        if(curl_exec($curl) === false)
        {
            echo 'Curl error: ' . curl_error($curl);
        } else {
            // Send the request & save response to $response
            $response = json_decode(curl_exec($curl));
        }

        // Close request to clear up some resources
        curl_close($curl);

        return $response;
    }

    public function getResponse()
    {

    }

    public function call($piction_method, $params, $follow_pagination=False)
    {
        /*
        Call a piction method and return response in the format requested.
        This method injects the authentication token
        in all calls.
        */

        // append authentication token
        // if (!isset($params['SURL'])){
        //     $params['SURL'] = $this->surl;
        // }

        $response = $this->_request($piction_method, $params);

        // Guessing if the response is a Piction paginated response. If the following conditions
        // happen the response contains a 't' attribute ( total ) and a 'r' attribute ( results )
        // If response is paginated and follow_pagination is True. Wrap response with PictionPaginatedResponse
        if ($follow_pagination == True){
        //     header = response.get('s', {})
        //     data   = response.get('r', None)
        //     total  = int(header.get('t', None))
        //     start  = params.get('START', 0)
        //     # Check if maxrows exists if not use 100 objects as default
        //     params = {k.upper(): v for k, v in params.items()}
        //     page_size = params['MAXROWS'] = int(params.get('MAXROWS', 100))
        //     if data and total:
        //         result = PictionPaginatedResponse(data, total, start, self._next_page(piction_method, params), page_size=page_size)
        //         response['r'] = result
        }

        return json_encode($response);
    }

    public function metadata()
    {

    }
}
