<?php
namespace EthTool;

class Callback{
  function __invoke($error,$result){
    // if($error) throw $error;
    $this->error = $error;
    $this->result = $result;
  }
}

?>
