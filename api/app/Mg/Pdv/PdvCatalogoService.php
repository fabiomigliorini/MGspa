<?php

namespace Mg\Pdv;

use Exception;
use Illuminate\Support\Facades\DB;

/**
 * Catálogo do PDV (TASK-197): produtos e pessoas em arquivo, uma versão a cada rodada do job
 * pdv:catalogo, para a sincronização incremental do negocios.
 *
 * Por tabela e por versão ficam dois arquivos em storage/app/pdv-catalogo:
 *
 *   produtos-20261017-135607.json.gz  todos os registros, um por linha, em ordem de chave
 *   produtos-20261017-135607.diff.gz  diferença contra o json da versão anterior: linha
 *                                     '-{...}' saiu, '+{...}' entrou; alterado vem nas duas
 *
 * A full baixa o json atual; o incremental aplica os diffs depois da versão que o PDV tem. O
 * diff sai de um merge das duas listas ordenadas, linha a linha, sem carregar nada em memória.
 * O manifesto catalogo.json aponta a última versão completa e é gravado por último: versão só
 * existe, para quem lista, quando todos os arquivos dela estão no lugar.
 *
 * Retenção: diffs por 30 dias; json só das 2 últimas versões (o atual serve a full, o anterior
 * calcula o próximo diff).
 */
class PdvCatalogoService
{
    const PASTA = 'pdv-catalogo';
    const MANIFESTO = 'catalogo.json';
    const PAGINA = 20000;
    const DIAS_DIFF = 30;
    const JSON_FLAGS = JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES;

    // tabela => chave; a chave é o primeiro campo de cada linha do json
    const TABELAS = [
        'produtos' => 'codprodutobarra',
        'pessoas' => 'codpessoa',
    ];

    public static function pasta(): string
    {
        $pasta = storage_path('app/' . static::PASTA);
        if (!is_dir($pasta)) {
            mkdir($pasta, 0775, true);
        }
        return $pasta;
    }

    public static function arquivo(string $tabela, string $versao, string $tipo): string
    {
        return static::pasta() . "/{$tabela}-{$versao}.{$tipo}.gz";
    }

    public static function manifesto(): ?array
    {
        $arquivo = static::pasta() . '/' . static::MANIFESTO;
        if (!is_file($arquivo)) {
            return null;
        }
        return json_decode(file_get_contents($arquivo), true) ?: null;
    }

    // ------------------------------------------------------------------ listagem (api)

    // versão atual (full) e diffs em disco até ela, em ordem crescente, com o tamanho do .gz:
    // o front escolhe full ou incremental e pula os diffs vazios
    public static function versoes(string $tabela, ?string $desde = null): array
    {
        $manifesto = static::manifesto();
        if (!$manifesto) {
            abort(404, 'O catálogo ainda não foi gerado no servidor.');
        }
        $atual = $manifesto['versao'];
        $diffs = [];
        foreach (glob(static::pasta() . "/{$tabela}-*.diff.gz") as $arquivo) {
            if (!preg_match('/-(\d{8}-\d{6})\.diff\.gz$/', $arquivo, $m)) {
                continue;
            }
            $versao = $m[1];
            // acima da atual é sobra de rodada que caiu no meio; abaixo de desde o PDV já tem
            if ($versao > $atual || ($desde !== null && $versao <= $desde)) {
                continue;
            }
            $diffs[$versao] = ['versao' => $versao, 'tamanho' => filesize($arquivo)];
        }
        ksort($diffs);
        return [
            'atual' => [
                'versao' => $atual,
                'tamanho' => filesize(static::arquivo($tabela, $atual, 'json')),
                'registros' => $manifesto['tabelas'][$tabela]['registros'] ?? null,
            ],
            // a versao do PDV ainda existe aqui (e' a atual ou tem o diff dela): o incremental a
            // partir dela e' valido; se nao, so' full
            'desde' => $desde === null ? null : [
                'versao' => $desde,
                'conhecida' => $desde == $atual || is_file(static::arquivo($tabela, $desde, 'diff')),
            ],
            'diffs' => array_values($diffs),
        ];
    }

    // ------------------------------------------------------------------ geração (job)

