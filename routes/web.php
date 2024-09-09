<?php

use Illuminate\Support\Facades\Route;
use Telegram\Bot\Laravel\Facades\Telegram;

Route::get('/', function () {
    return view('welcome');
});

/*Route::get('setwebhook', function () {
   $response = Telegram::setWebhook(['url' => 'https://ec76-213-230-88-237.ngrok-free.app/api/telegram/webhook']);

});*/
