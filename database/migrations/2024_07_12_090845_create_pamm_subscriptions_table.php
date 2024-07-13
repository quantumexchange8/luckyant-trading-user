<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('pamm_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->unsignedInteger('meta_login')->nullable();
            $table->unsignedBigInteger('master_id')->nullable();
            $table->unsignedInteger('master_meta_login')->nullable();
            $table->decimal('subscription_amount')->nullable();
            $table->string('type')->nullable();
            $table->unsignedBigInteger('transaction_id')->nullable();
            $table->string('subscription_number')->nullable();
            $table->integer('subscription_period')->nullable();
            $table->integer('settlement_period')->nullable();
            $table->decimal('subscription_fee')->nullable();
            $table->timestamp('settlement_date')->nullable();
            $table->timestamp('expired_date')->nullable();
            $table->timestamp('termination_date')->nullable();
            $table->decimal('max_out_amount')->nullable();
            $table->timestamp('approval_date')->nullable();
            $table->string('status')->nullable();
            $table->text('remarks')->nullable();
            $table->unsignedBigInteger('handle_by')->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->foreign('user_id')
                ->references('id')
                ->on('users')
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
        Schema::dropIfExists('pamm_subscriptions');
    }
};
