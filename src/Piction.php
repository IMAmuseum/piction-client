<?php

namespace Imamuseum\PictionClient;

use Exception;

class Piction
{

    public function getAccess()
    {
        $piction_login = config('piction-client.PICTION_ENDPOINT_URL')
            .'piction_login/USERNAME/'.config('piction-client.PICTION_USERNAME')
            .'/PASSWORD/'.config('piction-client.PICTION_PASSWORD')
            .'/'.config('piction-client.PICTION_FORMAT').'/TRUE';
		// Get cURL resource
		$curl = curl_init();
		// Set some options - we are passing in a useragent too here
		curl_setopt_array($curl, array(
		    CURLOPT_RETURNTRANSFER => 1,
		    CURLOPT_URL => $piction_login
		));
		// Send the request & save response to $response
		$response = json_decode(curl_exec($curl));
		// Close request to clear up some resources
		curl_close($curl);
        return $response->SURL;
    }

}
