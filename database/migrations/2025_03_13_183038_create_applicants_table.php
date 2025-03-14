<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('applicants', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('application_form_id');
            $table->unsignedBigInteger('user_id');
            $table->string('name');
            $table->string('gender');
            $table->unsignedBigInteger('country_id');
            $table->string('email');
            $table->string('phone_number');
            $table->string('identity_number');
            $table->boolean('requires_transport')->default(false);
            $table->boolean('requires_accommodation')->default(false);
            $table->boolean('requires_ib_training')->default(false);
            $table->string('status')->default('pending');
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('application_form_id')
                ->references('id')
                ->on('application_forms')
                ->onUpdate('cascade');

            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->onUpdate('cascade');

            $table->foreign('country_id')
                ->references('id')
                ->on('countries')
                ->onUpdate('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('applicants');
    }
};
