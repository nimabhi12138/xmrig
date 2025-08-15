<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AlterUsersAddTokenPrefix extends Migration
{
	public function up()
	{
		Schema::table('users', function (Blueprint $table) {
			$table->string('api_token_prefix', 16)->nullable()->after('api_token_hash');
			$table->index('api_token_prefix');
		});
	}

	public function down()
	{
		Schema::table('users', function (Blueprint $table) {
			$table->dropIndex(['api_token_prefix']);
			$table->dropColumn('api_token_prefix');
		});
	}
}