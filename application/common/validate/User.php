<?php
/**
 * Created by PhpStorm.
 * User: 65100
 * Date: 2018/11/19
 * Time: 14:46
 */

namespace app\common\validate;

use think\Validate;

class User extends Validate
{
    protected $rule = [
        'name|昵称' => [
            'require'     => 'require',
            'length'      => '1, 10',
            'unique'      => 'platform_user',
            'chsAlphaNum' => 'chsAlphaNum' // 仅允许使用汉字、字母、数字
        ],
        'mobile|电话' => [
            'require' => 'require',
            'mobile'  => 'mobile',
            'unique'  => 'platform_user',
        ],
        'address|地址' => [
            'require' => 'require',
            'length'   => '4, 20',
        ],
        'true_name|真实姓名' => [
            'require' => 'require',
        ],
        'school|学校' => [
            'length'   => '10, 13',
            'chsAlphaNum' => 'chsAlphaNum',
            'length'   => '4, 20',
        ],
        'student_id|学号' => [
            'unique'  => 'platform_user'
        ],
        'password|密码' => [
            'require' => 'require',
            'alphaNum' => 'alphaDash',
            'length'   => '6, 20',
        ],
    ];
}