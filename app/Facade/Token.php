<?php
/**
 * Created by PhpStorm.
 * User: run
 * Date: 2019/2/21
 * Time: 11:31
 */

namespace App\Facade;


use Illuminate\Support\Facades\Facade;

class Token extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'Token';
    }
}