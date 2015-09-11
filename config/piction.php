<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Piction Routes Configurations
    |--------------------------------------------------------------------------
    |
    | Set to true if you are using the Laravel PictionServiceProvider and
    | want to expose http routes to piction-client endpoints.
    |
    */

    'routes_enabled' => false,

    /*
    |--------------------------------------------------------------------------
    | Piction Query Configurations
    |--------------------------------------------------------------------------
    |
    | These items limit the queries uses to pull data from piction.
    |
    */

    'age' => 30,
    'maxrows' => 1000,
    'metatags' => 'IMA.PUBLICLY AVAILABLE,IMA.IRN',
    'collection_id_field' => 'IMA.IRN',
    'collection_id' => 'AID:7912565',
    'img_match' => "/\.(jpg|jpeg)/i",
    'url_format' => "TRUE"

    /*
    |--------------------------------------------------------------------------
    | Transform Configuration
    |--------------------------------------------------------------------------
    |
    | These items determine the piction field to use as an ID, which
    | image to pull from the dataset and which fields to map into our
    | output JSON
    |
    */

    'id_field' => "IRN",
    'img_to_pull' => "Original Asset",
    'field_map' => [
        "IRN" => "id",
        "TITACCESSIONNO" => "accession_num",
        "TITACCESSIONDATE" => "accession_date",
        "TITMAINTITLE" => "title",
        "CREDATECREATED" => "date_created",
        "CRECREATORREF_TAB" => "creator_ref",
        "CRECREATORATTRIBUTION_TAB" => "attribution",
        "CRECREATIONCULTUREORPEOPLE_TAB" => "culture_or_people",
        "CRECREATIONNATIONALITY2_TAB" => "nationality",
        "CRECREATIONPERIOD" => "period",
        "CRECREATIONDYNASTY" => "dynasty",
        "PHYMEDIUMANDSUPPORT" => "medium_and_support",
        "PHYMEDIUM_TAB" => "medium",
        "PHYSUPPORT_TAB" => "support",
        "PHYCONVERTEDDIMS" => "converted_dims",
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
    ],

];
