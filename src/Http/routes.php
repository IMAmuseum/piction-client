<?php

/*
|--------------------------------------------------------------------------
| Piction-Client Package Routes
|--------------------------------------------------------------------------
*/

use Imamuseum\PictionClient\PictionController as Piction;

Route::group(['prefix' => 'piction'], function() {

    Route::get('/getAllObjects', function() {
        $start = isset($_GET['start']) ? $_GET['start'] : 0;
        $transform = isset($_GET['transform']) ? $_GET['transform'] : true;
        $piction = new Piction();
        return $piction->getAllObjects($start, $transform);
    });

    Route::get('/getSpecificObject/{id}', function($id) {
        $transform = isset($_GET['transform']) ? $_GET['transform'] : true;
        $piction = new Piction();
        return $piction->getSpecificObject($id, $transform);
    });

    Route::get('/getAllObjectIDs', function() {
        $start = isset($_GET['start']) ? $_GET['start'] : 0;
        $maxrows = isset($_GET['maxrows']) ? $_GET['maxrows'] : null;
        $piction = new Piction();
        return $piction->getAllObjectIDs($start, $maxrows);
    });

    Route::get('/getUpdatedObjects', function() {
        $piction = new Piction();
        return $piction->getUpdatedObjects();
    });

    Route::get('/getUpdatedObjectIDs', function() {
        $start = isset($_GET['start']) ? $_GET['start'] : 0;
        $maxrows = isset($_GET['maxrows']) ? $_GET['maxrows'] : null;
        $piction = new Piction();
        return $piction->getUpdatedObjectIDs($start, $maxrows);
    });

});