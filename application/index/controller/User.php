<?php
/**
 * Created by PhpStorm.
 * User: 65100
 * Date: 2018/11/21
 * Time: 18:12
 */

namespace app\index\controller;

use app\index\common\controller\Base;
use think\facade\Request;
use app\common\model\User as UserModel;
use think\facade\Session;
use think\Image;
use app\common\validate\User as UserValidate;

class User extends Base
{
    // 验证码
    private $mobile_code = '';
    private $validate = null;

    private function saveImg($img, $w=50, $h=50)
    {
        $imgInfo = Request::file($img);
        if ($imgInfo) {
            $info = $imgInfo->validate([
                'size' => 1000000,
                'ext'  => 'jpg,jpeg,png,gif'
            ])->move('uploads/');
            $image = Image::open('uploads/'.$info->getSaveName());
            $image->thumb($w, $h)->save('uploads/'.$info->getSaveName());
            if ($info) {
                $this->userInfo[$img] = $info->getSaveName();
            } else {
                return ['status'=>-1, 'msg'=>$imgInfo->getError()];
            }
        }
    }
    // 用户注册
    public function register()
    {
        $this->is_reg();
        return $this->view->fetch('userRegister', ['title' => '用户登录']);
    }

    // 用户登录
    public function checkLogin()
    {
        $this->is_login();
        $userResult = null;
        $map = [];
        // 自定义验证规则
        $rule = [
            'mobile|手机' => [
                'require' => 'require',
                'mobile'   => 'mobile',
            ],
            'password|密码' => [
                'require'  => 'require',
                'length'    => '6, 20',
                'alphaDash' => 'alphaDash', // 仅允许使用字母、数字
            ],
            'captcha|验证码' => 'require|captcha'
        ];

        if (!Request::isAjax()) {
            return json(['status' => -1, 'msg' => '请求类型错误']);
        }
        // 获取数据
        $data = Request::param();
        // 验证
        $res = $this->validate($data, $rule);
        if (true !== $res) {
            return json(['status' => -1, 'msg' => $res]);
        }

        // 查询条件1
        $map[] = ['password', '=', sha1($data['password'])];
        $map[] = ['mobile', '=', $data['mobile']];
        $userResult = UserModel::where($map)->find();

        if (!is_null($userResult)) {
            UserModel::where('id', '=', $userResult['id'])->update(['is_login'=>1]);
            $this->is_black_user($userResult['id']);
            Session::set('user_id', $userResult['id']);
            Session::set('user_name', $userResult['name']);
            Session::set('user_avatar', $userResult['avatar']);
            return json(['status' => 1, 'msg' => '登录成功']);
        } else {
            return json(['status' => -1, 'msg' => '账号或密码错误，请重新登录']);
        }
    }

    // 处理用户注册
    public function handleUserRegister()
    {
        $this->validate = new UserValidate;

        if (!Request::isAjax()) {
            $this->error('请求类型错误');
        }
        // 获取数据
        $this->userInfo = Request::param();
        $rs = $this->validate->check($this->userInfo);
        // 验证数据
        if (!$rs) {
            return ['status' => -1, 'msg' => $this->validate->getError() ];
        }

        // 保存图片
        $this->saveImg('avatar');

        if ($userInfo = UserModel::create($this->userInfo)) {
            $result = UserModel::get($userInfo->id);
            UserModel::where('id', '=', $result->id)->update(['is_login'=>1]);
            Session::set('user_id', $result->id);
            Session::set('user_name', $result->name);
            Session::set('user_avatar', $result->avatar);
            return ['status'=>1, 'msg'=>'用户注册成功'];
        } else {
            return ['status'=>-1, 'msg'=>'用户注册失败，请重试'];
        }
    }

    public function handleResetPsw()
    {
        if (!Request::isAjax()) {
            $this->error('请求类型错误');
        }
        // 自定义验证规则
        $rule = [
            'mobile|手机' => [
                'require' => 'require',
                'mobile'   => 'mobile',
            ],
            'password|密码' => [
                'require'  => 'require',
                'length'    => '6, 20',
                'alphaDash' => 'alphaDash',
            ],
            'captcha|验证码' => 'require|captcha',
            'telCheckNum|手机验证码' => 'require'
        ];
        // 获取数据
        $this->userInfo = Request::param();
        // 验证
        $res = $this->validate($this->userInfo, $rule);
        if (true !== $res) {
            return json(['status' => -1, 'msg' => $res]);
        }
        if ($this->mobile_code != $this->userInfo['telCheckNum']) {
            return json(['code' => -1, 'msg' => '手机验证码错误']);
        }
        $res = UserModel::where('mobile', '=', $this->userInfo['mobile'])->update($this->userInfo);
        if ($res) {
            return json(['status' => 1, 'msg' => '修改密码成功']);
        } else {
            return json(['status' => -1, 'msg' => '修改密码失败，请重试']);
        }
    }

