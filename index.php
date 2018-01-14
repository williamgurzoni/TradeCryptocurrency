<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="">
    <meta name="author" content="">

    <title>Trade cryptocurrency</title>

    <!-- Bootstrap Core CSS -->
    <link href="css/bootstrap.min.css" rel="stylesheet">

    <!-- Custom CSS -->
    <link href="css/style.css" rel="stylesheet">
	
</head>

<body>

    <!-- Navigation -->
    <nav class="navbar navbar-inverse navbar-fixed-top" role="navigation">
        <div class="container">
            <!-- Brand and toggle get grouped for better mobile display -->
            <div class="navbar-header">
                <button type="button" class="navbar-toggle" data-toggle="collapse" data-target="#bs-example-navbar-collapse-1">
                    <span class="sr-only">Toggle navigation</span>
                    <span class="icon-bar"></span>
                    <span class="icon-bar"></span>
                    <span class="icon-bar"></span>
                </button>
                <a class="navbar-brand" href="#">Trade Cryptocurrency</a>
            </div>
            <!-- Collect the nav links, forms, and other content for toggling -->
            <div class="collapse navbar-collapse" id="bs-example-navbar-collapse-1">
                <ul class="nav navbar-nav">
                    <li>
                        <a href="#">V 0.1</a>
                    </li>
                </ul>
            </div>
            <!-- /.navbar-collapse -->
        </div>
        <!-- /.container -->
    </nav>

    <!-- Page Content -->
    <div class="container">

        <!-- Page Header -->
        <div class="row">
            <div class="col-lg-12">
                <h1 class="page-header">
					Plataforma de negociação automática
                    <small>Time: </small><small id="time">--</small>
                </h1>
            </div>
			<div id="grafico"></div>
        </div>
        <!-- /.row -->

        <!-- Projects Row -->
        <div class="row">
            <div class="col-md-4 portfolio-item">
				<h3>
                    <a href="#">Parâmetros</a>
                </h3>
                <p>Definição dos principais parâmetros da rotina</p>
                <div id="def-parametros">
					<form id="parametros" action="" method="post">
						<div cass="form-group"> 
							<label for="moeda">Moeda</label>
							<input type="text" class="form-control" name="pair" value="BTC_ETH">
						</div>
						<div cass="form-group"> 
							<label for="orcamento">Orçamento a utilizar %</label>
							<input type="text" class="form-control" name="orcamento" value="100">
						</div>
						<div cass="form-group"> 
							<label for="lucroParcela">Lucro % por parcela</label>
							<input type="text" class="form-control" name="lucroParc" value="1">
						</div>
						<div cass="form-group"> 
							<label for="compraAbaixoEMA">Compra abaixo da EMA %</label>
							<input type="text" class="form-control" name="percEMA" value="0.05">
						</div>
						<div cass="form-group"> 
							<label for="quantidadeNegocios">Quantidade de negócios</label>
							<input type="text" class="form-control" name="qtdNegocios" value="10">
						</div>
						<br>
						<ul class="list-group">
							<li class="list-group-item">
								Executar Compra
								<div class="material-switch pull-right">
									<input id="executarCompra" name="executarCompra" type="checkbox"/>
									<label for="executarCompra" class="label-default"></label>
								</div>
							</li>
							<li class="list-group-item">
								Executar Venda
								<div class="material-switch pull-right">
									<input id="executarVenda" name="executarVenda" type="checkbox"/>
									<label for="executarVenda" class="label-primary"></label>
								</div>
							</li>		
							<li class="list-group-item">
								Start no cruzamento das médias
								<div class="material-switch pull-right">
									<input id="startMedias" name="startMedias" type="checkbox"/>
									<label for="startMedias" class="label-danger"></label>
								</div>
							</li>
							<li class="list-group-item">
								Compra na cotacao atual
								<div class="material-switch pull-right">
									<input id="compraCotacaoAtual" name="compraCotacaoAtual" type="checkbox"/>
									<label for="compraCotacaoAtual" class="label-info"></label>
								</div>
							</li>
						</ul>						
						<button type="submit" class="btn btn-default">Enviar</button>
					</form>
				</div>
            </div>
            <div class="col-md-5 portfolio-item">
                <h3>
                    <a href="#">Mercado | Cálculos</a>
                </h3>
				<div id="mercado"></div>				
            </div>

            <div class="col-md-3 portfolio-item">                
                <h3>
                    <a href="#">Operação</a>
                </h3>
                <div id="operacao"></div>		
            </div>
		</div>
        <!-- /.row -->   
		
		<div class="row">
			<div class="col-md-12 portfolio-item">                                
                <div id="curve_chart"></div>		
            </div>
		</div>

        <hr>

        <!-- Footer -->
        <footer>
            <div class="row">
                <div class="col-lg-12">
                    <p>Copyright &copy; Will 2017</p>
                </div>
            </div>
            <!-- /.row -->
        </footer>

    </div>
    <!-- /.container -->

    <!-- jQuery -->
    <script src="js/jquery.js"></script>

    <!-- Bootstrap Core JavaScript -->
    <script src="js/bootstrap.min.js"></script>
	
	<!-- Google Chart -->
	<script type="text/javascript" src="https://www.gstatic.com/charts/loader.js"></script>
	
	<script type="text/javascript">
	
	var tempo = 300;
	var tPercorrido = tempo;
	var dadosGrafico = [];
	var x;
	var dados
	
	$(document).ready(function(){				
		$("#parametros").submit(function(){
			$("#mercado").text("Aguardando dados...");
			$("#operacao").text("Aguardando resultado das operacoes...");
			dados = $("#parametros").serialize();
			//console.log(dados);
			$.ajax({
				type: "POST",
				url: "trade.php",
				data: dados
			}).done(function(html){
				$("#mercado").text("");
				$("#mercado").append(html);
				operacao(dados);
			});
			
			inicializaGrafico();			
			return false;
		}); 					
		
		inicializaGrafico();
		
		// inicializa timer
		setInterval(trade, 1000);
	});
		
	function inicializaGrafico(){	
		dados = $("#parametros").serialize();		
		$.ajax({	
			type: "POST",
			url: "trade.php?function=grafico",
			data: dados
		}).done(function(html){		
			console.log(html);
			dadosGrafico = eval(html);			
			google.charts.load('current', {'packages':['corechart']});
			google.charts.setOnLoadCallback(drawChart);		
		});					
	}
	
	function drawChart() {				
        var data = google.visualization.arrayToDataTable(eval(dadosGrafico));
        var options = {
			chart: {
				title: 'Grafico de fechamentos',
				subtitle: 'em Bitcoin (BTC)'
			},
			legend: { position: 'right' },
			height: 300
        };
        var chart = new google.visualization.LineChart(document.getElementById('curve_chart'));
        chart.draw(data, options);
    }
	
	function operacao(dados){
		$("#operacao").text("Realizando operação...");
		$.ajax({
			type: "POST",
			url: "trade_operacao.php",
			data: dados
		}).done(function(html){
			$("#operacao").text("");
			$("#operacao").append(html);
		});
	}
	 
	function trade(){	 
		tPercorrido = tPercorrido - 1;
		$("#time").text(tPercorrido);		  
		if(tPercorrido == 0){
			$("#parametros").submit();
		tPercorrido = tempo;
		}  
	}
 
 
	</script>

</body>

</html>
