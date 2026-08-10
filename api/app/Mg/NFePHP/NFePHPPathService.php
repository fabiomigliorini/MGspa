<?php

namespace Mg\NFePHP;

use Carbon\Carbon;

use Mg\NotaFiscal\NotaFiscal;
use Mg\NotaFiscal\NotaFiscalService;
use Mg\Dfe\DistribuicaoDfe;
use Mg\Filial\Filial;
use Mg\Mdfe\Mdfe;

/**
 * Caminhos em disco de tudo que a biblioteca NFePHP gera ou recebe.
 *
 * ESTRUTURA
 *
 *   {NFE_PHP_PATH}{tipo}/{codfilial}/{ambiente}/{AAAA}/{MM}/{DD}/{arquivo}
 *
 *   nfe/  nfce/  mdfe/         documentos que emitimos, um arquivo por artefato:
 *                              {chave}-assinado.xml   o que foi (ou vai ser) enviado
 *                              {chave}-proc.xml       protocolado/autorizado, o definitivo
 *                              {chave}-denegado.xml
 *                              {chave}-cancelado.xml  evento de cancelamento
 *                              {chave}-encerrado.xml  encerramento (so MDF-e)
 *                              {chave}-cce-{seq}.xml  carta de correcao, uma por sequencia
 *                              {chave}.pdf            DANFE / DANFCE / DAMDFE
 *
 *   inutilizacoes/             {modelo}-{serie}-{nIni}-{nFin}-inut.xml
 *                              Arvore propria porque inutilizacao cobre uma FAIXA de
 *                              numeracao e nao pertence a documento nenhum: um procInutNFe
 *                              de 1..50 teria de ser duplicado em 50 pastas. A data que o
 *                              identifica e a da INUTILIZACAO, nao a emissao de nada.
 *
 *   dfe/                       {nsu}-{chave}.xml.gz   documento de terceiro recebido
 *                              {chave}-manif-{tpEvento}-{seq}.xml   nossa manifestacao
 *                              Indexado por NSU (contador sequencial por CNPJ), que e o
 *                              que se usa para conferir lacuna de recebimento.
 *
 *   conversas/                 {chave}-{id}-{operacao}.xml.gz  request+response da SEFAZ
 *                              {id}-{operacao}.xml.gz          quando nao ha nota/chave
 *                              Arvore SEPARADA de proposito: a retencao e de 2 anos,
 *                              contra "para sempre" do arquivo fiscal. Assim o comando de
 *                              limpeza nunca entra nas arvores de documento, e o guard e
 *                              estrutural em vez de depender de um padrao de nome. A chave
 *                              vai no nome para um `find -name "*{chave}*"` continuar
 *                              atravessando as duas arvores.
 *
 *   certificados/{codfilial}.pfx
 *
 * ESTADO VAI NO SUFIXO DO ARQUIVO, NUNCA EM PASTA
 *
 * Todos os artefatos de uma mesma nota moram no mesmo diretorio, distinguidos pelo sufixo.
 * Assim achar o XML de uma nota e um file_exists por ordem de prioridade dentro de UM
 * diretorio, em vez de um fallback em cascata por varias pastas.
 */
class NFePHPPathService
{
    const TIPO_NFE = 'nfe';
    const TIPO_NFCE = 'nfce';
    const TIPO_MDFE = 'mdfe';
    const TIPO_INUTILIZACAO = 'inutilizacoes';
    const TIPO_DFE = 'dfe';
    const TIPO_CONVERSA = 'conversas';
    const DIR_CERTIFICADO = 'certificados';

    /**
     * Nucleo unico: todas as arvores tem a mesma forma.
     */
    protected static function path(
        string $tipo,
        Filial $filial,
        Carbon $data,
        string $arquivo,
        bool $criar = false
    ) {
        $path = static::pathDiretorio($tipo, $filial, $data);
        if ($criar && !is_dir($path)) {
            @mkdir($path, 0775, true);
        }
        return $path . '/' . $arquivo;
    }

    /**
     * Diretorio do dia, sem arquivo. Util para varredura.
     */
    public static function pathDiretorio(string $tipo, Filial $filial, Carbon $data)
    {
        $ambiente = ($filial->nfeambiente == 1) ? 'producao' : 'homologacao';
        return rtrim(config('mg.paths.nfe_php'), '/')
            . "/{$tipo}/{$filial->codfilial}/{$ambiente}/"
            . $data->format('Y/m/d');
    }

    /**
     * Diretorio do mes (o dia fica como curinga). Para glob de fechamento mensal.
     */
    public static function pathDiretorioMes(string $tipo, Filial $filial, Carbon $mes)
    {
        $ambiente = ($filial->nfeambiente == 1) ? 'producao' : 'homologacao';
        return rtrim(config('mg.paths.nfe_php'), '/')
            . "/{$tipo}/{$filial->codfilial}/{$ambiente}/"
            . $mes->format('Y/m');
    }

    /**
     * NF-e (55) e NFC-e (65) vivem em arvores separadas: sao 3 milhoes de NFC-e contra
     * 340 mil NF-e, e misturadas a NF-e some no meio.
     */
    public static function tipoDaNotaFiscal(NotaFiscal $nf)
    {
        return ($nf->modelo == NotaFiscalService::MODELO_NFCE) ? static::TIPO_NFCE : static::TIPO_NFE;
    }

    protected static function pathNotaFiscal(NotaFiscal $nf, string $sufixo, bool $criar = false)
    {
        return static::path(
            static::tipoDaNotaFiscal($nf),
            $nf->Filial,
            $nf->emissao,
            "{$nf->nfechave}{$sufixo}",
            $criar
        );
    }

    // ------------------------------------------------------------------ NF-e / NFC-e

