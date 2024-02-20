<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('subscriptions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->unsignedBigInteger('trading_account_id')->nullable();
            $table->unsignedInteger('meta_login')->nullable();
            $table->unsignedBigInteger('transaction_id')->nullable();
            $table->unsignedBigInteger('master_id')->nullable();
            $table->string('type')->default('CopyTrade');
            $table->string('subscription_period')->default('Monthly');
            $table->decimal('subscription_fee', 13, 2)->nullable();
            $table->date('next_pay_date')->nullable();
            $table->string('status')->default('Active');
            $table->unsignedBigInteger('handle_by')->nullable();
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
            $table->foreign('master_id')
                ->references('id')
                ->on('masters')
                ->onUpdate('cascade');
            $table->foreign('transaction_id')
                ->references('id')
                ->on('transactions')
                ->onUpdate('cascade');
            $table->foreign('handle_by')
                ->references('id')
                ->on('users')
                ->onUpdate('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subscriptions');
    }
};
