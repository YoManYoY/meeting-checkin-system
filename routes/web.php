<?php
use Illuminate\Support\Facades\Route;

// Route::get('/', function () { return view('welcome'); });

// // 👈 ຕ້ອງເປັນແບບນີ້ - ຈັບທຸກ path ຂອງ checkin
// Route::get('/checkin/{any}', function () {
//     return view('app');
// })->where('any', '.*');

// Route::get('/{any}', function () {
//     return view('app');
// })->where('any', '^(?!api|storage|qrcodes).*$');


Route::get('/{any}', function () {
    return view('app');
})->where('any', '.*');
