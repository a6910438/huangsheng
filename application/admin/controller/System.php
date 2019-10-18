<?php
namespace app\admin\controller;

use app\common\entity\FrozenConfig;
use app\common\entity\IndexLog;
use app\common\entity\MoneyRate;
use app\common\entity\ReplyConfig;
use app\common\entity\User;
use app\common\entity\WalletAddressConfig;
use app\common\entity\ActiveConfig;
use app\common\entity\OvertimeConfig;
use app\common\entity\BigConfig;
use app\common\entity\SeachConfig;
use app\common\entity\ReturnConfig;
use app\common\entity\Question;
use app\common\entity\Answer;
use app\common\entity\StoreConfig;
use app\common\entity\CloseUserConfig;
use app\common\entity\DynamicConfig;
use app\common\entity\ManageUser;
use app\common\entity\SystemLog;
use app\common\entity\SystemConfig;
use app\common\entity\ExchangeHour;
use app\common\entity\SafeQuestion;
use service\LogService;


use think\Request;
use think\Db;

class System extends Admin
{
    /**
     * @power 系统设置|激活币设置
     * @rank 4
     */
    public function acticveConfig(Request $request)
    {
        $list = ActiveConfig::alias('ac')
            ->field('ac.*')
            ->order('sort')
            ->paginate(15,false,[
                'query' => $request->param()?$request->param():[],
            ]);

        return $this->render('acticveConfig',[
            'list' => $list,
        ]);
    }
    /**
     * @power 系统设置|添加激活币设置
     * @rank 4
     */
    public function addActicveConfig(Request $request)
    {
        $result = $this->validate($request->post(), 'app\admin\validate\AddActicveConfig');
        if (true !== $result) {
            return json()->data(['code' => 1, 'message' => $result]);
        }
        $query = new ActiveConfig();
        $res = $query->addNew($query,$request->post());
        if($res){
            LogService::write('系统设置', '用户添加激活币设置');
            return json()->data(['code' => 0,'message' => '添加成功']);
        }
        return json()->data(['code' => 1, 'message' => '操作失败']);
    }
    /**
     * @power 系统设置|修改激活币设置
     * @rank 4
     */
    public function editActicveConfig(Request $request)
    {
        $result = $this->validate($request->post(), 'app\admin\validate\AddActicveConfig');
        if (true !== $result) {
            return json()->data(['code' => 1, 'message' => $result]);
        }

        $query = ActiveConfig::where('id',$request->post('id'))->find();
        $res = $query->addNew($query,$request->post());
        if(is_int($res)){
            LogService::write('系统设置', '用户修改激活币设置');
            return json()->data(['code' => 0,'message' => '修改成功']);
        }
        return json()->data(['code' => 1, 'message' => '操作失败']);
    }
    /**
     * @power 系统设置|删除激活币设置
     * @rank 4
     */
    public function delActicveConfig(Request $request)
    {
        $id = $request->param('id');
        $res = ActiveConfig::where('id',$id)->delete();
        if($res){
            LogService::write('系统设置', '用户删除激活币设置');
            return json()->data(['code' => 0,'message' => '删除成功']);
        }
        return json()->data(['code' => 1, 'message' => '操作失败']);
    }
    /**
     * @power 系统设置|收款地址设置
     * @rank 4
     */
    public function address(Request $request)
    {
        $list = WalletAddressConfig::alias('wac')
            ->field('wac.*')
            ->order('create_time','desc')
            ->paginate(15,false,[
                'query' => $request->param()?$request->param():[],
            ]);
        return $this->render('address',[
            'list' => $list,
        ]);
    }
    /**
     * @power 系统设置|添加收款地址设置
     * @rank 4
     */
    public function addAddress(Request $request)
    {
        $result = $this->validate($request->post(), 'app\admin\validate\AddAddress');
        if (true !== $result) {
            return json()->data(['code' => 1, 'message' => $result]);
        }
        $query = new WalletAddressConfig();
        $res = $query->addNew($query,$request->post());
        if($res){
            LogService::write('系统设置', '用户添加收款地址设置');
            return json()->data(['code' => 0,'message' => '添加成功']);
        }
        return json()->data(['code' => 1, 'message' => '操作失败']);
    }
    /**
     * @power 系统设置|编辑收款地址设置
     * @rank 4
     */
    public function editAddress(Request $request)
    {
        $result = $this->validate($request->post(), 'app\admin\validate\AddAddress');
        if (true !== $result) {
            return json()->data(['code' => 1, 'message' => $result]);
        }
        $id = $request->post('id');
        $query = WalletAddressConfig::where('id',$id)->find();
        $res = $query->addNew($query,$request->post());
        if($res){
            LogService::write('系统设置', '用户编辑收款地址设置');
            return json()->data(['code' => 0,'message' => '修改成功']);
        }
        return json()->data(['code' => 1, 'message' => '操作失败']);
    }
    /**
     * @power 系统设置|删除激活币设置
     * @rank 4
     */
    public function delAddress(Request $request)
    {
        $id = $request->param('id');
        $res = WalletAddressConfig::where('id',$id)->delete();
        if($res){
            LogService::write('系统设置', '用户删除激活币设置');
            return json()->data(['code' => 0,'message' => '删除成功']);
        }
        return json()->data(['code' => 1, 'message' => '操作失败']);
    }
    /**
     * @power 系统设置|超时存入警报设置
     * @rank 4
     */
    public function overtimeConfig(Request $request)
    {
        $list = OvertimeConfig::alias('oc')
            ->field('oc.*')
            ->order('create_time','desc')
            ->paginate(15,false,[
                'query' => $request->param()?$request->param():[],
            ]);
        return $this->render('overtimeConfig',[
            'list' => $list,
            'types' => (new OvertimeConfig())->getAllTypes(),
        ]);
    }
    /**
     * @power 系统设置|添加超时存入警报设置
     * @rank 4
     */
    public function addOvertimeConfig(Request $request)
    {
        $result = $this->validate($request->post(), 'app\admin\validate\AddOvertimeConfig');
        if (true !== $result) {
            return json()->data(['code' => 1, 'message' => $result]);
        }
        $query = new OvertimeConfig();
        $res = $query->addNew($query,$request->post());
        if($res){
            LogService::write('系统设置', '用户添加超时存入警报设置');
            return json()->data(['code' => 0,'message' => '添加成功']);
        }
        return json()->data(['code' => 1, 'message' => '操作失败']);
    }
    /**
     * @power 系统设置|编辑超时存入警报设置
     * @rank 4
     */
    public function editOvertimeConfig(Request $request)
    {
        $result = $this->validate($request->post(), 'app\admin\validate\AddOvertimeConfig');
        if (true !== $result) {
            return json()->data(['code' => 1, 'message' => $result]);
        }
        $id = $request->post('id');
        $query = OvertimeConfig::where('id',$id)->find();
        $res = $query->addNew($query,$request->post());
        if($res){
            LogService::write('系统设置', '用户编辑超时存入警报设置');
            return json()->data(['code' => 0,'message' => '修改成功']);
        }
        return json()->data(['code' => 1, 'message' => '操作失败']);
    }
    /**
     * @power 系统设置|删除超时存入警报设置
     * @rank 4
     */
    public function delOvertimeConfig(Request $request)
    {
        $id = $request->param('id');
        $res = OvertimeConfig::where('id',$id)->delete();
        if($res){
            LogService::write('系统设置', '用户删除超时存入警报设置');
            return json()->data(['code' => 0,'message' => '删除成功']);
        }
        return json()->data(['code' => 1, 'message' => '操作失败']);
    }
    /**
     * @power 系统设置|大额度提现警报设置
     * @rank 4
     */
    public function bigConfig(Request $request)
    {
        $list = BigConfig::alias('bc')
            ->field('bc.*')
            ->order('create_time','desc')
            ->paginate(15,false,[
                'query' => $request->param()?$request->param():[],
            ]);
        return $this->render('bigConfig',[
            'list' => $list,
        ]);
    }
    /**
     * @power 系统设置|添加大额度提现警报设置
     * @rank 4
     */
    public function addBigConfig(Request $request)
    {
        $result = $this->validate($request->post(), 'app\admin\validate\AddBigConfig');
        if (true !== $result) {
            return json()->data(['code' => 1, 'message' => $result]);
        }
        $query = new BigConfig();
        $res = $query->addNew($query,$request->post());
        if($res){
            LogService::write('系统设置', '用户添加大额度提现警报设置');
            return json()->data(['code' => 0,'message' => '添加成功']);
        }
        return json()->data(['code' => 1, 'message' => '操作失败']);
    }
    /**
     * @power 系统设置|编辑大额度提现警报设置
     * @rank 4
     */
    public function editBigConfig(Request $request)
    {
        $result = $this->validate($request->post(), 'app\admin\validate\AddBigConfig');
        if (true !== $result) {
            return json()->data(['code' => 1, 'message' => $result]);
        }
        $id = $request->post('id');
        $query = BigConfig::where('id',$id)->find();
        $res = $query->addNew($query,$request->post());
        if($res){
            LogService::write('系统设置', '用户编辑大额度提现警报设置');
            return json()->data(['code' => 0,'message' => '修改成功']);
        }
        return json()->data(['code' => 1, 'message' => '操作失败']);
    }
    /**
     * @power 系统设置|删除大额度提现警报设置
     * @rank 4
     */
    public function delBigConfig(Request $request)
    {
        $id = $request->param('id');
        $res = BigConfig::where('id',$id)->delete();
        if($res){
            LogService::write('系统设置', '用户删除大额度提现警报设置');
            return json()->data(['code' => 0,'message' => '删除成功']);
        }
        return json()->data(['code' => 1, 'message' => '操作失败']);
    }
    /**
     * @power 系统设置|搜索交易设置
     * @rank 4
     */
    public function searchConfig(Request $request)
    {
        $list = SeachConfig::alias('bc')
            ->field('bc.*')
            ->order('create_time','desc')
            ->paginate(15,false,[
                'query' => $request->param()?$request->param():[],
            ]);
        return $this->render('searchConfig',[
            'list' => $list,
        ]);
    }
    /**
     * @power 系统设置|添加搜索交易设置
     * @rank 4
     */
    public function addSearchConfig(Request $request)
    {
        $result = $this->validate($request->post(), 'app\admin\validate\AddSearchConfig');
        if (true !== $result) {
            return json()->data(['code' => 1, 'message' => $result]);
        }
        $query = new SeachConfig();
        $res = $query->addNew($query,$request->post());
        if($res){
            LogService::write('系统设置', '用户添加搜索交易设置');
            return json()->data(['code' => 0,'message' => '添加成功']);
        }
        return json()->data(['code' => 1, 'message' => '操作失败']);
    }
    /**
     * @power 系统设置|编辑搜索交易设置
     * @rank 4
     */
    public function editSearchConfig(Request $request)
    {
        $result = $this->validate($request->post(), 'app\admin\validate\AddSearchConfig');
        if (true !== $result) {
            return json()->data(['code' => 1, 'message' => $result]);
        }
        $id = $request->post('id');
        $query = SeachConfig::where('id',$id)->find();
        $res = $query->addNew($query,$request->post());
        if($res){
            LogService::write('系统设置', '用户编辑搜索交易设置');
            return json()->data(['code' => 0,'message' => '修改成功']);
        }
        return json()->data(['code' => 1, 'message' => '操作失败']);
    }
    /**
     * @power 系统设置|删除搜索交易设置
     * @rank 4
     */
    public function delSearchConfig(Request $request)
    {
        $id = $request->param('id');
        $res = SeachConfig::where('id',$id)->delete();
        if($res){
            LogService::write('系统设置', '用户删除搜索交易设置');
            return json()->data(['code' => 0,'message' => '删除成功']);
        }
        return json()->data(['code' => 1, 'message' => '操作失败']);
    }

