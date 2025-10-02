<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\ApiAuthController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\TenantController;
use App\Http\Controllers\LeadController;
use App\Http\Controllers\SuperAdminController;
use App\Http\Controllers\TenantUserController;
use App\Http\Controllers\UserController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
| These routes are loaded by the RouteServiceProvider and assigned to "api".
|--------------------------------------------------------------------------
*/

/* ------------------ Public Routes ------------------ */

// CSRF cookie (for Sanctum)
Route::get('/sanctum/csrf-cookie', [ApiAuthController::class, 'csrf']);

// Auth
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [ApiAuthController::class, 'login']);

// Tenant registration (company creation)
Route::post('/tenants/register', [TenantController::class, 'registerTenant']);


/* ------------------ SuperAdmin Routes ------------------ */
Route::prefix('superadmin')
    ->middleware(['auth:sanctum', 'role:Super Admin'])
    ->group(function () {
        Route::get('/dashboard', [SuperAdminController::class, 'dashboard']);
        Route::get('/tenants', [SuperAdminController::class, 'tenants']);
        Route::get('/tenants/{tenant}', [SuperAdminController::class, 'tenantDetails']);
        Route::put('/tenants/{tenant}', [SuperAdminController::class, 'updateTenant']);
        Route::delete('/tenants/{tenant}', [SuperAdminController::class, 'deleteTenant']);

        // ✅ Roles & Tenants for dropdowns
        Route::get('/roles', [SuperAdminController::class, 'roles']);
        Route::get('/all-tenants', [SuperAdminController::class, 'allTenants']);

        // ✅ User management for super admin
        Route::get('/users', [UserController::class, 'index']);
        Route::post('/users', [UserController::class, 'store']);
        Route::get('/users/{id}', [UserController::class, 'show']);
        Route::put('/users/{id}', [UserController::class, 'update']);
        Route::delete('/users/{id}', [UserController::class, 'destroy']);

        // SuperAdmin Leads routes
Route::get('/leads/count', [SuperAdminController::class, 'leadsCount']); // <-- MUST be above /leads/{lead}
Route::get('/leads/stats/overview', [LeadController::class, 'stats']);
Route::get('/leads/filters/options', [LeadController::class, 'filterOptions']);
Route::post('leads/bulk-actions', [LeadController::class, 'bulkActions']);
Route::get('/leads/{lead}/activities', [LeadController::class, 'activities']);
Route::get('/leads/overdue/list', [LeadController::class, 'overdueLeads']);
    Route::get('/leads/due-today/list', [LeadController::class, 'dueTodayLeads']);

// Resource routes (show, index)
Route::get('/leads', [LeadController::class, 'index']); 
Route::get('/leads/{lead}', [LeadController::class, 'show']); 

         


    });


/* ------------------ Tenant Admin Routes (Tenant Admin only) ------------------ */
Route::prefix('tenant')
    ->middleware(['auth:sanctum', 'tenant', 'role:Tenant Admin'])
    ->group(function () {
        // Tenant Admin dashboard
        Route::get('/dashboard', [TenantController::class, 'dashboard']);

        // Tenant-scoped user management
        Route::get('/users', [TenantUserController::class, 'index']);
        Route::post('/users', [TenantUserController::class, 'store']);
        Route::get('/users/{id}', [TenantUserController::class, 'show']);
        Route::put('/users/{id}', [TenantUserController::class, 'update']);
        Route::delete('/users/{id}', [TenantUserController::class, 'destroy']);
        Route::get('/roles', [TenantUserController::class, 'roles']);
    });

/* ------------------ Tenant Leads Routes (ALL tenant roles: Admin, Manager, Agent) ------------------ */
Route::prefix('tenant')
    ->middleware(['auth:sanctum', 'tenant']) // ✅ no role restriction here
    ->group(function () {
        Route::apiResource('leads', LeadController::class);
        Route::get('leads/{lead}/activities', [LeadController::class, 'activities']);
        Route::post('leads/{lead}/assign', [LeadController::class, 'assign']);
        Route::post('leads/{lead}/status', [LeadController::class, 'updateStatus']);
        Route::post('leads/bulk-actions', [LeadController::class, 'bulkActions']);
        Route::get('leads/stats/overview', [LeadController::class, 'stats']);
        Route::get('leads/filters/options', [LeadController::class, 'filterOptions']);
        Route::get('leads/overdue/list', [LeadController::class, 'overdueLeads']);
        Route::get('leads/due-today/list', [LeadController::class, 'dueTodayLeads']);
    });
