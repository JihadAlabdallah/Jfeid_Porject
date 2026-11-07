<?php

use App\Models\Vehicle;
use App\Models\Policy;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\VehicleController;
use App\Http\Controllers\Api\OilchangeController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\WheelServiceController;
use App\Http\Controllers\Api\OtherServiceController;
use App\Http\Controllers\Api\GearOilChangeController;
use App\Http\Controllers\Api\ResetPasswordController;
use App\Http\Controllers\Api\BatteryServiceController;
use App\Http\Controllers\Api\MaintenanceRecordController;
use App\Http\Controllers\Api\PasswordResetlinkController;
use App\Http\Controllers\Api\SteeringOilChangeController;
use App\Http\Controllers\Api\DifferentialOilChangeController;
use App\Http\Controllers\Api\TransmissionOilChangeController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');



Route::post('/signup', 'App\Http\Controllers\Api\AuthController@signup');
Route::get('/signup', 'App\Http\Controllers\Api\AuthController@signup');
Route::post('/signin', 'App\Http\Controllers\Api\AuthController@signin');
Route::post('/signout','App\Http\Controllers\Api\AuthController@logout')->middleware('auth:sanctum');
Route::post('/profile/update/{token}', 'App\Http\Controllers\Api\AuthController@updateProfile')->middleware('auth:sanctum');
Route::get('/profile', 'App\Http\Controllers\Api\AuthController@getProfile')->middleware('auth:sanctum');
Route::delete('/profile/delete', 'App\Http\Controllers\Api\AuthController@destroy')->middleware('auth:sanctum');


// Signed-in user's own vehicles (multiple vehicles per client)
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/my-vehicles', [VehicleController::class, 'mine']);
    Route::post('/my-vehicles', [VehicleController::class, 'storeMine']);
    Route::patch('/my-vehicles/{id}', [VehicleController::class, 'updateMine']);
});

// Vehicle Routes
Route::get('/vehicles', [VehicleController::class, 'index']);
Route::post('/vehicles', [VehicleController::class, 'store']);
Route::get('/vehicles/{id}', [VehicleController::class, 'show']);
Route::put('/vehicles/{id}', [VehicleController::class, 'update']);
Route::delete('/vehicles/{id}', [VehicleController::class, 'destroy']);

// Maintenance Record Routes
Route::get('/maintenance-records', [MaintenanceRecordController::class, 'index']);
Route::post('/maintenance-records', [MaintenanceRecordController::class, 'store']);
Route::get('/maintenance-records/{id}', [MaintenanceRecordController::class, 'show']);
Route::put('/maintenance-records/{id}', [MaintenanceRecordController::class, 'update']);
Route::delete('/maintenance-records/{id}', [MaintenanceRecordController::class, 'destroy']);

// Oil Change Routes
Route::get('/oil-changes', [OilchangeController::class, 'index']);
Route::post('/oil-changes', [OilchangeController::class, 'store']);
Route::get('/oil-changes/{id}', [OilchangeController::class, 'show']);
Route::put('/oil-changes/{id}', [OilchangeController::class, 'update']);
Route::delete('/oil-changes/{id}', [OilchangeController::class, 'destroy']);

// battery service Routes
Route::get('/battery-services', [BatteryServiceController::class, 'index']);
Route::post('/battery-services', [BatteryServiceController::class, 'store']);
Route::get('/battery-services/{id}', [BatteryServiceController::class, 'show']);
Route::put('/battery-services/{id}', [BatteryServiceController::class, 'update']);
Route::delete('/battery-services/{id}', [BatteryServiceController::class, 'destroy']);


// Differenntial oil change  Routes
Route::get('/differential-oil-changes', [DifferentialOilChangeController::class, 'index']);
Route::post('/differential-oil-changes', [DifferentialOilChangeController::class, 'store']);
Route::get('/differential-oil-changes/{id}', [DifferentialOilChangeController::class, 'show']);
Route::put('/differential-oil-changes/{id}', [DifferentialOilChangeController::class, 'update']);
Route::delete('/differential-oil-changes/{id}', [DifferentialOilChangeController::class, 'destroy']);

// gear oil change  Routes
Route::get('/gear-oil-changes', [GearOilChangeController::class, 'index']);
Route::post('/gear-oil-changes', [GearOilChangeController::class, 'store']);
Route::get('/gear-oil-changes/{id}', [GearOilChangeController::class, 'show']);
Route::put('/gear-oil-changes/{id}', [GearOilChangeController::class, 'update']);
Route::delete('/gear-oil-changes/{id}', [GearOilChangeController::class, 'destroy']);

// Steering oil change Routes
Route::get('/steering-oil-changes', [SteeringOilChangeController::class, 'index']);
Route::post('/steering-oil-changes', [SteeringOilChangeController::class, 'store']);
Route::get('/steering-oil-changes/{id}', [SteeringOilChangeController::class, 'show']);
Route::put('/steering-oil-changes/{id}', [SteeringOilChangeController::class, 'update']);
Route::delete('/steering-oil-changes/{id}', [SteeringOilChangeController::class, 'destroy']);

// wheel service Routes
Route::get('/wheel-services', [WheelServiceController::class, 'index']);
Route::post('/wheel-services', [WheelServiceController::class, 'store']);
Route::get('/wheel-services/{id}', [WheelServiceController::class, 'show']);
Route::put('/wheel-services/{id}', [WheelServiceController::class, 'update']);
Route::delete('/wheel-services/{id}', [WheelServiceController::class, 'destroy']);

// transmission oil change Routes
Route::get('/transmission-oil-changes', [TransmissionOilChangeController::class, 'index']);
Route::post('/transmission-oil-changes', [TransmissionOilChangeController::class, 'store']);
Route::get('/transmission-oil-changes/{id}', [TransmissionOilChangeController::class, 'show']);
Route::put('/transmission-oil-changes/{id}', [TransmissionOilChangeController::class, 'update']);
Route::delete('/transmission-oil-changes/{id}', [TransmissionOilChangeController::class, 'destroy']);



// other (custom) service Routes
Route::get('/other-services', [OtherServiceController::class, 'index']);
Route::post('/other-services', [OtherServiceController::class, 'store']);
Route::get('/other-services/{id}', [OtherServiceController::class, 'show']);
Route::put('/other-services/{id}', [OtherServiceController::class, 'update']);
Route::delete('/other-services/{id}', [OtherServiceController::class, 'destroy']);

//forget password and Reset It
Route::post('forgot-password', [PasswordResetlinkController::class, 'sendResetPasswordEmail']);
Route::post('/password-reset/{token}', [ResetPasswordController::class, 'reset'])->name('password.update');


// Notification Routes
Route::post('/send-notification', [NotificationController::class, 'sendNotificationV1']);


// Policies Routes
Route::get('/policies/{type}', function ($type) {
    return response()->json([
        'status' => 'success',
        'data' => Policy::where('type', $type)->first(),
    ]);
});