    /**
     * @power 系统设置|排单币返还设置
     * @rank 4
     */
    public function lineConfig(Request $request)
    {
        $list = ReturnConfig::alias('bc')
            ->field('bc.*')
            ->order('create_time','desc')
            ->paginate(15,false,[
                'query' => $request->param()?$request->param():[],
            ]);
        return $this->render('lineConfig',[
            'list' => $list,
        ]);
    }
    /**
     * @power 系统设置|添加排单币返还设置
     * @rank 4
     */
    public function addLineConfig(Request $request)
    {
        $result = $this->validate($request->post(), 'app\admin\validate\AddLineConfig');
        if (true !== $result) {
            return json()->data(['code' => 1, 'message' => $result]);
        }
        $query = new ReturnConfig();
        $res = $query->addNew($query,$request->post());
        if($res){
            LogService::write('系统设置', '用户添加排单币返还设置');
            return json()->data(['code' => 0,'message' => '添加成功']);
        }
        return json()->data(['code' => 1, 'message' => '操作失败']);
    }
    /**
     * @power 系统设置|编辑排单币返还设置
     * @rank 4
     */
    public function editLineConfig(Request $request)
    {
        $result = $this->validate($request->post(), 'app\admin\validate\AddLineConfig');
        if (true !== $result) {
            return json()->data(['code' => 1, 'message' => $result]);
        }
        $id = $request->post('id');
        $query = ReturnConfig::where('id',$id)->find();
        $res = $query->addNew($query,$request->post());
        if($res){
            LogService::write('系统设置', '用户编辑排单币返还设置');
            return json()->data(['code' => 0,'message' => '修改成功']);
        }
        return json()->data(['code' => 1, 'message' => '操作失败']);
    }
    /**
     * @power 系统设置|删除排单币返还设置
     * @rank 4
     */
    public function delLineConfig(Request $request)
    {
        $id = $request->param('id');
        $res = ReturnConfig::where('id',$id)->delete();
        if($res){
            LogService::write('系统设置', '用户删除排单币返还设置');
            return json()->data(['code' => 0,'message' => '删除成功']);
        }
        return json()->data(['code' => 1, 'message' => '操作失败']);
    }
    /**
     * @power 系统设置|测评设置
     * @rank 4
     */
    public function testConfig(Request $request)
    {
        $entity = Question::alias('q')
            ->field('q.*');

        if ($keyword = $request->get('keyword')) {
            $type = $request->get('type');
            switch ($type) {
                case 'title':
                    $entity->where('title', 'like','%'.$keyword.'%');
                    break;
            }
            $map['type'] = $type;
            $map['keyword'] = $keyword;
        }
        $list = $entity
            ->order('sort')
            ->paginate(15,false,[
                'query' => $request->param()?$request->param():[],
            ]);
        return $this->render('testConfig',[
            'list' => $list,
        ]);
    }
    /**
     * @power 系统设置|禁用测评设置
     * @rank 4
     */
    public function testConfigClose(Request $request)
    {
        $res = Question::where('id',$request->param('id'))->setField('status',2);
        if($res){
            LogService::write('系统设置', '用户禁用测评问题');
            return json()->data(['code' => 0,'message' => '禁用成功']);
        }
        return json()->data(['code' => 1, 'message' => '操作失败']);
    }
    /**
     * @power 系统设置|启用测评设置
     * @rank 4
     */
    public function testConfigOpen(Request $request)
    {
        $res = Question::where('id',$request->param('id'))->setField('status',1);
        if($res){
            LogService::write('系统设置', '用户启用测评问题');
            return json()->data(['code' => 0,'message' => '启用成功']);
        }
        return json()->data(['code' => 1, 'message' => '操作失败']);
    }
    /**
     * @power 系统设置|编辑测评问题
     * @rank 4
     */
    public function updateTestQuestion(Request $request)
    {
        $id = $request->param('id');
        $entity = Question::where('id', $id)->find();
        if (!$entity) {
            return json()->data(['code' => 1, 'message' =>'用户对象不存在，请刷新页面']);
        }
        return $this->render('editQuestion', [
            'info' => $entity,
        ]);
    }
    /**
     * @power 系统设置|处理编辑测评问题
     * @rank 4
     */
    public function editTestQuestion(Request $request)
    {
        $id = $request->param('id');
        $entity = Question::where('id', $id)->find();
        if (!$entity) {
            return json()->data(['code' => 1, 'message' =>'用户对象不存在，请刷新页面']);
        }
        $res = $entity->addNew($entity,$request->post());
        if(is_int($res)){
            LogService::write('系统设置', '用户编辑测评问题');
            return json()->data(['code' => 0, 'toUrl' => url('/admin/System/testConfig')]);
        }
        return json()->data(['code' => 1, 'message' => '操作失败']);
    }
    /**
     * @power 系统设置|编辑测评问题答案
     * @rank 4
     */
    public function updateTestAnswer(Request $request)
    {
        $id = $request->param('id');
        $entity = Answer::where('qid', $id)->order('sort')->select();
        if (!$entity) {
            return $this->error('占无答案');
        }
        return $this->render('editAnswer', [
            'info' => $entity,
        ]);
    }
    /**
     * @power 系统设置|处理编辑测评问题答案
     * @rank 4
     */
    public function editTestAnswer(Request $request)
    {
        $update_data = $request->post();
        foreach ($update_data['id']  as $k => $v){
            $edit_data = [
                'content' =>  $update_data['content'][$k],
                'score' =>  $update_data['score'][$k],
                'sort' =>  $update_data['sort'][$k],
                'status' =>  $update_data['status'][$k],
            ];
            $entity = Answer::where('id', $v)->find();
            $res = $entity->editData($entity,$edit_data);
        }
        if(is_int($res)){
            LogService::write('系统设置', '用户处理编辑测评问题答案');
            return json()->data(['code' => 0, 'toUrl' => url('/admin/System/testConfig')]);
        }
        return json()->data(['code' => 1, 'message' => '操作失败']);
    }
    /**
     * @power 系统设置|添加测评问题答案
     * @rank 4
     */
    public function addTestAnswer(Request $request)
    {
        $id = $request->param('id');
        $list = Question::alias('q')
            ->field('q.*')
            ->where('id',$id)
            ->find();
        return $this->render('editAnswer',[
            'list' => $list,
        ]);
    }
    /**
     * @power 系统设置|处理添加测评问题答案
     * @rank 4
     */
    public function doAddTestAnswer(Request $request)
    {
        $result = $this->validate($request->post(), 'app\admin\validate\DoAddTestAnswer');
        if (true !== $result) {
            return json()->data(['code' => 1, 'message' => $result]);
        }
        $query = new Answer();
        $res = $query->addNew($query,$request->post());
        if($res){
            LogService::write('系统设置', '用户添加测评问题答案');
            return json()->data(['code' => 0, 'toUrl' => url('/admin/System/testConfig')]);
        }
        return json()->data(['code' => 1, 'message' => '操作失败']);
    }
    /**
     * @power 系统设置|添加测评问题
     * @rank 4
     */
    public function createTestQuestion(Request $request)
    {
        if($request->isGet()){
            return $this->render('editQuestion');
        }
        if($request->isPost()){
            $result = $this->validate($request->post(), 'app\admin\validate\DoTestQuestion');
            if (true !== $result) {
                return json()->data(['code' => 1, 'message' => $result]);
            }
            $query = new Question();
            $res = $query->addNew($query,$request->post());
            if($res){
                LogService::write('系统设置', '用户添加测评问题');
                return json()->data(['code' => 0, 'toUrl' => url('/admin/System/testConfig')]);
            }
            return json()->data(['code' => 1, 'message' => '操作失败']);
        }

    }
    /**
     * @power 系统设置|删除测评问题
     * @rank 4
     */
    public function delTestConfig(Request $request)
    {
        $id = $request->param('id');
        $query = new Question();
        $res = $query->delData($id);
        if($res){
            LogService::write('系统设置', '用户删除测评问题');
            return json()->data(['code' => 0, 'toUrl' => url('/admin/System/testConfig')]);
        }
        return json()->data(['code' => 1, 'message' => '操作失败']);
    }
    /**
     * @power 系统设置|存款设置
     * @rank 4
     */
    public function storeConfig(Request $request)
    {
        $list = StoreConfig::alias('sc')
            ->field('sc.*')
            ->order('create_time','desc')
            ->paginate(15,false,[
                'query' => $request->param()?$request->param():[],
            ]);
        return $this->render('storeConfig',[
            'list' => $list,
        ]);
    }
    /**
     * @power 系统设置|添加存款设置
     * @rank 4
     */
    public function addStoreConfig(Request $request)
    {
        $result = $this->validate($request->post(), 'app\admin\validate\AddStoreConfig');
        if (true !== $result) {
            return json()->data(['code' => 1, 'message' => $result]);
        }
        $query = new StoreConfig();
        $res = $query->addNew($query,$request->post());
        if($res){
            LogService::write('系统设置', '用户添加存款设置');
            return json()->data(['code' => 0,'message' => '添加成功']);
        }
        return json()->data(['code' => 1, 'message' => '操作失败']);
    }
    /**
     * @power 系统设置|编辑存款设置
     * @rank 4
     */
    public function editStoreConfig(Request $request)
    {
        $result = $this->validate($request->post(), 'app\admin\validate\AddStoreConfig');
        if (true !== $result) {
            return json()->data(['code' => 1, 'message' => $result]);
        }
        $id = $request->post('id');
        $query = StoreConfig::where('id',$id)->find();
        $res = $query->addNew($query,$request->post());
        if($res){
            LogService::write('系统设置', '用户编辑存款设置');
            return json()->data(['code' => 0,'message' => '修改成功']);
        }
        return json()->data(['code' => 1, 'message' => '操作失败']);
    }
    /**
     * @power 系统设置|删除存款设置
     * @rank 4
     */
    public function delStoreConfig(Request $request)
    {
        $id = $request->param('id');
        $res = StoreConfig::where('id',$id)->delete();
        if($res){
            LogService::write('系统设置', '用户删除存款设置');
            return json()->data(['code' => 0,'message' => '删除成功']);
        }
        return json()->data(['code' => 1, 'message' => '操作失败']);
    }
    /**
     * @power 系统设置|封号设置
     * @rank 4
     */
    public function freezeConfig(Request $request)
    {
        $list = CloseUserConfig::alias('cuc')
            ->field('cuc.*')
            ->order('create_time','desc')
            ->paginate(15,false,[
                'query' => $request->param()?$request->param():[],
            ]);
        return $this->render('freezeConfig',[
            'list' => $list,
        ]);
    }
    /**
     * @power 系统设置|添加封号设置
     * @rank 4
     */
    public function addFreezeConfig(Request $request)
    {
        $result = $this->validate($request->post(), 'app\admin\validate\AddFreezeConfig');
        if (true !== $result) {
            return json()->data(['code' => 1, 'message' => $result]);
        }
        $query = new CloseUserConfig();
        $res = $query->addNew($query,$request->post());
        if($res){
            LogService::write('系统设置', '用户添加封号设置');
            return json()->data(['code' => 0,'message' => '添加成功']);
        }
        return json()->data(['code' => 1, 'message' => '操作失败']);
    }
    /**
     * @power 系统设置|编辑封号设置
     * @rank 4
     */
    public function editFreezeConfig(Request $request)
    {
        $result = $this->validate($request->post(), 'app\admin\validate\AddFreezeConfig');
        if (true !== $result) {
            return json()->data(['code' => 1, 'message' => $result]);
        }
        $id = $request->post('id');
        $query = CloseUserConfig::where('id',$id)->find();
        $res = $query->addNew($query,$request->post());
        if($res){
            LogService::write('系统设置', '用户编辑封号设置');
            return json()->data(['code' => 0,'message' => '修改成功']);
        }
        return json()->data(['code' => 1, 'message' => '操作失败']);
    }
    /**
     * @power 系统设置|删除封号设置
     * @rank 4
     */
    public function delFreezeConfig(Request $request)
    {
        $id = $request->param('id');
        $res = CloseUserConfig::where('id',$id)->delete();
        if($res){
            LogService::write('系统设置', '用户删除封号设置');
            return json()->data(['code' => 0,'message' => '删除成功']);
        }
        return json()->data(['code' => 1, 'message' => '操作失败']);
    }
    /**
     * @power 系统设置|动态设置
     * @rank 4
     */
    public function dynamicConfig(Request $request)
    {
        $list = DynamicConfig::alias('dc')
            ->field('dc.*')
            ->order('create_time','desc')
            ->paginate(15,false,[
                'query' => $request->param()?$request->param():[],
            ]);
        return $this->render('dynamicConfig',[
            'list' => $list,
        ]);
    }
    /**
     * @power 系统设置|添加动态设置
     * @rank 4
     */
    public function addDynamicConfig(Request $request)
    {
        $result = $this->validate($request->post(), 'app\admin\validate\AddDynamicConfig');
        if (true !== $result) {
            return json()->data(['code' => 1, 'message' => $result]);
        }
        $query = new DynamicConfig();
        $res = $query->addNew($query,$request->post());
        if($res){
            LogService::write('系统设置', '用户添加动态设置');
            return json()->data(['code' => 0,'message' => '添加成功']);
        }
        return json()->data(['code' => 1, 'message' => '操作失败']);
    }
    /**
     * @power 系统设置|编辑动态设置
     * @rank 4
     */
    public function editDynamicConfig(Request $request)
    {
        $result = $this->validate($request->post(), 'app\admin\validate\AddDynamicConfig');
        if (true !== $result) {
            return json()->data(['code' => 1, 'message' => $result]);
        }
        $id = $request->post('id');
        $query = DynamicConfig::where('id',$id)->find();
        $res = $query->addNew($query,$request->post());
        if($res){
            LogService::write('系统设置', '用户编辑动态设置');
            return json()->data(['code' => 0,'message' => '修改成功']);
        }
        return json()->data(['code' => 1, 'message' => '操作失败']);
    }
    /**
     * @power 系统设置|删除动态设置
     * @rank 4
     */
    public function delDynamicConfig(Request $request)
    {
        $id = $request->param('id');
        $res = DynamicConfig::where('id',$id)->delete();
        if($res){
            LogService::write('系统设置', '用户删除动态设置');
            return json()->data(['code' => 0,'message' => '删除成功']);
        }
        return json()->data(['code' => 1, 'message' => '操作失败']);
    }
    /**
     * @power 系统设置|管理员设置
     * @rank 4
     */
    public function admin(Request $request)
    {
        $entity = ManageUser::alias('dc')
            ->field('dc.*');

        if ($keyword = $request->get('keyword')) {
            $type = $request->get('type');
            switch ($type) {
                case 'manage_name':
                    $entity->where('dc.manage_name', 'like','%'.$keyword.'%');
                    break;
            }
            $map['type'] = $type;
            $map['keyword'] = $keyword;
        }
        $startTime = $request->get('startTime');
        $endTime = $request->get('endTime');
        if($startTime && $endTime){
            $entity->where('dc.create_time', '<', strtotime($endTime))
                ->where('dc.create_time', '>=', strtotime($startTime));
            $map['startTime'] = $startTime;
            $map['endTime'] = $endTime;
        }
        $list = $entity
            ->order('create_time','desc')
            ->paginate(15,false,[
                'query' => $request->param()?$request->param():[],
            ]);
        return $this->render('admin',[
            'list' => $list,
        ]);
    }
    /**
     * @power 系统设置|添加管理员
     * @rank 4
     */
    public function createAdmin(Request $request)
    {
        if($request->isGet()){
            return $this->render('edit');
        }
        if($request->isPost()){
            $service = new \app\admin\service\rbac\Users\Service();
            $result = $this->validate($request->post(), 'app\admin\validate\CreateForm');

            if (true !== $result) {
                return json()->data(['code' => 1, 'message' => $result]);
            }
            $res = $service->checkName($request->post('username'));

            if (true === $res) {
                return json()->data(['code' => 1, 'message' => '该用户已存在']);
            }
            $salt = $service->getPasswordSalt();
            $pwd = $service->getPassword($request->post('password'),$salt);
            $add_data = [
                'manage_name' => $request->post('username'),
                'password' => $pwd,
                'password_salt' => $salt,
                'create_time' => time(),
            ];
            $log = ManageUser::insert($add_data);
            if($log){
                LogService::write('系统设置', '用户添加管理员');
                return json(['code' => 0, 'toUrl' => url('admin')]);
            }
            return json()->data(['code' => 1, 'message' => '添加失败']);
        }
    }
    /**
     * @power 系统设置|修改管理员
     * @rank 4
     */
    public function updateAdmin(Request $request)
    {
        if($request->isGet()){
            $id = $request->param('id');
            $info = ManageUser::where('id',$id)->find();
            return $this->render('edit',[
                'info' => $info,
            ]);
        }
        if($request->isPost()){
            if(!$request->post('manage_name'))  return json()->data(['code' => 1, 'message' => '用户名不能为空']);
            $service = new \app\common\service\Users\Service();
            $edit_data = [
                'manage_name' => $request->post('manage_name'),
                'update_time' => time(),
            ];
            if($request->post('password')){
                $model = new \app\admin\service\rbac\Users\Service();
                $user = ManageUser::where('id', $model->getManageId())->find();

                $pwd =  $model->getPassword($request->post('password'), $user->getPasswordSalt());
                $edit_data['password'] = $pwd;
            }
            $log = ManageUser::where('id',$request->param('id'))->update($edit_data);
            if($log){
                LogService::write('系统设置', '用户修改管理员');
                return json(['code' => 0, 'toUrl' => url('admin')]);
            }
            return json()->data(['code' => 1, 'message' => '添加失败']);
        }
    }
    /**
     * @power 系统设置|删除管理员
     * @rank 4
     */
    public function delAdmin(Request $request)
    {
        $id = $request->param('id');
        $res = ManageUser::where('id',$id)->delete();
        if($res){
            return json()->data(['code' => 0, 'toUrl' => url('admin')]);
        }
        return json()->data(['code' => 1, 'message' => '操作失败']);
    }
    /**
     * @power 系统设置|清空系统
     * @rank 4
     */
    public function clearConfig(Request $request)
    {
        return $this->render('clearConfig',[

        ]);
    }
    /**
     * @power 系统设置|确认清空系统
     * @rank 4
     */
    public function delClearConfig()
    {

        Db('user')->where('id','>',0)->delete();//用户
        Db('user_invite_code')->where('id','>',0)->delete();//用户邀请码
        Db('my_wallet')->where('id','>',0)->delete();//用户GTC
        Db('card')->where('id','>',0)->delete();//用户支付信息
        Db('my_log')->where('id','>',0)->delete();//测试日志
        Db('appeal_user')->where('id','>',0)->delete();//账户申诉
        Db('bathing_pool')->where('id','>',0)->delete();//酒馆信息
        Db('appointment_user')->where('id','>',0)->delete();//预约记录
        Db('fish')->where('id','>',0)->delete();//酒
        Db('fish_increment')->where('id','>',0)->delete();//酒增值信息
        Db('fish_order')->where('id','>',0)->delete();//酒订单
        Db('fish_tradable_num')->where('id','>',0)->delete();//可交易酒信息初始化
        Db('my_wallet_log')->where('id','>',0)->delete();//用户GTC记录
        Db('my_gc_log')->where('id','>',0)->delete();//用户GC记录
        Db('gc_withdraw_log')->where('id','>',0)->delete();//提币日志
        Db('fish_feed_log')->where('id','>',0)->delete();//装修时间记录
        Db('user_profit_log')->where('id','>',0)->delete();//用户收益记录
        Db('user_verify_log')->where('id','>',0)->delete();//用户收益记录
        Db('my_log')->where('id','>',0)->delete();//日志
        Db('team_log')->where('id','>',0)->delete();//团队收益
        Db('prohibit_log')->where('id','>',0)->delete();//推广收益
        Db('system_log')->where('id','>',0)->delete();
        Db('index_log')->where('id','>',0)->delete();
        return json()->data(['code' => 0, 'toUrl' => url('clearConfig')]);
        
    }
    /**
     * @power 系统设置|操作日志
     * @rank 4
     */
    public function log(Request $request)
    {
        $entity = SystemLog::alias('sl')
            ->field('sl.*');

        if ($keyword = $request->get('keyword')) {
            $type = $request->get('type');
            switch ($type) {
                case 'username':
                    $entity->where('username', 'like','%'.$keyword.'%');
                    break;
            }
            $map['type'] = $type;
            $map['keyword'] = $keyword;
        }
        $startTime = $request->get('startTime');
        $endTime = $request->get('endTime');
        if($startTime || $endTime){
			if(empty($startTime)){
				$startTime = date('Y-m-d H:i:s');
			}
			if(empty($endTime)){
				$endTime = date('Y-m-d H:i:s');
			}
            $entity->where('sl.create_at', '<', $endTime)
                ->where('sl.create_at', '>=', $startTime);
            $map['startTime'] = $startTime;
            $map['endTime'] = $endTime;
        }
        $list = $entity->order('create_at','desc')
            ->paginate(15,false,[
                'query' => $request->param()?$request->param():[],
            ]);
        return $this->render('log',[
            'list' => $list,
        ]);
    }
    /**
     * @power 系统设置|关闭系统
     * @rank 4
     */
    public function closeSystem(Request $request)
    {
        $info = SystemConfig::where('id','>',0)->find();
        return $this->render('closeSystem',[
            'info' => $info,
        ]);
    }
    /**
     * @power 系统设置|关闭系统设置
     * @rank 4
     */
    public function saveCloseSystem(Request $request)
    {
        $key = $request->param('key', '', 'trim');
        $configID = $request->param('configID', '1', 'trim');
        $tipsContent = $request->param('tipsContent', '', 'trim');
        $startTime = $request->param('startTime', 0, 'trim');
        $endTime = $request->param('endTime', 0, 'trim');

        // 关闭系统必须提供截止时间
        if ($key == 'close' && empty($endTime)) {
            return json()->data(['code' => 1, 'message' => '请输入截止时间']);
        }

        if (empty($configID)) {
            return json()->data(['code' => 1, 'message' => '参数错误']);
        }
        if ($key == 'close') {
            // 截止时间必须大于当前时间
            if (time() > strtotime($endTime)) {
                return json()->data(['code' => 1, 'message' => '截止时间必须大于当前时间']);
            }
        }
        $save = [];
        if ($key == 'on') { // 站点开启
            $save['status'] = 0;
            $save['content'] = '站点正常开启';
            $save['start_time'] = 0;
            $save['end_time'] = 0;
            $save['edit_time'] = strtotime(date('Y-m-d',time())); // 取重新打开站点的日期为上次维护的时间
        }else{ // 站点关闭
            $save['status'] = 1;
            $save['content'] = $tipsContent;
            $save['start_time'] = strtotime($startTime);
            $save['end_time'] = strtotime($endTime);
        }
        $res = SystemConfig::where('id',$configID)->update($save);
        if($res !== false){
            if ($key == 'on') {
                $message = '开启成功';
            }else{
                $message = '关闭成功';
            }
            return json()->data(['code' => 0, 'message' => $message,'toUrl' => url('closeSystem')]);
        }
        return json()->data(['code' => 1, 'message' => '系统错误,修改失败']);
    }

