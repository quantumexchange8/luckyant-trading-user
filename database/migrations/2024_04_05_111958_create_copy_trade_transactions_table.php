<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('copy_trade_transactions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->unsignedBigInteger('trading_account_id')->nullable();
            $table->unsignedInteger('meta_login')->nullable();
            $table->unsignedBigInteger('subscription_id')->nullable();
            $table->unsignedBigInteger('master_id')->nullable();
            $table->unsignedInteger('master_meta_login')->nullable();
            $table->decimal('amount', 13, 2)->nullable();
            $table->decimal('real_fund', 13, 2)->nullable();
            $table->decimal('demo_fund', 13, 2)->nullable();
            $table->string('type')->nullable();
            $table->string('status')->nullable();
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
            $table->foreign('subscription_id')
                ->references('id')
                ->on('subscriptions')
                ->onUpdate('cascade');
            $table->foreign('master_id')
                ->references('id')
                ->on('masters')
                ->onUpdate('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('copy_trade_transactions');
    }
};
