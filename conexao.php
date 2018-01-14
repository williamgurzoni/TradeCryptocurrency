<?php

error_reporting (E_ALL & ~ E_NOTICE & ~ E_DEPRECATED);

function conecta(){

	$dbHost	= " ### INFORMAR HOST DB ### ";
	$dbName	= " ### INFORMAR NOME DB ### ";
	$dbUser	= " ### INFORMAR USUARIO ### ";
	$dbPass	= " ### INFORMAR SENHA   ### ";

	$conexao = mysql_connect($dbHost, $dbUser, $dbPass); 
	if (!$conexao) {
		die('Não foi possível conectar: ' . mysql_error());
	} else{
		//echo  'Conexão bem sucedida';
		mysql_select_db($dbName);
	}
	return $conexao;
}
?>