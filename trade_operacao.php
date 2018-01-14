<?php

include("conexao.php");
include("trade_api.php");
include("trade_db.php");
include("trade_functions.php");

$cKey = "UPM50FNV-AQ3S9R62-6OADV6QA-00DYRQLB";
$cSecret = "ac3dd988ba2dc8c71874c24bf265c7abc6dc8bfb07639796d20271ce94c8a472f783c192e5a9aff7708c250af31fd2c04a9889518e659f0229c86655d8ea8800";
$oPoloniex = new poloniex($cKey, $cSecret);

if(isset($_POST['pair'])){
 $pair = $_POST['pair'];
 debug("info", "Iniciando operacoes com pair $pair");
}else{
 debug("warning", "Par de operacao nao definido");
 return;
}

// Realiza compras

debug("debug", "Inicializando rotina de compras...");
$ordensAberto = retornaOrdensAbertas($pair, "compra");
arrayToTable($ordensAberto, "");

if(gettype($ordensAberto) == "array" && count($ordensAberto) > 0){
 foreach($ordensAberto as $oA){
  $ret = "";
  $ret = $oPoloniex->buy($pair, $oA["valor_compra"], $oA["quantidade"]);
 
  debug("debug", "Retorno Poloniex (Compra): ");
  debug("debug", $ret);
 
  if(gettype($ret) == "array" && count($ret) > 0){
   atuNumOrdem("compra", $oA["id"], $ret["orderNumber"]);
   debug("info", "Ordem de compra efetuada: " . $pair . " a " . $oA['valor_compra'] . " quantidade: " . $oA['quantidade'] . " - Ordem: " . $ret['orderNumber']);
  }else{
   debug("warning", "Erro na compra, retorno vazio Corretora");
   debug("warning", $ret);
  }
 }
}else{
 debug("info", "Nenhuma ordem compra a executar :( ");
}

// Realiza vendas

debug("debug", "Inicializando rotina de vendas... ");
$ordensVenda = retornaOrdensAbertas($pair, "venda");
arrayToTable($ordensVenda, "");

if(gettype($ordensVenda) == "array" && count($ordensVenda) > 0){
 foreach($ordensVenda as $oV){
  $ret = "";
  $ret = $oPoloniex->sell($pair, $oV["valor_venda"], $oV["quantidade"]);
 
  debug("debug", "Retorno Poloniex (Venda): ");
  debug("debug", $ret);
 
  if(gettype($ret) == "array" && count($ret) > 0){
   atuNumOrdem("venda", $oV["id"], $ret["orderNumber"]);
   debug("info", "Ordem de venda efetuada: " . $pair . " a " . $oV['valor_venda'] . " quantidade: " . $oV['quantidade'] . " - Ordem: " . $ret['orderNumber']);
  }else{
   debug("warning", "Erro na tentativa de venda, retorno vazio Corretora");
   debug("warning", $ret);
  }
 }
}else{
 debug("info", "Nenhuma ordem venda a executar :( ");
}

// Imprime nova posicao
$ordensAberto = retornaOrdensAbertas($pair, "compra");
debug("info", "Ordens em aberto apos atualizacao:");
arrayToTable($ordensAberto, "");

// ******************** //
//  Funcoes auxiliares  //
// ******************** //

function atuNumOrdem($tipo, $id, $orderNumber){
 $sql = "update trade set ";
 if($tipo == "compra"){
  $sql .= " id_compra = '$orderNumber' ";
 }else{
  $sql .= " id_venda = '$orderNumber' ";
 }
 $sql .= " where id = $id";
 
 execQuery($sql);
}

?>