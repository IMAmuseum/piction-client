<?php

namespace Imamuseum\PictionClient;

use Exception;
use Imamuseum\PictionClient\Piction;
use Imamuseum\PictionClient\PictionTransformer;

class PictionController
{
    public function __construct()
    {
        $this->transformer = new PictionTransformer();
        $this->piction = new Piction();
        $this->getConfig();
        $this->image_url = getenv('PICTION_IMAGE_URL');
    }

    public function getSpecificObject($id, $transform=true)
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
        if($transform === true) $data = $this->transformer->item($data);

        return $data;
    }

    // Get just the ids of all objects
    public function getAllObjectIDs($start = 0, $maxrows = null)
    {
        $maxrows = $maxrows != null ? $maxrows : $this->maxrows;
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
        $data = $this->transformer->getIds($data);

        return $data;
    }

    // Get just the ids of objects that have been updated
    public function getUpdatedObjectIDs($start = 0, $maxrows = null)
    {
        $maxrows = $maxrows != null ? $maxrows : $this->maxrows;
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
        $data = $this->transformer->getIds($data);

        return $data;
    }

    public function getAllObjects($start=0, $transform=true)
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
        if($transform === true) $data = $this->transformer->collection($data);

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
        $data = $this->transformer->collection($data);

        return $data;
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

        $this->age = $config['age'];
        $this->maxrows = $config['maxrows'];
        $this->metatags = $config['metatags'];
        $this->collection_id_field = $config['collection_id_field'];
        $this->collection_id = $config['collection_id'];
        // Transform Config items
        $this->id_field = $config['id_field'];
        $this->img_to_pull = $config['img_to_pull'];
        $this->field_map = $config['field_map'];
        $this->img_match = $config['img_match'];
    }
}