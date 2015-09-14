<?php

namespace Imamuseum\PictionClient;

use Exception;

class PictionTransformer
{
    public function __construct()
    {
        $this->getConfig();
    }

    // transform the object data into our config field map
    public function transform($data, $images)
    {
        $newData = $this->fieldMap();
        if($data['t'] == "PHOTO" && (count($data['o']) > 0)) {
            foreach($data['m'] as $metadata) {
                if(array_key_exists($metadata['c'], $this->field_map)) {
                    $newData[$this->field_map[$metadata['c']]][] = htmlspecialchars($metadata['v']);
                }
            }
        }
        $newData = $this->checkNewData($newData);
        $newData['images'] = $images;
        return $newData;
    }

    public function getImages($data)
    {
        $matches = preg_match($this->img_match, $data['n']);
        if($matches) {
            foreach($data['o'] as $image) {
                // Check if the current image is the one we want
                if ($image['pn'] == $this->img_to_pull) {
                    // Create array of image data
                    $img = [
                        'source_url' => $this->image_url . $image['u'],
                        'source_id' => $data['id'],
                    ];
                }
            }
            return $img;
        }
    }

    // transform an individual object
    public function item($data)
    {
        $data = json_decode($data, true);
        $images = [];
        foreach ($data['r'] as $object) {
            if($image = $this->getImages($object)) {
                array_push($images, $image);
            }
            $result = $this->transform($object, $images);
        }
        return $result;
    }

    // transform a collection of objects
    public function collection($data)
    {
        $data = json_decode($data, true);
        $results = null;
        $current_id = 0;
        foreach ($data['r'] as $object) {
            if($current_id == 0 || $current_id != $this->getCurrentId($object)) {
                $images = [];
                if($image = $this->getImages($object)) {
                    array_push($images, $image);
                }
            }
            $results[] = $this->transform($object, $images);
            $current_id = $this->getCurrentId($object);
        }
        return [
            'results' => $results,
            'total' => count($results),
            'meta' => [
                'image_count' => $this->getImageCount($data),
            ]
        ];
    }

    public function getCurrentId($data)
    {
        if($data['t'] == "PHOTO" && (count($data['o']) > 0)) {
            foreach($data['m'] as $metadata) {
                foreach($metadata as $k => $v) {
                    if ($v == $this->id_field) {
                        $current_id = $metadata['v'];
                        return $current_id;
                    }
                }
            }
        }
    }

    // return object ids
    public function getIds($data)
    {
        $data = json_decode($data, true);
        $newData['results'] = [];
        // Loop through results items
        foreach($data['r'] as $result) {
            if($result['t'] == "PHOTO" && (count($result['o']) > 0)) {
                foreach($result['m'] as $metadata) {
                    foreach($metadata as $k => $v) {
                        if ($v == $this->id_field) {
                            $newData['results'][] = $metadata['v'];
                        }
                    }
                }
            }
        }
        $newData['results'] = array_filter(array_unique($newData['results']));
        sort($newData['results'], SORT_NUMERIC);
        $newData['total'] = count($newData['results']);
        return json_encode($newData);
    }

    // return image count from piction data
    public function getImageCount($data) {
        return $data['s']['t'];
    }

    // clean up redundant data
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

    // build empty field map from config
    public function fieldMap()
    {
        $fields = [];
        foreach ($this->field_map as $field) {
            $fields[$field] = [];
        }
        return $fields;
    }

    // load config
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

        $this->image_url = getenv('PICTION_IMAGE_URL');

        // Transform Config items
        $this->id_field = $config['id_field'];
        $this->img_to_pull = $config['img_to_pull'];
        $this->field_map = $config['field_map'];
        $this->img_match = $config['img_match'];
    }
}
