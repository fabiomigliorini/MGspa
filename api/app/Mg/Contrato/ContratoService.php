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
        'NaturezaOperacao',
        'PessoaNf',
        'ContratoFixacaoS',
        'ContratoPagamentoS',
        'EmbarqueContratoS.Embarque',
    ];

    public static function pesquisar(?array $filter = null, ?array $sort = null, ?array $fields = null)
    {
        $qry = Contrato::query()
            ->with(static::WITH)
            // Totais p/ a reconciliacao fisico/fiscal/financeiro:
            ->withSum('EmbarqueContratoS as carregado', 'quantidade') // sc carregadas
            ->withSum('EmbarqueContratoS as valornf', 'valornf')       // R$ das NFs
            ->withSum(['ContratoFixacaoS as fixado' => fn ($q) => $q->whereNull('inativo')], 'quantidade')
            ->withSum(['ContratoPagamentoS as pago' => fn ($q) => $q->whereNull('inativo')], 'valor');

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
        if (!empty($filter['tipo'])) {
            $qry->where('tipo', $filter['tipo']);
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
     * Cria/atualiza o contrato e mantém a fixação-espelho sincronizada.
     */
    public static function salvar(array $dados, ?Contrato $contrato = null): Contrato
    {
        $contrato = $contrato ?: new Contrato();
        $contrato->fill($dados);
        $contrato->save();
        static::sincronizarFixacaoAutomatica($contrato);
        return $contrato;
    }

    /**
     * Normaliza a fixação do FIXO: o preço já é travado no próprio contrato,
     * então mantemos UMA fixação "automática" (quantidade cheia, preço/moeda do
     * contrato) pra que fixado e preço médio rodem uniformemente sobre
     * tblcontratofixacao, sem caso especial. FIXAR/BARTER fixam à mão — não
     * mexemos nessas (automatico = false). Apaga a espelho anterior e recria,
     * cobrindo também troca de tipo (FIXAR↔FIXO) sem desincronizar.
     */
    public static function sincronizarFixacaoAutomatica(Contrato $contrato): void
    {
        ContratoFixacao::where('codcontrato', $contrato->codcontrato)
            ->where('automatico', true)
            ->delete();

        if ($contrato->tipo !== 'FIXO') {
            return;
        }

        $dados = [
            'codcontrato' => $contrato->codcontrato,
            'data' => $contrato->dataembarque ?: now()->toDateString(),
            'quantidade' => $contrato->quantidade,
            'preco' => $contrato->preco,
            'moeda' => $contrato->moeda ?: 'BRL',
            'dolar' => null,
            'automatico' => true,
        ];
        $dados['precoreal'] = static::precoReal($dados);
        ContratoFixacao::create($dados);
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
