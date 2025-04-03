<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('master_terms', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('master_id');
            $table->longText('term_contents')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('master_id')
                ->references('id')
                ->on('masters')
                ->onUpdate('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('master_terms');
    }
};
