<?php

namespace Imamuseum\PictionClient;

use Exception;

class PictionTransformer
{
    public function __construct()
    {
        $this->piction = new \Imamuseum\PictionClient\Piction();

        if (class_exists('\\Dotenv\\Dotenv')){
            $dotenv = new \Dotenv\Dotenv(__DIR__.'/..');
            $dotenv->load();
        }

        $this->image_url = getenv('PICTION_IMAGE_URL');

        $this->config = require 'config/piction.php';

        // Query config items
        $this->age = $this->config['age'];
        $this->maxrows = $this->config['maxrows'];
        $this->metatags = $this->config['metatags'];
        $this->collection_id_field = $this->config['collection_id_field'];
        $this->collection_id = $this->config['collection_id'];

        // Transform Config items
        $this->id_field = $this->config['id_field'];
        $this->img_to_pull = $this->config['img_to_pull'];
        $this->field_map = $this->config['field_map'];
    }

    public function getAllObjects($start=0)
    {
        $piction_method = 'image_query';

        // Set up query parameters to send to piction
        $params = array(
            'SEARCH' => (isset($this->collection_id) && $this->collection_id != "") ? $this->collection_id . ' AND IMAGE_TYPE:PHOTO' : 'IMAGE_TYPE:PHOTO',
            'FORCE_REFRESH' => True,
            'METADATA' => True,
            'MAXROWS' => $this->maxrows,
            'ORDERBY' => $this->collection_id_field,
            'START' => $start
        );

        // Make piction call
        $data = $this->piction->call($piction_method, $params);

        // Transform data into something more manageable
        $data = $this->transformData($data);

        return $data;
    }

    public function getUpdatedObjects()
    {
        $piction_method = 'image_query';

        // Set up query parameters to send to piction
        $params = array(
            'SEARCH' => (isset($this->collection_id) && $this->collection_id != "") ? 'AGE:' . $this->age . ' AND ' . $this->collection_id . ' AND IMAGE_TYPE:PHOTO' : 'AGE:' . $this->age . ' AND IMAGE_TYPE:PHOTO',
            'FORCE_REFRESH' => True,
            'METADATA' => True,
            'MAXROWS' => $this->maxrows,
            'ORDERBY' => $this->collection_id_field
        );

        // Make piction call
        $data = $this->piction->call($piction_method, $params);

        // Transform data into something more manageable
        $data = $this->transformData($data);

        return $data;
    }

    public function getSpecificObject($id)
    {
        $piction_method = 'image_query';

        // Set up query parameters to send to piction
        $params = array(
            'SEARCH' => 'META:"' . $this->collection_id_field . ',' . $id . '"',
            'FORCE_REFRESH' => True,
            'METADATA' => True,
            'MAXROWS' => $this->maxrows,
        );

        // Make piction call
        $data = $this->piction->call($piction_method, $params);

        // Transform data into something more manageable
        $data = $this->transformData($data);

        return $data;
    }

    // Get just the ids of all objects
    public function getAllObjectIDs($start = 0, $maxrows = 100)
    {
        $piction_method = 'image_query';

        // Set up query parameters to send to piction
        $params = array(
            'SEARCH' => (isset($this->collection_id) && $this->collection_id != "") ? $this->collection_id . ' AND IMAGE_TYPE:PHOTO' : 'IMAGE_TYPE:PHOTO',
            'FORCE_REFRESH' => True,
            'METATAGS' => $this->collection_id_field,
            'ORDERBY' => $this->collection_id_field,
            'MAXROWS' => $maxrows,
            'START' => $start
        );

        // Make piction call
        $data = $this->piction->call($piction_method, $params);

        // Transform data into something more manageable
        $data = $this->transformIdData($data);

        return $data;
    }

    // Get just the ids of objects that have been updated
    public function getUpdatedObjectIDs($start = 0, $maxrows = 100)
    {
        $piction_method = 'image_query';

        // Set up query parameters to send to piction
        $params = array(
            'SEARCH' => (isset($this->collection_id) && $this->collection_id != "") ? 'AGE:' . $this->age . ' AND ' . $this->collection_id . ' AND IMAGE_TYPE:PHOTO' : 'AGE:' . $this->age . ' AND IMAGE_TYPE:PHOTO',
            'FORCE_REFRESH' => True,
            'METATAGS' => $this->collection_id_field,
            'ORDERBY' => $this->collection_id_field,
            'MAXROWS' => $maxrows,
            'START' => $start
        );

        // Make piction call
        $data = $this->piction->call($piction_method, $params);

        // Transform data into something more manageable
        $data = $this->transformIdData($data);

        return $data;
    }

