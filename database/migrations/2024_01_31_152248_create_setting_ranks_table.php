<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('setting_ranks', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->integer('position')->nullable();
            $table->decimal('standard_lot')->nullable();
            $table->decimal('package_requirement')->nullable();
            $table->integer('direct_referral')->nullable();
            $table->string('cultivate_type')->nullable();
            $table->string('cultivate_member')->nullable();
            $table->decimal('group_sales', 13, 2)->nullable();
            $table->decimal('rebate')->nullable();
            $table->decimal('rank_worldpool')->default(0);
            $table->softDeletes();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('setting_ranks');
    }
};
