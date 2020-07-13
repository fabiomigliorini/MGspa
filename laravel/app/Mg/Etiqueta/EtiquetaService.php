<?php

namespace Mg\Etiqueta;

use DB;
use Carbon\Carbon;

use Mg\Produto\ProdutoBarra;
use Mg\Produto\Barras;

class EtiquetaService
{
    public static function impressoras()
    {
        $o = shell_exec("lpstat -d -p");
        $res = explode("\n", $o);
        foreach ($res as $r) {
            if (strpos($r, "printer") !== false) {
                $r = str_replace("printer ", "", $r);
                $r = explode(" ", $r);
                $printers[] = $r[0];
            }
        }
        return $printers;
    }

    // Imprime Etiqueta usando cups
    // Listagem das etiquetas = array com "codprodutobarra" e "quantidade"
    public static function imprimir($impressora, $modelo, $etiquetas)
    {

        // calcula quantidade de etiquetas para imprimir
        $quant = 0;
        foreach ($etiquetas as $etiqueta) {
            $quant += $etiqueta['quantidadeetiqueta'];
        }
        if ($quant == 0) {
            throw new \Exception("Nada para imprimir!", 1);
        }

        // conforme modelo, calcula colunas e tamanho da descricao
        switch ($modelo) {
            case '2colunas':
            case '2colunas_sempreco':
                $colunas = 2;
                $tamanhoDescricao = 32;
                break;

            case '3colunas':
            case '3colunas_sempreco':
                $colunas = 3;
                $tamanhoDescricao = 28;
                break;

            default:
                $colunas = 1;
                $tamanhoDescricao = 45;
                break;
        }

        // precorre array de etiquetas montando novo array
        // com as linhas para imprimir
        $coluna = 1;
        $linha = 1;
        foreach ($etiquetas as $etiqueta) {
            $pb = ProdutoBarra::findOrFail($etiqueta['codprodutobarra']);

            for ($i=0; $i<$etiqueta['quantidadeetiqueta']; $i++) {
                $linhas[$linha][$coluna]['DescricaoLinha1'] = trim(substr($pb->descricao, 0, $tamanhoDescricao));
                $linhas[$linha][$coluna]['DescricaoLinha2'] = trim(substr($pb->descricao, $tamanhoDescricao, $tamanhoDescricao));
                $linhas[$linha][$coluna]['Codigo'] = '#' . str_pad($pb->codproduto, 6, "0", STR_PAD_LEFT);
                $linhas[$linha][$coluna]['UnidadeMedida'] = $pb->UnidadeMedida->sigla;
                $linhas[$linha][$coluna]['Preco'] = number_format($pb->Produto->preco, 2, ',', '.');
                if (isset($pb->ProdutoEmbalagem)) {
                    $linhas[$linha][$coluna]['UnidadeMedida'] =
                        $pb->ProdutoEmbalagem->UnidadeMedida->sigla .
                        ' C/' .
                        (int) $pb->ProdutoEmbalagem->quantidade;
                    $linhas[$linha][$coluna]['Preco'] =
                        number_format($pb->ProdutoEmbalagem->preco??($pb->Produto->preco*$pb->ProdutoEmbalagem->quantidade), 2, ',', '.');
                }
                if (strlen($linhas[$linha][$coluna]['Preco']) < 6) {
                    $linhas[$linha][$coluna]['Preco'] = str_pad($linhas[$linha][$coluna]['Preco'], 6, ' ', STR_PAD_LEFT);
                }
                $linhas[$linha][$coluna]['Preco'] = str_replace(' ', '  ', $linhas[$linha][$coluna]['Preco']);

                // Decide o tipo de codigo de barras
                // f42 - Ean13
                // g63 - Ean8
                // e63 subtipo A
                // e63 subtipo C - Code128 somente números Par
                // o42 - Code93 - nao le codigo "100182-8" no leitor metrologic - nao utilizar
                // a42 - Code3 of 9
                $linhas[$linha][$coluna]['Barras'] = $pb->barras;
                $linhas[$linha][$coluna]['NumeroBarras'] = $pb->barras;

                if (Barras::validarEan13($pb->barras)) {
                    $linhas[$linha][$coluna]['ModeloBarras'] = 'f42';
                    $linhas[$linha][$coluna]['SubsetBarras'] = '';
                } elseif (Barras::validarEan8($pb->barras)) {
                    $linhas[$linha][$coluna]['ModeloBarras'] = 'g63';
                    $linhas[$linha][$coluna]['SubsetBarras'] = '';
                } elseif (Barras::validarCode128C($pb->barras)) {
                    if (strlen($linhas[$linha][$coluna]['Barras']) <= 10) {
                        $linhas[$linha][$coluna]['ModeloBarras'] = 'e63';
                    } else {
                        $linhas[$linha][$coluna]['ModeloBarras'] = 'e42';
                    }
                    $linhas[$linha][$coluna]['SubsetBarras'] = 'C';
                } else {
                    $linhas[$linha][$coluna]['ModeloBarras'] = 'e42';
                    $linhas[$linha][$coluna]['SubsetBarras'] = 'A';
                }


                if (!empty($pb->Produto->codmarca)) {
                    $linhas[$linha][$coluna]['Marca'] = $pb->Produto->Marca->marca;
                } else {
                    $linhas[$linha][$coluna]['Marca'] = '';
                }

                $linhas[$linha][$coluna]['Referencia'] = $pb->referencia??$pb->ProdutoVariacao->referencia??$pb->Produto->referencia;

                $linhas[$linha][$coluna]['Data'] = date('d/m/y');

                $coluna++;
                if ($coluna>$colunas) {
                    $coluna = 1;
                    $linha++;
                }
            }
        }

        // carrega template da etiqueta
        $template = file_get_contents(base_path("app/Mg/Etiqueta/modelos/{$modelo}.txt"));

        // percorre array das linhas gerando um arquivo texto com todas etiquetas
        $arquivo = tempnam(sys_get_temp_dir(), "MGEtiqueta");
        $handle = fopen($arquivo, "w");
        foreach ($linhas as $linha) {
            $conteudo = $template;
            $icoluna = 1;
            foreach ($linha as $coluna) {
                foreach ($coluna as $chave => $valor) {
                    $conteudo = str_replace("<{$chave}Coluna{$icoluna}>", $valor, $conteudo);
                }
                $icoluna++;
            }
            fwrite($handle, $conteudo);
        }

        // fecha arquivo e imprime
        fclose($handle);
        exec("lp -d {$impressora} {$arquivo}");
        unlink($arquivo);

        // retornar quantidade de etiquetas
        return [
            'quantidadeetiqueta' => $quant,
            'impressora' => $impressora,
            'modelo' => $modelo
        ];
    }

