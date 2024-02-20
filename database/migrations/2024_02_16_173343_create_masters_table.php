<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('masters', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->unsignedBigInteger('trading_account_id')->nullable();
            $table->unsignedInteger('meta_login')->nullable();
            $table->decimal('min_join_equity', 13, 2)->nullable();
            $table->decimal('sharing_profit')->default(0);
            $table->decimal('subscription_fee')->default(0);
            $table->boolean('signal_status')->default(true);
            $table->string('status')->default('Inactive');
            $table->softDeletes();
            $table->timestamps();

            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->onUpdate('cascade');
            $table->foreign('trading_account_id')
                ->references('id')
                ->on('trading_accounts')
                ->onUpdate('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('masters');
    }
};