    /**
     * @power 系统设置|修改白名单列表
     *
     * @param Request $request
     * @return void
     */
    public function editWhiteList(Request $request)
    {
        $info = SystemConfig::where('id','=',$request->param('id',1,'trim'))->field('id,phone_white_list')->find();
        return $this->render('editWhiteList',[
            'info' => $info,
        ]);
    }

    /**
     * @power 系统设置|保存白名单设置
     *
     * @param Request $request
     * @return void
     */
    public function saveWhiteList(Request $request)
    {
        $id = $request->param('id',1,'trim');
        $phone_white_list = $request->param('phone_white_list','','trim');
        $phone_white_list = trim($phone_white_list, ';'); // 去掉左右两边的分号
        $phone_white_list = array_unique(explode(';',$phone_white_list)); // 去掉重复值
        $phone_white_list = implode(';',$phone_white_list);
        $save_res = SystemConfig::where('id','=',$id)->setField('phone_white_list',$phone_white_list);
        if ($save_res !== false) {
            return json()->data(['code' => 0, 'message' => '修改成功', 'toUrl' => url('closeSystem')]);
        }else{
            return json()->data(['code' => 1, 'message' => '修改失败', 'toUrl' => url('editWhiteList')]);
        }
    }

    // /**
    //  * @power 系统设置|关闭系统设置
    //  * @rank 4
    //  */
    // public function saveCloseSystem(Request $request)
    // {
    //     $entry = new SystemConfig();
    //     $id = $request->param('id');
    //     if($id){
    //         $entry = SystemConfig::where('id',$id)->find();
    //         $res = $entry->editData($entry,$request->post());
    //     }else{
    //         $res = $entry->addNew($entry,$request->post());
    //     }
    //     if(is_int($res)){
    //         return json()->data(['code' => 0, 'toUrl' => url('closeSystem')]);
    //     }
    //     return json()->data(['code' => 1, 'message' => '添加失败']);
    // }
    /**
     * @power 系统设置|交易时间设置
     * @rank 4
     */
    public function exchangeHour(Request $request)
    {
        $info = ExchangeHour::where('id','>',0)->find();
        return $this->render('exchangeHour',[
            'info' => $info,
        ]);
    }
    /**
     * @power 系统设置|处理交易时间设置
     * @rank 4
     */
    public function saveExchangeHour(Request $request)
    {
        $entry = new ExchangeHour();
        $id = $request->param('id');
        if($id){
            $entry = ExchangeHour::where('id',$id)->find();
            $res = $entry->editData($entry,$request->post());
        }else{
            $res = $entry->addNew($entry,$request->post());
        }
        if(is_int($res)){
            return json()->data(['code' => 0, 'toUrl' => url('exchangeHour')]);
        }
        return json()->data(['code' => 1, 'message' => '添加失败']);
    }
    /**
     * @power 系统设置|安全问题设置
     * @rank 4
     */
    public function safeQuestion(Request $request)
    {
        $list = SafeQuestion::alias('sq')
            ->field('sq.*')
            ->order('sort')
            ->paginate(15,false,[
                'query' => $request->param()?$request->param():[],
            ]);
        return $this->render('safeQuestion',[
            'list' => $list,
        ]);
    }
    /**
     * @power 系统设置|添加安全问题
     * @rank 4
     */
    public function createSafeQuestion(Request $request)
    {
        if($request->isGet()){
            return $this->render('updateQuestion');
        }
        if($request->isPost()){
            $result = $this->validate($request->post(), 'app\admin\validate\DoTestQuestion');
            if (true !== $result) {
                return json()->data(['code' => 1, 'message' => $result]);
            }
            $query = new SafeQuestion();
            $res = $query->addNew($query,$request->post());
            if($res){
                LogService::write('系统设置', '用户添加安全问题');
                return json()->data(['code' => 0, 'toUrl' => url('/admin/System/safeQuestion')]);
            }
            return json()->data(['code' => 1, 'message' => '操作失败']);
        }

    }
    /**
     * @power 系统设置|禁用安全问题
     * @rank 4
     */
    public function safeQuestionClose(Request $request)
    {
        $res = SafeQuestion::where('id',$request->param('id'))->setField('status',2);
        if($res){
            LogService::write('系统设置', '用户禁用安全问题');
            return json()->data(['code' => 0,'message' => '禁用成功']);
        }
        return json()->data(['code' => 1, 'message' => '操作失败']);
    }
    /**
     * @power 系统设置|启用安全问题
     * @rank 4
     */
    public function safeQuestionOpen(Request $request)
    {
        $res = SafeQuestion::where('id',$request->param('id'))->setField('status',1);
        if($res){
            LogService::write('系统设置', '用户启用安全问题');
            return json()->data(['code' => 0,'message' => '启用成功']);
        }
        return json()->data(['code' => 1, 'message' => '操作失败']);
    }
    /**
     * @power 系统设置|编辑安全问题
     * @rank 4
     */
    public function updateSafeQuestion(Request $request)
    {
        $id = $request->param('id');
        $entity = SafeQuestion::where('id', $id)->find();
        if (!$entity) {
            return json()->data(['code' => 1, 'message' =>'用户对象不存在，请刷新页面']);
        }
        return $this->render('updateQuestion', [
            'info' => $entity,
        ]);
    }
    /**
     * @power 系统设置|处理编辑测评问题
     * @rank 4
     */
    public function editSafeQuestion(Request $request)
    {
        $id = $request->param('id');
        $entity = SafeQuestion::where('id', $id)->find();
        if (!$entity) {
            return json()->data(['code' => 1, 'message' =>'用户对象不存在，请刷新页面']);
        }
        $res = $entity->addNew($entity,$request->post());
        if(is_int($res)){
            LogService::write('系统设置', '用户编辑安全问题');
            return json()->data(['code' => 0, 'toUrl' => url('/admin/System/safeQuestion')]);
        }
        return json()->data(['code' => 1, 'message' => '操作失败']);
    }
    /**
     * @power 系统设置|删除测评问题
     * @rank 4
     */
    public function delSafeQuestion(Request $request)
    {
        $id = $request->param('id');
        $query = new SafeQuestion();
        $res = $query->where('id',$id)->delete();
        if($res){
            LogService::write('系统设置', '用户删除安全问题');
            return json()->data(['code' => 0, 'toUrl' => url('/admin/System/safeQuestion')]);
        }
        return json()->data(['code' => 1, 'message' => '操作失败']);
    }
    /**
     * @power 系统设置|冻结时间设置
     * @rank 4
     */
    public function frozenConfig(Request $request)
    {

        $list = FrozenConfig::alias('oc')
            ->field('oc.*')
            ->order('create_time','desc')
            ->paginate(15,false,[
                'query' => $request->param()?$request->param():[],
            ]);
        return $this->render('frozenConfig',[
            'list' => $list,
            'types' => (new FrozenConfig())->getAllTypes(),

        ]);
    }
    /**
     * @power 系统设置|添加冻结时间设置
     * @rank 4
     */
    public function addFrozenConfig(Request $request)
    {
        $result = $this->validate($request->post(), 'app\admin\validate\AddFrozenConfig');
        if (true !== $result) {
            return json()->data(['code' => 1, 'message' => $result]);
        }
        $query = new FrozenConfig();
        $res = $query->addNew($query,$request->post());
        if($res){
            LogService::write('系统设置', '用户添加冻结时间设置');
            return json()->data(['code' => 0,'message' => '添加成功']);
        }
        return json()->data(['code' => 1, 'message' => '操作失败']);
    }
    /**
     * @power 系统设置|删除冻结时间设置
     * @rank 4
     */
    public function delFrozenConfig(Request $request)
    {
        $id = $request->param('id');
        $res = FrozenConfig::where('id',$id)->delete();
        if($res){
            LogService::write('系统设置', '用户删除冻结时间设置');
            return json()->data(['code' => 0,'message' => '删除成功']);
        }
        return json()->data(['code' => 1, 'message' => '操作失败']);
    }
    /**
     * @power 系统设置|编辑冻结时间设置
     * @rank 4
     */
    public function editFrozenConfig(Request $request)
    {
        $result = $this->validate($request->post(), 'app\admin\validate\AddFrozenConfig');
        if (true !== $result) {
            return json()->data(['code' => 1, 'message' => $result]);
        }
        $id = $request->post('id');
        $query = FrozenConfig::where('id',$id)->find();
        $res = $query->addNew($query,$request->post());
        if($res){
            LogService::write('系统设置', '用户编辑冻结时间设置');
            return json()->data(['code' => 0,'message' => '修改成功']);
        }
        return json()->data(['code' => 1, 'message' => '操作失败']);
    }
    /**
     * @power 系统设置|取款额度基点设置
     * @rank 4
     */
    public function moneyRate(Request $request)
    {

        $list = MoneyRate::alias('mr')
            ->field('mr.*')
            ->order('create_time','desc')
            ->paginate(15,false,[
                'query' => $request->param()?$request->param():[],
            ]);
        return $this->render('moneyRate',[
            'list' => $list,
            'types' => (new MoneyRate())->getAllTypes(),

        ]);
    }
    /**
     * @power 系统设置|添加取款额度基点设置
     * @rank 4
     */
    public function addMoneyRate(Request $request)
    {
        $result = $this->validate($request->post(), 'app\admin\validate\AddMoneyRate');
        if (true !== $result) {
            return json()->data(['code' => 1, 'message' => $result]);
        }
        $query = new MoneyRate();
        $res = $query->addNew($query,$request->post());
        if($res){
            LogService::write('系统设置', '用户添加取款额度基点设置');
            return json()->data(['code' => 0,'message' => '添加成功']);
        }
        return json()->data(['code' => 1, 'message' => '操作失败']);
    }
    /**
     * @power 系统设置|删除取款额度基点设置
     * @rank 4
     */
    public function delMoneyRate(Request $request)
    {
        $id = $request->param('id');
        $res = MoneyRate::where('id',$id)->delete();
        if($res){
            LogService::write('系统设置', '用户删除取款额度基点设置');
            return json()->data(['code' => 0,'message' => '删除成功']);
        }
        return json()->data(['code' => 1, 'message' => '操作失败']);
    }
    /**
     * @power 系统设置|编辑取款额度基点设置
     * @rank 4
     */
    public function editMoneyRate(Request $request)
    {
        $result = $this->validate($request->post(), 'app\admin\validate\AddMoneyRate');
        if (true !== $result) {
            return json()->data(['code' => 1, 'message' => $result]);
        }
        $id = $request->post('id');
        $query = MoneyRate::where('id',$id)->find();
        $res = $query->addNew($query,$request->post());
        if($res){
            LogService::write('系统设置', '用户编辑取款额度基点设置');
            return json()->data(['code' => 0,'message' => '修改成功']);
        }
        return json()->data(['code' => 1, 'message' => '操作失败']);
    }
    /**
     * @power 系统设置|反馈设置
     * @rank 4
     */
    public function manage(Request $request)
    {
        $list = ReplyConfig::alias('rc')
            ->field('rc.*')
            ->order('create_time','desc')
            ->paginate(15,false,[
                'query' => $request->param()?$request->param():[],
            ]);
        return $this->render('manage',[
            'list' => $list,
        ]);
    }
    /**
     * @power 系统设置|编辑反馈设置
     * @rank 4
     */
    public function editManage(Request $request)
    {
        $id = $request->post('id');
        $query = ReplyConfig::where('id',$id)->find();
        $res = $query->addNew($query,$request->post());
        if($res){
            LogService::write('系统设置', '用户编辑反馈设置');
            return json()->data(['code' => 0,'message' => '修改成功']);
        }
        return json()->data(['code' => 1, 'message' => '操作失败']);
    }
    /**
     * @power 系统设置|前台操作日志
     * @rank 4
     */
    public function indexLog(Request $request)
    {
        $entity = IndexLog::alias('il')
            ->field('il.*');

        if ($keyword = $request->get('keyword')) {
            $type = $request->get('type');
            switch ($type) {
                case 'username':
                    $entity->where('username', 'like','%'.$keyword.'%');
                    break;
            }
            $map['type'] = $type;
            $map['keyword'] = $keyword;
        }
        $startTime = $request->get('startTime');
        $endTime = $request->get('endTime');
        if($startTime || $endTime){
			if(empty($startTime)){
				$startTime = date('Y-m-d H:i:s');
			}
			if(empty($endTime)){
				$endTime = date('Y-m-d H:i:s');
			}
            $entity->where('il.create_at', '<', $endTime)
                ->where('il.create_at', '>=', $startTime);
            $map['startTime'] = $startTime;
            $map['endTime'] = $endTime;
        }
        $list = $entity->order('create_at','desc')
            ->paginate(15,false,[
                'query' => $request->param()?$request->param():[],
            ]);
        return $this->render('indexLog',[
            'list' => $list,
        ]);
    }