    public static function gerar(): array
    {
        $inicio = microtime(true);
        $pasta = static::pasta();
        $anterior = static::manifesto()['versao'] ?? null;
        $versao = date('Ymd-His');
        if ($anterior !== null && $versao <= $anterior) {
            throw new Exception("Versão {$versao} não é maior que a atual {$anterior}: o relógio voltou?");
        }

        $tabelas = [];
        DB::beginTransaction();
        try {
            // uma foto só do banco para as duas tabelas, apesar das páginas
            DB::statement('set transaction isolation level repeatable read');
            foreach (static::TABELAS as $tabela => $chave) {
                $tabelas[$tabela] = static::gerarTabela($tabela, $chave, $versao, $anterior);
            }
            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            foreach (glob("{$pasta}/*-{$versao}.*.gz.tmp") as $tmp) {
                @unlink($tmp);
            }
            throw $e;
        }

        // diff antes do json, manifesto por último: quem lista só vê versão completa
        foreach (['diff', 'json'] as $tipo) {
            foreach (array_keys(static::TABELAS) as $tabela) {
                $arquivo = static::arquivo($tabela, $versao, $tipo);
                rename($arquivo . '.tmp', $arquivo);
            }
        }
        $manifesto = [
            'versao' => $versao,
            'anterior' => $anterior,
            'tabelas' => $tabelas,
            'segundos' => round(microtime(true) - $inicio, 1),
        ];
        $arquivo = "{$pasta}/" . static::MANIFESTO;
        file_put_contents($arquivo . '.tmp', json_encode($manifesto, static::JSON_FLAGS | JSON_PRETTY_PRINT));
        rename($arquivo . '.tmp', $arquivo);

        static::limpar($pasta, $versao, $anterior);
        return $manifesto;
    }

    // Escreve o json novo e, no mesmo passo, o diff contra o json anterior: merge de duas
    // listas ordenadas pela chave. A chave sai do começo da linha, sem decodificar o json.
    private static function gerarTabela(string $tabela, string $chave, string $versao, ?string $anterior): array
    {
        $json = gzopen(static::arquivo($tabela, $versao, 'json') . '.tmp', 'wb6');
        $diff = gzopen(static::arquivo($tabela, $versao, 'diff') . '.tmp', 'wb6');
        $ant = null;
        if ($anterior !== null && is_file(static::arquivo($tabela, $anterior, 'json'))) {
            $ant = gzopen(static::arquivo($tabela, $anterior, 'json'), 'rb');
        }
        $linhaAnt = $ant ? gzgets($ant) : false;
        $registros = $alterados = $excluidos = 0;
        $ultimaChave = 0;

        do {
            $regs = static::pagina($tabela, $ultimaChave);
            foreach ($regs as $reg) {
                $k = (int) $reg->$chave;
                $linha = json_encode($reg, static::JSON_FLAGS) . "\n";

                // o que o anterior tinha antes desta chave saiu
                while ($linhaAnt !== false && static::chaveDaLinha($linhaAnt) < $k) {
                    gzwrite($diff, '-' . $linhaAnt);
                    $excluidos++;
                    $linhaAnt = gzgets($ant);
                }
                if ($linhaAnt !== false && static::chaveDaLinha($linhaAnt) == $k) {
                    // nas duas: só entra no diff se mudou
                    if ($linhaAnt !== $linha) {
                        gzwrite($diff, '-' . $linhaAnt . '+' . $linha);
                        $alterados++;
                    }
                    $linhaAnt = gzgets($ant);
                } else {
                    gzwrite($diff, '+' . $linha);
                    $alterados++;
                }

                gzwrite($json, $linha);
                $registros++;
                $ultimaChave = $k;
            }
        } while (count($regs) == static::PAGINA);

        // o que sobrou no anterior depois da última chave nova saiu
        while ($linhaAnt !== false) {
            gzwrite($diff, '-' . $linhaAnt);
            $excluidos++;
            $linhaAnt = gzgets($ant);
        }

        if ($ant) {
            gzclose($ant);
        }
        gzclose($json);
        gzclose($diff);
        return compact('registros', 'alterados', 'excluidos');
    }

    private static function pagina(string $tabela, int $depois): array
    {
        return match ($tabela) {
            'produtos' => static::produto($depois, static::PAGINA),
            'pessoas' => static::pessoa($depois, null, static::PAGINA),
        };
    }

    // {"codprodutobarra":123,... : o inteiro logo depois do primeiro ':'
    private static function chaveDaLinha(string $linha): int
    {
        return (int) substr($linha, strpos($linha, ':') + 1);
    }

    // diffs por 30 dias; json só da versão nova e da anterior; sobra de rodada que caiu fora
    private static function limpar(string $pasta, string $versao, ?string $anterior): void
    {
        $corte = date('Ymd-His', time() - static::DIAS_DIFF * 86400);
        foreach (glob("{$pasta}/*.gz*") as $arquivo) {
            if (!preg_match('/^(\w+)-(\d{8}-\d{6})\.(json|diff)\.gz(\.tmp)?$/', basename($arquivo), $m)) {
                continue;
            }
            $v = $m[2];
            $tipo = $m[3];
            $tmp = isset($m[4]);
            $apaga = $tmp
                || ($tipo == 'json' && $v !== $versao && $v !== $anterior)
                || ($tipo == 'diff' && $v < $corte);
            if ($apaga) {
                @unlink($arquivo);
            }
        }
    }

    // ------------------------------------------------------------------ consultas

