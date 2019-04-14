<?php
/**
 * Created by PhpStorm.
 * User: 65100
 * Date: 2018/11/22
 * Time: 9:42
 */

namespace app\index\controller;

use app\index\common\controller\Base;
use app\common\model\Deal as DealModel;
use think\facade\Session;
use think\facade\Request;
use app\common\model\Feedback as FeedbackModel;
use app\common\validate\Feedback as FeedbackValidate;
use app\common\model\Comment as CommentModel;
use app\common\validate\Comment as CommentValidate;
use app\common\model\GoodsType as GoodsTypeModel;
use app\common\model\Goods as GoodsModel;
use app\common\model\GoodsSave as GoodsSaveModel;

class Trade extends Base
{
    private $validate = null;
    // 用户个人交易管理
    public function goTradeList()
    {
        // 全局查询条件
        $map = [];

        $this->isUserLogined();
        // 从前端获取信息
        $status = Request::param('status');

        $userId = Session::get('user_id');
        $this->is_black_user($userId);
        if ('' != $status) {
            $map[] = ['status', '=', $status];
        }
        $map[] = ['lend_id', '=', $userId];
        // 获取数据
        $dealList = DealModel::where($map)->order('create_time', 'desc')->paginate(10);
        // 模板赋值
        $this->view->assign('dealList', $dealList);
        $this->view->assign('status', $status);
        $this->view->assign('empty', "<h3 class='text-center text-danger'>没有用户数据</h3>");

        return $this->view->fetch('tradeList', ['title' => '交易列表']);
    }

    // 用户个人交易评论
    public function goCommentList()
    {
        $this->isUserLogined();
        $userId = Session::get('user_id');
        $this->is_black_user($userId);
        $map[] = ['user_id', '=', $userId];
        $map[] = ['status', '=', 1];
        $commentList = CommentModel::where($map)->order('create_time', 'desc')->paginate(10);
        // 模板赋值
        $this->view->assign('commentList', $commentList);
        $this->view->assign('empty', "<h3 class='text-center text-danger'>没有评论数据</h3>");
        return $this->view->fetch('commentList', ['title' => '评论管理']);
    }

    // 删除个人评论
    public function delComm()
    {
        $this->isUserLogined();

        $commInfo = Request::param();

        // 验证数据
        if (isset($commInfo['id']) && '' != $commInfo['id']) {
            $res = CommentModel::where('id', '=', $commInfo['id'])->update(['status'=>'0']);
            if ($res) {
                return ['status'=>1, 'msg'=>'删除评论成功'];
            } else {
                return ['status'=>-1, 'msg'=>'删除评论失败'];
            }
        }
    }

    // 发布物品
    public function goAddGoods()
    {
        $this->isUserLogined();
        // 获取数据
        $goodsTypeList = GoodsTypeModel::where('status', '=', 1)->field('id, name')->select();
        // 模板赋值
        if (count($goodsTypeList) > 0) {
            // 将查询到的栏目信息赋值给模板
            $this->assign('goodsTypeList', $goodsTypeList);
        } else {
            $this->error('管理员还未添加物品分类', 'trade/goTradeList');
        }
        return $this->view->fetch('addGoods', ['title' => '发布物品']);
    }

    // 我的物品列表
    public function goGoodsList()
    {
        // 全局查询条件
        $map = [];

        $this->isUserLogined();
        $userId = Session::get('user_id');
        $this->is_black_user($userId);
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
        $map[] = ['user_id', '=', $userId];
        // 获取数据
        // 栏目数据
        $typeList = GoodsTypeModel::where('status', '=', 1)->field('id, name')->select();
        $goodsList = GoodsModel::where($map)->order('create_time', 'desc')->paginate(10);
        // 模板赋值
        $this->view->assign('title', '校园易租后台管理系统-物品列表');
        $this->view->assign('goodsList', $goodsList);
        $this->view->assign('typeList', $typeList);
        $this->view->assign('keywords', $keywords);
        $this->view->assign('typeId', $typeId);
        $this->view->assign('empty', "<h3 class='text-center text-danger'>没有用户数据</h3>");
        return $this->view->fetch('goodsList', ['title' => '我的物品列表']);
    }


    // 我的收藏物品列表
    public function goSaveList()
    {
        // 全局查询条件
        $map = [];
        $this->isUserLogined();
        $userId = Session::get('user_id');
        $this->is_black_user($userId);
        // 封装查询条件
        if (!empty($keywords)) {
            $map[] = ['name', 'like', '%'.$keywords.'%'];
        }
        $map[] = ['lend_id', '=', $userId];
        $saveList = GoodsSaveModel::where($map)->order('create_time', 'desc')->paginate(10);
        $this->view->assign('saveList', $saveList);
        $this->view->assign('empty', "<h3 class='text-center text-danger'>没有用户数据</h3>");
        return $this->view->fetch('saveList', ['title' => '我的收藏列表']);
    }

    // 删除收藏
    public function deleSave()
    {
        $this->isUserLogined();

        $id = Request::param(id);

        // 验证数据
        if (isset($id) && '' != $id) {
            $res = GoodsSaveModel::where('id', '=', $id)->update(['status'=>'0']);
            if ($res) {
                return ['status'=>1, 'msg'=>'删除收藏成功'];
            } else {
                return ['status'=>-1, 'msg'=>'删除收藏失败'];
            }
        }
    }

    // 处理用户反馈
    public function handleFeedback()
    {
        $this->validate = new FeedbackValidate();
        $data = Request::param();
        $res = $this->validate->check($data);
        // 验证数据
        if (!$res) {
            return ['status' => -1, 'msg' => $this->validate->getError() ];
        }
        if(FeedbackModel::create($data)) {
            return ['status' => 1, 'msg'=>'反馈成功'];
        } else {
            return ['status' => -1, 'msg'=>'反馈失败，请重试'];
        }
    }

    // 处理用户评论
    public function handleComment()
    {
        $this->is_comment();
        $this->validate = new CommentValidate();
        $data = Request::param();
        $res = $this->validate->check($data);
        // 验证数据
        if (!$res) {
            return ['status' => -1, 'msg' => $this->validate->getError() ];
        }
        if(CommentModel::create($data)) {
            return ['status' => 1, 'msg'=>'评论成功'];
        } else {
            return ['status' => -1, 'msg'=>'评论失败，请重试'];
        }
    }

    // 签收
    public function sureGet()
    {
        $id = Request::param('id');
        $res = DealModel::where('id', '=', $id)->update(['status'=>1]);
        if ($res) {
            return ['status' => 1, 'msg'=>'已成功签收'];
        } else {
            return ['status' => -1, 'msg'=>'签收失败，请重试'];
        }
    }
}