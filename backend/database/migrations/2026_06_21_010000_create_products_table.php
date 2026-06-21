<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('name', 200)->comment('商品名称');
            $table->string('sku', 100)->nullable()->unique()->comment('商品SKU');
            $table->text('description')->nullable()->comment('商品描述');
            $table->string('image_url', 500)->nullable()->comment('商品图片');
            $table->decimal('price', 12, 2)->default(0)->comment('售价');
            $table->unsignedBigInteger('supplier_id')->nullable()->comment('供应商ID');
            $table->decimal('weight', 10, 2)->nullable()->comment('重量(克)');
            $table->string('unit', 20)->default('件')->comment('单位');
            $table->tinyInteger('status')->default(1)->comment('状态：1启用 0禁用');
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('supplier_id');
            $table->index('status');
            $table->index('sku');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
