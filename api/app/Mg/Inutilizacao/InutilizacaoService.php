<?php

namespace Mg\Inutilizacao;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

use Mg\Filial\Filial;
use Mg\NFePHP\NFePHPPathService;
use Mg\NFePHP\NFePHPService;
use Mg\NotaFiscal\NotaFiscal;

class InutilizacaoService
{
    /**
     * Inutiliza uma faixa de numeracao.
     *
     * Ordem: valida -> cria a linha -> chama a SEFAZ -> grava protocolo e XML -> marca as
     * notas que existirem dentro da faixa.
     */
    public static function inutilizar(
        Filial $filial,
        int $modelo,
        int $serie,
        int $numeroInicial,
        int $numeroFinal,
        string $justificativa
    ): Inutilizacao {
        if ($numeroFinal < $numeroInicial) {
            throw new \Exception('O número final da faixa não pode ser menor que o inicial!');
        }

        static::validarSobreposicao($filial, $modelo, $serie, $numeroInicial, $numeroFinal);

        $inut = Inutilizacao::create([
            'codfilial' => $filial->codfilial,
            'modelo' => $modelo,
            'serie' => $serie,
            'numeroinicial' => $numeroInicial,
            'numerofinal' => $numeroFinal,
            'ambiente' => $filial->nfeambiente,
            'justificativa' => $justificativa,
        ]);

        $res = NFePHPService::inutilizar($filial, $modelo, $serie, $numeroInicial, $numeroFinal, $justificativa);

        $inut->cstat = $res->cStat;
        $inut->xmotivo = mb_substr((string) $res->xMotivo, 0, 255);
        $inut->protocolo = $res->protocolo;
        $inut->protocolodata = $res->protocolodata;

        if ($res->sucesso && !empty($res->xml)) {
            $inut->arquivo = static::gravarXml($inut, $res->xml);
        }

        $inut->save();

        if ($res->sucesso) {
            static::marcarNotasDaFaixa($inut);
        }

        return $inut->fresh();
    }

    /**
     * Barra faixa que se sobrepoe a uma inutilizacao ja homologada.
     *
     * Primeira barreira contra reinutilizar numero que ja foi inutilizado — a segunda e a
     * propria SEFAZ (rejeicao 256/563), mas chegar la custa uma ida a rede e devolve um
     * erro pior de entender.
     */
    protected static function validarSobreposicao(
        Filial $filial,
        int $modelo,
        int $serie,
        int $numeroInicial,
        int $numeroFinal
    ): void {
        $sql = '
            select numeroinicial, numerofinal
            from tblinutilizacao
            where codfilial = :codfilial
            and modelo = :modelo
            and serie = :serie
            and protocolo is not null
            and numeroinicial <= :numerofinal
            and numerofinal >= :numeroinicial
            order by numeroinicial
            limit 1
        ';

        $conflito = DB::selectOne($sql, [
            'codfilial' => $filial->codfilial,
            'modelo' => $modelo,
            'serie' => $serie,
            'numeroinicial' => $numeroInicial,
            'numerofinal' => $numeroFinal,
        ]);

        if ($conflito) {
            throw new \Exception(
                "A faixa {$numeroInicial}-{$numeroFinal} se sobrepõe à inutilização já homologada " .
                "{$conflito->numeroinicial}-{$conflito->numerofinal}."
            );
        }
    }

