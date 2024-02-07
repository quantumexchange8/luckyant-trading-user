<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('category');
            $table->string('transaction_type');
            $table->unsignedBigInteger('from_wallet_id')->nullable();
            $table->unsignedBigInteger('to_wallet_id')->nullable();
            $table->unsignedBigInteger('from_meta_login')->nullable();
            $table->unsignedBigInteger('to_meta_login')->nullable();
            $table->string('ticket')->nullable();
            $table->string('transaction_number')->nullable();
            $table->string('to_wallet_address')->nullable();
            $table->string('txn_hash')->nullable();
            $table->decimal('amount')->nullable();
            $table->decimal('transaction_charges', 13, 2)->nullable();
            $table->decimal('transaction_amount', 13, 2)->nullable();
            $table->decimal('new_wallet_amount', 13,2)->nullable();
            $table->string('status')->nullable();
            $table->string('comment')->nullable();
            $table->text('remarks')->nullable();
            $table->unsignedBigInteger('handle_by')->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->onUpdate('cascade');
            $table->foreign('from_wallet_id')
                ->references('id')
                ->on('wallets')
                ->onUpdate('cascade');
            $table->foreign('to_wallet_id')
                ->references('id')
                ->on('wallets')
                ->onUpdate('cascade');
            $table->foreign('handle_by')
                ->references('id')
                ->on('users')
                ->onUpdate('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
