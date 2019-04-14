<?php
/**
 * Created by PhpStorm.
 * User: 65100
 * Date: 2018/11/19
 * Time: 19:16
 */

namespace app\admin\controller;

use app\admin\common\controller\Base;
use think\facade\Request;
use app\common\Model\User as UserModel;
use app\common\model\Notice as NoticeModel;
use app\common\validate\Notice as NoticeValidate;

class Notice extends Base
{
    // 初始化验证器
    private $validate = null;
    // 获得公告列表
    public function getNoticeList()
    {
        $this->isAdminLogined();
        // 全局查询条件
        $map = [];

        // 从前端获取信息
        $keywords = Request::param('keywords');
        $status = Request::param('status');
        $isDraft = Request::param('is_draft');

        // 封装查询条件
        if ('' != $keywords) {
            $map[] = ['name', 'like', '%'.$keywords.'%'];
        }
        if ('' != $status) {
            $map[] = ['status', '=', $status];
        }
        if ('' != $isDraft) {
            $map[] = ['is_draft', '=', $isDraft];
        }
        // 获取数据
        $noticeList = NoticeModel::where($map)->order('create_time', 'desc')->paginate(10);
        // 模板赋值
        $this->view->assign('title', '物品分类列表');
        $this->view->assign('noticeList', $noticeList);
        $this->view->assign('keywords', $keywords);
        $this->view->assign('status', $status);
        $this->view->assign('is_draft', $isDraft);
        $this->view->assign('empty', "<h3 class='text-center text-danger'>没有用户数据</h3>");
        return $this->view->fetch('noticeList', ['title' => '公告列表']);
    }

    // 渲染发布公告界面
    public function editNotice()
    {
        $this->isAdminLogined();
        return $this->view->fetch('editNotice', ['title' => '发布公告']);
    }

    // 处理公告增加与修改
    public function saveNotice() {
        $this->isAdminLogined();
        // 初始化数据
        $this->validate = new NoticeValidate;

        if (!Request::isAjax()) {
            return ['status'=>-1, 'msg'=>'请求类型错误'];
        }

        $noticeInfo = Request::param();

        $rs = $this->validate->check($noticeInfo);

        // 验证数据
        if (!$rs) {
            return ['status' => -1, 'msg' => $this->validate->getError() ];
        }
        // 验证数据
        if (isset($noticeInfo['id']) && '' != $noticeInfo['id']) {
            $res = NoticeModel::where('id', '=', $noticeInfo['id'])->update($noticeInfo);
            if ($res) {
                return ['status'=>1, 'msg'=>'修改公告成功'];
            } else {
                return ['status'=>-1, 'msg'=>'修改公告失败'];
            }
        } else {
            if (NoticeModel::create($noticeInfo)) {
                return ['status'=>1, 'msg'=>'发布公告成功'];
            } else {
                return ['status'=>-1, 'msg'=>'发布公告失败'];
            }
        }
    }

    // 恢复公告
    public function recoverNotice()
    {
        $this->isAdminLogined();

        if (!Request::isAjax()) {
            return ['status'=>-1, 'msg'=>'请求类型错误'];
        }

        $data = Request::param();
        if (!isset($data['id']) || '' == $data['id']) {
            return ['status'=>-1, 'msg'=>'请先传入公告id'];
        }
        if (0 == $data['status']) { // $id 0
            $showCount = NoticeModel::where('status', '=', 1)->count();
            if ($showCount < 1) {
                $res = NoticeModel::where('id', '=', $data['id'])->update(['status' => 1, 'is_draft' => 0]);
                if ($res) {
                    return ['status'=>1, 'msg'=>'该公告已启用'];
                } else {
                    return ['status'=>-1, 'msg'=>'公告启用过程中出错'];
                }
            } else {
                return ['status'=>-1, 'msg'=>'已经有启用的公告了，请先将启用的公告禁用'];
            }
        } elseif (1 == $data['status']) { // $id 1 禁用
            $res = NoticeModel::where('id', '=', $data['id'])->update(['status' => 0]);
            if ($res) {
                return ['status'=>1, 'msg'=>'该公告已禁用'];
            } else {
                return ['status'=>-1, 'msg'=>'公告禁用过程中出错'];
            }
        }
    }

    // 公告删除
    public function deleNotTrue()
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
        $res = NoticeModel::where('id', '=', $data['id'])->delete();
        if (1 == $res) {
            return ['status'=>1, 'msg'=>'已经删除公告'];
        } else {
            return ['status'=>-1, 'msg'=>'删除公告失败'];
        }
    }

}