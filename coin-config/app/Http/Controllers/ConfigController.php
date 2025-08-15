<?php

namespace App\Http\Controllers;

use App\Models\Coin;
use App\Services\ConfigRenderer;
use Illuminate\Http\Request;

class ConfigController extends Controller
{
	private ConfigRenderer $renderer;

	public function __construct(ConfigRenderer $renderer)
	{
		$this->renderer = $renderer;
	}

	public function publicConfig(Request $request, $userId)
	{
		$coinId = (int) $request->query('coin_id');
		$coin = $coinId ? Coin::findOrFail($coinId) : ($request->user()->default_coin_id ? Coin::findOrFail($request->user()->default_coin_id) : null);
		if (!$coin) {
			return response()->json(['message' => 'coin_id required'], 422);
		}
		try {
			$data = $this->renderer->renderForUserAndCoin((int)$userId, $coin);
			return response()->json($data);
		} catch (\App\Exceptions\MissingRequiredFieldsException $e) {
			return response()->json(['message' => 'Missing required fields', 'missing' => $e->missing], 422);
		} catch (\RuntimeException $e) {
			return response()->json(['message' => 'Template render error'], 500);
		}
	}

	public function myConfig(Request $request)
	{
		$coinId = (int) $request->query('coin_id');
		$coin = $coinId ? Coin::findOrFail($coinId) : ($request->user()->default_coin_id ? Coin::findOrFail($request->user()->default_coin_id) : null);
		if (!$coin) {
			return response()->json(['message' => 'coin_id required'], 422);
		}
		try {
			$data = $this->renderer->renderForUserAndCoin($request->user()->id, $coin);
			return response()->json($data);
		} catch (\App\Exceptions\MissingRequiredFieldsException $e) {
			return response()->json(['message' => 'Missing required fields', 'missing' => $e->missing], 422);
		} catch (\RuntimeException $e) {
			return response()->json(['message' => 'Template render error'], 500);
		}
	}
}