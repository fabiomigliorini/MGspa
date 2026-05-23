<?php

namespace Mg\NotaFiscal;

use Exception;
use Mpdf\Mpdf;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class NotaFiscalRelatorioService
{
    public static $filtrosAceitos = [
        'codfilial',
        'codpessoa',
        'codgrupoeconomico',
        'codnaturezaoperacao',
        'emitida',
        'modelo',
        'serie',
        'numero',
        'nfechave',
        'emissao_inicio',
        'emissao_fim',
        'saida_inicio',
        'saida_fim',
        'valortotal_inicio',
        'valortotal_fim',
        'status',
    ];

    // Monta a query base com os filtros — usado por index() e pelo relatorio
    public static function query(array $filtros)
    {
        $query = NotaFiscal::query();

        if (!empty($filtros['codfilial'])) {
            $query->where('codfilial', $filtros['codfilial']);
        }
        if (!empty($filtros['codpessoa'])) {
            $query->where('codpessoa', $filtros['codpessoa']);
        }
        if (!empty($filtros['codgrupoeconomico'])) {
            $query->whereHas('Pessoa', function ($q) use ($filtros) {
                $q->where('codgrupoeconomico', $filtros['codgrupoeconomico']);
            });
        }
        if (!empty($filtros['codnaturezaoperacao'])) {
            $query->where('codnaturezaoperacao', $filtros['codnaturezaoperacao']);
        }
        if (isset($filtros['emitida']) && $filtros['emitida'] !== null && $filtros['emitida'] !== '') {
            $emitida = ($filtros['emitida'] === 'true' || $filtros['emitida'] === true || $filtros['emitida'] === 1 || $filtros['emitida'] === '1');
            $query->where('emitida', $emitida);
        }
        if (!empty($filtros['modelo'])) {
            $query->where('modelo', $filtros['modelo']);
        }
        if (!empty($filtros['serie'])) {
            $query->where('serie', $filtros['serie']);
        }
        if (!empty($filtros['numero'])) {
            $query->where('numero', $filtros['numero']);
        }
        if (!empty($filtros['nfechave'])) {
            $query->where('nfechave', 'like', '%' . $filtros['nfechave'] . '%');
        }
        if (!empty($filtros['emissao_inicio'])) {
            $query->where('emissao', '>=', $filtros['emissao_inicio'] . ' 00:00:00');
        }
        if (!empty($filtros['emissao_fim'])) {
            $query->where('emissao', '<=', $filtros['emissao_fim'] . ' 23:59:59');
        }
        if (!empty($filtros['saida_inicio'])) {
            $query->where('saida', '>=', $filtros['saida_inicio'] . ' 00:00:00');
        }
        if (!empty($filtros['saida_fim'])) {
            $query->where('saida', '<=', $filtros['saida_fim'] . ' 23:59:59');
        }
        if (!empty($filtros['valortotal_inicio'])) {
            $query->where('valortotal', '>=', $filtros['valortotal_inicio']);
        }
        if (!empty($filtros['valortotal_fim'])) {
            $query->where('valortotal', '<=', $filtros['valortotal_fim']);
        }
        if (!empty($filtros['status'])) {
            $query->where('status', $filtros['status']);
        }

        return $query;
    }

    // Retorna a Collection de notas para o relatorio (ordenada para agrupar)
    // Usa SQL direto com JOINs para evitar overhead de hidratacao Eloquent + relacoes
    public static function buscarNotas(array $filtros)
    {
        $relevantes = Arr::only($filtros, static::$filtrosAceitos);
        $temFiltro = collect($relevantes)->filter(fn($v) => $v !== null && $v !== '')->isNotEmpty();
        if (!$temFiltro) {
            throw new Exception('Informe ao menos um filtro para gerar o relatório.');
        }

        $q = DB::table('tblnotafiscal as nf')
            ->leftJoin('tblfilial as f', 'f.codfilial', '=', 'nf.codfilial')
            ->leftJoin('tblnaturezaoperacao as no', 'no.codnaturezaoperacao', '=', 'nf.codnaturezaoperacao')
            ->leftJoin('tblpessoa as p', 'p.codpessoa', '=', 'nf.codpessoa')
            ->leftJoin('tblcidade as c', 'c.codcidade', '=', 'p.codcidade')
            ->leftJoin('tblestado as e', 'e.codestado', '=', 'c.codestado')
            ->select([
                'nf.codnotafiscal',
                'nf.codfilial',
                'nf.codnaturezaoperacao',
                'nf.serie',
                'nf.numero',
                'nf.modelo',
                'nf.status',
                'nf.emissao',
                'nf.saida',
                'nf.valorprodutos',
                'nf.icmsvalor',
                'nf.ipivalor',
                'nf.icmsstvalor',
                'nf.valorfrete',
                'nf.valorseguro',
                'nf.valordesconto',
                'nf.valoroutras',
                'nf.valortotal',
                'nf.nfedataautorizacao',
                'nf.nfedatacancelamento',
                'nf.nfedatainutilizacao',
                'f.filial',
                'no.naturezaoperacao',
                'p.cnpj',
                'p.fisica',
                'p.fantasia',
                'c.cidade',
                'e.sigla as uf',
            ]);

        if (!empty($filtros['codfilial']))          $q->where('nf.codfilial', $filtros['codfilial']);
        if (!empty($filtros['codpessoa']))          $q->where('nf.codpessoa', $filtros['codpessoa']);
        if (!empty($filtros['codgrupoeconomico']))  $q->where('p.codgrupoeconomico', $filtros['codgrupoeconomico']);
        if (!empty($filtros['codnaturezaoperacao'])) $q->where('nf.codnaturezaoperacao', $filtros['codnaturezaoperacao']);
        if (isset($filtros['emitida']) && $filtros['emitida'] !== null && $filtros['emitida'] !== '') {
            $emitida = ($filtros['emitida'] === 'true' || $filtros['emitida'] === true || $filtros['emitida'] === 1 || $filtros['emitida'] === '1');
            $q->where('nf.emitida', $emitida);
        }
        if (!empty($filtros['modelo']))        $q->where('nf.modelo', $filtros['modelo']);
        if (!empty($filtros['serie']))         $q->where('nf.serie', $filtros['serie']);
        if (!empty($filtros['numero']))        $q->where('nf.numero', $filtros['numero']);
        if (!empty($filtros['nfechave']))      $q->where('nf.nfechave', 'like', '%' . $filtros['nfechave'] . '%');
        if (!empty($filtros['emissao_inicio'])) $q->where('nf.emissao', '>=', $filtros['emissao_inicio'] . ' 00:00:00');
        if (!empty($filtros['emissao_fim']))    $q->where('nf.emissao', '<=', $filtros['emissao_fim'] . ' 23:59:59');
        if (!empty($filtros['saida_inicio']))   $q->where('nf.saida', '>=', $filtros['saida_inicio'] . ' 00:00:00');
        if (!empty($filtros['saida_fim']))      $q->where('nf.saida', '<=', $filtros['saida_fim'] . ' 23:59:59');
        if (!empty($filtros['valortotal_inicio'])) $q->where('nf.valortotal', '>=', $filtros['valortotal_inicio']);
        if (!empty($filtros['valortotal_fim']))    $q->where('nf.valortotal', '<=', $filtros['valortotal_fim']);
        if (!empty($filtros['status']))        $q->where('nf.status', $filtros['status']);

        return $q->orderBy('nf.codfilial')
            ->orderBy('nf.codnaturezaoperacao')
            ->orderBy('nf.status')
            ->orderBy('nf.numero')
            ->get();
    }

    // HTML renderizado do blade — util tambem para anexar em e-mail
    public static function html(array $filtros)
    {
        $notas = static::buscarNotas($filtros);
        $grupos = $notas->groupBy(fn($n) => $n->codfilial . '|' . $n->codnaturezaoperacao);

        return view('notafiscal.relatorio', [
            'grupos' => $grupos,
            'filtros' => $filtros,
        ])->render();
    }

    // Binario do PDF — para download, anexo de e-mail, salvar em disco, etc.
    public static function pdf(array $filtros)
    {
        $html = static::html($filtros);

        $tempDir = storage_path('app/mpdf');
        if (!is_dir($tempDir)) {
            @mkdir($tempDir, 0775, true);
        }

        $mpdf = new Mpdf([
            'mode'          => 'utf-8',
            'format'        => 'A4-L',
            'margin_left'   => 8,
            'margin_right'  => 8,
            'margin_top'    => 16,
            'margin_bottom' => 14,
            'margin_header' => 5,
            'margin_footer' => 5,
            'default_font'  => 'helvetica',
            'tempDir'       => $tempDir,
        ]);

        $mpdf->SetHTMLHeader(
            '<div style="font-size:14pt;font-weight:bold;border-bottom:2px solid #000;padding-bottom:3px;">'
          . 'Relat&oacute;rio de Notas Fiscais'
          . '</div>'
        );

        $mpdf->SetHTMLFooter(
            '<div style="text-align:right;font-size:6pt;color:#666;border-top:1px solid #000;padding-top:2px;">'
          . 'MGsis &mdash; {DATE j/m/Y H:i} &mdash; P&aacute;gina {PAGENO} de {nbpg}'
          . '</div>'
        );

        $mpdf->WriteHTML($html);

        return $mpdf->Output('', \Mpdf\Output\Destination::STRING_RETURN);
    }
}
