<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_costs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('product_id')->comment('商品ID');
            $table->string('cost_type', 50)->comment('成本类型：purchase采购/shipping物流/packaging包装/platform_fee平台/marketing营销/tax税费/other其他');
            $table->string('cost_name', 100)->comment('成本项目名称');
            $table->decimal('unit_cost', 12, 2)->default(0)->comment('单位成本');
            $table->integer('quantity')->default(1)->comment('数量/基数');
            $table->decimal('total_cost', 12, 2)->default(0)->comment('总成本 = unit_cost * quantity');
            $table->date('effective_date')->comment('生效日期');
            $table->date('expiry_date')->nullable()->comment('失效日期，null表示永久');
            $table->tinyInteger('is_active')->default(1)->comment('是否启用：1是 0否');
            $table->text('remark')->nullable()->comment('备注');
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();

            $table->index('product_id');
            $table->index('cost_type');
            $table->index(['is_active', 'effective_date']);
            $table->index(['product_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_costs');
    }
};
