<?php

namespace Mg\Contrato;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
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
     * Sugere o próximo Nº Nosso da safra na convenção CULTURA-AA/AA-NNNN.
     * Ex: SOJA-26/27-0001. O bloco do ano deriva do ciclo da cultura (cicloanos>=2
     * vira o ano → AA/AA; caso contrário só o ano de plantio → AA). O sequencial é
     * o maior sufixo numérico já gravado nessa safra + 1 (tolera nº manual/excluído).
     */
    public static function proximoNumero(int $codsafra): string
    {
        $safra = DB::selectOne(
            '
            select s.anoplantio, s.anocolheita, c.cultura, c.cicloanos
            from tblsafra s
            join tblcultura c on c.codcultura = s.codcultura
            where s.codsafra = ?
            ',
            [$codsafra],
        );
        if (!$safra) {
            abort(404, 'Safra não encontrada.');
        }

        $sigla = Str::upper(Str::ascii($safra->cultura));
        $plantio = substr((string) $safra->anoplantio, -2);
        $ano = $safra->cicloanos >= 2
            ? $plantio . '/' . substr((string) $safra->anocolheita, -2)
            : $plantio;
        $prefixo = "{$sigla}-{$ano}-";

        $row = DB::selectOne(
            "
            select coalesce(max(cast(substring(contrato from '([0-9]+)\$') as integer)), 0) + 1 as proximo
            from tblcontrato
            where codsafra = ? and contrato like ?
            ",
            [$codsafra, $prefixo . '%'],
        );

        return sprintf('%s%04d', $prefixo, $row->proximo);
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
        // Nº Nosso: convenção CULTURA-AA/AA-NNNN (ex: SOJA-26/27-0001), sequencial
        // por safra. Sugerido no form (editável); aqui é a rede de segurança que
        // garante numeração na criação quando o usuário não digitou nada.
        if (!$contrato->exists && empty($contrato->contrato) && !empty($contrato->codsafra)) {
            $contrato->contrato = static::proximoNumero((int) $contrato->codsafra);
        }
        // quantidade NULL = volume em aberto (leva o saldo do silo; sem teto). A
        // coluna é nullable (agro_contrato_refatoracao.sql); não ancorar em 0,
        // senão deixa de ser "em aberto" e o tipo/saldo derivam errado.
        $contrato->save();
        return $contrato;
    }

    /**
     * Preco em R$/saca de uma fixacao: moeda estrangeira (qualquer != BRL) com
     * cotacao travada => preco x cotacao; BRL (ou sem cotacao) => o proprio preco.
     */
    public static function precoReal(array $dados): ?float
    {
        $preco = $dados['preco'] ?? null;
        if ($preco === null || $preco === '') {
            return null;
        }
        if (($dados['moeda'] ?? 'BRL') !== 'BRL' && !empty($dados['dolar'])) {
            return round((float) $preco * (float) $dados['dolar'], 4);
        }
        return round((float) $preco, 4);
    }
}
