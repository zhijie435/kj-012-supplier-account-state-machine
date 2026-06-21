<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('order_id')->comment('订单ID');
            $table->unsignedBigInteger('product_id')->comment('商品ID');
            $table->unsignedBigInteger('product_cost_id')->nullable()->comment('关联成本配置ID');
            $table->string('product_name', 200)->comment('商品名称快照');
            $table->string('product_sku', 100)->nullable()->comment('商品SKU快照');
            $table->string('product_image', 500)->nullable()->comment('商品图片快照');
            $table->integer('quantity')->default(1)->comment('购买数量');
            $table->decimal('sale_price', 12, 2)->default(0)->comment('销售单价快照');
            $table->decimal('purchase_price', 12, 2)->default(0)->comment('采购成本快照');
            $table->decimal('shipping_cost', 12, 2)->default(0)->comment('物流成本快照');
            $table->decimal('packaging_cost', 12, 2)->default(0)->comment('包装成本快照');
            $table->decimal('platform_fee', 12, 2)->default(0)->comment('平台服务费快照');
            $table->decimal('commission_amount', 12, 2)->default(0)->comment('佣金快照');
            $table->decimal('tax_amount', 12, 2)->default(0)->comment('税费快照');
            $table->decimal('other_cost', 12, 2)->default(0)->comment('其他成本快照');
            $table->decimal('unit_cost', 12, 2)->default(0)->comment('单位总成本快照');
            $table->decimal('total_cost', 12, 2)->default(0)->comment('总成本快照');
            $table->decimal('subtotal', 12, 2)->default(0)->comment('销售小计');
            $table->decimal('profit', 12, 2)->default(0)->comment('利润');
            $table->decimal('profit_rate', 8, 4)->default(0)->comment('利润率');
            $table->timestamps();

            $table->index('order_id');
            $table->index('product_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_items');
    }
};
