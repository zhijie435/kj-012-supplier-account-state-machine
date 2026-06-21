<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('suppliers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('company_name')->nullable();
            $table->string('business_license')->nullable();
            $table->string('contact_person');
            $table->string('phone');
            $table->string('email')->nullable();
            $table->text('address')->nullable();
            $table->string('bank_name')->nullable();
            $table->string('bank_account')->nullable();
            $table->decimal('credit_limit', 15, 2)->default(0);
            $table->decimal('balance', 15, 2)->default(0);
            $table->enum('status', ['pending', 'verifying', 'active', 'suspended', 'rejected', 'cancelled'])->default('pending');
            $table->text('remark')->nullable();
            $table->string('country_code', 10)->nullable();
            $table->string('tax_id', 50)->nullable();
            $table->string('export_license', 100)->nullable();
            $table->string('import_export_code', 100)->nullable();
            $table->json('certifications')->nullable();
            $table->json('serviced_markets')->nullable();
            $table->boolean('is_cross_border')->default(false);
            $table->dateTime('verifying_at')->nullable();
            $table->dateTime('activated_at')->nullable();
            $table->dateTime('suspended_at')->nullable();
            $table->dateTime('rejected_at')->nullable();
            $table->dateTime('cancelled_at')->nullable();
            $table->unsignedBigInteger('operated_by')->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->index('status');
            $table->index('created_at');

            $table->foreign('operated_by')->references('id')->on('users')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('suppliers');
    }
};
