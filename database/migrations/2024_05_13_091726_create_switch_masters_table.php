<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('switch_masters', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->unsignedInteger('meta_login')->nullable();
            $table->unsignedBigInteger('old_master_id')->nullable();
            $table->unsignedInteger('old_master_meta_login')->nullable();
            $table->unsignedBigInteger('new_master_id')->nullable();
            $table->unsignedInteger('new_master_meta_login')->nullable();
            $table->unsignedBigInteger('old_subscriber_id')->nullable();
            $table->timestamp('approval_date')->nullable();
            $table->string('status')->nullable();
            $table->string('remarks')->nullable();
            $table->unsignedBigInteger('handle_by')->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->onUpdate('cascade');
            $table->foreign('old_master_id')
                ->references('id')
                ->on('masters')
                ->onUpdate('cascade');
            $table->foreign('new_master_id')
                ->references('id')
                ->on('masters')
                ->onUpdate('cascade');
            $table->foreign('old_subscriber_id')
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
        Schema::dropIfExists('switch_masters');
    }
};
