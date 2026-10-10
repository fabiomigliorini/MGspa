<?php

namespace Mg\Titulo;

use Carbon\Carbon;
use Mg\Pagamento\Pagamento;
use Mg\Pagamento\PagamentoListaService;
use Mg\Pagamento\PagamentoService;
use Mg\Pagamento\PagamentoTituloService;
use Mg\Pdv\Pdv;
use Mg\Portador\LancamentoDataService;

/**
 * Vale colaborador e adiantamentos (M8 do plano doc-3), o mesmo no contas e
 * no PDV: titulo com movimentaportador que ja' nasce com o dinheiro. Um
 * titulo, um pagamento (o mesmo wizard da baixa, uma forma; pode ser um
 * pagamento que ja' existe, com saldo livre), a implantacao ligada a ele e
 * o portador pela regra da baixa de titulos (PagamentoTituloService). Titulo
 * a receber (vale, adiantamento a fornecedor) = sai dinheiro; a pagar
 * (adiantamento/credito de cliente) = entra.
 *
 * No PDV: dinheiro na gaveta (pre-selecionada), filial do PDV e a saida
 * e' so' em dinheiro. Quem pode: o papel no portador (o controller confere). No contas: banco,
 * cofre, cartao da empresa, cheque, cartao e PIX QR, com a filial escolhida
 * (quem pode, o controller confere). Nos dois, a data com hora (TASK-204;
 * sem data, agora): a gaveta recusa sessao fechada.
 *
 * Estorno pela listagem de pagamentos (PagamentoTituloService::estornar
 * desfaz o titulo). Sem transacao interna.
 */
class TituloAdiantamentoService
{
    /**
     * $dados: codtipotitulo, codpessoa, codcontacontabil, vencimento,
     * observacao, codfilial e transacao (contas) e pagamentos[] (as formas do
     * wizard, no formato da baixa de titulos). Devolve os pagamentos criados.
     */
    public static function lancar(array $dados, ?Pdv $pdv = null): array
    {
        $tipo = TipoTitulo::findOrFail((int) $dados['codtipotitulo']);
        if (!$tipo->movimentaportador || !empty($tipo->inativo)) {
            abort(422, "Tipo {$tipo->tipotitulo} não nasce com pagamento!");
        }
        $entrada = !$tipo->ehReceber();
        $codfilial = $pdv->codfilial ?? (int) ($dados['codfilial'] ?? 0);
        if (empty($codfilial)) {
            abort(422, 'Informe a filial!');
        }
        // a data com hora (TASK-204), no contas e no PDV: o periodo sai dela
        $transacao = LancamentoDataService::dataInformada($dados['transacao'] ?? null);
        $vencimento = Carbon::parse($dados['vencimento'])->startOfDay();
        if ($vencimento->lt($transacao->copy()->startOfDay())) {
            abort(422, 'Vencimento não pode ser antes da data do lançamento!');
        }

        // um vale/adiantamento = um pagamento (conceito do Fabio, 09/10/2026)
        $formas = array_values($dados['pagamentos'] ?? []);
        if (count($formas) != 1) {
            abort(422, empty($formas)
                ? 'Informe como foi pago!'
                : 'Um vale ou adiantamento é um pagamento: informe uma forma só.');
        }
        $forma = $formas[0];
        $meio = (int) ($forma['meio'] ?? 0);
        PagamentoTituloService::exigirCentavos($forma['total'] ?? 0);
        if ((float) ($forma['total'] ?? 0) <= 0) {
            abort(422, 'O valor precisa ser maior que zero!');
        }
        if ($meio == PagamentoService::MEIO_COMPENSACAO || !empty($forma['codpagamentoorigem'])) {
            abort(422, "{$tipo->tipotitulo} nasce com dinheiro: compensação e devolução não se aplicam!");
        }
        if ($pdv && !$entrada && ($meio != PagamentoService::MEIO_DINHEIRO || !empty($forma['codpagamento']))) {
            abort(422, "{$tipo->tipotitulo} no PDV sai só em dinheiro!");
        }

        $pag = PagamentoTituloService::pagamentoDaForma($forma, $entrada, false, $dados, $transacao, $pdv, $codfilial);
        // o titulo nasce na data do pagamento (o fato), com o valor da forma
        // (um PIX maior amarra so' a parte dele aqui)
        $dataPagamento = Carbon::parse($pag->transacao ?? $transacao)->toDateString();
        TituloService::criar([
            'codtipotitulo' => $tipo->codtipotitulo,
            'codfilial' => $codfilial,
            'codpessoa' => (int) $dados['codpessoa'],
            'codcontacontabil' => (int) $dados['codcontacontabil'],
            'transacao' => $dataPagamento,
            'emissao' => $dataPagamento,
            'vencimento' => $vencimento->toDateString(),
            'valor' => round((float) $forma['total'], 2),
            'observacao' => $dados['observacao'] ?? null,
        ], $pag);
        if ($entrada && $pag->meio == PagamentoService::MEIO_CHEQUE) {
            PagamentoTituloService::gerarCheque($pag);
        }
        $pagamentos = [$pag];

        return array_map(fn($p) => PagamentoListaService::carregar($p->codpagamento), $pagamentos);
    }
}
