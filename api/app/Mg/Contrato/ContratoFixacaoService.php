<?php

namespace Mg\Contrato;

use Mg\MgService;

/**
 * Regras da fixação de preço. O coração aqui é recalcular() — a fonte única dos
 * 4 totais GRAVADOS na tblcontratofixacao, chamada a cada operação de trava de
 * câmbio (ContratoFixacaoCambioController) e a cada criar/editar fixação.
 *
 *   totalmoeda = quantidade × preco               (na moeda da fixação)
 *   saldomoeda = totalmoeda − Σ(trava.valor)       (moeda ainda a travar)
 *   totalbrl   = Σ(trava.valor × trava.cotacao)    (travado, bruto em R$)
 *   liquidobrl = totalbrl − impostos               (travado, líquido em R$)
 *
 * BRL é o caso trivial: não há câmbio a travar (saldomoeda=0), o total já está
 * em R$ (totalbrl = totalmoeda) e o líquido sai dos impostos sobre tudo.
 * Estrangeira: só a fatia TRAVADA vira R$/líquido; a flutuante (saldomoeda) não
 * tem R$ até travar/receber.
 */
class ContratoFixacaoService extends MgService
{
    public static function recalcular(ContratoFixacao $f): ContratoFixacao
    {
        $f->loadMissing('Moeda', 'Contrato.Filial');

        $estrangeira = (bool) $f->estrangeira;
        $qtd = (float) $f->quantidade;
        $preco = (float) $f->preco;
        $totalmoeda = round($qtd * $preco, 2);

        // Travas SEMPRE frescas do banco (recalcular roda logo após criar/editar/
        // excluir uma trava — uma relação já cacheada estaria desatualizada).
        $travas = $f->ContratoFixacaoCambioS()->whereNull('inativo')->get();
        $travadomoeda = (float) $travas->sum('valor');
        $travadobrl = (float) $travas->sum(fn ($c) => (float) $c->valor * (float) $c->cotacao);

        if ($estrangeira) {
            $totalbrl = round($travadobrl, 2);
            $saldomoeda = round($totalmoeda - $travadomoeda, 2);
            $sacastravadas = $preco > 0 ? $travadomoeda / $preco : 0.0;
        } else {
            $totalbrl = $totalmoeda;              // BRL já está em R$
            $saldomoeda = 0.0;
            $sacastravadas = $qtd;                // tudo firme
        }

        $f->totalmoeda = $totalmoeda;
        $f->saldomoeda = $saldomoeda;
        $f->totalbrl = $totalbrl;
        $f->liquidobrl = static::liquido($f, $totalbrl, $sacastravadas);
        $f->save();

        return $f;
    }

    /**
     * Líquido em R$ da parte firme (bruto − impostos). Reusa o motor fiscal por
     * saca (ContratoCalculoService) sobre o bruto/saca da fatia travada e
     * multiplica pelas sacas. Nada travado (bruto 0) → líquido 0 (a fatia
     * flutuante só ganha líquido quando o câmbio é travado).
     */
    protected static function liquido(ContratoFixacao $f, float $totalbrl, float $sacastravadas): float
    {
        if ($totalbrl <= 0 || $sacastravadas <= 0) {
            return 0.0;
        }
        $contrato = $f->Contrato;
        if (!$contrato || empty($contrato->codcultura)) {
            return round($totalbrl, 2); // sem cultura não há como deduzir
        }
        $funruralvenda = $contrato->codfilial && $contrato->Filial
            ? (bool) $contrato->Filial->funruralvenda
            : false;

        $tributos = is_array($f->tributos) && count($f->tributos) ? $f->tributos : null;

        $calc = ContratoCalculoService::calcular([
            'codcultura' => (int) $contrato->codcultura,
            'bruto' => $totalbrl / $sacastravadas, // R$/saca da fatia travada
            'data' => $f->data,
            'funruralvenda' => $funruralvenda,
            'tributos' => $tributos, // override quando o operador declarou as linhas
        ]);

        return round((float) $calc['liquido'] * $sacastravadas, 2);
    }
}
