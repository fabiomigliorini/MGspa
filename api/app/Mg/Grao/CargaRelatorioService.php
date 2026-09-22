<?php

namespace Mg\Grao;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Mpdf\Mpdf;

/**
 * Relatorio analitico de romaneios: uma linha por carga, com subtotais por
 * agrupamento e total geral.
 *
 * Reusa CargaService::qryFiltros — e o que faz "imprimir exatamente o que estou
 * vendo na tela" ser verdade, e nao uma segunda implementacao que diverge.
 */
class CargaRelatorioService
{
    /** Agrupamentos aceitos em `agrupar` (o front oferece os mesmos). */
    public const AGRUPAMENTOS = [
        'nenhum',
        'dia',
        'mes',
        'sentido',
        'etapa',
        'safra',
        'cultura',
        'unidade',
        'plantio',
        'contrato',
        'pessoa',
        'motorista',
        'placa',
    ];

    /**
     * Teto de linhas. Acima disso o PDF vira um tijolo inutil e o mPDF come
     * memoria sem limite — melhor recusar e pedir recorte.
     */
    private const LIMITE_LINHAS = 5000;

    public const SENTIDO_LABEL = [
        'ENTRADA' => 'Recebimento',
        'SAIDA' => 'Expedição',
        'TRANSFERENCIA' => 'Transferência',
    ];

    /** Abreviacao da coluna Sent. (11mm nao cabe "Transferência"). */
    public const SENTIDO_CURTO = [
        'ENTRADA' => 'Receb.',
        'SAIDA' => 'Exped.',
        'TRANSFERENCIA' => 'Transf.',
    ];

    public const ETAPA_LABEL = [
        'PBT' => 'Peso Bruto',
        'TARA' => 'Tara',
        'CLASSIFICACAO' => 'Classificação',
        'FISCAL' => 'Nota Fiscal',
        'FINALIZADO' => 'Finalizado',
    ];

    public static function pdf(array $filtros): string
    {
        $html = static::html($filtros);

        $tempDir = storage_path('app/mpdf');
        if (!is_dir($tempDir)) {
            @mkdir($tempDir, 0775, true);
        }

        // Paisagem: 11 colunas nao cabem em A4 retrato sem espremer origem/destino
        // a ponto de so sobrar reticencia.
        $mpdf = new Mpdf([
            'mode' => 'utf-8',
            'format' => 'A4-L',
            'margin_left' => 8,
            'margin_right' => 8,
            'margin_top' => 16,
            'margin_bottom' => 14,
            'margin_header' => 5,
            'margin_footer' => 5,
            'default_font' => 'helvetica',
            'tempDir' => $tempDir,
        ]);
        $mpdf->SetTitle('Romaneios');
        $mpdf->WriteHTML($html);

        return $mpdf->Output('', \Mpdf\Output\Destination::STRING_RETURN);
    }

    public static function html(array $filtros): string
    {
        ini_set('memory_limit', '512M');
        set_time_limit(180);

        $agrupar = $filtros['agrupar'] ?? 'nenhum';
        if (!in_array($agrupar, static::AGRUPAMENTOS, true)) {
            $agrupar = 'nenhum';
        }

        // Sem recorte isso varre a tblcarga inteira e o relatorio nao diz nada.
        if (
            empty($filtros['data_inicio'])
            && empty($filtros['data_fim'])
            && empty($filtros['codsafra'])
            && empty($filtros['codcarga'])
        ) {
            abort(422, 'Informe ao menos o período ou a safra para gerar o relatório.');
        }

        $qtd = CargaService::totais($filtros)['qtd'];
        if ($qtd > static::LIMITE_LINHAS) {
            abort(422, "Resultado com {$qtd} romaneios — refine o período ou os filtros (máximo "
                . static::LIMITE_LINHAS . ').');
        }

        $cargas = CargaService::pesquisar(
            $filtros,
            static::sortDoAgrupamento($agrupar),
            null,
            CargaService::WITH_LISTAGEM
        )->get();

        $grupos = static::agrupar($cargas, $agrupar);
        $total = static::somar($cargas);
        $legenda = static::legenda($filtros, $agrupar);

        return view('carga.relatorio', compact('grupos', 'total', 'agrupar', 'legenda'))->render();
    }

    /**
     * Ordem que ja entrega as linhas agrupadas do banco — o agrupamento em PHP
     * so precisa quebrar quando a chave muda.
     */
    protected static function sortDoAgrupamento(string $agrupar): array
    {
        return match ($agrupar) {
            'dia', 'mes' => ['data', 'codcarga'],
            'sentido' => ['sentido', '-data'],
            'etapa' => ['etapa', '-data'],
            'safra', 'cultura' => ['codsafra', '-data'],
            'motorista' => ['motorista', '-data'],
            'placa' => ['placa', '-data'],
            // Grupos derivados dos pontos nao dao pra ordenar no SQL sem join;
            // o agrupamento em memoria resolve e a data manda dentro do grupo.
            default => ['-data'],
        };
    }