    public function transformData($data)
    {
        // Get data from Piction
        $data = json_decode($data, true);

        // Create new array to store the transformed data
        $newData = array();

        // Some book keeping variables and arrays
        $found_file = array();
        $found_ids = array();
        $current_id = 0;

        // Loop through results items
        foreach($data['r'] as $result) {

            if($result['t'] == "PHOTO" && (count($result['o']) > 0) && !in_array($result['n'], $found_file)) {
                foreach($result['m'] as $metadata) {

                    // check if the metadata element is in our field mapping
                    if(array_key_exists($this->id_field, $this->field_map)) {

                        // Since piction stores the name of the field as a value and the value as another value
                        // we have to loop through the metadata to store the id
                        foreach($metadata as $k => $v) {

                            // If the current value matches the id field name
                            if ($v == $this->id_field){

                                // Store the value in a variable and only once in the found_ids array
                                $current_id = $metadata['v'];
                                if($current_id != ""){
                                    if(!in_array($current_id, $found_ids)){
                                        $found_ids[$current_id] = $current_id;
                                    }
                                }
                            }
                        }
                    }
                }

                // Check if current id exists and store the data
                if(in_array($current_id, $found_ids)){

                    // Loop through metadata
                    foreach($result['m'] as $metadata) {

                        // check if the metadata element is in our field mapping
                        if(array_key_exists($metadata['c'], $this->field_map)) {

                            // Store metadata item if doesn't currently exist or if the current value is blank
                            if (!isset($newData['results'][$current_id][$this->field_map[$metadata['c']]]) || $newData['results'][$current_id][$this->field_map[$metadata['c']]] == "") {
                                $newData['results'][$current_id][$this->field_map[$metadata['c']]] = htmlspecialchars($metadata['v']);
                            }
                        }
                    }

                    // Match for images with v##.jpg at the end of the url
                    // These are the primary images to use on the site.
                    $matches = preg_match('/\.(jpg|jpeg)/i', $result['n']);
                    if($matches) {

                        // If there was a match, add the match to the found files array
                        array_push($found_file, $result['n']);

                        // Loop through the images in current result
                        foreach($result['o'] as $image) {

                            // Check if the current image is the one we want
                            if ($image['pn'] == $this->img_to_pull){

                                // Create array of image data
                                $img_json = array(
                                    'source_url' => $this->image_url . $image['u']
                                );

                                // Store image data in final json
                                $newData['results'][$current_id]['images'][] = $img_json;
                            }
                        }
                    }
                }
            }
        }

        $newData['total'] = count($found_ids);
        $newData['image_count'] = $data['s']['t'];

        return json_encode($newData);
    }

    // Transform data to only show ID in results
    public function transformIdData($data)
    {
        // Get data from Piction
        $data = json_decode($data, true);

        // Create new array to store the transformed data
        $newData = array();

        // Some book keeping variables and arrays
        $found_ids = array();
        $current_id = 0;

        // Loop through results items
        foreach($data['r'] as $result) {

            if($result['t'] == "PHOTO" && (count($result['o']) > 0)) {
                foreach($result['m'] as $metadata) {

                    // check if the metadata element is in our field mapping
                    if(array_key_exists($this->id_field, $this->field_map)) {

                        // Since piction stores the name of the field as a value and the value as another value
                        // we have to loop through the metadata to store the id
                        foreach($metadata as $k => $v) {

                            // If the current value matches the id field name
                            if ($v == $this->id_field){

                                // Store the value in a variable and only once in the found_ids array
                                $current_id = $metadata['v'];
                                if($current_id != ""){
                                    if(!in_array($current_id, $found_ids)){
                                        $found_ids[] = $current_id;
                                        $newData['results'][] = $metadata['v'];
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }

        sort($newData['results'], SORT_NUMERIC);
        $newData['total'] = count($found_ids);
        $newData['image_count'] = $data['s']['t'];

        return json_encode($data);
    }
}
