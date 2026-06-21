<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('settlements', function (Blueprint $table) {
            $table->id();
            $table->string('settlement_no', 50)->unique()->comment('结算单号');
            $table->string('type', 50)->default('manual')->comment('结算类型：order订单结算/monthly月度结算/manual手动结算');
            $table->date('settlement_date')->comment('结算日期');
            $table->string('order_no', 50)->nullable()->comment('关联订单号（订单结算时）');
            $table->integer('order_count')->default(0)->comment('关联订单数量');
            $table->decimal('total_amount', 12, 2)->default(0)->comment('订单总金额');
            $table->decimal('product_cost', 12, 2)->default(0)->comment('商品成本');
            $table->decimal('platform_fee', 12, 2)->default(0)->comment('平台费用');
            $table->decimal('other_cost', 12, 2)->default(0)->comment('其他成本');
            $table->decimal('total_cost', 12, 2)->default(0)->comment('总成本');
            $table->decimal('total_profit', 12, 2)->default(0)->comment('总毛利润');
            $table->decimal('profit_rate', 8, 4)->default(0)->comment('毛利率');
            $table->decimal('supplier_ratio', 8, 4)->default(0.50)->comment('供应商分账比例');
            $table->decimal('distributor_ratio', 8, 4)->default(0.20)->comment('经销商分账比例');
            $table->decimal('platform_ratio', 8, 4)->default(0.30)->comment('平台分账比例');
            $table->decimal('supplier_share', 12, 2)->default(0)->comment('供应商分账金额');
            $table->decimal('distributor_share', 12, 2)->default(0)->comment('经销商分账金额');
            $table->decimal('platform_share', 12, 2)->default(0)->comment('平台分账金额');
            $table->string('status', 20)->default('pending')->comment('状态：pending待确认/confirmed已确认/settled已结算/cancelled已取消');
            $table->text('remark')->nullable()->comment('备注');
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->unsignedBigInteger('settled_by')->nullable()->comment('结算操作人');
            $table->timestamp('settled_at')->nullable()->comment('结算时间');
            $table->timestamps();
            $table->softDeletes();

            $table->index('settlement_no');
            $table->index('type');
            $table->index('status');
            $table->index('settlement_date');
            $table->index(['status', 'settlement_date']);
        });

        Schema::create('settlement_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('settlement_id')->comment('结算单ID');
            $table->unsignedBigInteger('product_id')->comment('商品ID');
            $table->string('product_name', 200)->comment('商品名称');
            $table->string('product_sku', 100)->nullable()->comment('商品SKU');
            $table->integer('quantity')->default(1)->comment('数量');
            $table->decimal('sale_price', 12, 2)->default(0)->comment('售价');
            $table->decimal('total_sales', 12, 2)->default(0)->comment('销售金额');
            $table->decimal('unit_cost', 12, 2)->default(0)->comment('单位成本');
            $table->decimal('total_cost', 12, 2)->default(0)->comment('总成本');
            $table->decimal('profit', 12, 2)->default(0)->comment('毛利');
            $table->timestamps();

            $table->index('settlement_id');
            $table->index('product_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('settlement_items');
        Schema::dropIfExists('settlements');
    }
};
