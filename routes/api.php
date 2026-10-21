<?php

use App\Http\Controllers\Api\CollectionController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Firestore-compatible read surface
|--------------------------------------------------------------------------
| Mirrors artifacts/{appId}/public/data/{collection} so the original SPA
| collections can be consumed without change.
*/
Route::get('/artifacts/{appId}/public/data/{collection}', [CollectionController::class, 'show'])
    ->name('api.collection');
