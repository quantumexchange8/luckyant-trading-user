<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('subscription_batches', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('trading_account_id');
            $table->unsignedInteger('meta_login');
            $table->decimal('meta_balance')->nullable();
            $table->decimal('real_fund')->default(0);
            $table->decimal('demo_fund')->default(0);
            $table->unsignedBigInteger('master_id')->nullable();
            $table->unsignedInteger('master_meta_login')->nullable();
            $table->string('type')->nullable();
            $table->unsignedBigInteger('subscriber_id')->nullable();
            $table->string('subscription_number')->nullable();
            $table->integer('subscription_period')->nullable();
            $table->decimal('subscription_fee')->nullable();
            $table->dateTime('termination_date')->nullable();
            $table->dateTime('settlement_date')->nullable();
            $table->string('status')->nullable();
            $table->boolean('auto_renewal')->nullable();
            $table->dateTime('approval_date')->nullable();
            $table->text('remarks')->nullable();
            $table->decimal('claimed_profit')->default(0);
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
            $table->foreign('subscriber_id')
                ->references('id')
                ->on('subscribers')
                ->onUpdate('cascade');
            $table->foreign('handle_by')
                ->references('id')
                ->on('users')
                ->onUpdate('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subscription_batches');
    }
};
