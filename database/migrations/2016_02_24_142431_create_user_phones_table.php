<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateUserPhonesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('user_phones', function (Blueprint $table) {
            $table->increments('id', 11);
            $table->integer('user_id')->index()->unsigned();
            $table->string('phone')->index();
            $table->string('code')->index()->nullable();
            $table->tinyInteger('confirmed')->default(0);
            // $table->string('token')->index();
            // $table->timestamps();
            $table->timestamp('request_at');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('user_phones');
    }
}
