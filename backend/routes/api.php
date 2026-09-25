<?php
use App\Http\Controllers\Api\AuthApiController;
use App\Http\Controllers\Api\CrmApiController;
use Illuminate\Support\Facades\Route;

Route::post('/login',[AuthApiController::class,'login']);
Route::middleware(['api.token','workspace'])->group(function(){
Route::get('/me',[AuthApiController::class,'me']);Route::post('/logout',[AuthApiController::class,'logout']);
Route::get('/dashboard',[CrmApiController::class,'dashboard']);
Route::get('/leads',[CrmApiController::class,'leads']);Route::post('/leads',[CrmApiController::class,'storeLead']);Route::get('/leads/{lead}',[CrmApiController::class,'lead']);Route::post('/leads/{lead}/convert-contact',[CrmApiController::class,'convertLead']);
Route::get('/contacts',[CrmApiController::class,'contacts']);Route::get('/contacts/{contact}',[CrmApiController::class,'contact']);Route::post('/contacts/{contact}/convert-customer',[CrmApiController::class,'convertContact']);
Route::get('/customers',[CrmApiController::class,'customers']);Route::get('/customers/{customer}',[CrmApiController::class,'customer']);
Route::get('/message-templates',[CrmApiController::class,'templates']);Route::get('/whatsapp/{type}/{id}',[CrmApiController::class,'whatsapp']);
Route::get('/follow-ups',[CrmApiController::class,'followUps']);Route::post('/follow-ups',[CrmApiController::class,'storeFollowUp']);
Route::post('/calls',[CrmApiController::class,'storeCall']);
});