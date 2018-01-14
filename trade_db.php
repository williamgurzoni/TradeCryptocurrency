<?php

function gravaIsca($isca){
	
	//limpaIscas();
	
	foreach($isca as $a){
		$query[] = "('" . $a['moeda'] . "', '" . $a['valor_compra'] . "', '" . $a['quantidade']	. "')";
	}	
	
	$sql = "insert into trade (moeda, valor_compra, quantidade) values " . implode(',', $query);
	
	execQuery($sql);
}

function gravaIscaVenda($idCompra, $valor_compra, $valor_venda, $qtd){
	$sql = "update trade set";
	$sql .= " valor_venda = " . $valor_venda . ", resultado = " . (($valor_venda - $valor_compra) * $qtd);
	$sql .= " where id_compra = " . $idCompra;
	
	execQuery($sql);	
}

function limpaIscas(){

 // Limpa ordens de compra nao executadas
	$sql = "delete from trade where data_compra is null and (id_compra is null or id_compra = '')";
	execQuery($sql);
	
	// Limpa ordens de venda nao executadas
	$sql = "update trade set valor_venda = null, resultado = null where id_venda is null";
	execQuery($sql);
}

function limpaIsca($pair, $orderNumber, $tipo){
	if($tipo == "compra"){
		$sql = "delete from trade where moeda = '$pair' and id_compra = '$orderNumber'";
		echo $sql;
	}else{
		$sql = "update trade set id_venda = null, valor_venda = null, resultado = null where moeda = '$pair' and id_venda = '$orderNumber'";
	}	
	execQuery($sql);
}

// Atualiza ordem de compra no Banco de dados com base na corretora
function atualizaOrdemCompra($orderNumber, $data_compra, $valor_compra, $quantidade){
	$sql = "update trade set";
	$sql .= " data_compra = '" . $data_compra . "'";
	$sql .= ", valor_compra = " . $valor_compra;
	$sql .= ", quantidade = " . $quantidade;
	$sql .= ", timestamp_compra = '" . convert_datetime($data_compra) . "'";
	$sql .= " where id_compra = '" . $orderNumber . "'";

	execQuery($sql);
}

// Atualiza ordem de venda no Banco de dados com base na corretora
function atualizaOrdemVenda($orderNumber, $data_venda, $valor_venda, $resultado){
	$sql = "update trade set";
	$sql .= " data_venda = '" . $data_venda . "'";
	$sql .= ", valor_venda = " . $valor_venda;
	$sql .= ", timestamp_venda = '" . convert_datetime($data_venda) . "'";
	$sql .= ", resultado = " . $resultado;
	$sql .= " where id_venda = '" . $orderNumber . "'";

	execQuery($sql);
}

// Retorna ordem não executada (Compra nao realizada)
function retornaCompraNaoExecutda($pair){
	$sql = "select * from trade where moeda = '$pair' and data_compra is null and id_compra is not null";
	return execConsulta($sql);
}

// Retorna ordem não executada (Venda nao realizada)
function retornaVendaNaoExecutda($pair){
	$sql = "select * from trade where moeda = '$pair' and data_venda is null and id_venda is not null";
	return execConsulta($sql);
}

// Retorna posições em aberto, sem venda
function retornaPosicao($pair){	
	$sql = "select * from trade where moeda = '$pair' and id_compra is not null and data_venda is null and data_compra is not null";
	return execConsulta($sql);
}

// Retorna carteira
function retornaCarteira($pair){	
	$sql = "select moeda, id_compra, valor_compra, quantidade, id_venda, valor_venda, data_venda, resultado from trade where moeda = '$pair' order by id_venda, valor_compra";
	return execConsulta($sql);
}

// Retorna ordens ainda não executadas
function retornaOrdensAbertas($pair, $tipo){	
	$sql = "select * from trade where moeda = '$pair' ";
	if($tipo == "compra"){
	 $sql .= " and data_compra is null and id_compra is null"; // adicionei a condicao de id_compra
	}else{
	 $sql .= " and id_compra is not null and id_venda is null and valor_venda is not null";
	}
	return execConsulta($sql);
}

function retornaOrcamentoUtilizado($pair){
	$sql = "select sum(valor_compra * quantidade) as soma from trade where data_venda is null and moeda = '$pair'";
	return execConsulta($sql);
}

function execQuery($sql){
	
	debug("fuck", $sql);
	
	$conexao = conecta();
	if (!mysql_query($sql)){		
		echo "Deu pau: ". mysql_error();
	}
	
	mysql_close($conexao);  //Esta linha está gerando erro - verificar para não deixar a conexao em aberto
}

function execConsulta($sql){		
	
	$conexao = conecta();
	
	// Variavel de retorno
	$aRet = array();
	
	// Resultado da query
	$result = mysql_query($sql); 

	// Verifica se tenho algum erro do banco
	if($result === FALSE) { 
		die(mysql_error()); 
	}
	
	$ind = 0;
	while($row = mysql_fetch_array($result, MYSQL_ASSOC)) {
		
		$aRet[$ind] = Array("id" => $ind);
		
		foreach($row as $key => $value){
			$aRet[$ind][$key] = utf8_encode($value);
		}
		$ind++;
	}
	
	mysql_close($conexao); 
	
	return $aRet;
}
?>