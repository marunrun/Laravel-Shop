<?php

namespace App\Admin\Controllers;

use App\Models\CrowdfundingProduct;
use App\Models\Product;
use Encore\Admin\Form;
use Encore\Admin\Grid;

class CrowdfundingProductsController extends CommonProductsController
{
    /**
     * 抽象方法  返回当前商品的类型.
     *
     * @return string
     */
    public function getProductType()
    {
        return Product::TYPE_CROWDFUNDING;
    }

    /**
     * 定义一个抽象方法，各个类型的控制器将实现本方法来定义列表应该展示哪些字段.
     *
     * @return void
     */
    protected function customGrid(Grid $grid)
    {
        $grid->id('Id')->sortable();
        $grid->title('商品名称');
        $grid->on_sale('已上架?')->display(function ($value) {
            return $value ? '是' : '否';
        });
        $grid->price('价格');

        // 展示众筹字段
        $grid->column('crowdfunding.target_amount', '目标金额');
        $grid->column('crowdfunding.end_at', '结束时间');
        $grid->column('crowdfunding.total_amount', '目前金额');
        $grid->column('crowdfunding.status', '状态')->display(function ($value) {
            return CrowdfundingProduct::$statusMap[$value];
        });
    }

    /**
     * 定义一个抽象方法，各个类型的控制器将实现本方法来定义表单应该有哪些额外的字段.
     *
     * @param Form $form
     *
     * @return void
     */
    protected function customForm(Form $form)
    {
        // 添加众筹相关字段
        $form->text('crowdfunding.target_amount', '众筹目标金额')->rules('required|numeric|min:0.01');
        $form->datetime('crowdfunding.end_at', '众筹结束时间')->rules('required|date');
    }
}
