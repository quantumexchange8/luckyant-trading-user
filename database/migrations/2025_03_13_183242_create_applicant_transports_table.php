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
        Schema::create('applicant_transports', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('application_form_id');
            $table->unsignedBigInteger('applicant_id');
            $table->unsignedBigInteger('user_id');
            $table->string('type');
            $table->string('name');
            $table->string('gender');
            $table->unsignedBigInteger('country_id');
            $table->date('dob');
            $table->string('phone_number');
            $table->string('identity_number');
            $table->string('departure_address');
            $table->string('return_address');
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('application_form_id')
                ->references('id')
                ->on('application_forms')
                ->onUpdate('cascade');

            $table->foreign('applicant_id')
                ->references('id')
                ->on('applicants')
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
        Schema::dropIfExists('applicant_transports');
    }
};
