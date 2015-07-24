<?php

namespace Imamuseum\PictionClient;

/*************************
    Helper Functions
*************************/


class PictionHelpers
{
    /* Test for boolean variable where is_bool() returns false positive */
    public function is_bool($var)
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
    public function to_raw($response)
    {
        return string($response);
    }

    // json
    public function to_json($response)
    {
        try {
            return json_decode($response);
        }
        catch (Exception $e) {
            echo 'Caught exception: ', $e->getMessage(), '\n';
        }
    }

    // xml
    public function to_xml($response)
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