<?php

/*
|--------------------------------------------------------------------------
| Piction-Client Package Routes
|--------------------------------------------------------------------------
*/

use Imamuseum\PictionClient\PictionTransformer as Piction;

Route::group(['prefix' => 'piction'], function() {

    Route::get('/getAllObjects', function() {
        $piction = new Piction();
        if(isset($_GET['start'])) {
            return $piction->getAllObjects($_GET['start']);
        } else {
            return $piction->getAllObjects();
        }
    });

    Route::get('/getSpecificObject/{id}', function($id) {
        $piction = new Piction();
        return $piction->getSpecificObject($id);
    });

    Route::get('/getAllObjectIDs', function() {
        $piction = new Piction();
        if(isset($_GET['start'])) {
            return $piction->getAllObjectIDs($_GET['start']);
        } else {
            return $piction->getAllObjectIDs();
        }
    });

    Route::get('/getUpdatedObjects', function() {
        $piction = new Piction();
        return $piction->getUpdatedObjects();
    });

    Route::get('/getUpdatedObjectIDs', function() {
        $piction = new Piction();
        return $piction->getUpdatedObjectIDs();
    });

});