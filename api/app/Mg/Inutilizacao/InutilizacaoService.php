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
     * Listagem com filtros. SQL cru, que e a preferencia do projeto em leitura.
     */
    public static function listar(array $filtros = [])
    {
        $where = [];
        $params = [];

        if (!empty($filtros['codfilial'])) {
            $where[] = 'i.codfilial = :codfilial';
            $params['codfilial'] = $filtros['codfilial'];
        }
        if (!empty($filtros['modelo'])) {
            $where[] = 'i.modelo = :modelo';
            $params['modelo'] = $filtros['modelo'];
        }
        if (!empty($filtros['serie'])) {
            $where[] = 'i.serie = :serie';
            $params['serie'] = $filtros['serie'];
        }
        if (!empty($filtros['numero'])) {
            $where[] = ':numero between i.numeroinicial and i.numerofinal';
            $params['numero'] = $filtros['numero'];
        }

        $sqlWhere = $where ? ('where ' . implode(' and ', $where)) : '';
        $limite = (int) ($filtros['limite'] ?? 200);

        $sql = "
            select i.*, f.filial
            from tblinutilizacao i
            inner join tblfilial f on (f.codfilial = i.codfilial)
            {$sqlWhere}
            order by i.criacao desc, i.codinutilizacao desc
            limit {$limite}
        ";

        return DB::select($sql, $params);
    }
}
