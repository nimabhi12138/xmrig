<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use App\Models\User;
use App\Models\Coin;
use App\Models\CoinField;

class DatabaseSeeder extends Seeder
{
	public function run()
	{
		$admin = User::firstOrCreate(
			['email' => 'admin@example.com'],
			[
				'name' => 'Admin',
				'password' => Hash::make('admin123'),
				'role' => 'admin',
			]
		);
		// set an api token
		$plain = 'public_' . Str::random(16);
		$admin->api_token_hash = Hash::make($plain);
		$admin->save();

		$coin = Coin::firstOrCreate(
			['name' => 'SampleCoin'],
			[
				'symbol' => 'SC',
				'icon_url' => null,
				'is_active' => true,
				'global_template_json' => json_encode([
					'network' => '{{NETWORK}}',
					'wallet' => '{{WALLET}}',
					'rpc' => [
						'endpoint' => 'https://api.example.com/{{NETWORK}}',
						'timeout' => 10
					]
				]),
			]
		);

		CoinField::firstOrCreate([
			'coin_id' => $coin->id,
			'placeholder' => 'NETWORK',
		], [
			'title' => 'Network',
			'type' => 'select',
			'is_required' => true,
			'options_json' => json_encode(['mainnet','testnet']),
			'sort_order' => 1,
		]);
		CoinField::firstOrCreate([
			'coin_id' => $coin->id,
			'placeholder' => 'WALLET',
		], [
			'title' => 'Wallet Address',
			'type' => 'text',
			'is_required' => true,
			'sort_order' => 2,
		]);
	}
}
