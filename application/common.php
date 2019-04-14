<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2016 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: 流年 <liu21st@gmail.com>
// +----------------------------------------------------------------------

// 应用公共文件
use think\Db;

// 查询用户名
if (!function_exists('getUserName')) {
    function getUserName ($id) {
        return Db::table('platform_user')
            ->where('id', '=', $id)
            ->value('name');
    }
}

// 获得用户介绍
if (!function_exists('getUserIntrduce')) {
    function getUserIntrduce ($id) {
        $shortIntrduce = Db::table('platform_user')
            ->where('id', '=', $id)
            ->value('short_intrduce');
        if (is_null($shortIntrduce) || '' == $shortIntrduce) {
            return '该用户什么也没留下';
        } else {
            return $shortIntrduce;
        }
    }
}

// 查询种类
if (!function_exists('getGoodsTypeName')) {
    function getGoodsTypeName ($id) {
        return Db::table('platform_goods_type')
            ->where('id', '=', $id)
            ->value('name');
    }
}

// 过滤文章摘要
if (!function_exists('getArtContent')) {
    function getArtContent ($content) {
        return mb_substr(strip_tags($content), 0, 20) . '......';
    }
}

if (!function_exists('filterContent')) {
    function filterContent ($content) {
        return mb_substr(strip_tags($content), 0, 10) . '...';
    }
}

if (!function_exists('getUserAvatar')) {
    function getUserAvatar ($id) {
        return Db::table('platform_user')
            ->where('id', '=', $id)
            ->value('avatar');
    }
}

// 获得商品名
if (!function_exists('getGoodsName')) {
    function getGoodsName ($id) {
        return Db::table('platform_goods')
            ->where('id', '=', $id)
            ->value('name');
    }
}

if (!function_exists('getGoodsPrice')) {
    function getGoodsPrice ($id) {
        return Db::table('platform_goods')
            ->where('id', '=', $id)
            ->value('price');
    }
}

if (!function_exists('getGoodsNum')) {
    function getGoodsNum ($id) {
        return Db::table('platform_goods')
            ->where('id', '=', $id)
            ->value('lend_total');
    }
}

if (!function_exists('getLendNum')) {
    function getLendNum ($id) {
        return Db::table('platform_deal')->where([
            ['id', '=', $id],
            ['status', '=', 1]
        ])->count();
    }
}