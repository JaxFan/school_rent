<?php
/**
 * Created by PhpStorm.
 * User: 65100
 * Date: 2018/10/15
 * Time: 10:21
 */

namespace app\admin\controller;

use app\admin\common\controller\Base;
use think\facade\Request;
use app\common\model\User as UserModel;
use think\facade\Session;
use think\Image;
use app\common\validate\User as UserValidate;

class User extends Base
{
    // 用户信息
    private $userInfo = [];
    private $validate = null;

    private function saveImg($img, $w=50, $h=50) {
        if (!Request::file($img)) {
            return;
        }
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

    // 渲染后台登录页面
    public function renderAdminLoginPage()
    {
        return $this->view->fetch('login', ['title'=>'校园易租后台管理系统-管理员登录']);
    }

    // 管理员登录检查
    public function checkAdminLogin()
    {
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
        $res = $this->validate($data, $rule); // 验证
        if (true !== $res) {
            return json(['status' => -1, 'msg' => $res]);
        }

        // 查询条件1
        $map[] = ['password', '=', sha1($data['password'])];
        $map[] = ['mobile', '=', $data['mobile']];
        $userResult = UserModel::where($map)->find();

        if (!is_null($userResult)) {
            UserModel::where('id', '=', $userResult['id'])->update(['is_login'=>1]);
            Session::set('admin_id', $userResult['id']);
            Session::set('admin_name', $userResult['name']);
            Session::set('admin_level', $userResult['is_admin']);
            return json(['status' => 1, 'msg' => '登录成功']);
        } else {
            return json(['status' => -1, 'msg' => '账号或密码错误，请重新登录']);
        }
    }

    // 退出登录
    public function logout()
    {
        $id = Session::get('admin_id');
        UserModel::where('id', '=', $id)->update(['is_login'=>0]);
        Session::clear();
        $this->success('已成功退出管理系统', 'admin/user/renderAdminLoginPage');
    }

    // 用户列表界面
    public function getUserList()
    {
        // 全局查询条件
        $map = [];

        $this->isAdminLogined();

        // 从前端获取信息
        $keywords = Request::param('keywords');
        $type = Request::param('type');
        $status = Request::param('status');

        // 封装查询条件
        if (!empty($keywords)) {
            $map[] = ['name', 'like', '%'.$keywords.'%'];
        }
        if ('' != $type) {
            $map[] = ['type', '=', $type];
        }
        if ('' != $status) {
            $map[] = ['status', '=', $status];
        }
        // 获取数据
        $userList = UserModel::where($map)->order('create_time', 'desc')->paginate(10);
        // 模板赋值
        $this->view->assign('title', '校园易租后台管理系统-用户列表');
        $this->view->assign('userList', $userList);
        $this->view->assign('keywords', $keywords);
        $this->view->assign('type', $type);
        $this->view->assign('status', $status);
        $this->view->assign('empty', "<h3 class='text-center text-danger'>没有用户数据</h3>");
        // 渲染模板
        return $this->view->fetch('userList');
    }

    // 增加用户
    public function addUser()
    {
        $this->isAdminLogined();

        return $this->view->fetch('addUser', ['title'=>'校园易租后台管理系统-增加用户']);
    }

    // 增加用户逻辑
    public  function handleUserAdd()
    {

        $this->validate = new UserValidate;

        if (!Request::isAjax()) {
            return ['status'=>-1, 'msg'=>'请求类型错误'];
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
        if (UserModel::create($this->userInfo)) {
            return ['status'=>1, 'msg'=>'新建用户成功'];
        } else {
            return ['status'=>-1, 'msg'=>'新建用户失败'];
        }
    }
    // 删除用户
    public function deleteUser()
    {
        if (!Request::isAjax()) {
            return ['status'=>-1, 'msg'=>'请求类型错误'];
        }
        $id = Request::param('userId');
        $res = UserModel::where('id', '=', $id)->update(['status'=>0]);
        if (1 == $res) {
            return ['status'=>1, 'msg'=>'该用户已被加入黑名单'];
        } else {
            return ['status'=>-1, 'msg'=>'用户加入黑名单失败'];
        }
    }

    // 撤销删除
    public function recoverUser() {
        if (!Request::isAjax()) {
            return ['status'=>-1, 'msg'=>'请求类型错误'];
        }
        $id = Request::param('userId');
        $res = UserModel::where('id', '=', $id)->update(['status'=>1]);
        if (1 == $res) {
            return ['status'=>1, 'msg'=>'该用户已经恢复为正常用户'];
        } else {
            return ['status'=>-1, 'msg'=>'用户恢复正常用户失败'];
        }
    }
    /*
     * 删除用户
     * 真删除 即把文章从数据库中删除
     */
    public function deleteArticleTrue()
    {
        if (!Request::isAjax()) {
            return ['status'=>-1, 'msg'=>'请求类型错误'];
        }
        // 从前台获取数据
        $data = Request::param();
        // 查询条件
        $map[] = ['id', '=', $data['adminId']];
        $map[] = ['password', '=', sha1($data['adminPsw'])];
        $userResult = User::where($map)->find();
        if (is_null($userResult)) {
            return ['status'=>-1, 'msg'=>'密码错误'];
        }
        $res = UserModel::where('id', '=', $data['userId'])->delete();
        if (1 == $res) {
            return ['status'=>1, 'msg'=>'已从数据库彻底删除该用户'];
        } else {
            return ['status'=>-1, 'msg'=>'删除用户失败'];
        }
    }

    // 用户详情
    public function getUserDetail()
    {
        $this->isAdminLogined();

        // 获取数据
        $id = Request::param('id');
        $user = UserModel::where('id', '=', $id)->find();

        // 模板赋值
        $this->view->assign('title', '校园易租后台管理系统-用户列表');
        $this->view->assign('user', $user);
        $this->view->assign('empty', "<h3 class='text-center text-danger'>没有用户数据</h3>");

        // 渲染模板
        return $this->view->fetch('userDetail');
    }
}