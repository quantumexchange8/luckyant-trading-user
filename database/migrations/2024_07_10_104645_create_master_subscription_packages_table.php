<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('master_subscription_packages', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('master_id')->nullable();
            $table->unsignedInteger('meta_login')->nullable();
            $table->string('label')->nullable();
            $table->decimal('amount')->nullable();
            $table->decimal('max_out_amount')->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->foreign('master_id')
                ->references('id')
                ->on('masters')
                ->onUpdate('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('master_subscription_packages');
    }
};
