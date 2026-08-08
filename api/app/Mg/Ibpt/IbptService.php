<?php

namespace Mg\Ibpt;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

use Mg\MgService;

/**
 * Importação da tabela do IBPT (De Olho no Imposto).
 *
 * O usuário baixa o ZIP no site do IBPT, descompacta e sobe os 27 CSVs pela tela.
 * Cada arquivo é uma UF e vem em um request separado - o volume por UF é pequeno
 * (~12 mil NCMs), então a carga é síncrona e idempotente: pode repetir à vontade.
 */
class IbptService extends MgService
{
    /**
     * Cabeçalho que o site do IBPT gera. Serve para rejeitar arquivo trocado antes
     * de escrever qualquer coisa no banco.
     */
    const CABECALHO = 'codigo;ex;tipo;descricao;nacionalfederal;importadosfederal;'
        . 'estadual;municipal;vigenciainicio;vigenciafim;chave;versao;fonte';

    const COLUNAS = [
        'codigo', 'ex', 'tipo', 'descricao', 'nacionalfederal', 'importadosfederal',
        'estadual', 'municipal', 'vigenciainicio', 'vigenciafim', 'chave', 'versao', 'fonte',
    ];

    /**
     * Situação da tabela por UF, para a tela mostrar o que já foi carregado e avisar
     * do vencimento. Traz todas as UFs, inclusive as que ainda não têm nada.
     */
    public static function status()
    {
        $sql = "
            SELECT
                e.codestado,
                e.sigla,
                e.estado,
                count(i.codibpt) AS ncms,
                max(i.versao) AS versao,
                min(i.vigenciainicio) AS vigenciainicio,
                max(i.vigenciafim) AS vigenciafim,
                max(i.alteracao) AS atualizacao
            FROM tblestado e
            LEFT JOIN tblibpt i ON (i.codestado = e.codestado)
            WHERE e.codpais = 1
            GROUP BY e.codestado, e.sigla, e.estado
            ORDER BY e.sigla
        ";

        return DB::select($sql);
    }

