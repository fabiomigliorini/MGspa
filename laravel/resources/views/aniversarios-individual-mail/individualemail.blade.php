<!DOCTYPE html>
<html>

<head>
	<style>
		table {
			font-family: arial, sans-serif;
			border-collapse: collapse;
			width: 100%;
		}

		td,
		th {
			border: 1px solid #dddddd;
			text-align: left;
			padding: 8px;
		}

		tr:nth-child(even) {
			background-color: #dddddd;
		}
	</style>
</head>

<body>

	<h2>Parabéns {{$aniv->pessoa}}!</h2>

	@if ($aniv->tipo == 'Idade')
		<p>Feliz aniversário!</p>

		<p>Que seu dia seja alegre.</p>

		<p>Que sejas sempre feliz.</p>

		<p>Que sua jornada seja leve.</p>

		<p>Que a felicidade seja sua melhor companhia.</p>

		<p>E que Deus lhe traga toda felicidade possível.</p>

		<p>Parabéns.</p>

	@elseif ($aniv->tipo == 'Empresa')
		<p>
			Hoje você celebra {{ $aniv->idade }} 
			@if ($aniv->idade > 1) 
				anos
			@else
				ano
			@endif 
			de história, crescimento, aprendizado e grandes momentos nesta empresa. 
		</p>

		<p>Que sua jornada continue sendo repleta de momentos memoráveis e sucesso profissional.</p>

		<p>Certamente seus colegas tem um enorme sentimento de gratidão e orgulho por fazerem parte da sua jornada.</p>

		<p>Comemore, conte e compartilhe com eles essa conquista! </p>

		<p>Tenha certeza que eles se sentem abençoados por terem você ao seu lado!</p>

	@endif
	<p>São os votos da Diretoria e toda equipe da <b>MG Papelaria!</b></p>


</body>

</html>