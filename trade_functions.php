<?php

function round8($par){
	return round($par, 8);
}

function arrayToTable($arr, $type){
	echo "<div class='table-responsive' style='font-size:9px'><table class='table table-bordered table-hover'>";
	if($type == "carteira"){
		echo "<thead><tr>";		
		echo "<td>#</td>";
		echo "<td>Moeda</td>";		
		echo "<td>Id Compra</td>";	
		echo "<td>Valor Compra</td>";
		echo "<td>Quantidade</td>";
		echo "<td>Id Venda</td>";
		echo "<td>Valor Venda</td>";
		echo "<td>Resultado</td>";
		echo "</tr></thead>";		
	}
	echo "<tbody>";
	foreach ($arr as $row) {
	   echo "<tr>";
	   foreach ($row as $column) {
		  echo "<td>$column</td>";
	   }
	   echo "</tr>";
	}    
	echo "</tbody>";
	echo "</table></div>";
}

// Converte data em timestamp YYYY-MM-DD HH:MM:SS
function convert_datetime($str) {
 
 list($date, $time) = explode(' ', $str);
 list($year, $month, $day) = explode('-', $date);
 list($hour, $minute, $second) = explode(':', $time);
 
 $timestamp = mktime($hour, $minute, $second, $month, $day, $year);
 
 return $timestamp;
}

function debug($type, $value){
	
	$info = true;
	$debug = true;
	$warning = true;
	$fuck = true;
	$ativo = false; // Não alterar
	
	if($type == "info" && $info)
		$ativo = true;
	else if($type == "debug" && $debug)
		$ativo = true;
	else if($type == "warning" && $warning)
		$ativo = true;
	else if($type == "fuck" && $fuck)
		$ativo = true;
	
	if($ativo){
		
		//echo "<pre>";
		
		if(gettype($value) == "array"){
			print_r($value);
		}else{
			echo $value;
		}
		echo "<br>";
		//echo "</pre>";
	}
}

function notifica($subject, $body){
	
	$subject = "[TRADE] " + $subject;
	$to = " ## EMAIL ### ";
	
	//mail($to, $subject, $body);
}
?>