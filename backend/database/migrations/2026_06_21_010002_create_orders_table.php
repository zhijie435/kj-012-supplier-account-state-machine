<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_no', 50)->unique()->comment('订单号');
            $table->unsignedBigInteger('supplier_id')->nullable()->comment('供应商ID');
            $table->unsignedBigInteger('distributor_id')->nullable()->comment('经销商ID');
            $table->decimal('total_amount', 12, 2)->default(0)->comment('订单总金额');
            $table->decimal('total_cost', 12, 2)->default(0)->comment('订单总成本');
            $table->decimal('total_profit', 12, 2)->default(0)->comment('总利润');
            $table->tinyInteger('status')->default(0)->comment('订单状态：0待付款 1已付款 2已发货 3已完成 4已取消 5已退款 6部分退款');
            $table->text('remark')->nullable()->comment('备注');
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('shipped_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('order_no');
            $table->index('supplier_id');
            $table->index('distributor_id');
            $table->index('status');
            $table->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
