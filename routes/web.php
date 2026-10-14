<?php

use Illuminate\Support\Facades\Route;

/*
 * Page routes live in each module's app/Modules/<Name>/Routes/web.php and are
 * loaded by that module's service provider. Only the entry point is here.
 */

Route::redirect('/', '/login')->name('home');
