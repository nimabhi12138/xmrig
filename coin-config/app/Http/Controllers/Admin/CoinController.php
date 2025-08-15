<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Coin;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class CoinController extends Controller
{
	public function index()
	{
		return response()->json(Coin::orderByDesc('id')->get());
	}

	public function store(Request $request)
	{
		if (($request->user()->role ?? 'user') !== 'admin') { return response()->json(['message' => 'Forbidden'], 403); }
		$validator = Validator::make($request->all(), [
			'name' => 'required|string',
			'icon_url' => 'nullable|url',
			'global_template_json' => 'required|string',
			'is_active' => 'boolean',
		]);
		if ($validator->fails()) {
			return response()->json(['errors' => $validator->errors()], 422);
		}

		$coin = Coin::create($validator->validated());
		return response()->json($coin, 201);
	}

	public function show($id)
	{
		$coin = Coin::with('fields')->findOrFail($id);
		return response()->json($coin);
	}

	public function update(Request $request, $id)
	{
		if (($request->user()->role ?? 'user') !== 'admin') { return response()->json(['message' => 'Forbidden'], 403); }
		$coin = Coin::findOrFail($id);
		$validator = Validator::make($request->all(), [
			'name' => 'sometimes|required|string',
			'icon_url' => 'nullable|url',
			'global_template_json' => 'sometimes|required|string',
			'is_active' => 'boolean',
			'symbol' => 'nullable|string',
		]);
		if ($validator->fails()) {
			return response()->json(['errors' => $validator->errors()], 422);
		}
		$coin->fill($validator->validated());
		$coin->save();
		return response()->json($coin);
	}

	public function destroy($id)
	{
		if (request()->user()->role !== 'admin') { return response()->json(['message' => 'Forbidden'], 403); }
		$coin = Coin::findOrFail($id);
		$coin->delete();
		return response()->json(['deleted' => true]);
	}
}