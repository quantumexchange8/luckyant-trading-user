<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->timestamp('email_verified_at')->nullable();
            $table->string('email')->unique();
            $table->string('password');
            $table->string('phone')->unique();
            $table->string('chinese_name')->nullable();
            $table->date('dob');
            $table->text('address_1')->nullable();
            $table->text('address_2')->nullable();
            $table->string('postcode')->nullable();
            $table->mediumInteger('city')->nullable();
            $table->mediumInteger('state')->nullable();
            $table->mediumInteger('country')->nullable();
            $table->ipAddress('register_ip')->nullable()->default('::1');
            $table->ipAddress('last_login_ip')->nullable()->default('::1');
            $table->string('cash_wallet_id')->nullable();
            $table->decimal('cash_wallet', 11, 2)->default(0.00);
            $table->string('kyc_approval')->default('Pending');
            $table->date('kyc_approval_date')->nullable();
            $table->text('kyc_approval_description')->nullable();
            $table->string('gender')->nullable();
            $table->unsignedBigInteger('upline_id')->nullable();
            $table->string('hierarchyList')->nullable();
            $table->string('referral_code')->nullable();
            $table->string('role')->default('member');
            $table->string('remark')->nullable();
            $table->rememberToken();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
