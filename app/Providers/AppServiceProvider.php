<?php

namespace App\Providers;

use App\Facade\TokenClass;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\ServiceProvider;
use Monolog\Logger;
use Yansongda\Pay\Pay;

class AppServiceProvider extends ServiceProvider
{
    public function boot()
    {
        // 新增phone表单验证
        Validator::extend('phone', function ($attribute, $value, $parameters, $validator) {
            $regex = '/^((13[0-9])|(14[5,7])|(15[0-3,5-9])|(17[0,3,5-8])|(18[0-9])|166|198|199|(147))\\d{8}$/';

            $res = preg_match($regex, $value);

            return (bool)$res;
        });

        \View::composer(['products.index', 'products.show'], \App\Http\ViewComposers\CategoryTreeComposer::class);

        if ('local' === $this->app->environment()) {
            // 监听sql语句
            \DB::listen(function ($query) {
                $tmp       = str_replace('?', '"'.'%s'.'"', $query->sql);
                $qBindings = [];
                foreach ($query->bindings as $key => $value) {
                    if (is_numeric($key)) {
                        $qBindings[] = $value;
                    } else {
                        $tmp = str_replace(':'.$key, '"'.$value.'"', $tmp);
                    }
                }
                $tmp = vsprintf($tmp, $qBindings);
                $tmp = str_replace('\\', '', $tmp);
                file_put_contents(storage_path(
                    'logs'.DIRECTORY_SEPARATOR.date('Y-m-d').'_query.log'),
                    ' execution time: '.$query->time.'ms; '.$tmp.PHP_EOL,
                    FILE_APPEND);
            });
        }
    }

    /**
     * Register any application services.
     */
    public function register()
    {

        /*
         *  注册支付宝支付的服务容器
         *  使用app('alipay') 使用
         */
        $this->app->singleton('alipay', function () {
            $config = config('pay.alipay');

            $uri = app('router')->getRoutes()->getByName('payment.alipay.notify')->uri;

            $config['notify_url'] = config('shop.url').$uri;

            // 支付宝的验证url
            // 支付宝的跳转url
            $config['return_url'] = route('payment.alipay.return');

            // 判断当前项目运行环境是否为线上环境
//            if ('production' !== app()->environment()) {
                $config['mode']         = 'dev';
                $config['log']['level'] = Logger::DEBUG;
//            } else {
//                $config['log']['level'] = Logger::WARNING;
//            }

            // 调用 Yansongda\Pay 来创建一个支付宝支付对象
            return Pay::alipay($config);
        });

        /*
         *  注册微信支付的服务容器
         *  使用app('wechat_pay') 使用
         */
        $this->app->singleton('wechat_pay', function () {
            $config = config('pay.wechat');
            // 回调地址
            $config['notify_url'] = 'http://requestbin.fullcontact.com/[替换成你自己的url]';

            // 判断当前项目运行环境是否为线上环境
//            if ('production' !== app()->environment()) {
                $config['mode']         = 'dev';
                $config['log']['level'] = Logger::DEBUG;
//            } else {
//                $config['log']['level'] = Logger::WARNING;
//            }

            // 调用 Yansongda\Pay 来创建一个支付宝支付对象
            return Pay::wechat($config);
        });


        $this->app->singleton('Token', function () {
            return new TokenClass();
        });



        if ('local' == $this->app->environment()) {
            $this->app->register(\Barryvdh\LaravelIdeHelper\IdeHelperServiceProvider::class);
            $this->app->register(\Laracasts\Generators\GeneratorsServiceProvider::class);
        }
    }
}