    public function adopt(){

        $uid = $this->userId;
        $page = input('post.page')?input('post.page'):1;
        $type = input('post.type')?input('post.type'):0;
        $limit = input('post.limit')?input('post.limit'):15;

        if($type == 0){
            $map['au.status'] = ['in','1,2'];// 点击领取,分配到酒,上传支付

        }elseif ($type ==1){

            $map['au.status'] = ['in','3,4'];
        }else{
            $map['au.status']  = ['in','-4'];//投诉取消
        }




        $map['au.uid'] = $uid;

        $list =  Db::table('appointment_user')
            ->alias('au')
            ->join('bathing_pool bp','bp.id = au.pool_id')
            ->join('user u','au.uid = u.id')
            ->where($map)
            ->field('au.id,bp.contract_time,bp.worth_max worth,bp.profit,u.nick_name user_name,bp.name,au.new_fid,au.pre_endtime over_time,au.oid,au.status')
            ->page($page)
            ->paginate($limit)
            ->toArray();


        if(empty($list)){
            $list = array();
        }else{
            $list = $list['data'];
            $time = time();

            foreach ($list as $k => $v){
                $is_fo = Db::table('fish_order')->where('id',$v['oid'])->field('id,over_time,f_id,worth,order_number')->find();
                $list[$k]['id'] = $v['id'];
                if($v['status'] == 1){
                    $list[$k]['status_name'] = '匹配中';
                    $list[$k]['over_time'] = $list[$k]['over_time'] -$time;
                    $list[$k]['status'] = 1;
                    $list[$k]['fid'] = 0;
                    $list[$k]['order_number'] = '';

                }elseif ($v['status'] == 2){

                    $list[$k]['status_name'] = '待付款';
//                    $list[$k]['over_time'] = $is_fo['over_time'] -$time;
                    $list[$k]['over_time'] = 400;
                    $list[$k]['status'] = 2;
                    $list[$k]['fid'] = $is_fo['f_id'];
                    $list[$k]['worth'] = $is_fo['worth'];
                    $list[$k]['order_number'] =$is_fo['order_number'];



                }elseif($v['status'] == 3){
                    $list[$k]['status_name'] = '待完成';
                    $list[$k]['over_time'] = date('Y-m-d H:i:s',$is_fo['over_time']);
                    $list[$k]['status'] = 3;
                    $list[$k]['fid'] = $is_fo['f_id'];
                    $list[$k]['worth'] = $is_fo['worth'];
                    $list[$k]['order_number'] =$is_fo['order_number'];

                }elseif($v['status'] == 4){
                    $list[$k]['status_name'] = '完成';
                    $list[$k]['over_time'] = date('Y-m-d H:i:s',$is_fo['over_time']);
                    $list[$k]['status'] = 4;
                    $list[$k]['fid'] = $is_fo['f_id'];
                    $list[$k]['worth'] = $is_fo['worth'];
                    $list[$k]['order_number'] =$is_fo['order_number'];

                }elseif($v['status'] == -4){
                    $is_a = Db::table('appeal')->where('order_id',$v['oid'])->field('status,create_time')->order('create_time desc')->find();

                    if($is_a['status'] == -2){
                        $list[$k]['status_name'] = '取消';
                    }elseif ($is_a['status'] == -1){
                        $list[$k]['status_name'] = '驳回';
                    }elseif ($is_a['status'] == 0){
                        $list[$k]['status_name'] = '申诉';
                    }elseif ($is_a['status'] == 1){
                        $list[$k]['status_name'] = '通过';
                    }
                    $list[$k]['over_time'] = date('Y-m-d H:i:s',$is_a['create_time']);
                    $list[$k]['status'] = -4;
                    $list[$k]['fid'] = $is_fo['f_id'];
                    $list[$k]['worth'] = $is_fo['worth'];
                    $list[$k]['order_number'] =$is_fo['order_number'];
                }
                if( $list[$k]['fid']){
                    $list[$k]['uid'] = Db::table('fish')->where('id',$list[$k]['fid'])->value('u_id');
                }else{
                    $list[$k]['uid'] = 0;
                }


            }


        }
        return json(['code' => 0, 'msg' => 'access!','info' => $list]);
    }


