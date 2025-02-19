<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\TaskController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\OrderController;
/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});


    Route::get('/tasks',                    [TaskController::class, 'index']);
    Route::get('/tasks/{id}',               [TaskController::class, 'show']);
    Route::post('/tasks',                   [TaskController::class, 'store']);
    Route::put('/tasks/{id}',               [TaskController::class, 'update']);
    Route::delete('/tasks/{id}',            [TaskController::class, 'destroy']);

    Route::get('/products',                 [ProductController::class, 'getProducts']);
    Route::get('/products/{id}',            [ProductController::class, 'getProductById']);
    Route::post('/products',                [ProductController::class, 'createProduct']);
    Route::put('/products/{id}',            [ProductController::class, 'updateProduct']);
    Route::delete('/products/{id}',         [ProductController::class, 'deleteProduct']);

    Route::get('/orders',                    [OrderController::class, 'index']);
    Route::get('/orders/{id}',               [OrderController::class, 'show']);
    Route::post('/orders',                   [OrderController::class, 'store']);
    Route::put('/orders/{id}',               [OrderController::class, 'update']);
    Route::delete('/orders/{id}',            [OrderController::class, 'destroy']);

    Route::get('/order/meta/organizations', [OrderController::class, 'getOrganizations']);
    Route::get('/order/meta/saleschannels', [OrderController::class, 'getSalesChannels']);
    Route::get('/order/meta/projects', [OrderController::class, 'getProjects']);
    Route::get('/order/meta/products', [OrderController::class, 'getProducts']);
    

