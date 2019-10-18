<?php

namespace app\common\model;

use Elliptic\EC;
use kornrunner\Keccak;
use EthTool\KeyStore;
use think\console\Input;
use think\console\Output;

use Web3\Web3;
use EthTool\Callback;
use Web3\Contract;

class GC
{

    private $web3;
    public $output;

    public function __construct()
    {
        $this->web3 = new Web3('http://154.48.247.104:8545');
    }

    // 下面的代码首先创建一个密钥对，然后再利用公钥生成账户地址
    public function newaccount()
    {
        $curl = new \service\cURL;
        $result = $curl->init('https://app.galaxycoin.vip/api/v1/dapp/new_address', [], [CURLOPT_HTTPHEADER => ['token:db923da211e1f2636e28d1d9c5cd8195c56bae5fd7d9bb037e794d9c662b8f18']]);
        if (!preg_match("/^0x[a-zA-Z0-9]{40}$/", $result)) {
            return '';
        }
        return $result;
    }

    public function newaccount_backup()
    {
        $ec = new EC('secp256k1');
        $keyPair = $ec->genKeyPair();
        $privateKey = $keyPair->getPrivate()->toString(16, 2);
        $wfn = KeyStore::save($privateKey, '', ROOT_PATH . '/ethtool/blockchain/keystore');
        $publicKey = $keyPair->getPublic()->encode('hex');
        $address = '0x' . substr(Keccak::hash(substr(hex2bin($publicKey), 1), 256), 24);
        return $address;
        exit;
    }

    public function balance($address): array
    {
        $this->output = new Output;
        $curl = new \service\cURL;
        $balance_ok = $curl->init('https://app.galaxycoin.vip/api/v1/dapp/balance?address=' . $address, [], [CURLOPT_HTTPHEADER => ['token:db923da211e1f2636e28d1d9c5cd8195c56bae5fd7d9bb037e794d9c662b8f18']]);

        if (!empty($balance_ok)) {
            $str = explode('"', $balance_ok);
            $number = $str[1];
            return ['code' => 1, 'balance' => round($number, 4)];
        }
        return ['code' => 0];
    }

    public function balance_backup($address): array
    {
        $contract = $this->loadContract('Gc');//获取代币合约
        $balance = $this->balanceOf($contract, $address);
        if (empty($balance)) {
            return ['code' => 0];
        };
        $balance_ok = bcdiv($balance, pow(10, 18), 10);//新查询的数额
        return ['code' => 1, 'balance' => $balance_ok];
    }

    private function loadContract($artifact)
    {
        $path = ROOT_PATH . 'contract/build/' . $artifact;
        $abi = file_get_contents($path . '.abi');
        $addr = file_get_contents($path . '.addr');
        $contract = new Contract($this->web3->provider, $abi);
        $contract->at($addr);
        return $contract;
    }

    private function balanceOf($contract, $address)
    {
        $cb = new Callback;
        $opts = [];
        $contract->call('balanceOf', $address, $cb);
        if ($cb->error) {
            return 0;
        }
        return $cb->result['balance']->toString();
    }

}