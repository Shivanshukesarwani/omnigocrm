<?php
use App\Http\Controllers\Api\AuthApiController;
use App\Http\Controllers\Api\CrmApiController;
use App\Http\Controllers\Api\SaaSApiController;
use App\Http\Controllers\Api\NotificationApiController;
use Illuminate\Support\Facades\Route;

Route::post('/login',[AuthApiController::class,'login']);

Route::middleware(['api.token','workspace'])->group(function(){
 Route::get('/me',[AuthApiController::class,'me']);
 Route::post('/logout',[AuthApiController::class,'logout']);
 Route::get('/notifications',[NotificationApiController::class,'index']);
 Route::post('/notifications/{id}/read',[NotificationApiController::class,'read']);

 Route::get('/dashboard',[CrmApiController::class,'dashboard']);
 Route::get('/leads',[CrmApiController::class,'leads']);
 Route::post('/leads',[CrmApiController::class,'storeLead']);
 Route::get('/leads/{lead}',[CrmApiController::class,'lead']);
 Route::post('/leads/{lead}/convert-contact',[CrmApiController::class,'convertLead']);
 Route::get('/contacts',[CrmApiController::class,'contacts']);
 Route::get('/contacts/{contact}',[CrmApiController::class,'contact']);
 Route::post('/contacts/{contact}/convert-customer',[CrmApiController::class,'convertContact']);
 Route::get('/customers',[CrmApiController::class,'customers']);
 Route::get('/customers/{customer}',[CrmApiController::class,'customer']);
 Route::get('/message-templates',[CrmApiController::class,'templates']);
 Route::get('/whatsapp/{type}/{id}',[CrmApiController::class,'whatsapp']);
 Route::get('/follow-ups',[CrmApiController::class,'followUps']);
 Route::post('/follow-ups',[CrmApiController::class,'storeFollowUp']);
 Route::post('/calls',[CrmApiController::class,'storeCall']);

 Route::post('/calls/{call}/recording',[SaaSApiController::class,'uploadRecording']);

 Route::get('/companies',[SaaSApiController::class,'companies']);
 Route::post('/companies',[SaaSApiController::class,'storeCompany']);

 Route::get('/tasks',[SaaSApiController::class,'tasks']);
 Route::post('/tasks',[SaaSApiController::class,'storeTask']);
 Route::patch('/tasks/{task}',[SaaSApiController::class,'updateTask']);

 Route::get('/tags',[SaaSApiController::class,'tags']);
 Route::post('/tags',[SaaSApiController::class,'storeTag']);

 Route::get('/products',[SaaSApiController::class,'products']);
 Route::get('/quotations',[SaaSApiController::class,'quotations']);
 Route::post('/quotations',[SaaSApiController::class,'storeQuotation']);

 Route::get('/orders',[SaaSApiController::class,'orders']);
 Route::post('/orders',[SaaSApiController::class,'storeOrder']);

 Route::get('/payments',[SaaSApiController::class,'payments']);
 Route::post('/payments',[SaaSApiController::class,'storePayment']);

 Route::post('/imports/leads',[SaaSApiController::class,'importLeads']);
});
