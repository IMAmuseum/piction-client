<?php

namespace Imamuseum\PictionClient;

use Exception;

class Piction
{

    public function __construct()
    {
        $dotenv = new \Dotenv\Dotenv(__DIR__.'/..');
        $dotenv->load();
    }

    public function getAccess()
    {
        $piction_login = getenv('PICTION_ENDPOINT_URL')
            .'piction_login/USERNAME/'.getenv('PICTION_USERNAME')
            .'/PASSWORD/'.getenv('PICTION_PASSWORD')
            .'/'.getenv('PICTION_FORMAT').'/TRUE';
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
