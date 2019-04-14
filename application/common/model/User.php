<?php
/**
 * Created by PhpStorm.
 * User: 65100
 * Date: 2018/11/17
 * Time: 21:09
 */

namespace app\common\model;

use think\Model;

class User extends Model
{
    // 主键
    protected $pk = 'id';
    // 数据表
    protected $table = 'platform_user';
    // 自动时间戳
    protected $autoWriteTimestamp = true;
    protected $createTime = 'create_time'; // 创建时间
    protected $updateTime = 'update_time'; // 更新时间

    // 开启自动设置
    protected $auto = []; // 无论是新增还是更新都要设置的字段
    // 仅新增的有效
    protected $insert = ['create_time', 'status'=>1, 'type'=>1, 'is_admin'=>0];
    // 仅更新的时候有效
    protected $update = ['update_time'];

    // 修改器
    public function setPasswordAttr($value)
    {
        return sha1($value);
    }
}