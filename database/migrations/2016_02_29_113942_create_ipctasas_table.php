<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateIpctasasTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('ipc_tasas', function(Blueprint $table){
            
            $table->engine = 'InnoDB';

            $table->bigIncrements('id');
            $table->UnsignedBigInteger('user_id');

            $table->date('gest')->required();

            $table->double('ipc');
            
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users'); 

        });

        Schema::table('aportes', function (Blueprint $table) {

            $table->UnsignedBigInteger('ipc_tasa_id')->nullable();
            $table->foreign('ipc_tasa_id')->references('id')->on('ipc_tasas');

        });

    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('ipc_tasas');
    }
}