    /**
     * 邀请背景图
     * @return mixed
     */
    public function create_invitation_img() {
        $info = Db::table('invitationimg')->find();

        return $this->render('editinvimg',[
            'info' => $info,
        ]);
    }

    /**
     * 添加修改背景图
     * @param Request $request
     * @return $this|\think\response\Json
     */
    public function invitationsave(Request $request) {


        $path = $request->post('path');
        if(empty($path)){
            return json()->data(['code' => 1, 'message' => '图片不能为空']);
        }

        Db::startTrans();

        try {

            Db::commit();
            Db::table('invitationimg')->where('id','>','0')->delete();
            $add['img'] = $path;
            $is_add =  Db::table('invitationimg')->insert($add);
            if (!$is_add) {
                return json()->data(['code' => 1, 'message' => '保存失败']);
            }



        } catch (\Exception $e) {
            Db::rollback();
            return json()->data(['code' => 1, 'message' => '保存失败']);

        }



        LogService::write('邀请背景图', '修改背景图');
        return json(['code' => 0]);

    }


    /**
     * 银行设置
     * @param Request $request
     * @return mixed
     */
    public function  bank(Request $request)
    {

        $list = Db::table('bank')
            ->order('create_time','desc')
            ->paginate(15,false,[
                'query' => $request->param()?$request->param():[],
            ]);
        return $this->render('bank',[
            'list' => $list,
        ]);
    }


