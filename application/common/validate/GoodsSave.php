<?php
/**
 * Created by PhpStorm.
 * User: 65100
 * Date: 2018/11/23
 * Time: 10:11
 */

namespace app\common\validate;

use think\Validate;

class GoodsSave extends Validate
{
    protected $rule = [
        'lend_id|租赁用户' => [
            'require' => 'require'
        ],
        'rent_id|提供用户' => [
            'require' => 'require'
        ],
        'goods_id|物品编号' => [
            'require' => 'require'
        ]
    ];
}