    public static function buscarProdutoBarraComPrecoAlterado (Carbon $datainicial, Carbon $datafinal)
    {
        $sql = '
            select distinct t.codproduto, t.codprodutoembalagem, p.produto, pe.quantidade
            from tblprodutohistoricopreco t
            inner join tblproduto p on (p.codproduto = t.codproduto)
            left join tblprodutoembalagem pe on (pe.codprodutoembalagem = t.codprodutoembalagem)
            where t.criacao between :datainicial and :datafinal
            and date_trunc(\'day\', t.criacao) != date_trunc(\'day\', p.criacao)
            order by p.produto, pe.quantidade nulls first
            ';
        $regs = DB::select($sql, [
            'datainicial' => $datainicial,
            'datafinal' => $datafinal,
        ]);
        $pbs = collect();
        foreach ($regs as $reg) {
            $qry = ProdutoBarra::where('codproduto', $reg->codproduto);
            if (empty($reg->codprodutoembalagem)) {
                $qry->whereNull('codprodutoembalagem');
            } else {
                $qry->where('codprodutoembalagem', $reg->codprodutoembalagem);
            }
            $qry->orderBy('criacao');
            $pb = $qry->first();
            if ($pb = $qry->first()) {
                $pbs[] = $pb;
            }
        }
        return $pbs;
    }
}
