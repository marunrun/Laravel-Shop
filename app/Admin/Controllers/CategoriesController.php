<?php

namespace App\Admin\Controllers;

use App\Models\Category;
use App\Http\Controllers\Controller;
use Encore\Admin\Controllers\HasResourceActions;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Layout\Content;
use Illuminate\Http\Request;
use Illuminate\Pagination\Paginator;

class CategoriesController extends Controller
{
    use HasResourceActions;

    /**
     * Index interface.
     *
     * @param Content $content
     *
     * @return Content
     */
    public function index(Content $content)
    {
        return $content
            ->header('商品类目列表')
            ->body($this->grid());
    }

    /**
     * Edit interface.
     *
     * @param mixed $id
     * @param Content $content
     *
     * @return Content
     */
    public function edit($id, Content $content)
    {
        return $content
            ->header('编辑商品类目')
            ->body($this->form()->edit($id));
    }

    /**
     * Create interface.
     *
     * @param Content $content
     *
     * @return Content
     */
    public function create(Content $content)
    {
        return $content
            ->header('创建商品类目')
            ->body($this->form());
    }

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new Category());

        $grid->id('Id')->sortable();
        $grid->name('名称');
        $grid->level('层级');
        $grid->is_directory('是否目录')->display(function ($value) {
            return $value ? '是' : '否';
        });
        $grid->path('类目路径');
        $grid->actions(function ($actions) {

            /** @var Grid\Displayers\Actions $actions */
            $actions->disableView();
        });

        return $grid;
    }

    /**
     * Make a form builder.
     *
     * @param bool $isEditing
     * @return Form
     */
    protected function form($isEditing = false)
    {
        $form = new Form(new Category());

        $form->text('name', '分类名称')->rules('required');
        // 如果是编辑
        if ($isEditing) {
            // 不允许用户修改『是否目录』和『父类目』字段的值
            // 用 display() 方法来展示值，with() 方法接受一个匿名函数，会把字段值传给匿名函数并把返回值展示出来
            $form->display('is_directory', '是否目录')->with(function ($value) {
                return $value ? '是' : '否';
            });

            // 支持用符号 . 来展示关联关系的字段
            $form->display('parent.name', '父类目');
        } else {
            $states = [
                'on'  => ['value' => 1, 'text' => '是', 'color' => 'success'],
                'off' => ['value' => 0, 'text' => '否'],
            ];
            $form->switch('is_directory', '是否目录')
                ->states($states)
                ->default(0)
                ->rules('required');

            // 定义一个名为父类目的下拉框
            $form->select('parent_id', '父类目')->ajax('/admin/api/categories');
        }

        return $form;
    }

    public function apiIndex(Request $request)
    {
        // 用户输入的值通过 q 参数获取
        $search = $request->input('q');

        $res = Category::query()
            // 通过 is_directory 来控制
            ->where('is_directory', boolval($request->input('is_directory', true)))
            ->where('name', 'like', "%{$search}%")
            ->paginate();


        /**
         * 把查询出来的结果重新组装成 Laravel-Admin 需要的格式
         * @var Paginator $res
         */
        $res->setCollection($res->getCollection()->map(function (Category $category) {
            return ['id' => $category->id, 'text' => $category->full_name];
        }));

        return $res;
    }
}
