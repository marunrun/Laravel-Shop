<?php

namespace App\Providers;

use App\Facade\TokenClass;
use function foo\func;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\ServiceProvider;
use Monolog\Logger;
use Yansongda\Pay\Pay;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        // 新增phone表单验证
        Validator::extend('phone', function ($attribute, $value, $parameters, $validator) {
            $regex = '/^((13[0-9])|(14[5,7])|(15[0-3,5-9])|(17[0,3,5-8])|(18[0-9])|166|198|199|(147))\\d{8}$/';

            $res = preg_match($regex, $value);
            return (bool)$res;
        });

        // 监听sql语句
//        \DB::listen(function ($query) {
//            $tmp = str_replace('?', '"' . '%s' . '"', $query->sql);
//            $qBindings = [];
//            foreach ($query->bindings as $key => $value) {
//                if (is_numeric($key)) {
//                    $qBindings[] = $value;
//                } else {
//                    $tmp = str_replace(':' . $key, '"' . $value . '"', $tmp);
//                }
//            }
//            $tmp = vsprintf($tmp, $qBindings);
//            $tmp = str_replace("\\", "", $tmp);
//            \Log::info(' execution time: ' . $query->time . 'ms; ' . $tmp . "\n\n\t");
//        }
//        );
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {

        $this->app->singleton('alipay',function () {
            $config = config('pay.alipay');

            // 判断当前项目运行环境是否为线上环境
            if (app()->environment() !== 'production') {
                $config['mode']         = 'dev';
                $config['log']['level'] = Logger::DEBUG;
            } else {
                $config['log']['level'] = Logger::WARNING;
            }
            // 调用 Yansongda\Pay 来创建一个支付宝支付对象
            return Pay::alipay($config);
        });

        $this->app->singleton('wechat_pay',function () {
            $config = config('pay.wechat');

            // 判断当前项目运行环境是否为线上环境
            if (app()->environment() !== 'production') {
                $config['mode']         = 'dev';
                $config['log']['level'] = Logger::DEBUG;
            } else {
                $config['log']['level'] = Logger::WARNING;
            }
            // 调用 Yansongda\Pay 来创建一个支付宝支付对象
            return Pay::wechat($config);
        });


        $this->app->singleton('Token',function (){
            return new TokenClass();
        });
    }
}