    protected static function gravarXml(Inutilizacao $inut, string $xml): ?string
    {
        try {
            $path = NFePHPPathService::pathInutilizacao(
                $inut->Filial,
                $inut->modelo,
                $inut->serie,
                $inut->numeroinicial,
                $inut->numerofinal,
                $inut->protocolodata ?? now(),
                true
            );
            file_put_contents($path, $xml);

            return ltrim(str_replace(rtrim(config('mg.paths.nfe_php'), '/'), '', $path), '/');
        } catch (\Throwable $e) {
            Log::warning("Inutilizacao #{$inut->codinutilizacao}: falha ao gravar XML: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Marca as notas que existirem dentro da faixa.
     *
     * TEM que ser Eloquent nota a nota, NAO DB::table()->update(): o
     * NotaFiscalObserver::updating usa isDirty() e so dispara em save do Eloquent. Com
     * update em massa o observer nao roda e o status nao vira INU.
     *
     * E a excecao a preferencia do projeto por SQL cru, e o motivo esta aqui para ninguem
     * "otimizar" isso depois.
     */
    protected static function marcarNotasDaFaixa(Inutilizacao $inut): int
    {
        $notas = NotaFiscal::where('codfilial', $inut->codfilial)
            ->where('modelo', $inut->modelo)
            ->where('serie', $inut->serie)
            ->whereBetween('numero', [$inut->numeroinicial, $inut->numerofinal])
            ->whereNull('nfeinutilizacao')
            ->get();

        foreach ($notas as $nf) {
            $nf->nfeinutilizacao = $inut->protocolo;
            $nf->nfedatainutilizacao = $inut->protocolodata;
            $nf->justificativa = $inut->justificativa;
            $nf->save(); // dispara o observer -> status = INU
        }

        return $notas->count();
    }

    /**
     * Listagem com filtros, para a tela de inutilizacoes.
     */
    public static function listar(array $filtros = [])
    {
        $qry = Inutilizacao::with('Filial');

        if (!empty($filtros['codfilial'])) {
            $qry->where('codfilial', $filtros['codfilial']);
        }
        if (!empty($filtros['modelo'])) {
            $qry->where('modelo', $filtros['modelo']);
        }
        if (!empty($filtros['serie'])) {
            $qry->where('serie', $filtros['serie']);
        }
        if (!empty($filtros['numero'])) {
            $qry->where('numeroinicial', '<=', $filtros['numero'])
                ->where('numerofinal', '>=', $filtros['numero']);
        }

        // Faixa de datas, e nao "extract(year from ...) = :ano", para que a consulta continue
        // aproveitavel por um indice se um dia a tabela crescer a ponto de precisar de um.
        // O coalesce acompanha o agrupamento de anos(): a faixa ainda sem protocolo pertence
        // ao ano em que foi tentada — e e justamente a que o usuario precisa achar.
        if (!empty($filtros['ano'])) {
            $ano = (int) $filtros['ano'];
            $qry->whereRaw('coalesce(protocolodata, criacao) >= ?', ["{$ano}-01-01"])
                ->whereRaw('coalesce(protocolodata, criacao) < ?', [($ano + 1) . '-01-01']);
        }

        // Pendente no topo do ano: sem protocolo a faixa exige acao, entao nao pode ficar
        // enterrada no meio de milhares de linhas ja homologadas.
        $qry->orderByRaw('coalesce(protocolodata, criacao) desc')->orderBy('numeroinicial', 'desc');

        // Limite so quando pedido: a tela carrega o ano inteiro e o pior ano do historico tem
        // 11.231 faixas (filial 101 em 2020). Um default cortaria isso em silencio.
        if (!empty($filtros['limite'])) {
            $qry->limit((int) $filtros['limite']);
        }

        return $qry->get();
    }

    /**
     * Anos que tem inutilizacao, de TODAS as filiais — o filtro principal da tela.
     *
     * O coalesce com criacao existe porque a linha e gravada ANTES da chamada a SEFAZ, e a
     * coluna protocolodata voltou a aceitar nulo por causa disso: se a resposta se perder num
     * timeout, o registro previo e a unica prova de que a faixa ja foi enviada — sem ele, a
     * proxima tentativa bate em "faixa ja inutilizada" sem ninguem entender o motivo.
     *
     * Essas faixas pendentes PRECISAM aparecer (sao as que exigem acao), entao caem no ano em
     * que foram tentadas, e nao num "ano 0" a parte. E o mesmo criterio que o
     * database/inutilizacao_protocolodata.sql usou para datar o historico importado.
     */
    public static function anos(): array
    {
        $sql = '
            select extract(year from coalesce(protocolodata, criacao))::int as ano,
                count(*) as faixas,
                sum(numerofinal - numeroinicial + 1) as numeros
            from tblinutilizacao
            group by 1
            order by 1 desc
        ';

        return array_map(fn ($reg) => [
            'ano' => (int) $reg->ano,
            'faixas' => (int) $reg->faixas,
            'numeros' => (int) $reg->numeros,
        ], DB::select($sql));
    }

    /**
     * Filiais que tem inutilizacao NAQUELE ANO, com os totais do ano — as abas do topo.
     *
     * Recortado pelo ano de proposito: a aba precisa refletir o que o usuario vai encontrar
     * se clicar nela. Uma filial que so inutilizou em 2014 nao aparece quando o ano e 2026.
     *
     * Nao e a mesma lista de quem PODE inutilizar (essa e emitenfe, no select do dialog):
     * aqui interessa quem tem historico para mostrar, inclusive filial ja desativada.
     */
    public static function filiais(int $ano): array
    {
        $sql = '
            select i.codfilial,
                f.filial,
                count(*) as faixas,
                sum(i.numerofinal - i.numeroinicial + 1) as numeros
            from tblinutilizacao i
            inner join tblfilial f on (f.codfilial = i.codfilial)
            where coalesce(i.protocolodata, i.criacao) >= :inicio
            and coalesce(i.protocolodata, i.criacao) < :fim
            group by i.codfilial, f.filial
            order by i.codfilial
        ';

        return array_map(fn ($reg) => [
            'codfilial' => (int) $reg->codfilial,
            'filial' => $reg->filial,
            'faixas' => (int) $reg->faixas,
            'numeros' => (int) $reg->numeros,
        ], DB::select($sql, [
            'inicio' => "{$ano}-01-01",
            'fim' => ($ano + 1) . '-01-01',
        ]));
    }
}
