<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateInstallmentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('installments', function (Blueprint $table) {
            $table->increments('id');
            $table->string('no')->unique()->comment('流水号');
            $table->unsignedInteger('user_id')->comment('用户id');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->unsignedInteger('order_id')->comment('订单id');
            $table->foreign('order_id')->references('id')->on('orders')->onDelete('cascade');
            $table->decimal('total_amount')->comment('订单总价');
            $table->unsignedInteger('count')->comment('还款期数');
            $table->float('fee_rate')->comment('还款费率');
            $table->float('fine_rate')->comment('逾期费率');
            $table->string('status')->default(\App\Models\InstallmentItem::REFUND_STATUS_PENDING)->comment('状态');
            $table->timestamps();
        });

        DB::statement("ALTER TABLE `installments` COMMENT '分期付款表'");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('installments');
    }
}
