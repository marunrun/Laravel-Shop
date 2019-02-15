<?php

namespace App\Admin\Controllers;

use App\Models\Product;
use App\Http\Controllers\Controller;
use Encore\Admin\Controllers\HasResourceActions;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Layout\Content;
use Encore\Admin\Show;

class ProductsController extends Controller
{
    use HasResourceActions;

    /**
     * Index interface.
     *
     * @param Content $content
     * @return Content
     */
    public function index(Content $content)
    {
        return $content
            ->header('商品')
            ->description('列表')
            ->body($this->grid());
    }


    /**
     * Edit interface.
     *
     * @param mixed $id
     * @param Content $content
     * @return Content
     */
    public function edit($id, Content $content)
    {
        return $content
            ->header('编辑商品')
            ->description(' ')
            ->body($this->form()->edit($id));
    }

    /**
     * Create interface.
     *
     * @param Content $content
     * @return Content
     */
    public function create(Content $content)
    {
        return $content
            ->header('添加商品')
            ->description(' ')
            ->body($this->form());
    }

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new Product);

        $grid->id('Id')->sortable();
        $grid->title('商品名称');
//        $grid->image('Image');
        $grid->on_sale('已上架?')->display(function ($value){
            return $value ? '是' : '否';
        });

        $grid->price('价格');
        $grid->rating('评分');
        $grid->sale_count('销量');
        $grid->review_count('评论数');

        $grid->actions(function ($actions) {
            $actions->disableView();
            $actions->disableDelete();
        });

        $grid->tools(function ($tools) {
            // 禁用批量删除按钮
            $tools->batch(function ($batch) {
                $batch->disableDelete();
            });
        });

        return $grid;
    }


    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        $form = new Form(new Product);
        // 一个输入框
        $form->text('title', '商品名称')->rules('required');
        // 选择图片
        $form->image('image', '封面图片')->rules('required|image');
        // 富文本编辑器
        $form->editor('description', '商品描述')->rules('required');
        $form->radio('on_sale', '上架')->options(['1' => '是' , '0' => '否'])->default(0);

        $form->hasMany('skus','SKU列表',function (Form\NestedForm $form){
            $form->text('title','SKU 名称')->rules('required');
            $form->text('description','SKU 描述')->rules('required');
            $form->text('price','单价')->rules('required|numeric|min:0.01');
            $form->text('stock','库存')->rules('required|integer|min:0');
        });
        $form->saving(function (Form $form){
            $form->model()->price =
                collect($form->input('skus'))->where(Form::REMOVE_FLAG_NAME, 0)->min('price') ?: 0;
        });
        return $form;
    }
}