    /**
     * Uma carga entra em EXATAMENTE UM grupo. Explodir por ponto duplicaria os
     * kg nos subtotais — o relatorio e analitico por romaneio, nao por ponto.
     */
    protected static function agrupar(Collection $cargas, string $agrupar): Collection
    {
        $grupos = collect();

        foreach ($cargas as $c) {
            [$chave, $rotulo] = static::chaveGrupo($c, $agrupar);
            if (!$grupos->has($chave)) {
                $grupos->put($chave, ['rotulo' => $rotulo, 'cargas' => collect()]);
            }
            $grupos[$chave]['cargas']->push($c);
        }

        // Grupos derivados de ponto saem fora de ordem (o SQL nao ordenou por
        // eles); ordena pelo rotulo, com o "(sem ...)" no fim.
        if (in_array($agrupar, ['unidade', 'plantio', 'contrato', 'pessoa'], true)) {
            $grupos = $grupos->sortBy(
                fn ($g) => (str_starts_with($g['rotulo'], '(') ? 'zzz' : '') . $g['rotulo'],
                SORT_NATURAL | SORT_FLAG_CASE
            );
        }

        return $grupos->map(function ($g) {
            $g['total'] = static::somar($g['cargas']);
            return $g;
        });
    }

    protected static function chaveGrupo(Carga $c, string $agrupar): array
    {
        $vazio = fn (string $oque) => ['__sem__', "(sem {$oque})"];

        switch ($agrupar) {
            case 'dia':
                $d = Carbon::parse($c->data);
                return [$d->format('Y-m-d'), $d->format('d/m/Y')];

            case 'mes':
                $d = Carbon::parse($c->data);
                return [$d->format('Y-m'), mb_strtoupper($d->translatedFormat('F/Y'))];

            case 'sentido':
                return [$c->sentido, static::SENTIDO_LABEL[$c->sentido] ?? $c->sentido];

            case 'etapa':
                return [$c->etapa, static::ETAPA_LABEL[$c->etapa] ?? $c->etapa];

            case 'safra':
                return [(string) $c->codsafra, $c->Safra->safra ?? "Safra {$c->codsafra}"];

            case 'cultura':
                $cod = $c->Safra->codcultura ?? null;
                return $cod
                    ? [(string) $cod, $c->Safra->Cultura->cultura ?? "Cultura {$cod}"]
                    : $vazio('cultura');

            case 'motorista':
                return $c->motorista ? [$c->motorista, $c->motorista] : $vazio('motorista');

            case 'placa':
                return $c->placa ? [$c->placa, $c->placa] : $vazio('placa');

            case 'unidade':
            case 'plantio':
            case 'contrato':
            case 'pessoa':
                return static::chaveGrupoPonto($c, $agrupar) ?? $vazio(match ($agrupar) {
                    'unidade' => 'unidade',
                    'plantio' => 'talhão',
                    'contrato' => 'contrato',
                    default => 'cliente',
                });
        }

        return ['', ''];
    }

    /**
     * Grupo derivado do ponto: usa o PAPEL DOMINANTE da carga — numa expedicao o
     * que interessa e pra onde foi; no resto, de onde veio. Mesma regra do
     * pontosResumo() do front (agro/src/utils/carga.js).
     *
     * Se o papel dominante nao tiver o tipo procurado, cai no outro papel antes
     * de desistir: transferencia filtrada por contrato, por exemplo, nao tem
     * contrato em lado nenhum, mas uma entrada com destino contrato tem.
     */
    protected static function chaveGrupoPonto(Carga $c, string $agrupar): ?array
    {
        $dominante = $c->sentido === 'SAIDA' ? 'DESTINO' : 'ORIGEM';
        $tipo = match ($agrupar) {
            'unidade' => 'UNIDADE',
            'plantio' => 'PLANTIO',
            default => 'CONTRATO',
        };

        foreach ([$dominante, $dominante === 'ORIGEM' ? 'DESTINO' : 'ORIGEM'] as $papel) {
            foreach ($c->CargaPontoS as $p) {
                if ($p->papel !== $papel || $p->contatipo !== $tipo) {
                    continue;
                }
                if ($agrupar === 'pessoa') {
                    $pessoa = $p->Contrato->Pessoa ?? null;
                    if (!$pessoa) {
                        continue;
                    }
                    return [(string) $pessoa->codpessoa, $pessoa->fantasia ?: $pessoa->pessoa];
                }
                $cod = match ($agrupar) {
                    'unidade' => $p->codunidadearmazenadora,
                    'plantio' => $p->codplantio,
                    default => $p->codcontrato,
                };
                return [(string) $cod, CargaPontoService::rotulo($p) ?: (string) $cod];
            }
        }

        return null;
    }

    protected static function somar(Collection $cargas): array
    {
        $tot = ['qtd' => 0, 'bruto' => 0.0, 'desconto' => 0.0, 'liquido' => 0.0, 'sacas' => 0.0];

        foreach ($cargas as $c) {
            $tot['qtd']++;
            $tot['bruto'] += (float) $c->bruto;
            $tot['desconto'] += (float) $c->desconto;
            $tot['liquido'] += (float) $c->liquido;
            $tot['sacas'] += static::sacas($c);
        }

        return $tot;
    }

