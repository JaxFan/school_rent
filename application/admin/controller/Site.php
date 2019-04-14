<?php
/**
 * Created by PhpStorm.
 * User: 65100
 * Date: 2018/11/19
 * Time: 18:22
 */

namespace app\admin\controller;

use app\admin\common\controller\Base;
use app\common\model\Site as SiteModel;
use app\common\model\User as UserModel;
use think\facade\Request;

class Site extends Base
{
    public function siteMange()
    {
        $this->isAdminLogined();
        $site = SiteModel::where('id', '=', 1)->find();
        $this->view->assign('site', $site);
        return $this->view->fetch('siteMange', ['title' => '站点管理']);
    }

    public function handleSiteMange()
    {
        if (!Request::isAjax()) {
            return json(['status'=>-1, 'msg'=>'请求类型错误']);
        }
        // 从前台获取数据
        $data = Request::param();
        // 查询条件
        $map[] = ['id', '=', $data['adminId']];
        $map[] = ['password', '=', sha1($data['adminPsw'])];
        $userResult = UserModel::where($map)->find();
        if (is_null($userResult)) {
            return json(['status'=>-1, 'msg'=>'密码错误']);
        }
        unset($data['adminId']);
        unset($data['adminPsw']);
        $res = SiteModel::where('id', '=', 1)->update($data);
        if (1 == $res) {
            return json(['status'=>1, 'msg'=>'已重新设置站点信息']);
        } else {
            return json(['status'=>-1, 'msg'=>'重新设置站点信息失败']);
        }
    }
}