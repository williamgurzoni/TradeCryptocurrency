<?php

	class poloniex {
		protected $api_key;
		protected $api_secret;
		protected $trading_url = "https://poloniex.com/tradingApi";
		protected $public_url = "https://poloniex.com/public";
		
		public function __construct($api_key, $api_secret) {
			$this->api_key = $api_key;
			$this->api_secret = $api_secret;
		}
			
		private function query(array $req = array()) {
			// API settings
			$key = $this->api_key;
			$secret = $this->api_secret;
		 
			// generate a nonce to avoid problems with 32bit systems
			$mt = explode(' ', microtime());
			$req['nonce'] = $mt[1].substr($mt[0], 2, 6);
		 
			// generate the POST data string
			$post_data = http_build_query($req, '', '&');
			$sign = hash_hmac('sha512', $post_data, $secret);
		 
			// generate the extra headers
			$headers = array(
				'Key: '.$key,
				'Sign: '.$sign,
			);

			// curl handle (initialize if required)
			static $ch = null;
			if (is_null($ch)) {
				$ch = curl_init();
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
				curl_setopt($ch, CURLOPT_USERAGENT, 
					'Mozilla/4.0 (compatible; Poloniex PHP bot; '.php_uname('a').'; PHP/'.phpversion().')'
				);
			}
			curl_setopt($ch, CURLOPT_URL, $this->trading_url);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
			curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);

			// run the query
			$res = curl_exec($ch);

			if ($res === false) throw new Exception('Curl error: '.curl_error($ch));
			//echo "RES: " . $res;
			$dec = json_decode($res, true);
			if (!$dec){
				//throw new Exception('Invalid data: '.$res);
				//echo "ERRO API" . $res;
				return false;
			}else{
				return $dec;
			}
		}
		
		protected function retrieveJSON($URL) {
			$opts = array('http' =>
				array(
					'method'  => 'GET',
					'timeout' => 40 
				)
			);
			$context = stream_context_create($opts);
			$feed = file_get_contents($URL, false, $context);
			$json = json_decode($feed, true);
			return $json;
		}
		
		public function get_balances() {
			return $this->query( 
				array(
					'command' => 'returnBalances'
				)
			);
		}
		
		// Eu fiz esse - retorna o orcamento que tenho em determinada moeda
		public function get_budget($pair){
		 $pair = substr($pair, 0, 3); 
		 $balances = $this->get_balances();
		 return $balances[$pair];
		}
		
		public function get_account_balances() {
			return $this->query( 
				array(
					'command' => 'returnAvailableAccountBalances'
				)
			);
		}
		
		public function get_open_orders($pair) {		
			return $this->query( 
				array(
					'command' => 'returnOpenOrders',
					'currencyPair' => strtoupper($pair)
				)
			);
		}
		
		public function get_my_trade_history($pair) {
			return $this->query(
				array(
					'command' => 'returnTradeHistory',
					'currencyPair' => strtoupper($pair),
					'start' => '1488326400',
					'end' => '99999999999'
				)
			);
		}
		
		public function buy($pair, $rate, $amount) {
			return $this->query( 
				array(
					'command' => 'buy',	
					'currencyPair' => strtoupper($pair),
					'rate' => $rate,
					'amount' => $amount
				)
			);
		}
		
		public function sell($pair, $rate, $amount) {
			return $this->query( 
				array(
					'command' => 'sell',	
					'currencyPair' => strtoupper($pair),
					'rate' => $rate,
					'amount' => $amount
				)
			);
		}
		
		public function cancel_order($pair, $order_number) {
			return $this->query( 
				array(
					'command' => 'cancelOrder',	
					'currencyPair' => strtoupper($pair),
					'orderNumber' => $order_number
				)
			);
		}
		
		public function withdraw($currency, $amount, $address) {
			return $this->query( 
				array(
					'command' => 'withdraw',	
					'currency' => strtoupper($currency),				
					'amount' => $amount,
					'address' => $address
				)
			);
		}
		
		// Eu fiz esse
		public function get_order_trades($orderNumber){
			return $this->query(
				array(
					'command' => 'returnOrderTrades',
					'orderNumber' => $orderNumber
				)
			);
		}
		
		public function get_trade_history($pair) {
			$trades = $this->retrieveJSON($this->public_url.'?command=returnTradeHistory&currencyPair='.strtoupper($pair));
			return $trades;
		}
		
		public function get_order_book($pair) {
			$orders = $this->retrieveJSON($this->public_url.'?command=returnOrderBook&currencyPair='.strtoupper($pair));
			return $orders;
		}
		
		public function get_volume() {
			$volume = $this->retrieveJSON($this->public_url.'?command=return24hVolume');
			return $volume;
		}
	
		public function get_ticker($pair = "ALL") {
			$pair = strtoupper($pair);
			$prices = $this->retrieveJSON($this->public_url.'?command=returnTicker');
			if($pair == "ALL"){
				return $prices;
			}else{
				$pair = strtoupper($pair);
				if(isset($prices[$pair])){
					return $prices[$pair];
				}else{
					return array();
				}
			}
		}
		
		public function get_trading_pairs() {
			$tickers = $this->retrieveJSON($this->public_url.'?command=returnTicker');
			return array_keys($tickers);
		}
		
		public function get_total_btc_balance() {
			$balances = $this->get_balances();
			$prices = $this->get_ticker();
			
			$tot_btc = 0;
			
			foreach($balances as $coin => $amount){
				$pair = "BTC_".strtoupper($coin);
			
				// convert coin balances to btc value
				if($amount > 0){
					if($coin != "BTC"){
						$tot_btc += $amount * $prices[$pair];
					}else{
						$tot_btc += $amount;
					}
				}

				// process open orders as well
				if($coin != "BTC"){
					$open_orders = $this->get_open_orders($pair);
					foreach($open_orders as $order){
						if($order['type'] == 'buy'){
							$tot_btc += $order['total'];
						}elseif($order['type'] == 'sell'){
							$tot_btc += $order['amount'] * $prices[$pair];
						}
					}
				}
			}

			return $tot_btc;
		}
		
		public function get_chart_data($pair, $periodoCandle, $start, $end){			
			return $this->retrieveJSON($this->public_url.'?command=returnChartData&currencyPair=' . $pair . '&start='. $start .'&end='. $end .'&period=' . $periodoCandle);			
		}
		
		public function get_char_data_csv($pair, $periodoCandle){
			$periodosParaAnalise = 72; // Estou informando que quero 200 períodos para análise (Vou usar essa quantidade apenas para o grafico)
			$date = new DateTime();
			$end = $date->getTimestamp();					
			$start = $end - ($periodoCandle * $periodosParaAnalise); 
			
			$dados = $this->get_chart_data($pair, $periodoCandle, $start, $end);
			
			$dados_array = "\"[['Data', 'Cotacao']";
			
			//Ordem do CSV: Date,Open,High,Low,Close,Volume
			/* Ordem que tenho
			[date] => 1496246400
            [high] => 0.09980999
            [low] => 0.09715001
            [open] => 0.09962181
            [close] => 0.09796044
            [volume] => 2262.26356025
            [quoteVolume] => 22974.09532393
            [weightedAverage] => 0.09847019
			*/
			
			foreach($dados as $d){
				//$dados_csv .= date('Y-m-d\TH:i:s\Z',$d["date"]) . "," . $d["open"] . "," . $d["high"] . "," . $d["low"] . "," . $d["close"] . "," . $d["volume"] . "\n";
				$dados_array .= ",['" . date('m-d H:i',$d["date"]) . "'," . $d["close"] . "]";
			}
			$dados_array .= "]\"";
						
			echo $dados_array;
		}
		
		public function get_chart_data_EMA($pair, $periodoCandle){
			$periodosParaAnalise = 72 * 2; // Estou informando que quero 200 períodos para análise (Vou usar essa quantidade apenas para o grafico)
			$date = new DateTime();
			$end = $date->getTimestamp();					
			$start = $end - ($periodoCandle * $periodosParaAnalise); 
			
			$dados = $this->get_chart_data($pair, $periodoCandle, $start, $end);
			
			$dados_array = "\"[['Data', 'Cotacao', 'EMA(20)', 'EMA(72)']";
		
			// Calculo da ema 20
			// Verifica a primeira SMA - Pega os primeiros registros 
			for($i = 0; $i < 20; $i++){
				$primeiraSMA += $dados[$i]["close"];
				$dados[$i]["EMA20"] = 0;
			}
			
			// Grava a primeira SMA (Media simples)
			$dados[20]["EMA20"] = $primeiraSMA / 20;
			
			// Realiza a gravação da EMA20
			for($i = 20 + 1; $i < $periodosParaAnalise; $i++){
				$dados[$i]["EMA20"] = ($dados[$i]["close"] - $dados[$i - 1]["EMA20"]) * (2 / (20 + 1)) + $dados[$i - 1]["EMA20"];
			}		
			
			// Calculo da ema 72
			// Verifica a primeira SMA - Pega os primeiros registros 
			$primeiraSMA = 0;
			for($i = 0; $i < 72; $i++){
				$primeiraSMA += $dados[$i]["close"];
				$dados[$i]["EMA72"] = 0;
			}
			
			// Grava a primeira SMA (Media simples)
			$dados[72]["EMA72"] = $primeiraSMA / 72;
			
			// Realiza a gravação da EMA72
			for($i = 72 + 1; $i < $periodosParaAnalise; $i++){
				$dados[$i]["EMA72"] = ($dados[$i]["close"] - $dados[$i - 1]["EMA72"]) * (2 / (72 + 1)) + $dados[$i - 1]["EMA72"];
			}		
			
			for($i = 0; $i < 72; $i++){
				unset($dados[$i]);
			}
			
			foreach($dados as $d){
				$dados_array .= ",['" . date('m-d H:i',$d["date"]) . "'," . $d["close"] . "," . $d["EMA20"] . "," . $d["EMA72"] . "]";
			}
			$dados_array .= "]\"";
						
			echo $dados_array;
		}
		
		public function get_SMA($pair, $periodoCandle, $periodosEMA){
			$periodosParaAnalise = 2 * $periodosEMA; // Estou informando que quero 200 períodos para análise (Vou usar essa quantidade apenas para o grafico)
			$primeiraSMA = 0;
			$date = new DateTime();
			$end = $date->getTimestamp();					
			$start = $end - ($periodoCandle * $periodosParaAnalise); 
			
			// Dados do grafico
			$dados = $this->get_chart_data($pair, $periodoCandle, $start, $end);																
			
			// Verifica a primeira SMA - Pega os primeiros registros 
			for($i = 0; $i < $periodosEMA; $i++){
				$primeiraSMA += $dados[$i]["close"];
				$dados[$i]["EMA"] = 0;
			}
	
			// Grava a primeira SMA (Media simples)
			$dados[$periodosEMA]["EMA"] = $primeiraSMA / $periodosEMA;
			
			// Realiza a gravação das EMAs
			for($i = $periodosEMA + 1; $i < $periodosParaAnalise; $i++){
				$dados[$i]["EMA"] = ($dados[$i]["close"] - $dados[$i - 1]["EMA"]) * (2 / ($periodosEMA + 1)) + $dados[$i - 1]["EMA"];
			}			
			
			//echo "<pre>";			
			//print_r($dados);
			
			return $dados[$periodosParaAnalise - 1]["EMA"];
		}
	}
	
	
	
?>