<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('setting_payment_methods', function (Blueprint $table) {
            $table->id();
            $table->string('payment_method')->nullable();
            $table->string('payment_account_name')->nullable();
            $table->string('payment_platform_name')->nullable();
            $table->string('account_no')->nullable();
            $table->string('bank_swift_code')->nullable();
            $table->string('bank_code')->nullable();
            $table->string('bank_code_type')->nullable();
            $table->string('country')->nullable();
            $table->string('currency')->nullable();
            $table->string('status')->nullable();
            $table->unsignedBigInteger('handle_by')->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->foreign('handle_by')
                ->references('id')
                ->on('users')
                ->onUpdate('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('setting_payment_methods');
    }
};
