<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('world_pool_allocations', function (Blueprint $table) {
            $table->id();
            $table->timestamp('allocation_date');
            $table->decimal('allocation_amount', 13);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('world_pool_allocations');
    }
};
