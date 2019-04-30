<?php
namespace app\index\controller;

use app\index\common\controller\Base;
use app\common\model\GoodsType;
use app\common\model\Goods;
use think\facade\Request;
use app\common\validate\Deal as DealValidate;
use app\common\model\Deal as DealModel;
use app\common\model\GoodsSave as GoodsSaveModel;
use app\common\model\Comment as CommentModel;
use app\common\validate\GoodsSave as GoodsSaveValidate;
use app\common\model\Site as SiteModel;
use app\common\model\Notice as NoticeModel;
use think\facade\Session;



//----------------获取数据库数据方法：在控制器中使用模型/Goods 的 get()方法---------------------
class Index extends Base
{
    // 验证器
    private $validate = null;

    public function index()
    {
        $goodsAllArray = [];

//        数据库查询各式
        $typeList = GoodsType::field('id, name')->where('status', '=', 1)->select();
        for ($i = 0; $i < count($typeList); $i++) {
            $result = Goods::field('id, name, score, image, user_id, price')
                ->where([
                    ['status', '=', 1],
//                    type_id？？
                    ['type_id', '=', $typeList[$i]['id']],
                ])
                ->order('pv', 'DESC')
                ->limit(8)
                ->select();
            $goodsItemArray = [
                'type_id' => $typeList[$i]['id'],
                'goods_list' => $result
            ];
//            def:array_unshift($param,$arr) $param will be show in the first index in arr[]
            array_unshift($goodsAllArray, $goodsItemArray);
        }
//        站点访问数增加？
        SiteModel::where('id', '=', '1')->setInc('visit_num');
//        为什么assign？
        $this->view->assign('typeList', $typeList);
        $this->view->assign('goodsAllArray', $goodsAllArray);
        $this->view->assign('empty', "<h3 class='text-center text-danger'>没有数据</h3>");
        return $this->view->fetch('index', ['title' => '首页']);
    }

    // 种类对应的详细物品列表
    public function typeList()
    {
//        why init like this?
        $map[] = ['status', '=', 1];
//        $data?
        $data = Request::param();
//        isset自身表示变量存在且不空时，返回true,写这个是否多余了呢？
//        id和 关键字只要一个不为空，都能查出
        if(isset($data['keywords']) && '' != $data['keywords']) {
            $map[] = ['name', 'like', '%'.$data['keywords'].'%'];
            $goodsList = Goods::field('id, name, score, image, user_id, price')
                ->where($map)
                ->order('pv', 'DESC')
                ->limit(9)
                ->select();
        } elseif (isset($data['id']) && '' != $data['id']) {
            $map[] = ['type_id', '=', $data['id']];
            $goodsList = Goods::field('id, name, score, image, user_id, price')
                ->where($map)
                ->order('pv', 'DESC')
                ->limit(9)
                ->select();
        }
        $typeList = GoodsType::field('id, name')->where('status', '=', 1)->select();
        $this->view->assign('typeList', $typeList);
        $this->view->assign('goodsList', $goodsList);
//        goodList在这里的作用怎么体现出来？
        return $this->view->fetch('typeList', ['title'=>'物品列表']);
    }

    // 物品详情
    public function goodsDetail()
    {
        $id = Request::param('id');
        $level = Request::param('level');
        if (!isset($level)) {
            $level = -1;
        }
        if (!isset($id) || '' == $id) {
            $this->error('请求类型错误', 'index/index');
        }
//        why?
        $goodsInfo = Goods::get(function ($query) use ($id) {
            $query->where([
                    ['id', '=', $id],
                    ['status', '=', 1]
                ])->setInc('pv', 1);
        });
        $scoreAndNum = $this->getScoreAndNum($id);
        $commentList = $this->getCommentNum($level, $id);
        $typeList = GoodsType::field('id, name')->where('status', '=', 1)->select();
//此处的assign,特别是empty这个，作用过程没看懂
        $this->view->assign('empty', "<div class='text-danger'>还没有评论信息</div>");
        $this->view->assign('scoreAndNum', $scoreAndNum);
        $this->view->assign('typeList', $typeList);
        $this->view->assign('commentList', $commentList);
        $this->view->assign('goodsInfo', $goodsInfo);
        return $this->view->fetch('goodsDetail', ['title' => '物品列表']);
    }

