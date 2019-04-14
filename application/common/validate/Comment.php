<?php
/**
 * Created by PhpStorm.
 * User: 65100
 * Date: 2018/11/22
 * Time: 22:31
 */

namespace app\common\validate;

use think\Validate;

class Comment extends Validate
{
    protected $rule = [
        'content|反馈内容' => [
            'require' => 'require'
        ],
        'comment_level|评论等级' => [
            'require' => 'require'
        ]
    ];
}