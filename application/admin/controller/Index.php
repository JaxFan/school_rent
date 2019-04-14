<?php
/**
 * Created by PhpStorm.
 * User: 65100
 * Date: 2018/11/6
 * Time: 19:59
 */

namespace app\admin\controller;

use app\admin\common\controller\Base;
use app\common\model\Goods as GoodsModel;
use app\common\model\User as UserModel;
use app\common\model\Deal as DealModel;
use app\common\model\Site as SiteModel;
use app\common\model\Notice as NoticeModel;
use app\common\model\Feedback as FeedbackModel;
use app\common\model\GoodsType as GoodsTypeModel;
use think\facade\Request;
use think\facade\Session;

class Index extends Base
{
    // 渲染后台主页
    public function index()
    {
        $this->isAdminLogined();
        $dealNum = $this->getDealNum();
        $visitNum = $this->getVisitNum();
        $regNum = $this->getRegNum();
        $loginNum = $this->getLoginNum();
        $goodsList = $this->getHotGoods();
        $notice = $this->getNotice();
        $userFeedbackList = $this->getUserFeedback();
        $siteStatus = $this->getSiteStatus();
        $this->view->assign('dealNum', $dealNum);
        $this->view->assign('visitNum', $visitNum);
        $this->view->assign('regNum', $regNum);
        $this->view->assign('loginNum', $loginNum);
        $this->view->assign('notice', $notice);
        $this->view->assign('siteStatus', $siteStatus);
        $this->view->assign('userFeedbackList', $userFeedbackList);

        $this->view->assign('goodsList', $goodsList);
        return $this->view->fetch('index', ['title' => '校园易租后台管理系统']);
    }

    // 获得成交量
    private function getDealNum()
    {
        return DealModel::where('status','=', 1)->count();
    }

    // 获得访问量
    private function getVisitNum()
    {
        $res = SiteModel::field('visit_num')->where('id','=', 1)->find();
        return $res->visit_num;
    }

    // 获得注册量
    private function getRegNum()
    {
        return UserModel::count();
    }

    // 获得登录人数
    private function getLoginNum()
    {
        return UserModel::where('is_login', '=', 1)->count();
    }

    // 获得用户反馈
    private function getUserFeedback()
    {
        return FeedbackModel::field('content, create_person, create_time')->where('status', '=', 1)->select();
    }

    // 获得最热物品
    private function getHotGoods()
    {
        $goodsList = GoodsModel::where('status', '=', 1)
            ->order('pv', 'desc')
            ->limit(5)
            ->select();
        return $goodsList;
    }

    // 获得公告
    private function getNotice()
    {
        $res = NoticeModel::field('name, content')->where('status','=', 1)->find();
        return $res;
    }

    // 获得类型
    public function getGoodsType()
    {
        $typeList = GoodsTypeModel::field('id, name')->where('status','=', 1)->select();
        return $typeList;
    }
    // 获得商品种类及其对应数量
    public function getGoodsTypeNum()
    {
        $goodsTypeNumList = [];
        $res = $this->getGoodsType();
        for ($i = 0; $i < count($res); $i++) {
            $num = GoodsModel::where([
                ['status','=', 1],
                ['type_id', '=', $res[$i]['id']]
            ])->count();
            array_push($goodsTypeNumList, ['name'=>$res[$i]['name'], 'value'=>$num]);
        }

        return ['list' => $goodsTypeNumList];
    }

    // 获得网站状态
    private function getSiteStatus()
    {
        return SiteModel::where('id', '=', 1)->find();
    }
    // 锁屏
    public function lackScreen()
    {
        $lockScreen = Request::param('lockScreen');
        if (isset($lockScreen) && 1 == $lockScreen) {
            Session::set('is_lock_screen', 1);
            return ['status'=>1, 'msg'=>'已开始锁屏'];
        } else {
            return ['status'=>-1, 'msg'=>'锁屏失败，请重试'];
        }
    }

    // 获得屏幕状态
    public function getScreenStatus()
    {
        if (Session::has('is_lock_screen')) {
            return ['status'=>1, 'msg'=>'锁屏状态'];
        } else {
            return ['status'=>0, 'msg'=>'无锁屏状态'];
        }
    }

    // 清除锁屏
    public function clearLackScreen()
    {
        if (!Request::isAjax()) {
            return ['status'=>-1, 'msg'=>'请求类型错误'];
        }
        // 从前台获取数据
        $data = Request::param();
        // 自定义验证规则
        $rule = [
            'password|密码' => [
                'require'  => 'require'
            ]
        ];
        $res = $this->validate($data, $rule);
        if (true !== $res) {
            return json(['status' => -1, 'msg' => $res]);
        }
        // 查询条件
        $map[] = ['id', '=', $data['adminId']];
        $map[] = ['password', '=', sha1($data['password'])];
        $userResult = UserModel::where($map)->find();
        if (is_null($userResult)) {
            return ['status'=>-1, 'msg'=>'密码错误'];
        }
        if (0 == $data['lockScreen']) {
            Session::delete('is_lock_screen');
            return ['status'=>1, 'msg'=>'已解除锁屏状态'];
        } else {
            return ['status'=>-1, 'msg'=>'解除锁屏状态失败'];
        }
    }
}