    /**
     * Carrega um CSV do IBPT para a UF informada.
     *
     * @return array resumo do que foi gravado
     */
    public static function importar(UploadedFile $arquivo, $uf)
    {
        $uf = strtoupper(trim($uf));

        $estado = DB::selectOne(
            "SELECT codestado, sigla FROM tblestado WHERE codpais = 1 AND upper(sigla) = ?",
            [$uf]
        );
        if (!$estado) {
            abort(422, "UF inválida: {$uf}");
        }

        static::validarNomeArquivo($arquivo, $uf);

        $linhas = static::lerCsv($arquivo->getRealPath());
        if (empty($linhas)) {
            abort(422, 'O arquivo não tem nenhuma linha de NCM.');
        }

        $codusuario = optional(Auth::guard('api')->user() ?: Auth::user())->codusuario;

        return DB::transaction(function () use ($estado, $linhas, $codusuario) {
            static::carregarStaging($linhas);

            $doIbpt = static::gravarNcmsDoIbpt($estado->codestado, $codusuario);
            $aproximados = static::gravarNcmsForaDaTabela($estado->codestado, $codusuario);

            $vigencia = DB::selectOne("
                SELECT
                    max(versao) AS versao,
                    min(to_date(vigenciainicio, 'DD/MM/YYYY')) AS vigenciainicio,
                    max(to_date(vigenciafim, 'DD/MM/YYYY')) AS vigenciafim
                FROM ibpt_csv
            ");

            return [
                'uf' => $estado->sigla,
                'versao' => $vigencia->versao,
                'vigenciainicio' => $vigencia->vigenciainicio,
                'vigenciafim' => $vigencia->vigenciafim,
                'ncms' => $doIbpt,
                'aproximados' => $aproximados,
            ];
        });
    }

    /**
     * O CSV não diz a que UF pertence - só o nome do arquivo diz. Como subir o arquivo
     * errado gravaria alíquota de outro estado silenciosamente, exigimos o nome original.
     */
    protected static function validarNomeArquivo(UploadedFile $arquivo, $uf)
    {
        $nome = $arquivo->getClientOriginalName();

        if (!preg_match('/TabelaIBPTax([A-Za-z]{2})/', $nome, $m)) {
            abort(422, "O arquivo '{$nome}' não parece ser uma tabela do IBPT."
                . ' Envie os arquivos TabelaIBPTax<UF>.csv como vieram do site, sem renomear.');
        }

        if (strtoupper($m[1]) != $uf) {
            abort(422, "O arquivo '{$nome}' é da UF " . strtoupper($m[1]) . ", não de {$uf}.");
        }
    }

    /**
     * Os CSVs vêm em Windows-1252 e com as descrições entre aspas. Converte para UTF-8
     * e devolve as linhas no formato TEXT do COPY (separadas por TAB).
     */
    protected static function lerCsv($caminho)
    {
        $handle = fopen($caminho, 'r');
        if (!$handle) {
            abort(422, 'Não foi possível ler o arquivo enviado.');
        }

        $cabecalho = fgets($handle);
        if (strtolower(trim($cabecalho)) != static::CABECALHO) {
            fclose($handle);
            abort(422, 'O arquivo não tem o layout da tabela do IBPT.'
                . ' Envie o TabelaIBPTax<UF>.csv do ZIP baixado no site deles.');
        }

        $linhas = [];
        while (($campos = fgetcsv($handle, 0, ';')) !== false) {
            // 00000000 é a linha "PRODUTO NAO ESPECIFICADO NA LISTA DE NCM", não é NCM
            if (count($campos) < 13 || $campos[0] === '00000000') {
                continue;
            }
            $campos = array_slice($campos, 0, 13);
            $linhas[] = implode("\t", array_map([static::class, 'escapar'], $campos)) . "\n";
        }

        fclose($handle);

        return $linhas;
    }

    protected static function escapar($valor)
    {
        if ($valor === '' || $valor === null) {
            return '\\N';
        }

        $valor = mb_convert_encoding($valor, 'UTF-8', 'Windows-1252');

        return str_replace(["\\", "\t", "\n", "\r"], ['\\\\', '\\t', '\\n', '\\r'], $valor);
    }

    /**
     * Tabela temporária em vez de uma staging fixa: some sozinha no fim da conexão e
     * dois usuários importando ao mesmo tempo não atrapalham um ao outro.
     */
    protected static function carregarStaging($linhas)
    {
        DB::statement('DROP TABLE IF EXISTS ibpt_csv');
        DB::statement("
            CREATE TEMP TABLE ibpt_csv (
                codigo varchar,
                ex varchar,
                tipo varchar,
                descricao varchar,
                nacionalfederal real,
                importadosfederal real,
                estadual real,
                municipal real,
                vigenciainicio varchar,
                vigenciafim varchar,
                chave varchar,
                versao varchar,
                fonte varchar
            )
        ");

        DB::connection()->getPdo()->pgsqlCopyFromArray(
            'ibpt_csv',
            $linhas,
            "\t",
            '\\\\N',
            implode(',', static::COLUNAS)
        );
    }

    /**
     * O mesmo NCM pode aparecer mais de uma vez com "ex" diferentes, então deduplicamos
     * pela própria chave do destino antes do ON CONFLICT.
     */
    protected static function gravarNcmsDoIbpt($codestado, $codusuario)
    {
        $sql = "
            INSERT INTO tblibpt (
                codestado, ncm, extarif, descricao, nacional, estadual, importado,
                municipal, tipo, vigenciainicio, vigenciafim, chave, versao, fonte,
                criacao, codusuariocriacao, alteracao, codusuarioalteracao
            )
            SELECT
                ?, c.codigo, c.extarif, c.descricao, c.nacionalfederal, c.estadual,
                c.importadosfederal, c.municipal, c.tipo, c.vigenciainicio, c.vigenciafim,
                c.chave, c.versao, c.fonte,
                date_trunc('second', now()), ?, date_trunc('second', now()), ?
            FROM (
                SELECT DISTINCT ON (codigo, coalesce(cast(ex as int), 0))
                    codigo,
                    coalesce(cast(ex as int), 0) AS extarif,
                    left(descricao, 400) AS descricao,
                    nacionalfederal,
                    estadual,
                    importadosfederal,
                    municipal,
                    cast(tipo as smallint) AS tipo,
                    to_date(vigenciainicio, 'DD/MM/YYYY') AS vigenciainicio,
                    to_date(vigenciafim, 'DD/MM/YYYY') AS vigenciafim,
                    chave,
                    versao,
                    fonte
                FROM ibpt_csv
            ) c
            ON CONFLICT (codestado, ncm, extarif) DO UPDATE SET
                descricao = EXCLUDED.descricao,
                nacional = EXCLUDED.nacional,
                estadual = EXCLUDED.estadual,
                importado = EXCLUDED.importado,
                municipal = EXCLUDED.municipal,
                tipo = EXCLUDED.tipo,
                vigenciainicio = EXCLUDED.vigenciainicio,
                vigenciafim = EXCLUDED.vigenciafim,
                chave = EXCLUDED.chave,
                versao = EXCLUDED.versao,
                fonte = EXCLUDED.fonte,
                alteracao = date_trunc('second', now()),
                codusuarioalteracao = EXCLUDED.codusuarioalteracao
        ";

        return DB::affectingStatement($sql, [$codestado, $codusuario, $codusuario]);
    }

    /**
     * Nem todo NCM do nosso cadastro está na tabela do IBPT. Sem isso, esses produtos
     * caem na consulta on-line a cada emissão. Como aproximação usamos o maior
     * percentual do grupo (4 primeiros dígitos do NCM) - mesmo critério do SQL antigo.
     */
    protected static function gravarNcmsForaDaTabela($codestado, $codusuario)
    {
        $sql = "
            INSERT INTO tblibpt (
                codestado, ncm, extarif, descricao, nacional, estadual, importado,
                municipal, tipo, vigenciainicio, vigenciafim, fonte,
                criacao, codusuariocriacao, alteracao, codusuarioalteracao
            )
            SELECT
                ?, n.ncm, 0, left(n.descricao, 400), g.nacional, g.estadual, g.importado,
                g.municipal, 0, g.vigenciainicio, g.vigenciafim, 'Aproximado pelo grupo do NCM',
                date_trunc('second', now()), ?, date_trunc('second', now()), ?
            FROM tblncm n
            INNER JOIN (
                SELECT
                    substring(codigo, 1, 4) AS grupo,
                    max(nacionalfederal) AS nacional,
                    max(estadual) AS estadual,
                    max(importadosfederal) AS importado,
                    max(municipal) AS municipal,
                    max(to_date(vigenciainicio, 'DD/MM/YYYY')) AS vigenciainicio,
                    max(to_date(vigenciafim, 'DD/MM/YYYY')) AS vigenciafim
                FROM ibpt_csv
                GROUP BY substring(codigo, 1, 4)
            ) g ON (g.grupo = substring(n.ncm, 1, 4))
            WHERE length(n.ncm) = 8
              AND NOT EXISTS (SELECT 1 FROM ibpt_csv c WHERE c.codigo = n.ncm)
            ON CONFLICT (codestado, ncm, extarif) DO UPDATE SET
                nacional = EXCLUDED.nacional,
                estadual = EXCLUDED.estadual,
                importado = EXCLUDED.importado,
                municipal = EXCLUDED.municipal,
                vigenciainicio = EXCLUDED.vigenciainicio,
                vigenciafim = EXCLUDED.vigenciafim,
                fonte = EXCLUDED.fonte,
                alteracao = date_trunc('second', now()),
                codusuarioalteracao = EXCLUDED.codusuarioalteracao
        ";

        return DB::affectingStatement($sql, [$codestado, $codusuario, $codusuario]);
    }
}
