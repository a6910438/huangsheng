<?php


namespace app\index\command;

use think\console\Command;
use think\console\Input;
use think\console\Output;

use app\index\model\Accesscontract as Acc;
use \think\Request; 
use \think\Db; 
use EthTool\KeyStore;
use kornrunner\Keccak;


require('vendor/autoload.php');
use Web3\Web3;
use EthTool\Callback;
use Web3\Contract;
use Web3\Utils;
use Web3\Providers\HttpProvider;
use Web3\Personal;
use Web3\RequestManagers\HttpRequestManager;
use EthTool\Credential;



class Getdbbalance extends Command
{

    public $web3;

    /*public function __construct(){

    	$this->web3 = new Web3('http://154.48.247.104:8545');

    }*/

    protected function configure()
    {
        $this->setName('getdbbalance')->setDescription('Here is the remark ');
    }

    protected function execute(Input $input, Output $output)
    {
        $this->web3 = new Web3('http://154.48.247.104:8545');
		$output->writeln("TestCommand:");
	}

    public function balance($address){

        $web3 = $this->web3;

        $cb = new Callback();

        $model = new Acc();

        $contract = $model -> loadContract($web3,'Gc');//获取代币合约
        
        $balance1 = $model -> balanceOf($contract,$address);

        $balance = bcdiv($balance1,pow(10,18),18);//新查询的数额
        
        //return json(['code' => 1 , 'address' => $address , 'balance' => $balance]);        
        return json(['code' => 1 , 'balance' => $balance]);

    }
}



