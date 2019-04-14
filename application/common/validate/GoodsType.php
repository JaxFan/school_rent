<?php
/**
 * Created by PhpStorm.
 * User: 65100
 * Date: 2018/11/18
 * Time: 9:55
 */

namespace app\common\validate;

use think\Validate;

class GoodsType extends Validate
{
    protected $rule = [
        'name|分类名称' => [
            'require'     => 'require',
            'length'      => '2, 6',
            'unique'      => 'platform_goods_type',
            'chsAlpha' => 'chsAlpha' // 仅允许使用汉字、字母
        ]
    ];
}