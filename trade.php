<?php

include("conexao.php");
include("trade_api.php");
include("trade_db.php");
include("trade_functions.php");

// Recebe variáveis por parametro
$function = $_GET["function"];

$cKey = " ### API POLONIEX ### ";
$cSecret = " ### SENHA API POLONIEX ### ";
$oPoloniex = new poloniex($cKey, $cSecret);
$pair = "";

$lucroParc = 1; // Lucro desejado a cada operação em %
$qtdNegocios = 10; // Define quantos negócios quero fazer com meu orçamento
$nPercEMA = 0.05; // Percentual para compra abaixo da media historica
$compraCotacaoAtual = false; // Define se compra na cotação atual, independente de já possuir posição.
$periodoCandle = 30 * 60; // Periodo de cada Candle em segundos
$executarCompra = false;
$executarVenda = false;
$startMedias = false;
$percOrcamento = 0;

// Recebe variaveis do post - formulario
if(isset($_POST['compraCotacaoAtual'])){
 $compraCotacaoAtual = true;
}

if(isset($_POST['executarCompra'])){
 $executarCompra = true;
}

if(isset($_POST['executarVenda'])){
 $executarVenda = true;
}

if(isset($_POST['startMedias'])){
 $startMedias = true;
}

if(isset($_POST['pair'])){
 $pair = $_POST['pair'];
}else{
 debug("warning", "Moeda nao definida");
 return;
}

if(isset($_POST['lucroParc'])){
 $lucroParc = $_POST['lucroParc'];
}

if(isset($_POST['qtdNegocios'])){
 $qtdNegocios = $_POST['qtdNegocios'];
}

if(isset($_POST['percEMA'])){
 $nPercEMA = $_POST['percEMA'];
}

if(isset($_POST['orcamento'])){
 $percOrcamento = $_POST['orcamento'];
}

if(isset($function)){
	if($function == "grafico"){
		return $oPoloniex->get_chart_data_EMA($pair, $periodoCandle);		
	}
}

debug("info", "Ultima atualização: " . date('F j, Y, g:i a'));

// Atualiza as ordens de compra e venda
atualizaOrdensCompra($pair);
atualizaOrdensVenda($pair);

// Limpa ordens não executadas
limpaOrdensNaoExecutadas($pair);

$orcamento = $oPoloniex->get_budget($pair) * $percOrcamento / 100; // Orçamento em BTC
$aTicker = $oPoloniex->get_ticker($pair);
$cotacao = null;
$cotacao = $aTicker["last"];
$periodosEMA = 20; // Estou solicitando uma EMA de 20 periodos (Quantos fechamentos vou analisar para fazer a EMA)
$EMA = $oPoloniex->get_SMA($pair, $periodoCandle, $periodosEMA); // Media historica
$EMA72 = $oPoloniex->get_SMA($pair, $periodoCandle, 72); // Media historica
$emTeste = 0;

// Proteção para o caso de problema na Poloniex
if($cotacao == null || $cotacao <= 0){
	debug("warning", "Deu pau no retriave da Poloniex, parando o sistema, tente mais tarde :(");
	return;
}

// Verifica se tenho posição:
$posicao = retornaPosicao($pair);
$qtdPosicao = gettype($posicao) == "array" ? count($posicao) : 0;
//debug("debug","Posição");
//arrayToTable($posicao);

// Define ultima compra de proteção de acordo com a EMA
$ultimaCompra = $EMA * (1 - $nPercEMA);

// Define o valor de cada lote (de acordo com a quantidade de negócios que quero fazer)
$valorLote = $orcamento / ($qtdNegocios - $qtdPosicao);

// Valor escada (diferença de cotação entre uma compra e outra)
if(($qtdNegocios - $qtdPosicao - 1) == 0){
 $escada = 0;
}else{
 $escada = ($cotacao - $ultimaCompra) / ($qtdNegocios - $qtdPosicao - 1);
}

// Verifica horizonte das medias moveis
if($startMedias){
 if($EMA < $EMA72)
  $startMedias = false;
}else{
 $startMedias = true;
}

debug("info", "<b>Parametros:</b>");
debug("info", "Orçamento BTC: $orcamento");
debug("debug", "Lucro por parcela: $lucroParc %");
debug("debug", "Percentual de compra abaixo da EMA: $nPercEMA %");
debug("debug", "Quantidade de negócios de quero fazer com o orçamento: $qtdNegocios");
debug("info", "Quantidade de negocios existentes: $qtdPosicao");

debug("info", "<b>Mercado:</b>");
debug("debug", "Par negociado: $pair");
debug("info", "Cotação : $cotacao");
print("EMA($periodosEMA) : " . round8($EMA) . " - "); if($cotacao > $EMA ? print('Alta') : print('Baixa'));
print("<br>");
print("EMA(72) : " . round8($EMA72) . " - "); if($cotacao > $EMA72 ? print('Alta') : print('Baixa'));
print("<br>");
print("Tendencia principal de ");
if($EMA < $EMA72 ? print("baixa") : print("alta"));

