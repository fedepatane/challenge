<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTasksCollection extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
      Schema::connection('mongodb')->create('tasks', function ($collection) {
          $collection->increments('id');
          $collection->string('title');
          $collection->string('description');
          $collection->timestamp('due_date');
          $collection->boolean('completed')->default(false);
          $collection->timestamps();
      });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('tasks');
    }
}
