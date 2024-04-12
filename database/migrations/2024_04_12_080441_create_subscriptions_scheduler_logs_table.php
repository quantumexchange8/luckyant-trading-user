<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('subscriptions_scheduler_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('old_subscription_id');
            $table->unsignedBigInteger('new_subscription_id');
            $table->dateTime('old_expired_date');
            $table->dateTime('new_expired_date');
            $table->string('old_status');
            $table->string('new_status');
            $table->softDeletes();
            $table->timestamps();

            $table->foreign('old_subscription_id')
                ->references('id')
                ->on('subscriptions')
                ->onUpdate('cascade');
            $table->foreign('new_subscription_id')
                ->references('id')
                ->on('subscriptions')
                ->onUpdate('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subscriptions_scheduler_logs');
    }
};
