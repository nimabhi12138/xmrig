<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Coin;
use App\Models\CoinField;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class CoinFieldController extends Controller
{
	public function index($coinId)
	{
		$coin = Coin::findOrFail($coinId);
		return response()->json($coin->fields()->get());
	}

	public function store(Request $request, $coinId)
	{
		if (($request->user()->role ?? 'user') !== 'admin') { return response()->json(['message' => 'Forbidden'], 403); }
		$coin = Coin::findOrFail($coinId);
		$validator = Validator::make($request->all(), [
			'title' => 'required|string',
			'type' => 'required|in:text,textarea,select',
			'placeholder' => 'required|string',
			'is_required' => 'boolean',
			'options_json' => 'nullable|string',
			'help_text' => 'nullable|string',
			'sort_order' => 'integer',
		]);
		if ($validator->fails()) {
			return response()->json(['errors' => $validator->errors()], 422);
		}
		$field = new CoinField($validator->validated());
		$field->coin_id = $coin->id;
		$field->save();
		return response()->json($field, 201);
	}

	public function update(Request $request, $coinId, $fieldId)
	{
		if (($request->user()->role ?? 'user') !== 'admin') { return response()->json(['message' => 'Forbidden'], 403); }
		$field = CoinField::where('coin_id', $coinId)->findOrFail($fieldId);
		$validator = Validator::make($request->all(), [
			'title' => 'sometimes|required|string',
			'type' => 'sometimes|required|in:text,textarea,select',
			'placeholder' => 'sometimes|required|string',
			'is_required' => 'boolean',
			'options_json' => 'nullable|string',
			'help_text' => 'nullable|string',
			'sort_order' => 'integer',
		]);
		if ($validator->fails()) {
			return response()->json(['errors' => $validator->errors()], 422);
		}
		$field->fill($validator->validated());
		$field->save();
		return response()->json($field);
	}

	public function destroy($coinId, $fieldId)
	{
		if (request()->user()->role !== 'admin') { return response()->json(['message' => 'Forbidden'], 403); }
		$field = CoinField::where('coin_id', $coinId)->findOrFail($fieldId);
		$field->delete();
		return response()->json(['deleted' => true]);
	}
}