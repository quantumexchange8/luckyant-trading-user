<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('verify_otps', function (Blueprint $table) {
            $table->id();
            $table->string('email');
            $table->string('mobile')->nullable();
            $table->integer('otp');
            $table->softDeletes();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('verify_otps');
    }
};
