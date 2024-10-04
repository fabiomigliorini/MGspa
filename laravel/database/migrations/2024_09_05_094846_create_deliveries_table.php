<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDeliveriesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('deliveries', function (Blueprint $table) {
            $table->id();
            $table->uuid('deal_id');
            $table->string('ref');
            $table->integer('status')->default(0);
            $table->string('name');
            $table->string('phone');
            $table->string('street');
            $table->integer('number');
            $table->string('neighborhood');
            $table->string('city');
            $table->string('state');
            $table->string('additional_info')->nullable();
            $table->string('payment_method');
            $table->text('observations')->nullable();
            $table->timestamps();

            $table->foreign('deal_id')->references('uuid')->on('tblnegocio');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('deliveries', function (Blueprint $table) {
            $table->dropForeign(['deal_id']);
        });

        Schema::dropIfExists('deliveries');
    }
}
