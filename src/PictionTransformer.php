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
                    $value = mb_convert_encoding(trim($metadata['v']), "UTF-8", "auto");
                    $newData[$this->field_map[$metadata['c']]][] = $value;
                }
            }
        }
        $newData = $this->checkNewData($newData);
        $newData = $this->transformFields($newData);
        if (! empty($this->field_addition)) $newData = $this->addFields($newData);
        $newData['images'] = $images;
        return $newData;
    }

    // get all images for object
    public function getImages($data)
    {
        // if matches the config img_match regex
        $matches = preg_match($this->img_match, $data['n']);
        if($matches) {
            foreach($data['o'] as $image) {
                // Check if the current image is the one we want
                if ($image['pn'] == $this->img_to_pull) {
                    // Create array of image data
                    $img = [
                        'source_url' => $this->image_url . $image['u'],
                        'source_id' => (int) $data['id'],
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
        return json_encode($result);
    }

    // transform a collection of objects
    public function collection($data)
    {
        $data = json_decode($data, true);
        $results = null;
        $current_id = 0;
        foreach ($data['r'] as $object) {
            // check if current id has changed if so clear images array and rebuild
            if($current_id == 0 || $current_id != $this->getCurrentId($object)) {
                $images = [];
                if($image = $this->getImages($object)) {
                    array_push($images, $image);
                }
            }
            // pass images to the results
            $results[] = $this->transform($object, $images);
            // set the currrent id for image check
            $current_id = $this->getCurrentId($object);
        }
        return json_encode([
            'results' => $results,
            'total' => count($results),
            'meta' => [
                'image_count' => $this->getImageCount($data),
            ]
        ]);
    }

    // return object ids
    public function getIds($data)
    {
        $data = json_decode($data, true);
        $newData['results'] = [];
        // Loop through results items
        foreach($data['r'] as $result) {
            $newData['results'][] = $this->getCurrentId($result);
        }
        $newData['results'] = array_filter(array_unique($newData['results']));
        sort($newData['results'], SORT_NUMERIC);
        $newData['total'] = count($newData['results']);
        return json_encode($newData);
    }

    // return the object id for current object
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

    // return image count from piction data
    public function getImageCount($data) {
        return $data['s']['t'];
    }

    // clean up redundant data
    public function checkNewData($data)
    {
        $result = [];
        foreach ($data as $key => $value) {
            $value = array_filter(array_unique($value));
            if(count($value) == 1) $value = $value[0];
            if(count($value) == 0) $value = NULL;
            $result[$key] = $value;
        }
        return $result;
    }

    // transform field data
    public function transformFields($data)
    {
        $newData = [];
        foreach ($data as $key => $value) {
            if(array_key_exists($key, $this->field_transform)) {
                $function = $this->field_transform[$key];
                $value = $this->field_transform_class->$function($value);

            }
            $newData[$key] = $value;
        }
        return $newData;
    }

    // add fields to data
    public function addFields($data)
    {
        foreach($this->field_addition as $newField => $function) {
            $newData = $this->field_transform_class->$function($data);
            //$data[$key] = $newFieldValue;
        }
        return $newData;
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
        $this->field_transform = $config['field_transform'];
        $this->field_addition = $config['field_addition'];
        $this->field_transform_class = new $config['field_transform_class'];
    }
}
