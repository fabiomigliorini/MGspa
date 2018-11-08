<!DOCTYPE html>
<html>
<head>
  <title>Produtos Abaixo do Minimo</title>
</head>
<style>
body {
  font-family: Roboto, Helvetica, Arial, sans-serif;
}
</style>
<body>
    @foreach ($marcas as $marca)
      <h3>
        {{ $marca->marca }}
        <small style="color: grey">
          R$ {{ formataNumero($marca->total, 2) }} Pedido Sugerido /
          {{ $marca->comprar }} Itens para Comprar
          @if ($marca->abaixominimo)
            / {{ $marca->abaixominimo }} Abaixo do Mínimo
          @endif
          @if ($marca->critico)
            / {{ $marca->critico }} Criticos
          @endif
        </small>
      </h3>
      <ul>
        @foreach ($marca->produtos as $produto)
          <li>
            <a style="color: #000001; text-decoration: none;" href="http://sistema.mgpapelaria.com.br/MGLara/produto/{{ $produto->codproduto }}">
              {{ $produto->produto }}
              @if ($produto->critico)
              <span style="background-color: red; color:white">Crítico</span>
              @endif
            </a><br />
            <small style="color: grey">
              (<span style="color: darkblue;">{{ formataNumero($produto->estoque, 0) }}
                @if (!empty($produto->chegando))
                +
                {{ formataNumero($produto->chegando, 0) }} chegando
                @endif
              </span>
              de
              <span style="color: darkred;">
                {{ formataNumero($produto->estoqueminimo, 0) }}
              </span>
              /
              <span style="color: darkgreen;">{{ formataNumero($produto->estoquemaximo, 0) }}</span>)
              Ref: {{ $produto->referencia }}
            </small>

            <br />
            <br />
          </li>
        @endforeach
      </ul>
    @endforeach
</body>
</html>