    // Uma linha por código de barras. Com limite 1 é a consulta ao vivo pelo código exato
    // (bipe); com mais é a página seguinte à chave, para o catálogo.
    public static function produto($codprodutobarra, $limite)
    {
        $sql = '
            select
            	pb.codprodutobarra,
                p.codproduto,
            	pb.barras,
                p.produto,
                pv.variacao,
                p.abc,
            	coalesce(ume.sigla, um.sigla) as sigla,
            	pe.quantidade,
            	pri.codimagem,
            	coalesce(pe.preco, p.preco * coalesce(pe.quantidade, 1)) as preco,
            	coalesce(pv.inativo, p.inativo) as inativo
            from tblproduto p
            inner join tblunidademedida um on (um.codunidademedida = p.codunidademedida)
            inner join tblprodutovariacao pv  on (pv.codproduto = p.codproduto)
            inner join tblprodutobarra pb on (pb.codprodutovariacao = pv.codprodutovariacao)
            left join tblprodutoembalagem pe on (pe.codprodutoembalagem = pb.codprodutoembalagem)
            left join tblunidademedida ume on (ume.codunidademedida = pe.codunidademedida)
            left join tblprodutoimagem pri on (pri.codprodutoimagem = pv.codprodutoimagem)
        ';
        if ($limite == 1) {
            $sql .= '
                where pb.codprodutobarra = :codprodutobarra
                order by pb.codprodutobarra
                limit :limite
            ';
        } else {
            $sql .= '
                where pb.codprodutobarra > :codprodutobarra
                order by pb.codprodutobarra
                limit :limite
            ';
        }
        $regs = DB::select($sql, [
            'codprodutobarra' => $codprodutobarra,
            'limite' => $limite,
        ]);
        return array_map(function ($item) {
            if ($item->quantidade) {
                $item->quantidade = floatval($item->quantidade);
            }
            $item->preco = floatval($item->preco);
            // nome base para agrupar por produto na pesquisa (TASK-173); produto é a descrição
            // completa, com variação e embalagem
            $item->nome = $item->produto;
            $item->produto = static::montarDescricaoProduto($item->produto, $item->variacao, $item->sigla, $item->quantidade);
            $item->busca =
                $item->produto . ' ' .
                number_format($item->preco, 2, ',', '')  . ' ' .
                $item->barras  . ' ' .
                substr($item->barras, -6, 6);
            $item->buscaArr = array_values(array_unique(explode(' ', $item->busca)));
            return $item;
        }, $regs);
    }

    public static function montarDescricaoProduto($produto, $variacao, $unidade, $quantidade)
    {
        $descricao = $produto;
        if (!empty($variacao)) {
            $descricao .= ' ' . $variacao;
        }
        $descricao .= ' ' . $unidade;
        if (!empty($quantidade)) {
            $descricao .= ' C/' . intval($quantidade);
        }
        return $descricao;
    }

    // Com cnpj é a consulta ao vivo (pesquisa por CNPJ/CPF); com limite 1 é a pessoa exata
    // (cadastro recém-feito no PDV); com mais é a página seguinte à chave, para o catálogo.
    public static function pessoa($codpessoa, $cnpj, $limite)
    {
        $params = [
            'limite' => $limite,
        ];
        $sql = '
            select 
                p.codpessoa,
                p.pessoa,
                p.fantasia,
                case when p.fisica then to_char(p.cnpj, \'FM00000000000\') else to_char(p.cnpj, \'FM00000000000000\') end as cnpj,
                p.ie,
                p.fisica,
                p.endereco,
                p.numero,
                p.bairro,
                p.complemento,
                c.cidade,
                e.sigla as uf,
                p.vendedor,
                p.inativo,
                p.mensagemvenda,
                p.codformapagamento,
                p.desconto
            from tblpessoa p
            left join tblcidade c on (c.codcidade = p.codcidade)
            left join tblestado e on (e.codestado = c.codestado)
        ';
        if (!empty($cnpj)) {
            $params['cnpj'] = "%{$cnpj}%";
            $sql .= '
                where to_char(cnpj, \'FM00000000000000\') ilike :cnpj
            ';
        } else {
            $params['codpessoa'] = $codpessoa;
            if ($limite == 1) {
                $sql .= '
                    where codpessoa = :codpessoa
                ';
            } else {
                $sql .= '
                    where codpessoa > :codpessoa
                ';
            }
        }
        $sql .= '
            order by codpessoa
            limit :limite
        ';
        $regs = DB::select($sql, $params);
        return array_map(function ($item) {
            $busca = "{$item->pessoa} . {$item->fantasia} . {$item->cnpj} " . str_pad($item->codpessoa, 8, "0", STR_PAD_LEFT);
            $busca = trim(preg_replace('/[^A-Za-z0-9 ]/', '', $busca));
            $busca = preg_replace('/\s+/', ' ', $busca);
            $item->busca = $busca;
            $item->buscaArr = array_values(array_unique(explode(' ', $item->busca)));
            return $item;
        }, $regs);
    }
}
