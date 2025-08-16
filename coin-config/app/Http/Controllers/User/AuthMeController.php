<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class AuthMeController extends Controller
{
	public function __invoke(Request $request)
	{
		$user = $request->user();
		return response()->json([
			'id' => $user->id,
			'email' => $user->email,
			'role' => $user->role ?? 'user',
			'default_coin_id' => $user->default_coin_id,
		]);
	}
}