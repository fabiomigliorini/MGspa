<pre>
<?php

$doc = new DOMDocument();
$arquivo = file_get_contents('CONVÊNIO ICMS 52_17 — Conselho Nacional de Política Fazendária CONFAZ.html');
$doc->loadHtml($arquivo);

$noticia = $doc->getElementById('parent-fieldname-text');

$anexos = [];
$titulos = [];
$tabelas = [];

$iAnexo = 0;

$iNode = 0;
foreach($noticia->childNodes as $item ){

  if (strpos($item->nodeValue, 'ANEXO') !== false) {

    // ANEXO
    if ($item->nodeName != 'p') {
      continue;
    }
    $anexos[$iAnexo] = $item->nodeValue;

    // TITULO
    if ($noticia->childNodes[$iNode+2]->nodeName == 'p') {
      $titulos[$iAnexo] = $noticia->childNodes[$iNode+2]->nodeValue;
    } else {
      $titulos[$iAnexo] = null;
    }

    // TABELA
    if ($noticia->childNodes[$iNode+2]->nodeName == 'table') {
      $tabelas[$iAnexo] = $noticia->childNodes[$iNode+2];
    } elseif ($noticia->childNodes[$iNode+4]->nodeName == 'table') {
      $tabelas[$iAnexo] = $noticia->childNodes[$iNode+4];
    } elseif ($noticia->childNodes[$iNode+6]->nodeName == 'table') {
      $tabelas[$iAnexo] = $noticia->childNodes[$iNode+6];
    } elseif ($noticia->childNodes[$iNode+7]->nodeName == 'table') {
      $tabelas[$iAnexo] = $noticia->childNodes[$iNode+7];
    } elseif ($noticia->childNodes[$iNode+8]->nodeName == 'table') {
      $tabelas[$iAnexo] = $noticia->childNodes[$iNode+8];
    } else {
      $tabelas[$iAnexo] = false;
    }

    $iAnexo++;

  }

  $iNode++;

}

// ANEXO IV - CERVEJAS, CHOPES, REFRIGERANTES, ÁGUAS E OUTRAS BEBIDAS
$iAnexo = 3;

for ($iAnexo = 3; $iAnexo <= 26; $iAnexo++) {
  foreach ($tabelas[$iAnexo]->getElementsByTagName('tr') as $linha) {
    if (trim($linha->childNodes[0]->nodeValue) == 'ITEM') {
      continue;
    }
    $item = trim($linha->childNodes[0]->nodeValue);
    $cest = trim($linha->childNodes[2]->nodeValue);
    $ncms = trim($linha->childNodes[4]->nodeValue);
    $descricao = trim($linha->childNodes[6]->nodeValue);


    $ncms = str_replace('Capítulos', ' ', $ncms);
    $ncms = str_replace('Capítulo', ' ', $ncms);
    $ncms = str_replace(' e ', ' ', $ncms);
    $ncms = str_replace(' a ', ' ', $ncms);
    $ncms = preg_replace( '/[^[:print:]]/', ' ', $ncms);
    $ncms = preg_replace('/,/', ' ', $ncms);
    /*
    $ncms = preg_replace('/(\r\n|\r|\n)+/', "\n", $ncms);
    */
    $ncms = preg_replace('/\s+/', ' ', $ncms);

    foreach (explode(' ', trim($ncms)) as $ncm) {
      if (empty($ncm)) {
        continue;
      }

      $saida[] = [
        $anexos[$iAnexo],
        $titulos[$iAnexo],
        $item,
        $cest,
        $ncm,
        $descricao,
      ];
    }
  }
}

$arquivoCsv = fopen("/tmp/cest.csv",'w');
fputs($arquivoCsv, "\xEF\xBB\xBF");
fputcsv($arquivoCsv, [
  'Anexo',
  'Título',
  'Item',
  'CEST',
  'NCM',
  'Descricao',
]);
foreach($saida as $linha) {
    fputcsv($arquivoCsv, $linha);
}
fclose($arquivoCsv);

$dbh = new PDO("pgsql:dbname=mgsis;host=192.168.1.205", 'mgsis', 'mgsis');

$stmt = $dbh->prepare("INSERT INTO tblcest_2017_52 (anexo, titulo, item, cest, ncm, descricao) VALUES (?, ?, ?, ?, ?, ?)");
$stmt->bindParam(1, $linha_anexo);
$stmt->bindParam(2, $linha_titulo);
$stmt->bindParam(3, $linha_item);
$stmt->bindParam(4, $linha_cest);
$stmt->bindParam(5, $linha_ncm);
$stmt->bindParam(6, $linha_descricao);

foreach ($saida as $linha) {

  $linha_anexo = $linha[0];
  $linha_titulo = $linha[1];
  $linha_item = $linha[2];
  $linha_cest = $linha[3];
  $linha_ncm = $linha[4];
  $linha_descricao = $linha[5];
  $stmt->execute();

}


?>


/*
foreach ($noticia->getElementsByTagName('table') as $tabela) {
  foreach ($tabela->getElementsByTagName('tr') as $linha)
  {
    $colunas = $linha->getElementsByTagName('td');
    if ($colunas[0]->nodeValue == )
  }
  $tabelas[] = $tabela;
}
*/
$arr = [];

?>
<?php print_r($saida); ?>
<?php print_r($anexos); ?>
<?php print_r($titulos); ?>
<hr>
<?php print_r($arr); ?>
</pre>
