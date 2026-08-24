<?php

namespace Mg\NFePHP;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

use Mg\Filial\Empresa;

/**
 * Entra e sai da contingencia off-line da NFC-e a partir da LATENCIA REAL das emissoes.
 *
 * POR QUE NAO USAR sefazStatus
 *
 * O servico de status da SEFAZ mente: responde "Servico em Operacao" com frequencia em
 * momentos em que a emissao falha. Por isso a metrica so olha operacoes de emissao de
 * verdade (enviaLote/consultaChave) — nada de ping sintetico.
 *
 * POR QUE A VOLTA PARA ONLINE FUNCIONA SEM PING
 *
 * Em contingencia a NFC-e nao vai a SEFAZ, o que parece tirar a fonte de dados que
 * decidiria sair. Mas cada NFC-e emitida offline vira uma NOTA PENDENTE, e o robo
 * (nfe-php:resolver-pendentes, a cada 10 min, com a janela de 26h) tenta transmiti-la.
 * Essas transmissoes SAO tentativas de conexao reais e alimentam a janela de boas. Ou
 * seja: emitir offline gera, ele mesmo, o trafego que decide a volta. A NF-e (modelo 55)
 * continua online e tambem conta.
 *
 * O unico caso em que o contador nao anda e entrar em contingencia num momento parado,
 * sem backlog e sem NF-e — e ai FICAR em contingencia e o comportamento seguro: emissao
 * offline sempre funciona, enquanto voltar para online sem evidencia trava o balcao. Na
 * primeira NFC-e do movimento seguinte o robo transmite em ate 10 min e a evidencia volta
 * a acumular sozinha.
 *
 * NAO "consertar" isso adicionando ping: ja foi avaliado e descartado.
 *
 * LIMITE_BOAS e parametro, nao invariante: a volta acontece na velocidade das transmissoes
 * reais, entao num dia fraco demora mais. Calibrar com os dados da tblsefazcomunicacao.
 */
class ContingenciaService
{
    /** Conversas consecutivas ruins que disparam a entrada em contingencia. */
    const LIMITE_RUINS = 10;

    /** Conversas consecutivas boas que devolvem a empresa para o modo online. */
    const LIMITE_BOAS = 20;

    /**
     * Operacoes que contam como "tentativa de emissao" para a metrica.
     * Deliberadamente NAO inclui 'status' (sefazStatus mente) nem consultas de cadastro.
     */
    const OPERACOES_RELEVANTES = ['enviaLote', 'consultaChave'];

    public static function operacaoRelevante(string $operacao): bool
    {
        return in_array($operacao, static::OPERACOES_RELEVANTES, true);
    }

    /**
     * Avalia a janela de conversas recentes e alterna o modo de emissao se for o caso.
     *
     * Roda FORA do try/catch do SefazLogService de proposito — ver comentario em
     * NFePHPService::registrarConversa().
     */
    public static function avaliar(?Empresa $empresa): void
    {
        if ($empresa === null || !$empresa->contingenciaautomatica) {
            return;
        }

        $normal = ($empresa->modoemissaonfce != Empresa::MODOEMISSAONFCE_OFFLINE);
        $limite = $normal ? static::LIMITE_RUINS : static::LIMITE_BOAS;
        $janela = static::janela($empresa, $limite);

        // Janela incompleta: nao ha evidencia suficiente, nao mexe.
        if ($janela->total < $limite) {
            return;
        }

        if ($normal && $janela->ruins >= static::LIMITE_RUINS) {
            static::forcar($empresa, sprintf(
                'Ativacao automatica: %d comunicacoes seguidas com a SEFAZ acima de %ds ou com erro',
                static::LIMITE_RUINS,
                $empresa->contingenciatolerancia
            ));
            Log::alert("Contingencia ATIVADA automaticamente para a empresa {$empresa->codempresa} ({$empresa->empresa})");
            return;
        }

        if (!$normal && $janela->ruins == 0) {
            static::normalizar($empresa);
            Log::notice("Contingencia DESATIVADA automaticamente para a empresa {$empresa->codempresa} ({$empresa->empresa})");
        }
    }

    /**
     * Ultimas N conversas de emissao da empresa (todas as filiais somadas).
     *
     * Agrega por EMPRESA e nao por filial porque e la que mora o modoemissaonfce, todas as
     * filiais falam com a mesma SEFAZ e juntar da mais amostras. O indice
     * (codfilial, criacao desc, codsefazcomunicacao desc) mantem isso sub-milissegundo.
     */
    protected static function janela(Empresa $empresa, int $limite)
    {
        $sql = "
            select
                count(*) as total,
                count(*) filter (where not sucesso or duracaoms > :toleranciams) as ruins
            from (
                select c.sucesso, c.duracaoms
                from tblsefazcomunicacao c
                inner join tblfilial f on (f.codfilial = c.codfilial)
                where f.codempresa = :codempresa
                and c.operacao in ('enviaLote', 'consultaChave')
                order by c.criacao desc, c.codsefazcomunicacao desc
                limit {$limite}
            ) t
        ";

        return DB::selectOne($sql, [
            'codempresa' => $empresa->codempresa,
            'toleranciams' => $empresa->contingenciatolerancia * 1000,
        ]);
    }

    /**
     * Entra em contingencia. Usado pela avaliacao automatica e pelo override manual.
     */
    public static function forcar(Empresa $empresa, string $justificativa): Empresa
    {
        $empresa->modoemissaonfce = Empresa::MODOEMISSAONFCE_OFFLINE;
        $empresa->contingenciadata = Carbon::now();
        $empresa->contingenciajustificativa = mb_substr($justificativa, 0, 256);
        $empresa->save();

        return $empresa;
    }

    /**
     * Volta para o modo online.
     *
     * Limpa contingenciadata: fora da contingencia ela so confundiria, porque descreve
     * um episodio encerrado e nao o estado atual. As notas emitidas offline carregam as
     * suas proprias congeladas (tblnotafiscal), entao a retransmissao nao depende desta.
     *
     * A justificativa fica: nao atrapalha e serve de rascunho para o proximo episodio.
     */
    public static function normalizar(Empresa $empresa): Empresa
    {
        $empresa->modoemissaonfce = Empresa::MODOEMISSAONFCE_NORMAL;
        $empresa->contingenciadata = null;
        $empresa->save();

        return $empresa;
    }
}
