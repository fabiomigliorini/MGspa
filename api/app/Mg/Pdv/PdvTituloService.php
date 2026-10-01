<?php

namespace Mg\Pdv;

use Carbon\Carbon;
use Mg\Pagamento\Pagamento;
use Mg\Pagamento\PagamentoListaService;
use Mg\Pagamento\PagamentoService;
use Mg\Pagamento\PagamentoTituloService;
use Mg\Titulo\TipoTitulo;
use Mg\Titulo\TituloService;
use Mg\Usuario\Autorizador;

/**
 * Vale colaborador e adiantamentos no PDV (M8 do plano doc-3): titulo com
 * movimentaportador que ja' nasce com o dinheiro. Um titulo por forma, com
 * a implantacao ligada ao pagamento (total = valor). Vale colaborador e
 * adiantamento a fornecedor saem da gaveta em dinheiro; adiantamento de
 * cliente entra pelas formas do Receber titulo (cobranca integrada amarrada
 * pelo codpagamento que a confirmacao criou). O caixa lanca sozinho; fica
 * registrado quem lancou. Estorno pela listagem de pagamentos
 * (PagamentoTituloService::estornar desfaz o titulo). Sem transacao interna.
 */
class PdvTituloService
{
    const TIPO_VALE_COLABORADOR = 2;
    const TIPO_ADTO_FORNECEDOR = 120;
    const TIPO_ADTO_CLIENTE = 220;

    const TIPOS = [
        self::TIPO_VALE_COLABORADOR,
        self::TIPO_ADTO_FORNECEDOR,
        self::TIPO_ADTO_CLIENTE,
    ];

    /**
     * $dados: codtipotitulo, codpessoa, codcontacontabil, vencimento,
     * observacao e pagamentos[] (as formas do wizard, no formato da baixa de
     * titulos). Devolve os pagamentos criados.
     */
    public static function lancar(Pdv $pdv, array $dados): array
    {
        if (!Autorizador::pode([]) && !Autorizador::pode(['Caixa', 'Gerente'], $pdv->codfilial)) {
            abort(403, 'Vale e adiantamento só Caixa da filial, Gerente ou Administrador!');
        }
        $tipo = TipoTitulo::findOrFail((int) $dados['codtipotitulo']);
        if (!in_array($tipo->codtipotitulo, static::TIPOS) || !$tipo->movimentaportador || !empty($tipo->inativo)) {
            abort(422, "Tipo {$tipo->tipotitulo} não é lançado no PDV!");
        }
        // titulo a receber (vale, adiantamento a fornecedor) = sai dinheiro;
        // a pagar (adiantamento de cliente) = entra
        $entrada = !$tipo->ehReceber();
        $vencimento = Carbon::parse($dados['vencimento'])->startOfDay();
        if ($vencimento->lt(Carbon::today())) {
            abort(422, 'Vencimento não pode ser antes de hoje!');
        }

        $formas = array_values($dados['pagamentos'] ?? []);
        if (empty($formas)) {
            abort(422, 'Informe como foi pago!');
        }
        foreach ($formas as $f) {
            if ((float) ($f['total'] ?? 0) <= 0) {
                abort(422, 'O valor de cada forma precisa ser maior que zero!');
            }
            if (!$entrada && ((int) ($f['meio'] ?? 0) != PagamentoService::MEIO_DINHEIRO || !empty($f['codpagamento']) || !empty($f['codpagamentoorigem']))) {
                abort(422, "{$tipo->tipotitulo} no PDV sai só em dinheiro!");
            }
            if (!empty($f['codpagamento'])) {
                $pag = Pagamento::findOrFail((int) $f['codpagamento']);
                if (!empty($pag->codpdv) && $pag->codpdv != $pdv->codpdv) {
                    abort(422, "O pagamento {$pag->codpagamento} é de outro PDV!");
                }
            }
        }

        $agora = Carbon::now();
        $zero = ['juros' => 0, 'multa' => 0, 'desconto' => 0];
        $pagamentos = [];
        foreach ($formas as $forma) {
            $pag = PagamentoTituloService::pagamentoDaForma($forma, $entrada, false, $zero, $dados, $agora, $pdv, $pdv->codfilial);
            TituloService::criar([
                'codtipotitulo' => $tipo->codtipotitulo,
                'codfilial' => $pdv->codfilial,
                'codpessoa' => (int) $dados['codpessoa'],
                'codcontacontabil' => (int) $dados['codcontacontabil'],
                'transacao' => $agora->toDateString(),
                'emissao' => $agora->toDateString(),
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
