<?php

use App\Http\Middleware\PreventAccessFromTenantDomains;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Central Domain — redirect to System
|--------------------------------------------------------------------------
| Customers now go through system.localhost to purchase/manage instances.
| Tenant routes are loaded from routes/tenant.php by TenancyServiceProvider.
*/

Route::middleware([
    'web',
    PreventAccessFromTenantDomains::class,
])->group(function () {
    Route::get('/', fn () => redirect('https://system.localhost'));
});