    // 接受用户手机号
    public function getMobile()
    {
        // 短信接口
        $target = "http://106.ihuyi.com/webservice/sms.php?method=Submit";
        // apiid
        $account = "C09233800";
        // apikey
        $apikey = "56c2b561012cb98acfbb0b747ddc734a";
        if (!Request::isAjax()) {
            return json(['status' => -1, 'msg' => '请求类型错误']);
        }

        $data = Request::param();
        // 登录验证
        $rule = [
            'mobile|手机号码' => [
                'require'  => 'require',
                'mobile'   => 'mobile',
            ],
            'captcha|验证码' => 'require|captcha'
        ];
        $res = $this->validate($data, $rule);
        if (true !== $res) {
            return json(['status' => -1, 'msg' => $res]);
        }
        // 生成随机数
        $this->mobile_code = $this->random(4,1);
        $post_data = "account=".$account."&password=".$apikey."&mobile=".
            $data['mobile'].
            "&content=".rawurlencode("您的验证码是：".$this->mobile_code."。请不要把验证码泄露给其他人。");
        $gets = $this->xml_to_array($this->Post($post_data, $target));
        if ($gets['SubmitResult']['code'] == 2) {
            return json(['status' => 1, 'msg' => '发送成功，请通过手机查收']);
        }
        return json(['status' => 0, 'msg' => '发送失败，请重试']);
    }

    //请求数据到短信接口，检查环境是否 开启 curl init。
    private function Post($curlPost,$url)
    {
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_HEADER, false);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_NOBODY, true);
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $curlPost);
        $return_str = curl_exec($curl);
        curl_close($curl);
        return $return_str;
    }

    //将 xml数据转换为数组格式。
    private function xml_to_array($xml)
    {
        $reg = "/<(\w+)[^>]*>([\\x00-\\xFF]*)<\\/\\1>/";
        if(preg_match_all($reg, $xml, $matches)){
            $count = count($matches[0]);
            for($i = 0; $i < $count; $i++){
                $subxml= $matches[2][$i];
                $key = $matches[1][$i];
                if(preg_match( $reg, $subxml )){
                    $arr[$key] = $this->xml_to_array( $subxml );
                }else{
                    $arr[$key] = $subxml;
                }
            }
        }
        return $arr;
    }

    //random() 函数返回随机整数。
    private function random($length = 6, $numeric = 0)
    {
        PHP_VERSION < '4.2.0' && mt_srand((double)microtime() * 1000000);
        if($numeric) {
            $hash = sprintf('%0'.$length.'d', mt_rand(0, pow(10, $length) - 1));
        } else {
            $hash = '';
            $chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789abcdefghjkmnpqrstuvwxyz';
            $max = strlen($chars) - 1;
            for($i = 0; $i < $length; $i++) {
                $hash .= $chars[mt_rand(0, $max)];
            }
        }
        return $hash;
    }

    // 退出登录
    public function logout()
    {
        $id = Session::get('user_id');
        UserModel::where('id', '=', $id)->update(['is_login'=>0]);
        Session::clear();
        $this->success('退出成功', 'index/index');
    }


    // 我的资料管理
    public function goMyInfoMange()
    {
        $this->isUserLogined();
        $id = Session::get('user_id');
        $this->is_black_user($id);
        $userInfo = UserModel::where([
            ['id', '=', $id],
            ['status', '=', 1]
        ])->find();
        if (!is_null($userInfo)) {
            $this->view->assign('userInfo', $userInfo);
        }
        return $this->view->fetch('myInfoMange', ['title' => '个人资料']);
    }

    // 设置个人密码
    public function goResetPsw()
    {
        return $this->view->fetch('resetPsw', ['title' => '修改密码']);
    }

    // 用户登录
    public function login()
    {
        return $this->view->fetch('login', ['title' => '用户登录']);
    }
}