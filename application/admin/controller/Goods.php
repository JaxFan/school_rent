<?php
/**
 * Created by PhpStorm.
 * User: 65100
 * Date: 2018/11/18
 * Time: 9:23
 */

namespace app\admin\controller;

use app\admin\common\controller\Base;
use think\facade\Request;
use app\common\model\GoodsType as GoodsTypeModel;
use app\common\validate\GoodsType as GoodsTypeValidate;
use app\common\model\Goods as GoodsModel;
use app\common\validate\Goods as GoodsValidate;
use app\common\model\User;
use think\Image;

class Goods extends Base
{
    private $validate = null;

    // 物品信息
    private $goodsInfo = [];

    private function saveImg($img, $w=500, $h=500) {
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
                $this->goodsInfo[$img] = $info->getSaveName();
            } else {
                return ['status'=>-1, 'msg'=>$imgInfo->getError()];
            }
        }
    }

    // 渲染增加物品分类页面
    public function goAddGoodsTypePage()
    {
        $this->isAdminLogined();
        return $this->view->fetch('addGoodsType', ['title'=>'校园易租后台管理系统-添加物品分类']);
    }

    // 增加物品分类
    public function addGoodsType()
    {
        $this->validate = new GoodsTypeValidate;

        if (!Request::isAjax()) {
            return ['status' => -1, 'msg' => '请求类型错误'];
        }
        // 获取数据
        $data = Request::param();

        // 验证数据
        $rs = $this->validate->check($data);
        if (!$rs) {
            return ['status' => -1, 'msg' => $this->validate->getError() ];
        }

        // 增加数据
        if (GoodsTypeModel::create($data)) {
            return ['status' => 1, 'msg' => '添加物品种类成功'];
        } else {
            return ['status' => 0, 'msg' => '添加物品种类失败'];
        }
    }

    // 渲染种类列表界面
    public function goGoodsTypeListPage()
    {
        // 全局查询条件
        $map = [];

        $this->isAdminLogined();

        // 从前端获取信息
        $keywords = Request::param('keywords');
        $status = Request::param('status');

        // 封装查询条件
        if (!empty($keywords)) {
            $map[] = ['name', 'like', '%'.$keywords.'%'];
        }
        if ('' != $status) {
            $map[] = ['status', '=', $status];
        }
        // 获取数据
        $typeList = GoodsTypeModel::where($map)->order('create_time', 'desc')->paginate(10);
        // 模板赋值
        $this->view->assign('title', '物品分类列表');
        $this->view->assign('typeList', $typeList);
        $this->view->assign('keywords', $keywords);
        $this->view->assign('status', $status);
        $this->view->assign('empty', "<h3 class='text-center text-danger'>没有物品数据</h3>");
        // 渲染模板
        return $this->view->fetch('goodsTypeList', ['title'=>'-物品分类列表']);
    }

    // 修改物品类型状态
    public function saveGoodsType()
    {
        $this->validate = new GoodsTypeValidate;

        if (!Request::isAjax()) {
            return ['status' => -1, 'msg' => '请求类型错误'];
        }

        $data = Request::param();
        // 验证数据
        $rs = $this->validate->check($data);
        if (!$rs) {
            return ['status' => -1, 'msg' => $this->validate->getError() ];
        }
        unset($data['captcha']);

        $res = GoodsTypeModel::where('id', '=', $data['id'])->update($data);
        if ($res) {
            return ['status' => 1, 'msg' => '更新物品种类成功'];
        } else {
            return ['status' => -1, 'msg' => '更新物品种类失败'];
        }
    }

    // 彻底删除栏目
    public function deleteGoodsTrue()
    {
        if (!Request::isAjax()) {
            return ['status'=>-1, 'msg'=>'请求类型错误'];
        }
        // 从前台获取数据
        $data = Request::param();
        // 自定义验证规则
        $rule = [
            'password|密码' => [
                'require'  => 'require',
                'length'    => '6, 20',
                'alphaDash' => 'alphaDash',
            ],
            'captcha|验证码' => 'require|captcha'
        ];
        $res = $this->validate($data, $rule); // 验证
        if (true !== $res) {
            return json(['status' => -1, 'msg' => $res]);
        }
        // 查询条件
        $map[] = ['id', '=', $data['adminId']];
        $map[] = ['password', '=', sha1($data['password'])];
        $userResult = User::where($map)->find();
        if (is_null($userResult)) {
            return ['status'=>-1, 'msg'=>'密码错误'];
        }
        $res = GoodsTypeModel::where('id', '=', $data['typeId'])->delete();
        if (1 == $res) {
            return ['status'=>1, 'msg'=>'删除成功'];
        } else {
            return ['status'=>-1, 'msg'=>'删除失败'];
        }
    }

    // 渲染增加物品界面
    public function goAddGoods()
    {
        $this->isAdminLogined();
        // 获取数据
        $goodsTypeList = GoodsTypeModel::where('status', '=', 1)->field('id, name')->select();
        // 模板赋值
        $this->view->assign('title', '增加物品');
        if (count($goodsTypeList) > 0) {
            // 将查询到的栏目信息赋值给模板
            $this->assign('goodsTypeList', $goodsTypeList);
        } else {
            $this->error('请先添加栏目', 'goods/goGoodsTypeListPage');
        }
        // 渲染模板
        return $this->view->fetch('addGoods', ['title'=>'增加物品']);
    }

    // 处理物品增加与修改
    public function saveGoods() {
        // 初始化数据
        $this->validate = new GoodsValidate;

        if (!Request::isAjax()) {
            return ['status'=>-1, 'msg'=>'请求类型错误'];
        }

        $this->goodsInfo = Request::param();
        // 获取数据并保存图片
        $this->saveImg('image', 450, 300);

        $rs = $this->validate->check($this->goodsInfo);

        // 验证数据
        if (!$rs) {
            return ['status' => -1, 'msg' => $this->validate->getError() ];
        }
        // 验证数据
        if (isset($this->goodsInfo['id']) && '' != $this->goodsInfo['id']) {
            $res = GoodsModel::where('id', '=', $this->goodsInfo['id'])->update($this->goodsInfo);
            if ($res) {
                return ['status'=>1, 'msg'=>'修改物品信息成功'];
            } else {
                return ['status'=>-1, 'msg'=>'修改物品信息失败'];
            }
        } else {
            if (GoodsModel::create($this->goodsInfo)) {
                return ['status'=>1, 'msg'=>'发布物品信息成功'];
            } else {
                return ['status'=>-1, 'msg'=>'发布物品信息失败'];
            }
        }
    }

    // 物品列表
    public function goodsList()
    {
        // 全局查询条件
        $map = [];

        $this->isAdminLogined();

        // 从前端获取信息
        $keywords = Request::param('keywords');
        $typeId = Request::param('type_id');

        // 封装查询条件
        if (!empty($keywords)) {
            $map[] = ['name', 'like', '%'.$keywords.'%'];
        }
        if ('' != $typeId) {
            $map[] = ['type_id', '=', $typeId];
        }
        // 获取数据
        // 栏目数据
        $typeList = GoodsTypeModel::where('status', '=', 1)->field('id, name')->select();
        $goodsList = GoodsModel::where($map)->order('create_time', 'desc')->paginate(10);
        // 模板赋值
        $this->view->assign('title', '物品列表');
        $this->view->assign('goodsList', $goodsList);
        $this->view->assign('typeList', $typeList);
        $this->view->assign('keywords', $keywords);
        $this->view->assign('typeId', $typeId);
        $this->view->assign('empty', "<h3 class='text-center text-danger'>没有物品数据</h3>");
        return $this->view->fetch('goodsList', ['title' => '物品列表']);
    }

    /*
    * 删除物品
    * 假删除 即把文章状态设为0,即隐藏
    */
    public function deleteGoods()
    {
        if (!Request::isAjax()) {
            return ['status'=>-1, 'msg'=>'请求类型错误'];
        }
        $id = Request::param('goodsId');
        $res = GoodsModel::where('id', '=', $id)->update(['status'=>0]);
        if (1 == $res) {
            return ['status'=>1, 'msg'=>'禁用物品成功'];
        } else {
            return ['status'=>-1, 'msg'=>'禁用物品失败'];
        }
    }
    /*
     * 删除栏目
     * 真删除 即把文章从数据库中删除
     */
    public function deleteAuthorTrue()
    {
        if (!Request::isAjax()) {
            return ['status'=>-1, 'msg'=>'请求类型错误'];
        }
        // 从前台获取数据
        $data = Request::param();
        // 查询条件
        $map[] = ['id', '=', $data['adminId']];
        $map[] = ['password', '=', sha1($data['adminPsw'])];
        $userResult = UserModel::where($map)->find();
        if (is_null($userResult)) {
            return ['status'=>-1, 'msg'=>'密码错误'];
        }
        $res = GoodsModel::where('id', '=', $data['goodsId'])->delete();
        if (1 == $res) {
            return ['status'=>1, 'msg'=>'已彻底删除该商品'];
        } else {
            return ['status'=>-1, 'msg'=>'删除商品失败'];
        }
    }

    // 撤销禁用
    public function recoverGoods() {
        if (!Request::isAjax()) {
            return ['status'=>-1, 'msg'=>'请求类型错误'];
        }
        $id = Request::param('goodsId');
        $res = GoodsModel::where('id', '=', $id)->update(['status'=>1]);
        if (1 == $res) {
            return ['status'=>1, 'msg'=>'该物品已经恢复正常'];
        } else {
            return ['status'=>-1, 'msg'=>'该物品恢复正常失败'];
        }
    }
}