<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('from_trading_acc_no')->nullable();
            $table->unsignedBigInteger('to_trading_acc_no')->nullable();
            $table->string('ticket');
            $table->string('transaction_id');
            $table->string('category')->nullable();
            $table->string('type')->nullable();
            $table->decimal('amount');
            $table->string('currency')->nullable();
            $table->decimal('payment_charges')->nullable();
            $table->string('account_type')->nullable();
            $table->string('account_no')->nullable();
            $table->date('approval_date')->nullable();
            $table->text('remarks');
            $table->unsignedBigInteger('handle_by');
            $table->softDeletes();
            $table->timestamps();

            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->onUpdate('cascade');
            $table->foreign('handle_by')
                ->references('id')
                ->on('users')
                ->onUpdate('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
