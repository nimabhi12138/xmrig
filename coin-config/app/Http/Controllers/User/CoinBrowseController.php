<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Coin;
use App\Models\CoinField;
use App\Models\UserCoinValue;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class CoinBrowseController extends Controller
{
	public function listCoins()
	{
		return response()->json(Coin::where('is_active', true)->orderBy('name')->get(['id','name','symbol','icon_url']));
	}

	public function listFields($coinId)
	{
		$coin = Coin::findOrFail($coinId);
		return response()->json($coin->fields()->get());
	}

	public function saveValues(Request $request, $coinId)
	{
		$coin = Coin::findOrFail($coinId);
		$payload = $request->json()->all() ?: $request->all();
		$validator = Validator::make($payload, [
			'values' => 'required|array',
			'values.*.field_id' => 'required|integer',
			'values.*.value' => 'nullable|string',
		]);
		if ($validator->fails()) {
			return response()->json(['errors' => $validator->errors()], 422);
		}

		$fields = $coin->fields()->get()->keyBy('id');
		$inputValues = collect($payload['values']);

		// Validate required fields
		$missing = [];
		foreach ($fields as $field) {
			if ($field->is_required) {
				$val = $inputValues->firstWhere('field_id', $field->id)['value'] ?? null;
				if ($val === null || $val === '') {
					$missing[] = $field->placeholder;
				}
			}
		}
		if (!empty($missing)) {
			return response()->json(['message' => 'Missing required fields', 'missing' => $missing], 422);
		}

		// Upsert values
		foreach ($inputValues as $item) {
			if (!$fields->has($item['field_id'])) {
				continue;
			}
			UserCoinValue::updateOrCreate(
				[
					'user_id' => $request->user()->id,
					'coin_id' => $coin->id,
					'field_id' => $item['field_id'],
				],
				['value' => $item['value'] ?? '']
			);
		}

		return response()->json(['saved' => true]);
	}
}