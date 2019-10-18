<?php
namespace app\common\service\Market;

use app\common\entity\User;
use app\common\service\Users\Identity;
use app\index\model\SiteAuth;
use think\exception\HttpException;
use think\facade\Cache;
use think\Request;

class Auth
{
    const CACHE_NAME = 'flow_box_market_%s';
    const CACHE_TTS = 1800;

    private $memberId = 0;

    private $userInfo = null;

    public function __construct()
    {
        $model = new Identity();
        $this->memberId = $model->getUserId();

        $userInfo = User::where('id', $this->memberId)->find();
        if (!$userInfo) {
            throw new HttpException('对象不存在');
        }

        $this->userInfo = $userInfo;
    }

    /**
     * @return bool
     */
    public function checkAuth()
    {
        $auth = Cache::get(self::getCacheKey());
        if ($auth) {
            return true;
        }
        return false;
    }

    /**
     * @param $password
     * @return bool
     */
    public function check($password)
    {
        $service = new \app\common\service\Users\Service();
        $result = $service->checkSafePassword($password, $this->userInfo);
        if ($result) {
            Cache::set($this->getCacheKey(), 1, self::CACHE_TTS);
            return true;
        }
        return false;
    }

    /**
     * @return string
     */
    private function getCacheKey()
    {
        return sprintf(self::CACHE_NAME, $this->memberId);
    }

    public function identity()
    {
        //判断市场交易时间
        $siteAuth = new SiteAuth();
        if (($auth = $siteAuth->checkSite()) !== true) {
            if (Request()->isAjax()) {
                response(['code' => 1, 'message' => $auth], 200, [], 'json')->send();
                exit;
            } else {
                $siteAuth->alert($auth);
            }
        } elseif (($marketAuth = SiteAuth::checkMarket()) !== true) {
            if (Request()->isAjax()) {
                response(['code' => 1, 'message' => $marketAuth], 200, [], 'json')->send();
                exit;
            } else {
                $siteAuth->alert($marketAuth);
            }

        } elseif ($this->userInfo->is_certification == User::AUTH_ERROR) {
            //判断是否实名认证
            if (Request()->isAjax()) {
                response(['code' => 1, 'message' => '请先去进行实名认证'], 200, [], 'json')->send();
                exit;
            } else {
                $siteAuth->alert('请先去进行实名认证');
            }
        } else {

//            if (!$this->checkAuth()) {
//                if (Request()->isAjax()) {
//                    response(['code' => 1, 'message' => '请先登录交易市场','toUrl' => url('member/login')], 200, [], 'json')->send();
//                    exit;
//                } else {
//                    $siteAuth->alert('请先登录交易市场', url('member/login'));
//                }
//            }
        }
    }
}