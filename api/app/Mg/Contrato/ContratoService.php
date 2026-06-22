<?php

namespace Mg\Contrato;

use Mg\MgService;

class ContratoService extends MgService
{
    const WITH = ['Pessoa', 'Cultura', 'Safra'];

    const WITH_DETALHE = [
        'Pessoa',
        'Cultura',
        'Safra',
        'Filial',
        'Portador',
        'Corretora',
        'Cooperativa',
        'ContratoFixacaoS',
        'ContratoPagamentoS',
        'ContratoNotaS.NaturezaOperacao',
        'ContratoNotaS.PessoaNf',
        'MovimentoGraoS.Carga',
    ];

    public static function pesquisar(?array $filter = null, ?array $sort = null, ?array $fields = null)
    {
        // Entregue/recebido = SUM(liquido) no extrato (tblmovimentograo) das
        // linhas ativas deste contrato. Carga inativada some do extrato (estorno),
        // entao nao precisa filtrar carga aqui.
        $movAtivo = fn($q) => $q->whereNull('inativo');

        $qry = Contrato::query()
            ->with(static::WITH)
            // Totais p/ a reconciliacao fisico/fiscal/financeiro:
            ->withSum(['MovimentoGraoS as carregadokg' => $movAtivo], 'liquido') // KG fisico entregue
            ->withSum(
                ['CargaPontoS as valornf' => fn($q) => $q->whereHas('Carga', fn($c) => $c->whereNull('inativo'))],
                'valornf',
            ) // R$ das NFs por contrato (tblcargaponto; 0 ate emitir NFe)
            ->withSum(['ContratoFixacaoS as fixado' => fn($q) => $q->whereNull('inativo')], 'quantidade')
            ->withSum(['ContratoPagamentoS as pago' => fn($q) => $q->whereNull('inativo')], 'valor')
            // barter (settlement em insumos) vive no pagamento; usado p/ derivar o tipo.
            ->withCount(['ContratoPagamentoS as bartercount' => fn($q) => $q->where('forma', 'BARTER')->whereNull('inativo')]);

        if (!empty($filter['codcontrato'])) {
            $qry->where('codcontrato', $filter['codcontrato']);
        }
        if (!empty($filter['contrato'])) {
            $qry->palavras('contrato', $filter['contrato']);
        }
        if (!empty($filter['codpessoa'])) {
            $qry->where('codpessoa', $filter['codpessoa']);
        }
        if (!empty($filter['codcultura'])) {
            $qry->where('codcultura', $filter['codcultura']);
        }
        if (!empty($filter['codsafra'])) {
            $qry->where('codsafra', $filter['codsafra']);
        }
        if (!empty($filter['inativo'])) {
            $qry->AtivoInativo($filter['inativo']);
        }

        $qry = self::qryOrdem($qry, $sort ?: ['-codcontrato']);
        $qry = self::qryColunas($qry, $fields);
        return $qry;
    }

    public static function detalhe(int $codcontrato): Contrato
    {
        return static::pesquisar(['codcontrato' => $codcontrato])
            ->with(static::WITH_DETALHE)
            ->firstOrFail();
    }

    /**
     * Cria/atualiza o contrato. Precificação vive nas fixações (tblcontratofixacao):
     * um contrato "FIXO" é só um contrato que recebe a fixação cheia na assinatura;
     * não há mais fixação-espelho automática.
     */
    public static function salvar(array $dados, ?Contrato $contrato = null): Contrato
    {
        $contrato = $contrato ?: new Contrato();
        $contrato->fill($dados);
        if (empty($contrato->operacao)) {
            $contrato->operacao = 'VENDA';
        }
        // Contrato nasce como rascunho (só identificação): a quantidade é
        // definida depois na tela do contrato. A coluna é NOT NULL, então
        // ancora em 0 ("ainda não contratado") — o FormRequest aceita null.
        if ($contrato->quantidade === null) {
            $contrato->quantidade = 0;
        }
        $contrato->save();
        return $contrato;
    }

    /**
     * Preco em R$/saca de uma fixacao: USD travado => preco x dolar; senao preco.
     */
    public static function precoReal(array $dados): ?float
    {
        $preco = $dados['preco'] ?? null;
        if ($preco === null || $preco === '') {
            return null;
        }
        if (($dados['moeda'] ?? 'BRL') === 'USD' && !empty($dados['dolar'])) {
            return round((float) $preco * (float) $dados['dolar'], 4);
        }
        return round((float) $preco, 4);
    }
}
