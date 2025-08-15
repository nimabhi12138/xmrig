<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AlterUsersAddRoleAndApiToken extends Migration
{
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('users', function (Blueprint $table) {
			$table->enum('role', ['admin', 'user'])->default('user')->after('password');
			$table->string('api_token_hash')->nullable()->after('role');
			$table->unsignedBigInteger('default_coin_id')->nullable()->after('api_token_hash');
			$table->foreign('default_coin_id')->references('id')->on('coins')->onDelete('set null');
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('users', function (Blueprint $table) {
			$table->dropColumn(['role', 'api_token_hash', 'default_coin_id']);
		});
	}
}