    /**
     * kg -> sacas pelo peso da saca da CULTURA DA SAFRA da carga (soja/milho 60,
     * mas o cadastro manda). Somar saca de culturas diferentes so faz sentido
     * dentro de um grupo homogeneo — por isso o total geral de sacas e
     * informativo, e o kg e que fecha.
     */
    public static function sacas(Carga $c): float
    {
        $pesosaca = (float) ($c->Safra->Cultura->pesosaca ?? 0);
        if ($pesosaca <= 0) {
            $pesosaca = 60;
        }
        return (float) $c->liquido / $pesosaca;
    }

    /** Rotulos dos pontos de um papel, juntos — "Talhao 12 · Talhao 14". */
    public static function rotulosDoPapel(Carga $c, string $papel): string
    {
        return collect($c->CargaPontoS)
            ->filter(fn ($p) => $p->papel === $papel)
            ->map(fn ($p) => CargaPontoService::rotulo($p))
            ->filter()
            ->implode(' · ');
    }

    /**
     * Linha de filtros ativos no cabecalho — sem isso o PDF impresso vira uma
     * tabela orfa, e quem recebe nao sabe de que recorte ela saiu.
     */
    protected static function legenda(array $filtros, string $agrupar): string
    {
        $partes = [];

        $ini = $filtros['data_inicio'] ?? null;
        $fim = $filtros['data_fim'] ?? null;
        if ($ini && $fim) {
            $partes[] = 'Período: ' . Carbon::parse($ini)->format('d/m/Y') . ' a ' . Carbon::parse($fim)->format('d/m/Y');
        } elseif ($ini) {
            $partes[] = 'A partir de ' . Carbon::parse($ini)->format('d/m/Y');
        } elseif ($fim) {
            $partes[] = 'Até ' . Carbon::parse($fim)->format('d/m/Y');
        }

        if (!empty($filtros['codsafra'])) {
            $safra = \Mg\Safra\Safra::find($filtros['codsafra']);
            $partes[] = 'Safra: ' . ($safra->safra ?? $filtros['codsafra']);
        }
        if (!empty($filtros['codcultura'])) {
            $cultura = \Mg\Cultura\Cultura::find($filtros['codcultura']);
            $partes[] = 'Cultura: ' . ($cultura->cultura ?? $filtros['codcultura']);
        }
        if (!empty($filtros['sentido'])) {
            $partes[] = static::SENTIDO_LABEL[$filtros['sentido']] ?? $filtros['sentido'];
        }
        if (!empty($filtros['etapa'])) {
            $partes[] = 'Etapa: ' . (static::ETAPA_LABEL[$filtros['etapa']] ?? $filtros['etapa']);
        }
        if (!empty($filtros['codunidadearmazenadora'])) {
            $u = UnidadeArmazenadora::find($filtros['codunidadearmazenadora']);
            $partes[] = 'Unidade: ' . ($u->unidadearmazenadora ?? $filtros['codunidadearmazenadora']);
        }
        if (!empty($filtros['codplantio'])) {
            $p = \Mg\Fazenda\Plantio::find($filtros['codplantio']);
            $partes[] = 'Talhão: ' . ($p->talhao ?? $filtros['codplantio']);
        }
        if (!empty($filtros['codcontrato'])) {
            $ct = \Mg\Contrato\Contrato::find($filtros['codcontrato']);
            $partes[] = 'Contrato: ' . ($ct->contrato ?? $filtros['codcontrato']);
        }
        if (!empty($filtros['codpessoacontrato'])) {
            $pe = \Mg\Pessoa\Pessoa::find($filtros['codpessoacontrato']);
            $partes[] = 'Cliente: ' . ($pe ? ($pe->fantasia ?: $pe->pessoa) : $filtros['codpessoacontrato']);
        }
        if (!empty($filtros['papel'])) {
            $partes[] = 'Somente como ' . mb_strtolower($filtros['papel']);
        }
        foreach (['placa' => 'Placa', 'placacarreta' => 'Carreta', 'motorista' => 'Motorista'] as $k => $rot) {
            if (!empty($filtros[$k])) {
                $partes[] = "{$rot}: {$filtros[$k]}";
            }
        }
        if ((string) ($filtros['inativo'] ?? '1') !== '1') {
            $partes[] = 'Inclui canceladas';
        }
        if ($agrupar !== 'nenhum') {
            $partes[] = 'Agrupado por ' . static::rotuloAgrupamento($agrupar);
        }

        return implode('  ·  ', $partes);
    }

    public static function rotuloAgrupamento(string $agrupar): string
    {
        return match ($agrupar) {
            'dia' => 'dia',
            'mes' => 'mês',
            'sentido' => 'tipo de romaneio',
            'etapa' => 'etapa',
            'safra' => 'safra',
            'cultura' => 'cultura',
            'unidade' => 'unidade armazenadora',
            'plantio' => 'talhão',
            'contrato' => 'contrato',
            'pessoa' => 'cliente/fornecedor',
            'motorista' => 'motorista',
            'placa' => 'placa',
            default => 'nenhum',
        };
    }
}
