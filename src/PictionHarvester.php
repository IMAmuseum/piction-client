<?php

namespace Imamuseum\PictionClient;

use Exception;

class PictionHarvester
{
    public function __construct()
    {
        $this->piction = new \Imamuseum\PictionClient\Piction();

        $dotenv = new \Dotenv\Dotenv(__DIR__.'/..');
        $dotenv->load();

        $this->image_url = getenv('PICTION_IMAGE_URL');

        $this->age = 10;
        $this->maxrows = 1500;
        $this->metatags = 'IMA.PUBLICLY AVAILABLE,IMA.IRN';

        $this->id_field = 'IMA.IRN';

        $this->collection_id = 'AID:7912565';

    }

    public function getAllObjects()
    {
        $piction_method = 'image_query';

        if(isset($this->collection_id) && $this->collection_id != ""){
            $params = array(
                'SEARCH' => $this->collection_id . ' AND IMAGE_TYPE:PHOTO',
                'FORCE_REFRESH' => True,
                'METADATA' => True,
                'MAXROWS' => $this->maxrows
            );
        } else {
            $params = array(
                'SEARCH' => 'IMAGE_TYPE:PHOTO',
                'FORCE_REFRESH' => True,
                'METADATA' => True,
                'MAXROWS' => $this->maxrows
            );
        }

        $data = $this->piction->call($piction_method, $params);

        $data = $this->transformData($data);

        return $data;
    }

    public function getUpdatedObjects()
    {
        $piction_method = 'image_query';
        if(isset($this->collection_id) && $this->collection_id != ""){
            $params = array(
                'SEARCH' => 'AGE:' . $this->age . ' AND ' . $this->collection_id . ' AND IMAGE_TYPE:PHOTO',
                'FORCE_REFRESH' => True,
                'METADATA' => True,
                //'METATAGS' => $this->metatags,
                'MAXROWS' => $this->maxrows
            );
        } else {
            $params = array(
                'SEARCH' => 'AGE:' . $this->age . ' AND IMAGE_TYPE:PHOTO',
                'FORCE_REFRESH' => True,
                'METADATA' => True,
                'MAXROWS' => $this->maxrows
            );
        }

        $data = $this->piction->call($piction_method, $params);

        $data = $this->transformData($data);

        return $data;
    }

    public function getSpecificObject($id)
    {
        $piction_method = 'image_query';
        $params = array(
            'SEARCH' => 'META:"' . $this->id_field . ',' . $id . '"',
            'FORCE_REFRESH' => True,
            'METADATA' => True,
            'MAXROWS' => $this->maxrows
        );

        $data = $this->piction->call($piction_method, $params);

        $data = $this->transformData($data);

        return $data;
    }

    public function transformData($data)
    {
        $ID_FIELD = "IRN";

        $IMAGE_TO_PULL = "Original Asset";

        $FIELD_MAP = array(
            "IRN" => "id",
            "TITACCESSIONNO" => "accession_num",
            "TITACCESSIONDATE" => "accession_date",
            "TITMAINTITLE" => "title",
            "CREDATECREATED" => "date_created",
            "CRECREATORREF_TAB" => "creator_ref",
            "CRECREATORATTRIBUTION_TAB" => "creator_attribution",
            "CRECREATIONCULTUREORPEOPLE_TAB" => "creation_culture_or_people",
            "CRECREATIONNATIONALITY2_TAB" => "creation_nationality",
            "CRECREATIONPERIOD" => "creation_period",
            "CRECREATIONDYNASTY" => "creation_dynasty",
            "PHYMEDIUMANDSUPPORT" => "phy_medium_and_support",
            "PHYMEDIUM_TAB" => "phy_medium",
            "PHYSUPPORT_TAB" => "phy_support",
            "PHYCONVERTEDDIMS" => "phy_converted_dims",
            "SUMCREDITLINE" => "credit_line",
            "RIGACKNOWLEDGEMENT" => "rights",
            "PHYCOLLECTIONAREA" => "collection",
            "CREPROVENANCE" => "provenance",
            "REFIMAGETYPE_TAB" => "image_type",
            "ADMPUBLISHWEBNOPASSWORD" => "publish_web",
            "ONDISPLAY" => "on_display",
            "AUTHORISER" => "authoriser",
            "LOCCURRENTLOCATIONREF" => "current_location",
            "PUBLICLY AVAILABLE" => "publically_available",
            "DECADE" => "decade",
            "YEAR" => "year"
        );

        // Get data from Piction
        $data = json_decode($data, true);

        // Create new array to store the transformed data
        $newData = array();
        // $newData['total'] = $data['s']['t'];

        // Some book keeping variables and arrays
        $found_file = array();
        $found_ids = array();
        $current_id = 0;
        $priority_primary = 0;
        $priority_secondary = 0;

        // Loop through results items
        foreach($data['r'] as $result) {

            if($result['t'] == "PHOTO" && (count($result['o']) > 0) && !in_array($result['n'], $found_file)) {
                foreach($result['m'] as $metadata) {

                    // check if the metadata element is in our field mapping
                    if(array_key_exists($ID_FIELD, $FIELD_MAP)) {

                        // Since piction stores the name of the field as a value and the value as another value
                        // we have to loop through the metadata to store the id
                        foreach($metadata as $k => $v) {

                            // If the current value matches the id field name
                            if ($v == $ID_FIELD){

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
                        if(array_key_exists($metadata['c'], $FIELD_MAP)) {

                            // Store metadata item if doesn't currently exist or if the current value is blank
                            if (!isset($newData['results'][$current_id][$FIELD_MAP[$metadata['c']]]) || $newData['results'][$current_id][$FIELD_MAP[$metadata['c']]] == "") {
                                $newData['results'][$current_id][$FIELD_MAP[$metadata['c']]] = htmlspecialchars($metadata['v']);
                            }
                        }
                    }

                    // Match for images with v##.jpg at the end of the url
                    // These are the primary images to use on the site.
                    $matches = preg_match('/(ps.+v\d+)\.(jpg|jpeg)/i', $result['n']);
                    if($matches) {

                        // If there was a match, add the match to the found files array
                        array_push($found_file, $result['n']);

                        // Loop through the images in current result
                        foreach($result['o'] as $image) {

                            // Check if the current image is the one we want
                            if ($image['pn'] == $IMAGE_TO_PULL){

                                // Create array of image data
                                $img_json = array(
                                    'source_url' => $this->image_url . $image['u'],
                                    'priority' => $priority_primary
                                );

                                // Store image data in final json
                                $newData['results'][$current_id]['images']['primary'][] = $img_json;

                                $priority_primary = count($newData['results'][$current_id]['images']['primary']);
                            }
                        }
                    } else {
                        // Match for images with d##.jpg at the end of the url
                        // These are the secondary images to use on the site.
                        $matches = preg_match('/(ps.+d\d+)\.(jpg|jpeg)/i', $result['n']);
                        if($matches) {

                            // If there was a match, add the match to the found files array
                            array_push($found_file, $result['n']);

                            // Loop through the images in current result
                            foreach($result['o'] as $image) {

                                // Check if the current image is the one we want
                                if ($image['pn'] == $IMAGE_TO_PULL){

                                    // Create array of image data
                                    $img_json = array(
                                        'source_url' => $this->image_url . $image['u'],
                                        'priority' => $priority_secondary
                                    );

                                    // Store image data in final json
                                    $newData['results'][$current_id]['images']['secondary'][] = $img_json;

                                    $priority_secondary = count($newData['results'][$current_id]['images']['secondary']);
                                }
                            }
                        }
                    }
                }
            }
        }

        return json_encode($newData);
    }
}