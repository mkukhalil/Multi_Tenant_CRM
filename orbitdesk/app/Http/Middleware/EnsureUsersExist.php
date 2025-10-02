<?php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class EnsureUsersExist
{
    public function handle(Request $request, Closure $next)
    {
        if (DB::table('users')->count() === 0) {
            return response()->json([
                'message' => 'No users exist. Please create the first user.'
            ], 403);
        }

        return $next($request);
    }
}
