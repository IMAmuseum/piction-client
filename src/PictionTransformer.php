<?php

namespace Imamuseum\PictionClient;

use Exception;

class PictionTransformer
{
    public function __construct()
    {
        $this->getConfig();
    }

    public function transform($data)
    {
        // Get data from Piction
        $data = json_decode($data, true);
        $newData = $this->fieldMap();
        foreach($data['r'] as $result) {
            if($result['t'] == "PHOTO" && (count($result['o']) > 0)) {
                foreach($result['m'] as $metadata) {
                    if(array_key_exists($metadata['c'], $this->field_map)) {
                        $newData[$this->field_map[$metadata['c']]][] = htmlspecialchars($metadata['v']);
                    }
                }
            }
        }
        $newData = $this->checkNewData($newData);
        return $newData;
    }

    public function item($data)
    {
        return $data = $this->transform($data);
    }

    public function transformData($data, $specific = false)
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

                            // check if specific object has been requested
                            if (! $specific ) {
                                // Store metadata item if doesn't currently exist or if the current value is blank
                                if (!isset($newData['results'][$current_id][$this->field_map[$metadata['c']]]) || $newData['results'][$current_id][$this->field_map[$metadata['c']]] == "") {
                                    $newData['results'][$current_id][$this->field_map[$metadata['c']]] = htmlspecialchars($metadata['v']);
                                }
                            } else {
                                if (!isset($newData[$this->field_map[$metadata['c']]]) || $newData[$this->field_map[$metadata['c']]] == "") {
                                    $newData[$this->field_map[$metadata['c']]] = htmlspecialchars($metadata['v']);
                                }
                            }
                        }
                    }

                    // Match for images with v##.jpg at the end of the url
                    // These are the primary images to use on the site.
                    $matches = preg_match($this->img_match, $result['n']);
                    if($matches) {

                        // If there was a match, add the match to the found files array
                        array_push($found_file, $result['n']);

                        // Loop through the images in current result
                        foreach($result['o'] as $image) {

                            // Check if the current image is the one we want
                            if ($image['pn'] == $this->img_to_pull){

                                // Create array of image data
                                $img_json = array(
                                    'source_url' => $this->image_url . $image['u'],
                                    'source_id' => $result['id'],
                                );

                                // check if specific object has been requested
                                if (! $specific ) {
                                    // Store image data in final json
                                    $newData['results'][$current_id]['images'][] = $img_json;
                                } else {
                                    $newData['images'][] = $img_json;
                                }
                            }
                        }
                    }
                }
            }
        }

        // check if specific object has been requested
        if (! $specific ) {
            $newData['total'] = count($found_ids);
            $newData['image_count'] = $data['s']['t'];
        }

        return json_encode($newData);
    }

    // Transform data to only show ID in results
    public function ids($data)
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
        //$newData['image_count'] = $data['s']['t'];

        return json_encode($newData);
    }

    public function checkNewData($data)
    {
        $result = [];
        foreach ($data as $key => $value) {
            $newValue = array_filter(array_unique($value));
            if(count($newValue) == 1) $newValue = $newValue[0];
            if(count($newValue) == 0) $newValue = null;
            $result[$key] = $newValue;
        }
        return $result;
    }

    public function fieldMap()
    {
        $fields = [];
        foreach ($this->field_map as $field) {
            $fields[$field] = [];
        }
        return $fields;
    }

    public function getConfig()
    {
        // if Laravel config function
        if (function_exists("config")) {
            if (config('piction')) {
                // use Laravel config/piction.php
                $config = config('piction');
            }
        } else {
            // use the package config
            $config = require __DIR__ . '/../config/piction.php';
        }

        // Transform Config items
        $this->id_field = $config['id_field'];
        $this->img_to_pull = $config['img_to_pull'];
        $this->field_map = $config['field_map'];
        $this->img_match = $config['img_match'];
    }
}
