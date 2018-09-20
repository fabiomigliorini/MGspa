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
  <table>
    <tbody>
      <tr>
        <td>
          @foreach ($marcas as $marca => $produtos)
            <h1>{{ $marca }}</h1>
            <ul>
              @foreach ($produtos as $produto)
                <li>
                  {{ $produto->codproduto }}
                  {{ $produto->produto }}
                </li>
              @endforeach
            </ul>
          @endforeach
        </td>
      </p>
      </tr>
    </tbody>
  </table>
</body>
</html>
