<?php
/**
 * Created by PhpStorm.
 * User: 65100
 * Date: 2018/11/19
 * Time: 19:48
 */

namespace app\common\validate;

use think\Validate;

class Notice extends Validate
{
    protected $rule = [
        'name|公告标题' => [
            'require'     => 'require',
            'length'      => '1, 10',
            'unique'      => 'platform_notice',
            'chsAlphaNum' => 'chsAlphaNum' // 仅允许使用汉字、字母
        ],
        'content|公告标题' => [
            'require'     => 'require',
            'unique'      => 'platform_notice',
        ],
    ];
}