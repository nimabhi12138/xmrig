<?php

namespace App\Services;

use App\Models\Coin;
use App\Models\UserCoinValue;
use App\Exceptions\MissingRequiredFieldsException;

class ConfigRenderer
{
	public function renderForUserAndCoin(int $userId, Coin $coin): array
	{
		$template = $coin->global_template_json;
		$fields = $coin->fields()->get(['id','placeholder']);
		$values = UserCoinValue::where('user_id', $userId)
			->where('coin_id', $coin->id)
			->get(['field_id','value'])
			->keyBy('field_id');

		$replacements = [];
		$missing = [];
		foreach ($fields as $field) {
			$val = $values[$field->id]->value ?? null;
			if ($val !== null && $val !== '') {
				$replacements[$field->placeholder] = $val;
			} elseif ($field->is_required) {
				$missing[] = $field->placeholder;
			}
		}
		if (!empty($missing)) {
			throw new MissingRequiredFieldsException($missing);
		}

		$json = $template;
		foreach ($replacements as $placeholder => $value) {
			$json = str_replace('{{' . $placeholder . '}}', $value, $json);
		}

		$decoded = json_decode($json, true);
		if ($decoded === null) {
			throw new \RuntimeException('Rendered JSON invalid');
		}
		return $decoded;
	}
}