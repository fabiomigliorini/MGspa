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

	<h2>Lista dos Próximos Aniversariantes!</h2>
	<table>
		<tr>
			<th>Pessoa</th>
			<th>Aniversario</th>
			<th>Anos</th>
			<th>Tipo</th>
			<th>Data</th>

		</tr>
		@foreach($anivs as $aniv)
		
		<tr>
			<td>{{ $aniv->pessoa }}</td>
			<?php
				$aniversario = \Carbon\Carbon::parse($aniv->aniversario);
				$aniversario = $aniversario->isoFormat('dddd, D MMMM');
			?>
			<td>{{ $aniversario }}</td>
			<td>{{ $aniv->idade }}</td>
			<td>{{ $aniv->tipo }}</td>
			<td>{{ formataData($aniv->data, 'd/m/Y') }}</td>
		</tr>
		@endforeach

	</table>
</body>

</html>