    /**
     * 添加银行
     * @param Request $request
     * @return $this|mixed|\think\response\Json
     */
    public function createBank(Request $request)
    {
        if($request->isGet()){
            return $this->render('bankedit');
        }
        if($request->isPost()){
            $name = $request->post('bankname');
            $name = trim($name);
            if (!$name) {
                return json()->data(['code' => 1, 'message' =>'银行名称不能为空！']);
            }
            $add_data['create_time'] = time();
            $add_data['bank_name'] = $name;
            $log = Db::table('bank')->insert($add_data);
            if($log){
                LogService::write('系统设置', '用户添加银行信息');
                return json(['code' => 0]);
            }
            return json()->data(['code' => 1, 'message' => '添加失败']);
        }
    }

    public function updateBank(Request $request)
    {
        if($request->isGet()){
            $id = $request->param('id');
            $info = Db::table('bank')->where('id',$id)->find();
            return $this->render('bankedit',[
                'info' => $info,
            ]);
        }
        if($request->isPost()){
            $name = $request->post('bank_name');

            $name = trim($name);
            if (!$name) {
                return json()->data(['code' => 1, 'message' =>'银行名称不能为空！']);
            }
            $id = $request->post('id');

            $id = trim($id);
            if (!$id) {
                return json()->data(['code' => 1, 'message' =>'id名称不能为空！']);
            }
            $up_data['update_time'] = time();
            $up_data['bank_name'] = $name;
            $log = Db::table('bank')->where('id',$id)->update($up_data);
            if($log){
                LogService::write('系统设置', '用户修改银行信息');
                return json(['code' => 0]);
            }

            return json()->data(['code' => 1, 'message' => '添加失败']);
        }
    }

}
