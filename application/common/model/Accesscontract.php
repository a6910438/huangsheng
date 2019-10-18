<?php
namespace app\common\model;

use think\Db;
// use app\index\controller\Accesscontract;

use Web3\Contract;
use Web3\Web3;
use EthTool\Callback;
use Web3\Utils;


class Accesscontract
{
	
	function loadContract($web3,$artifact){
		$dir = './contract/build/';
		$abi = file_get_contents($dir . $artifact . '.abi');
		$addr = file_get_contents($dir . $artifact . '.addr');
		$contract = new Contract($web3->provider,$abi);
		$contract->at($addr);
		return $contract;
	}

	  
	function balanceOf($contract,$account){
		// echo $account . PHP_EOL;
		$cb = new Callback;
		$opts = [];
		$contract->call('balanceOf',$account,$opts,$cb);
		return $cb->result['balance']->toString();
	}

	
}	