<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});
// routes/web.php (temporary)
Route::get('/phpinfo', fn () =>
response()->json([
    'upload_max_filesize' => ini_get('upload_max_filesize'),
    'post_max_size'        => ini_get('post_max_size'),
    'memory_limit'         => ini_get('memory_limit'),
    'max_execution_time'   => ini_get('max_execution_time'),
    'max_file_uploads'     => ini_get('max_file_uploads'),
])
);
Route::get('/phpinfo-path', function () {
    ob_start();
    phpinfo(INFO_GENERAL);
    $html = ob_get_clean();
    return response($html);
});
