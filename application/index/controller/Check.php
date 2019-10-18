<?php
namespace app\index\controller;

use app\index\model\Checks;

class Check
{
	
    public function Index(){

		Checks::getmagic();
		// return ;
    }



}