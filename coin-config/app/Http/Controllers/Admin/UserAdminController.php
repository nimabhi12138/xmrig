<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class UserAdminController extends Controller
{
	public function setDefaultCoin(Request $request, $userId)
	{
		$validator = Validator::make($request->all(), [
			'default_coin_id' => 'required|integer',
		]);
		if ($validator->fails()) {
			return response()->json(['errors' => $validator->errors()], 422);
		}
		$user = User::findOrFail($userId);
		$user->default_coin_id = (int) $request->input('default_coin_id');
		$user->save();
		return response()->json(['updated' => true]);
	}

	public function setRole(Request $request, $userId)
	{
		$validator = Validator::make($request->all(), [
			'role' => 'required|in:admin,user',
		]);
		if ($validator->fails()) {
			return response()->json(['errors' => $validator->errors()], 422);
		}
		$user = User::findOrFail($userId);
		$user->role = $request->input('role');
		$user->save();
		return response()->json(['updated' => true]);
	}
}