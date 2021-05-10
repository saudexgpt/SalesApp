<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCustomersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->integer('customer_type_id')->nullable();
            $table->integer('tier_id')->nullable();
            $table->integer('sub_region_id')->nullable();
            $table->integer('region_id')->nullable();
            $table->string('business_name')->unique();
            $table->string('email')->unique();
            $table->string('phone1');
            $table->string('phone2')->nullable();
            $table->text('address')->nullable();
            $table->string('street')->nullable();
            $table->string('area')->nullable();
            $table->double('longitude', 10, 2)->nullable();
            $table->double('latitude', 10, 2)->nullable();
            $table->integer('credit_limit')->nullable();
            $table->integer('yearly_target')->nullable();
            $table->integer('registered_by')->nullable();
            $table->double('registrar_lng', 10, 2)->nullable();
            $table->double('registrar_lat', 10, 2)->nullable();
            $table->integer('relating_officer')->nullable();
            $table->integer('verified_by')->nullable();
            $table->string('photo')->default('assets/images/profile-image.png');
            $table->timestamps();
            $table->timestamp('deleted_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('customers');
    }
}