    public static function pathNFeAssinada(NotaFiscal $nf, bool $criar = false)
    {
        return static::pathNotaFiscal($nf, '-assinado.xml', $criar);
    }

    public static function pathNFeAutorizada(NotaFiscal $nf, bool $criar = false)
    {
        return static::pathNotaFiscal($nf, '-proc.xml', $criar);
    }

    public static function pathNFeDenegada(NotaFiscal $nf, bool $criar = false)
    {
        return static::pathNotaFiscal($nf, '-denegado.xml', $criar);
    }

    public static function pathNFeCancelada(NotaFiscal $nf, bool $criar = false)
    {
        return static::pathNotaFiscal($nf, '-cancelado.xml', $criar);
    }

    /**
     * A sequencia no nome corrige um bug antigo: o arquivo era {chave}-CCe.xml, sem
     * sequencia, entao a 2a carta de correcao da nota sobrescrevia a 1a.
     */
    public static function pathCartaCorrecao(NotaFiscal $nf, int $sequencia, bool $criar = false)
    {
        return static::pathNotaFiscal($nf, "-cce-{$sequencia}.xml", $criar);
    }

    public static function pathDanfe(NotaFiscal $nf, bool $criar = false)
    {
        return static::pathNotaFiscal($nf, '.pdf', $criar);
    }

    // ------------------------------------------------------------------ Inutilizacao

    public static function pathInutilizacao(
        Filial $filial,
        int $modelo,
        int $serie,
        int $numeroInicial,
        int $numeroFinal,
        Carbon $data,
        bool $criar = false
    ) {
        return static::path(
            static::TIPO_INUTILIZACAO,
            $filial,
            $data,
            "{$modelo}-{$serie}-{$numeroInicial}-{$numeroFinal}-inut.xml",
            $criar
        );
    }

    // ------------------------------------------------------------------ MDF-e

    protected static function pathMdfeArquivo(Mdfe $mdfe, string $sufixo, bool $criar = false)
    {
        return static::path(
            static::TIPO_MDFE,
            $mdfe->Filial,
            $mdfe->emissao,
            "{$mdfe->chmdfe}{$sufixo}",
            $criar
        );
    }

    public static function pathMdfeAssinado(Mdfe $mdfe, bool $criar = false)
    {
        return static::pathMdfeArquivo($mdfe, '-assinado.xml', $criar);
    }

    public static function pathMdfeAutorizado(Mdfe $mdfe, bool $criar = false)
    {
        return static::pathMdfeArquivo($mdfe, '-proc.xml', $criar);
    }

    public static function pathMdfeCancelado(Mdfe $mdfe, bool $criar = false)
    {
        return static::pathMdfeArquivo($mdfe, '-cancelado.xml', $criar);
    }

    public static function pathMdfeEncerrado(Mdfe $mdfe, bool $criar = false)
    {
        return static::pathMdfeArquivo($mdfe, '-encerrado.xml', $criar);
    }

    public static function pathDamdfe(Mdfe $mdfe, bool $criar = false)
    {
        return static::pathMdfeArquivo($mdfe, '.pdf', $criar);
    }

    // ------------------------------------------------------------------ DFe (terceiros)

    /**
     * Nomeado pelo NSU (identificador da SEFAZ), nao pelo id interno da tabela: o NSU e
     * o que se usa para conferir lacuna de recebimento. A chave entra quando existe, para
     * dar para achar o arquivo por ela.
     */
    public static function pathDfe(DistribuicaoDfe $dfe, bool $criar = false)
    {
        // A coluna e numeric(50,0) mas o model faz cast para float, o que renderiza
        // "1.2345678901235E+14" na interpolacao. Le o valor cru quando disponivel.
        $nsu = $dfe->getRawOriginal('nsu') ?? $dfe->nsu;
        $nsu = is_numeric($nsu) ? number_format((float) $nsu, 0, '', '') : (string) $nsu;

        $arquivo = empty($dfe->nfechave)
            ? "{$nsu}.xml.gz"
            : "{$nsu}-{$dfe->nfechave}.xml.gz";

        return static::path(static::TIPO_DFE, $dfe->Filial, $dfe->criacao, $arquivo, $criar);
    }

    /**
     * Manifestacao do destinatario: evento que NOS emitimos sobre documento de TERCEIRO.
     * Por isso mora na arvore do dfe, junto do documento a que se refere, e nao em nfe/.
     */
    public static function pathManifestacao(
        Filial $filial,
        string $chave,
        int $tpEvento,
        int $sequencia,
        ?Carbon $data = null,
        bool $criar = false
    ) {
        return static::path(
            static::TIPO_DFE,
            $filial,
            $data ?? Carbon::now(),
            "{$chave}-manif-{$tpEvento}-{$sequencia}.xml",
            $criar
        );
    }

    // ------------------------------------------------------------------ Conversas SEFAZ

    public static function pathConversa(
        Filial $filial,
        int $id,
        string $operacao,
        ?string $chave = null,
        ?Carbon $data = null,
        bool $criar = false
    ) {
        $operacao = preg_replace('/[^A-Za-z0-9_-]/', '', $operacao);
        $arquivo = empty($chave)
            ? "{$id}-{$operacao}.xml.gz"
            : "{$chave}-{$id}-{$operacao}.xml.gz";

        return static::path(static::TIPO_CONVERSA, $filial, $data ?? Carbon::now(), $arquivo, $criar);
    }

    // ------------------------------------------------------------------ Certificado

    /**
     * O rtrim e necessario: o env NFE_PHP_PATH termina em '/'.
     */
    public static function pathCertificado(Filial $filial)
    {
        return rtrim(config('mg.paths.nfe_php'), '/') . '/' . static::DIR_CERTIFICADO . "/{$filial->codfilial}.pfx";
    }
}
