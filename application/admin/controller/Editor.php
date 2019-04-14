<?php
/**
 * Created by PhpStorm.
 * User: 65100
 * Date: 2018/11/18
 * Time: 16:01
 */

namespace app\admin\controller;

use app\admin\common\controller\Base;
use think\facade\Request;
use think\Image;

class Editor extends Base
{
    // 富文本编辑器内的图片上传并压缩
    public function upload()
    {
        $images = []; // 保存图片在服务器上的地址
        $errors = []; // 保存错误信息
        $files= Request::file();

        foreach ($files as $file) {
            $info = $file->move('uploads/');
            $image = Image::open('uploads/'.$info->getSaveName());
            $image->thumb(400, 300)->save('uploads/'.$info->getSaveName());
            if($info){
                $path = '/uploads/'.$info->getSaveName();
                array_push($images, $path);
            }else{
                array_push($errors, $file->getError());
            }
        }

        if (0 == count($errors)) {
            $msg['errno'] = 0;
            $msg['data'] = $images;
            return json($msg);
        } else {
            $msg['errno'] = 1;
            $msg['data'] = $images;
            return json($msg);
        }
    }
}