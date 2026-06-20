<?php

namespace Mg\NotaFiscal;

use Mg\Contrato\Contrato;
use Mg\Contrato\ContratoCalculoService;
use Mg\Grao\CargaPonto;

/**
 * Inteligencia da emissao de NF de graos a partir do CONTRATO (espelho do
 * NotaFiscalNegocioService, lado contrato). Resolve, para um contrato + carga,
 * o PLANO de notas a emitir: a operacao triangular (venda a ordem) gera N notas
 * pra mesma carga, numa sequencia (ordem) em que cada nota pode referenciar a
 * chave de outra (refNFe) — o plano vem de tblcontratonota (ver ContratoNota).
 *
 * Este servico calcula tudo que o emissor precisa (partes, kg/sacas rateados da
 * carga, valor bruto/liquido por saca com a tributacao de grao ja existente, e
 * o encadeamento pai->filho). A CRIACAO dos registros tblnotafiscal + a
 * transmissao a SEFAZ reusam a pipeline de NotaFiscal existente; falta apenas o
 * vinculo cultura -> produto fiscal (codprodutobarra/NCM), que ainda nao existe
 * no cadastro de cultura e e pre-requisito p/ o item da NFe.
 */
class NotaFiscalContratoService
{
    /**
     * Plano de emissao das notas de um contrato para UMA carga.
     *
     * @return array {
     *   codcontrato, codcarga, cultura, pesosaca,
     *   kg, sacas, precobruto, precoliquido,
     *   notas: [{ ordem, codcontratonota, refereordem, natureza, pessoa,
     *             observacao, valormercadoria, tributos:[...], emitivel }]
     * }
     */
    public static function planoEmissao(int $codcontrato, int $codcarga): array
    {
        $contrato = Contrato::with([
            'Cultura',
            'Filial',
            'ContratoFixacaoS',
            'ContratoNotaS' => fn ($q) => $q->whereNull('inativo')->orderBy('ordem')->orderBy('codcontratonota'),
            'ContratoNotaS.NaturezaOperacao',
            'ContratoNotaS.PessoaNf',
        ])->findOrFail($codcontrato);

        $pesosaca = (float) ($contrato->Cultura->pesosaca ?? 60) ?: 60;

        // kg desta carga rateado a este contrato (pontos da carga que o apontam).
        $kg = (float) CargaPonto::where('codcarga', $codcarga)
            ->where('codcontrato', $codcontrato)
            ->whereHas('Carga', fn ($q) => $q->whereNull('inativo'))
            ->sum('liquido');
        $sacas = $pesosaca > 0 ? $kg / $pesosaca : 0.0;

        // Preco bruto/liquido por saca + quebra de tributos (motor fiscal do agro).
        $calc = ContratoCalculoService::calcularDoContrato($contrato);
        $precobruto = (float) $calc['bruto'];
        $precoliquido = (float) $calc['liquido'];

        // mapa codcontratonota -> ordem, p/ informar qual nota cada filha referencia.
        $ordemPorCod = $contrato->ContratoNotaS->pluck('ordem', 'codcontratonota');

        $notas = $contrato->ContratoNotaS->map(function ($n) use ($sacas, $calc, $ordemPorCod) {
            $natureza = $n->NaturezaOperacao;
            // tributos da carga = tributo R$/saca x sacas desta carga.
            $tributos = collect($calc['itens'])->map(fn ($t) => [
                'codigo' => $t['codigo'],
                'descricao' => $t['descricao'],
                'valor' => round((float) $t['valor'] * $sacas, 2),
            ])->values();

            return [
                'ordem' => (int) $n->ordem,
                'codcontratonota' => (int) $n->codcontratonota,
                // ordem da nota-pai (a que esta referencia via refNFe), se houver.
                'refereordem' => $n->codcontratonotapai
                    ? (int) ($ordemPorCod[$n->codcontratonotapai] ?? 0)
                    : null,
                'codnaturezaoperacao' => $n->codnaturezaoperacao,
                'natureza' => $natureza->naturezaoperacao ?? null,
                'emitida' => $natureza ? (bool) $natureza->emitida : false,
                'codpessoanf' => $n->codpessoanf,
                'pessoa' => $n->PessoaNf->fantasia ?? ($n->PessoaNf->pessoa ?? null),
                'observacao' => $n->observacaonf,
                // valor da mercadoria da nota = sacas x preco bruto (mesma carga).
                'valormercadoria' => round($sacas * (float) $calc['bruto'], 2),
                'tributos' => $tributos,
            ];
        })->values();

        return [
            'codcontrato' => (int) $contrato->codcontrato,
            'codcarga' => $codcarga,
            'cultura' => $contrato->Cultura->cultura ?? null,
            'pesosaca' => $pesosaca,
            'kg' => round($kg, 3),
            'sacas' => round($sacas, 2),
            'precobruto' => $precobruto,
            'precoliquido' => $precoliquido,
            'valorbruto' => round($sacas * $precobruto, 2),
            'valorliquido' => round($sacas * $precoliquido, 2),
            'notas' => $notas,
            'semplano' => $notas->isEmpty(),
        ];
    }
}
