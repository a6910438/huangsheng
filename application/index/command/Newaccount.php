<?php
namespace app\index\command;

use think\console\Command;
use think\console\Input;
use think\console\Output;

use app\index\model\User;
use app\index\model\Apply;
use \think\Request; 
use \think\Db; 
use Elliptic\EC;
use kornrunner\Keccak;
use EthTool\KeyStore;

use Web3\Web3;
use EthTool\Callback;
use Web3\Contract;
use Web3\Utils;
use Web3\Providers\HttpProvider;
use Web3\RequestManagers\HttpRequestManager;



class Newaccount extends Command
{

	public $web3;

    /*public function __construct(){

		parent::__construct();
    	$this->web3 = new Web3('http://154.48.247.104:8545');

	}*/
	
	protected function configure()
    {
        $this->setName('newaccount')->setDescription('Here is the remark ');
    }

    protected function execute(Input $input, Output $output)
    {
		$this->web3 = new Web3('http://154.48.247.104:8545');
		$output->writeln("TestCommand:");
		$this->index();
	}

	
	// 下面的代码首先创建一个密钥对，然后再利用公钥生成账户地址
    public function Index(){

    	
    	$web3 = $this->web3;

    	$cb = new Callback();

    	$ec = new EC('secp256k1');

		$keyPair = $ec->genKeyPair();

		$privateKey = $keyPair->getPrivate()->toString(16,2);

		$wfn = KeyStore::save($privateKey,'','/ethtool/blockchain/keystore');

		$publicKey = $keyPair->getPublic()->encode('hex');

		$address = '0x' . substr(Keccak::hash(substr(hex2bin($publicKey),1),256),24);
		
		return $address;
		
		exit;

		
		
    }



}