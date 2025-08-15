<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCoinFieldsTable extends Migration
{
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('coin_fields', function (Blueprint $table) {
			$table->id();
			$table->unsignedBigInteger('coin_id');
			$table->string('title');
			$table->enum('type', ['text', 'textarea', 'select'])->default('text');
			$table->string('placeholder');
			$table->boolean('is_required')->default(false);
			$table->text('options_json')->nullable();
			$table->string('help_text')->nullable();
			$table->integer('sort_order')->default(0);
			$table->timestamps();

			$table->unique(['coin_id', 'placeholder'], 'uniq_coin_placeholder');
			$table->foreign('coin_id')->references('id')->on('coins')->onDelete('cascade');
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::dropIfExists('coin_fields');
	}
}