debug("info", "<br><b>Cálculos:</b>");
debug("info", "Última compra: $ultimaCompra");
debug("info", "Valor de cada lote: " . round8($valorLote) . " " . substr($pair, 0, 3));
debug("info", "Escada: " . round8($escada));
if($valorLote > 0)
 debug("info", "Percentual de escada em relacao ao valor medio das compras: " . round($escada / (($cotacao + $ultimaCompra)/2) * 100, 2) . "% \n");

echo "<hr>";

debug("debug", "Inicia operações:");

//if (count($posicao) > 1){
	
	// Vende posição que está com lucro	
	if($executarVenda){
		foreach($posicao as $pos){
		 if($cotacao - $pos["valor_compra"] >= $pos["valor_compra"] * $lucroParc / 100){
				gravaIscaVenda($pos["id_compra"], $pos["valor_compra"], $cotacao, $pos["quantidade"]);
				debug("info", "<h1 style='color=green'>Vendendo posicao com lucro, na cotacao atual </h1>");
				debug("info", "Vende id: " . $pos["id_compra"] . " Compra: " . $pos["valor_compra"] . " a: " . $cotacao);
			}else{
			 $valorVendaComLucro = $pos["valor_compra"] + ($pos["valor_compra"] * $lucroParc / 100);
			 gravaIscaVenda($pos["id_compra"], $pos["valor_compra"], $valorVendaComLucro, $pos["quantidade"]);
			 debug("info", "<b style='color:green'>Adicionando ordem de venda com lucro definido...</b>");
			 debug("info", "Vende id: " . $pos["id_compra"] . " Compra: " . $pos["valor_compra"] . " a: " . $valorVendaComLucro);
			}
		}
	}else{
		debug("info", "Venda desabilitada");
	}
 // Adiciona iscas para baixo:
 ordensParaBaixo($qtdNegocios - count($posicao));				

/*
	eliminei o if para sempre vender, 
	independente da qtd de posicoes
} else{
	debug("info","<ht><b>Caso não tenha posição: </b>");
	debug("info", "<b>Iscas para baixo: </b>");
	
	// Adiciona iscas para baixo:
	ordensParaBaixo($qtdNegocios);
}
*/

// Mostra Orcamento Utilizado
debug("info", "Orcamento utilizado:");
arrayToTable(retornaOrcamentoUtilizado($pair), "");

// Mostra nova posição
$carteira = retornaCarteira($pair);
debug("info", "Carteira:");
arrayToTable($carteira, "carteira");

// ******************** //
//  Funcoes auxiliares  //
// ******************** //

// Limpa ordens nao executadas, para que sejam colocadas novas
function limpaOrdensNaoExecutadas($pair){
	
	global $oPoloniex;

	$ordens = $oPoloniex->get_open_orders($pair);
	
	debug("debug", "Ordens");
	debug("debug", $ordens);
	
	if(gettype($ordens) == "array" && count($ordens) > 0){
		foreach($ordens as $ordem){
			$ret = $oPoloniex->cancel_order($pair, $ordem['orderNumber']);
			
			$type = "compra";
			if($ordem['type'] == "sell"){
			 $type = "venda";
			}
			
			debug("debug", "Retorno da Poloniex sobre exclusao da ordem " . $ordem['orderNumber']);
			debug("debug", $ret);
			
			if($ret['success'] == 1){
				debug("info", "Realizando a limpeza da ordem de $type " . $ordem['orderNumber'] . " ...");
				limpaIsca($pair, $ordem['orderNumber'], "compra");
			}
		}
	}
	// Rotina para limpar iscas que não foram colocadas
	limpaIscas();
}

// Atualiza ordens de Compra
function atualizaOrdensCompra($pair){
	
	global $oPoloniex;
	
	$ordens = retornaCompraNaoExecutda($pair);
	
	// Percorre as ordens do banco de dados
	foreach($ordens as $ordem){
		$ret = $oPoloniex->get_order_trades($ordem['id_compra']);
		
		debug("debug", "<h1> Traders " . $ordem['id_compra'] . "</h1>");
		debug("debug", $ret);
		
		$quantidadeTraders = 0;
		$valorSomado = 0; // Sera utilizado para fazer o preco medio da ordem
		$quantidadeMoedas = 0;
		$dataUltimoTrade;
		$atualizarOrdem = false;
		
		// Percorre trades de uma ordem
		foreach($ret as $value){
			
			// Verifica se a ordem possue traders, caso não possua, a corretora volta um texto de erro
			if(gettype($value) == "array"){ 
				debug("info","Processar a ordem " . $ordem['id_compra'] . ", ela deu certo");
												
				$dataUltimoTrade = $value['date'];
				$valorSomado += $value['rate'];
				$quantidadeMoedas += $value['amount'];
				$quantidadeTraders++;
				$atualizarOrdem = true;
				
			}else{
				debug("debug", "A ordem de compra <b>" . $ordem['id_compra'] . "</b> aparentemente não foi executada, será excluída, erro: <b>" . $value . "</b>");
			}
		}
		if($atualizarOrdem){
			$precoMedio = ($valorSomado / $quantidadeTraders);
			atualizaOrdemCompra($ordem['id_compra'], $dataUltimoTrade, $precoMedio, $quantidadeMoedas);
			
			notifica("Compra de $pair", "Ordem: " . $ordem['id_compra'] . " Preço: $precoMedio Quantidade: $quantidadeMoedas");			
		}
	}		
}

