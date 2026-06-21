<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('supplier_account_status_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('supplier_id');
            $table->enum('from_status', ['pending', 'verifying', 'active', 'suspended', 'rejected', 'cancelled'])->nullable();
            $table->enum('to_status', ['pending', 'verifying', 'active', 'suspended', 'rejected', 'cancelled']);
            $table->text('remark')->nullable();
            $table->unsignedBigInteger('operated_by')->nullable();
            $table->timestamps();

            $table->foreign('supplier_id')->references('id')->on('suppliers')->onDelete('cascade');
            $table->foreign('operated_by')->references('id')->on('users')->onDelete('set null');
            $table->index('supplier_id');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('supplier_account_status_logs');
    }
};