    // 获取物品平均分和人数
    private function getScoreAndNum($id)
    {
        $averageScore = CommentModel::where([
            ['goods_id', '=', $id],
            ['status', '=', 1]
        ])->avg('comment_level');
        $scoreCount = CommentModel::where([
            ['goods_id', '=', $id],
            ['status', '=', 1]
        ])->count();
        return ['score' => $averageScore, 'count' => $scoreCount];
    }

    // 获取评论数
    public function getCommentNum($level, $id)
    {
//        ==-1是啥意思？没有评论数？
        if ($level == -1) {
            $commentList = CommentModel::field('user_id, comment_level, content')
            ->where([
                ['goods_id', '=', $id],
                ['status', '=', 1]
            ])->select();

        } else {
            $commentList = CommentModel::field('user_id, comment_level, content')
            ->where([
                ['goods_id', '=', $id],
                ['comment_level', '=', $level],
                ['status', '=', 1]
            ])->select();
        }
        return $commentList;
    }

    // 获得公告
    public function getNotice()
    {
//        has:判断是否已被赋值
        if (Session::has('is_first_visit')) { // 不是第一次访问
            return json(['status' => -1]);
//            返回这个干啥？
        }
        // 第一次访问
        Session::set('is_first_visit', 1);
        $notice = NoticeModel::field('name, content')->where('status', '=', 1)->find();
        if (!is_null($notice)) {
//            查询到有时，返回查询结果
            return json(['status' => 1, 'notice' => $notice]);
        }
    }

    // 物品收藏
    public function saveGoods()
    {
//        这是啥写法？
//        1.设置验证规则

        $this->validate = new GoodsSaveValidate();
        $this->isUserLogined();
        $data = Request::param();

        if (Session::has('user_id')) {
            $userId = Session::get('user_id'); // 获取用户id
        }
        $rs = $this->validate->check($data);
        // 验证数据
        if (!$rs) {
            return json(['status' => -1, 'msg' => $this->validate->getError()]);
        }
//        物品为自己上架的
        if ($userId == $data['rent_id']) {
            return json(['status' => -1, 'msg' => '你不能收藏自己的物品哦']);
        }

        $res = GoodsSaveModel::field('goods_id')->where('goods_id', '=', $data['goods_id'])->find();
        if ($res['goods_id']) {
            return json(['status' => -1, 'msg' => '你已经收藏这件物品啦']);
        }
//        黑名单内：
        $this->is_black_user($data['lend_id']);

//        这里if内是什么意思呢？
//        创建收藏？
        if (GoodsSaveModel::create($data)) {
//            测试语句
//            echo("<script>console.log('".json_encode($data)."');</script>");
            return json(['status' => 1, 'msg' => '收藏这件物品成功']);
        } else {
            return json(['status' => -1, 'msg' => '收藏这件物品失败，请重试']);
        }
    }

    // 处理交易流程
    public function handleDeal()
    {
        $this->is_deal();
        $this->isUserLogined();
        $this->validate = new DealValidate();
        $data = Request::param();
        $this->is_black_user($data['lend_id']);
        $rs = $this->validate->check($data);
        if (Session::has('user_id')) {
            $userId = Session::get('user_id'); // 获取用户id
        }
        // 验证数据
        if (!$rs) {
            return ['status' => -1, 'msg' => $this->validate->getError() ];
        }
        if ($userId == $data['rent_id']) {
            return ['status' => -1, 'msg' => '你不能购买自己的物品哦'];
        }
        $res = Goods::field('lend_total')->where('id', '=', $data['goods_id'])->find();
        if ($res->lend_total < 1) {
            return ['status' => -1, 'msg' => '对不起，该物品已经被抢光了'];
        } else {
            $lendTotal = $res->lend_total - $data['buy_num'];
            $res = Goods::where('id', '=', $data['goods_id'])->update(['lend_total'=>$lendTotal]);
            if (!$res) {
                return ['status' => -1, 'msg' => '订单创建失败，请重试'];
            }
        }
        if (DealModel::create($data)) {
            return ['status' => 1, 'msg' => '订单创建成功，请注意查收物流信息'];
        } else {
            return ['status' => -1, 'msg' => '订单创建失败，请重试'];
        }
    }
}
