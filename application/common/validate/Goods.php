<?php
/**
 * Created by PhpStorm.
 * User: 65100
 * Date: 2018/11/18
 * Time: 19:08
 */

namespace app\common\validate;

use think\Validate;

class Goods extends Validate
{
    protected $rule = [
        'name|物品名称' => [
            'require'     => 'require',
            'length'      => '2, 30',
            'unique'      => 'platform_goods',
            'chsAlphaNum' => 'chsAlphaNum' // 仅允许使用汉字、字母
        ],
        'price|物品价格' => [
            'require' => 'require',
            'float'   => 'float',
        ],
        'type_id|种类编号' => [
            'require' => 'require',
        ],
        'lend_total|物品总量' => [
            'require' => 'require',
            'number'  => 'number'
        ],
        'lend_time|物品可借时间' => [
            'require' => 'require',
            'number'  => 'number'
        ],
        'describe|物品描述' => [
            'require' => 'require'
        ]
    ];
}