// Atualiza ordens de Venda
function atualizaOrdensVenda($pair){
	
	global $oPoloniex;
	
	$ordens = retornaVendaNaoExecutda($pair);
	
	// Percorre as ordens do banco de dados
	foreach($ordens as $ordem){
		$ret = $oPoloniex->get_order_trades($ordem['id_venda']);
		
		debug("debug", "<h1> Traders " . $ordem['id_venda'] . "</h1>");
		debug("debug", $ret);
		
		$quantidadeTraders = 0;
		$valorSomado = 0; // Sera utilizado para fazer o preco medio da ordem
		$quantidadeMoedas = 0;
		$dataUltimoTrade;
		$atualizarOrdem = false;
		
		// Percorre trades de uma ordem
		foreach($ret as $value){
			
			// Verifica se a ordem possue traders, caso não possua, a corretora volta um texto de erro
			if(gettype($value) == "array"){ 
				debug("info","Processar a ordem " . $ordem['id_venda'] . ", ela deu certo");
												
				$dataUltimoTrade = $value['date'];
				$valorSomado += $value['rate'];
				$quantidadeMoedas += $value['amount'];
				$quantidadeTraders++;
				$atualizarOrdem = true;
				
			}else{
				debug("debug", "A ordem de venda <b>" . $ordem['id_venda'] . "</b> aparentemente não foi executada, será excluída, erro: <b>" . $value . "</b>");
			}
		}
		if($atualizarOrdem){
			$precoMedio = ($valorSomado / $quantidadeTraders);
			$resultado = ($precoMedio - $ordem['valor_compra']) * $ordem['quantidade'];
			atualizaOrdemVenda($ordem['id_venda'], $dataUltimoTrade, $precoMedio, $resultado);
		}
	}		
	//debug("debug", $oPoloniex->get_my_trade_history($pair));
}

function ordensParaBaixo($qtd){
	
	global $cotacao, $ultimaCompra, $escada, $pair, $qtdNegocios, $valorLote, $compraCotacaoAtual, $orcamento;
	global $emTeste, $executarCompra, $startMedias;

	if($executarCompra && $startMedias)	{	
		$isca;	
		
		if($emTeste == 1){
			$qtd = 2;
		}
		
		// Filtra lotes com valores inferior a 0.002
		if($valorLote > 0.002){
			if($qtd == $qtdNegocios || $compraCotacaoAtual){
				$isca[0]["moeda"] = $pair;
				$isca[0]["valor_compra"] = round8($cotacao);
				$isca[0]["quantidade"] = round8($valorLote / $cotacao);
			}else{
				$isca[0]["moeda"] = $pair;
				$isca[0]["valor_compra"] = round8($cotacao - (($qtdNegocios - $qtd) * $escada));
				$isca[0]["quantidade"] = round8($valorLote / $isca[0]["valor_compra"]);
			}
			
			$isca[0]["total"] = $isca[0]["valor_compra"] * $isca[0]["quantidade"];
			$total = $isca[0]["total"];
			
			for ($i = 1; $i < $qtd; $i++){								
				$isca[$i]["moeda"] = $pair;
				$isca[$i]["valor_compra"] = round8($isca[$i - 1]["valor_compra"] - $escada);
				$isca[$i]["quantidade"] = round8($valorLote / $isca[$i]["valor_compra"]);
				$isca[$i]["total"] = $isca[$i]["valor_compra"] * $isca[$i]["quantidade"];
				$total += $isca[$i]["total"];
				if($total > $orcamento){
				 debug("debug", "Total maior que orcamento, deletando item $i do array de compras");
				 $total -= $isca[$i]["total"];
				 unset($isca[$i]);
				}
			}	
			debug("fuck", "Isca:");
			debug("fuck", $isca);
			debug("fuck", "Total = $total");
			debug("fuck", $orcamento - $total);
			debug("fuck", "Orcamento = $orcamento");
			
			if(gettype($isca) == "array" && count($isca) > 0){
			 debug("fuck", "Gravando iscas");
				gravaIsca($isca);
			}else{
				debug("debug", "Nao foi possivel gerar novas ordens, quantidade maxima atingida. You Rock!");
			}	
		}else{
			debug("info", "Valor do lote inferior a 0.002 ($valorLote)");
		}
	}else{
		debug("info", "Compra desabilitada");
	}
}

?>