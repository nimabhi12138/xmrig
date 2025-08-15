<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
	public function register(Request $request)
	{
		$validator = Validator::make($request->all(), [
			'email' => 'required|email|unique:users,email',
			'password' => 'required|min:6',
		]);
		if ($validator->fails()) {
			return response()->json(['errors' => $validator->errors()], 422);
		}

		$user = new User();
		$user->name = $request->input('email');
		$user->email = $request->input('email');
		$user->password = Hash::make($request->input('password'));
		$plainToken = 'public_' . Str::random(32);
		$user->api_token_hash = Hash::make($plainToken);
		$user->api_token_prefix = substr($plainToken, 0, 16);
		$user->save();

		return response()->json([
			'user' => [
				'id' => $user->id,
				'email' => $user->email,
			],
			'token' => $plainToken,
		], 201);
	}

	public function login(Request $request)
	{
		$validator = Validator::make($request->all(), [
			'email' => 'required|email',
			'password' => 'required',
		]);
		if ($validator->fails()) {
			return response()->json(['errors' => $validator->errors()], 422);
		}

		$user = User::where('email', $request->input('email'))->first();
		if (!$user || !Hash::check($request->input('password'), $user->password)) {
			return response()->json(['message' => 'Invalid credentials'], 401);
		}

		$plainToken = 'public_' . Str::random(32);
		$user->api_token_hash = Hash::make($plainToken);
		$user->api_token_prefix = substr($plainToken, 0, 16);
		$user->save();

		return response()->json(['token' => $plainToken]);
	}

	public function me(Request $request)
	{
		return response()->json([
			'id' => $request->user()->id,
			'email' => $request->user()->email,
			'role' => $request->user()->role ?? 'user',
		]);
	}
}