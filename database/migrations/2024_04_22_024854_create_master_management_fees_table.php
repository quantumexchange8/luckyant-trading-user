<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('master_management_fees', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('master_id');
            $table->unsignedInteger('meta_login');
            $table->integer('penalty_days')->nullable();
            $table->decimal('penalty_percentage')->nullable();
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
        Schema::dropIfExists('master_management_fees');
    }
};
