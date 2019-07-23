<?php

function route_class()
{
    return str_replace('.','-',Route::currentRouteName());
}

function big_number($number, $scale = 2)
{
    return new \Moontoast\Math\BigNumber($number,$scale);
}