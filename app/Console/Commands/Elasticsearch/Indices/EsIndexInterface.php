<?php


namespace App\Console\Commands\Elasticsearch\Indices;


interface EsIndexInterface
{
    public static function getAliasName();
    public static function getProperties();
    public static function getSettings();
    public static function reBuild($indexName);
}
