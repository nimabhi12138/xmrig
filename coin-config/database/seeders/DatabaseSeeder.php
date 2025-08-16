<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
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

		$seedToken = env('ADMIN_SEED_TOKEN', 'public_admin_token_123456');
		$admin->api_token_hash = Hash::make($seedToken);
		$admin->api_token_prefix = substr($seedToken, 0, 16);
		$admin->save();

		@mkdir(storage_path('logs'), 0777, true);
		file_put_contents(storage_path('logs/seeded_admin_token.txt'), $seedToken . "\n");

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
