<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class Test extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * 将数组中某个值移动到指定位置
     */
    public function handle()
    {
        $arr = range(1, 100);

        $start = microtime(true);
        var_dump($this->moveBySlice($arr, 50, 100)[50]);
        var_dump(microtime(true) - $start);

        $start = microtime(true);
        var_dump($this->moveBySort($arr, 50, 1)[50]);
        var_dump(microtime(true) - $start);
    }


    /**
     * 通过分割数组实现
     * @param $array
     * @param $key
     * @param $value
     * @return array
     */
    public function moveBySlice($array, $key, $value)
    {
        // 如果value不存在就不处理了
        if (!in_array($value, $array)) {
            return $array;
        }
        // value与key反转
        $keys = array_flip($array);

        // 取到原本的key
        $originalKey = $keys[$value];
        if ($originalKey == $key) {
            return $array;
        }
        // 如果是移动到第一位
        if ($key <= 0) {
            // 先删掉原有数据
            unset($array[$originalKey]);
            // 再从头部添加一下
            array_unshift($array, $value);

            return $array;
        }

        // 如果是移动到末尾
        if ($key >= count($array) - 1) {
            // 删掉原有的数据
            unset($array[$originalKey]);
            $array[] = $value;

            return $array;
        }

        // 其他的就是正常的前后移动
        // 先分割一下数组
        $left = array_slice($array, 0, $key);
        $right = array_slice($array, $key);
        if (in_array($value, $left)) {
            // 如果原来的数据在分割的左边 那就直接根据原有key删除
            unset($left[$originalKey]);
        } else {
            // 在右边的话 就是拿原有的key 减去移动后的key
            unset($right[$originalKey - $key]);
        }
        $left[] = $value;

        return array_merge($left, $right);

    }

    /**
     * 通过排序来实现
     * @param $array
     * @param $key
     * @param $value
     * @return array|void
     */
    public function moveBySort($array, $key, $value)
    {
        // 如果value不存在就不处理了
        if (!in_array($value, $array)) {
            return $array;
        }

        // value与key反转
        $keys = array_flip($array);

        // 取到原本的key
        $originalKey = $keys[$value];
        if ($originalKey == $key) {
            return $array;
        }

        // 如果是移动到第一位
        if ($key <= 0) {
            // 先删掉原有数据
            unset($array[$originalKey]);
            // 再从头部添加一下
            array_unshift($array, $value);

            return $array;
        }

        // 如果是移动到末尾
        if ($key >= count($array) - 1) {
            // 删掉原有的数据
            unset($array[$originalKey]);
            $array[] = $value;

            return $array;
        }

        // 其他的就是正常的前后移动

        $keys[$value] = $key;

        asort($keys);
        dump($keys);
        return array_keys($keys);
    }
}
