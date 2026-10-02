<?php

namespace Mg\Titulo;

use Carbon\Carbon;
use Mg\Pagamento\Pagamento;
use Mg\Pagamento\PagamentoListaService;
use Mg\Pagamento\PagamentoService;
use Mg\Pagamento\PagamentoTituloService;
use Mg\Pdv\Pdv;
use Mg\Usuario\Autorizador;

/**
 * Vale colaborador e adiantamentos (M8 do plano doc-3), o mesmo no contas e
 * no PDV: titulo com movimentaportador que ja' nasce com o dinheiro. Um
 * titulo por forma, com a implantacao ligada ao pagamento (total = valor) e
 * o portador pela regra da baixa de titulos (PagamentoTituloService). Titulo
 * a receber (vale, adiantamento a fornecedor) = sai dinheiro; a pagar
 * (adiantamento/credito de cliente) = entra.
 *
 * No PDV: dinheiro na gaveta, data = agora, filial do PDV, o caixa lanca
 * sozinho (Caixa/Gerente da filial) e a saida e' so' em dinheiro. No contas:
 * banco, cofre, cartao da empresa, cheque, cartao e PIX QR, com data e
 * filial escolhidas (quem pode, o controller confere).
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
        if ($pdv && !Autorizador::pode([]) && !Autorizador::pode(['Caixa', 'Gerente'], $pdv->codfilial)) {
            abort(403, 'Vale e adiantamento só Caixa da filial, Gerente ou Administrador!');
        }
        $tipo = TipoTitulo::findOrFail((int) $dados['codtipotitulo']);
        if (!$tipo->movimentaportador || !empty($tipo->inativo)) {
            abort(422, "Tipo {$tipo->tipotitulo} não nasce com pagamento!");
        }
        $entrada = !$tipo->ehReceber();
        $codfilial = $pdv->codfilial ?? (int) ($dados['codfilial'] ?? 0);
        if (empty($codfilial)) {
            abort(422, 'Informe a filial!');
        }
        $transacao = $pdv ? Carbon::now() : Carbon::parse($dados['transacao'] ?? 'today')->startOfDay();
        $vencimento = Carbon::parse($dados['vencimento'])->startOfDay();
        if ($vencimento->lt($transacao->copy()->startOfDay())) {
            abort(422, 'Vencimento não pode ser antes da data do lançamento!');
        }

        $formas = array_values($dados['pagamentos'] ?? []);
        if (empty($formas)) {
            abort(422, 'Informe como foi pago!');
        }
        foreach ($formas as $f) {
            $meio = (int) ($f['meio'] ?? 0);
            if ((float) ($f['total'] ?? 0) <= 0) {
                abort(422, 'O valor de cada forma precisa ser maior que zero!');
            }
            if ($meio == PagamentoService::MEIO_COMPENSACAO || !empty($f['codpagamentoorigem'])) {
                abort(422, "{$tipo->tipotitulo} nasce com dinheiro: compensação e devolução não se aplicam!");
            }
            if ($pdv && !$entrada && ($meio != PagamentoService::MEIO_DINHEIRO || !empty($f['codpagamento']))) {
                abort(422, "{$tipo->tipotitulo} no PDV sai só em dinheiro!");
            }
            if ($pdv && !empty($f['codpagamento'])) {
                $pag = Pagamento::findOrFail((int) $f['codpagamento']);
                if (!empty($pag->codpdv) && $pag->codpdv != $pdv->codpdv) {
                    abort(422, "O pagamento {$pag->codpagamento} é de outro PDV!");
                }
            }
        }

        $zero = ['juros' => 0, 'multa' => 0, 'desconto' => 0];
        $pagamentos = [];
        foreach ($formas as $forma) {
            $pag = PagamentoTituloService::pagamentoDaForma($forma, $entrada, false, $zero, $dados, $transacao, $pdv, $codfilial);
            TituloService::criar([
                'codtipotitulo' => $tipo->codtipotitulo,
                'codfilial' => $codfilial,
                'codpessoa' => (int) $dados['codpessoa'],
                'codcontacontabil' => (int) $dados['codcontacontabil'],
                'transacao' => $transacao->toDateString(),
                'emissao' => $transacao->toDateString(),
                'vencimento' => $vencimento->toDateString(),
                'valor' => (float) $pag->total,
                'observacao' => $dados['observacao'] ?? null,
            ], $pag);
            if ($entrada && $pag->meio == PagamentoService::MEIO_CHEQUE) {
                PagamentoTituloService::gerarCheque($pag);
            }
            $pagamentos[] = $pag;
        }

        return array_map(fn($p) => PagamentoListaService::carregar($p->codpagamento), $pagamentos);
    }
}
