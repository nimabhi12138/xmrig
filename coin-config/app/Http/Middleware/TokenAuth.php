<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class TokenAuth
{
	public function handle(Request $request, Closure $next)
	{
		$token = $request->query('token') ?: $request->bearerToken();
		if (!$token) {
			return response()->json(['message' => 'Unauthorized'], 401);
		}
		$userId = $request->route('user_id');
		$user = null;

		if ($userId) {
			$user = User::find($userId);
			if (!$user || !($user->api_token_hash && Hash::check($token, $user->api_token_hash))) {
				return response()->json(['message' => 'Unauthorized'], 401);
			}
		} else {
			$prefix = substr($token, 0, 16);
			$query = User::where('api_token_prefix', $prefix);
			foreach ($query->cursor() as $candidate) {
				if (Hash::check($token, $candidate->api_token_hash)) {
					$user = $candidate;
					break;
				}
			}
			if (!$user) {
				return response()->json(['message' => 'Unauthorized'], 401);
			}
		}

		$request->setUserResolver(function () use ($user) {
			return $user;
		});

		return $next($request);
	}
}