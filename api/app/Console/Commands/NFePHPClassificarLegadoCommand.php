<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

use Mg\Filial\Filial;
use Mg\NFePHP\NFePHPPathService;
use Mg\NotaFiscal\NotaFiscal;

/**
 * Resgata da arvore legada os XMLs que a estrutura nova sabe guardar.
 *
 * POR QUE UM COMMAND SEPARADO
 *
 * O nfe-php:migrar-arquivos varre o BANCO e calcula o caminho de origem. Isso cobre o que
 * o codigo ATUAL escreve, mas o disco tem 14 subpastas — nove delas (recebidas, validadas,
 * temporarias, rejeitadas, inutilizadas, eventos, entradas, dpec, consultadas) sao do MGsis
 * antigo e nenhum codigo vivo escreve nelas. Este command faz o contrario: varre o DISCO.
 *
 * POR QUE NAO CONFIAR NO NOME DO ARQUIVO
 *
 * Sao 17 formatos de nome, de geracoes diferentes, e eles MENTEM. Exemplos reais colhidos
 * na amostra de producao:
 *
 *   92998901-procEventoNFe.xml  ->  tpEvento real 110111 (cancelamento)
 *   81505001-procEventoNFe.xml  ->  tpEvento real 110111
 *   46929401-procEventoNFe.xml  ->  tpEvento real 210210 (ciencia)
 *   21021001-procEventoNFe.xml  ->  tpEvento real 210210
 *
 * So o ultimo tem prefixo que coincide com o tpEvento. Classificar pelo nome arquivaria
 * milhares de cancelamentos como manifestacao. Por isso a classificacao le o CONTEUDO:
 * elemento raiz + tpEvento + chave, todos de dentro do XML.
 *
 * TRES DESTINOS
 *
 *   1. Estrutura nova  — reconheceu o documento E conseguiu enderecar (nota no banco, ou
 *                        dados completos no proprio XML no caso de inutilizacao/manifestacao).
 *   2. legado/         — reconheceu o que e, mas nao cabe na estrutura nova: chave sem nota
 *                        em tblnotafiscal, pedido/consulta (que nao e registro fiscal).
 *                        Preserva o caminho relativo, entao continua organizado e achavel.
 *   3. Fica onde esta  — NAO conseguiu interpretar.
 *
 * O ponto do 3: no fim, o que sobrar em Arquivos/ e exatamente a lista do que o
 * classificador nao entendeu — uma fila de trabalho curta e precisa para decidir na mao,
 * em vez de um monte misturando "conhecido mas nao migravel" com "desconhecido".
 */
class NFePHPClassificarLegadoCommand extends Command
{
    protected $signature = 'nfe-php:classificar-legado
        {--origem=Arquivos : diretorio da arvore antiga, relativo a NFE_PHP_PATH}
        {--legado=legado : para onde vai o que e reconhecido mas nao cabe na estrutura nova}
        {--relatorio : so classifica e conta, nao move nada (rode isto primeiro)}
        {--limite= : maximo de arquivos a processar}
        {--dry-run : mostra origem -> destino sem mover}';

    protected $description = 'Le o legado arquivo por arquivo, classifica pelo conteudo e resgata o que a estrutura nova sabe guardar';

    /** Quanto do arquivo basta ler: raiz, chave e tpEvento ficam todos no comeco. */
    const BYTES_CABECALHO = 8192;

    protected array $contagem = [];
    protected array $exemplos = [];
    protected int $movidos = 0;
    protected int $paraLegado = 0;
    protected int $ficaram = 0;

    protected string $dirOrigem = '';
    protected string $dirLegado = '';
    protected int $falhas = 0;

    /** Cache de filial por codigo, e de nota por chave. */
    protected array $filiais = [];

    public function handle()
    {
        $raiz = rtrim(config('mg.paths.nfe_php'), '/');
        $origem = $raiz . '/' . trim($this->option('origem'), '/');
        $this->dirOrigem = $origem;
        $this->dirLegado = $raiz . '/' . trim($this->option('legado'), '/');

        if (!is_dir($origem)) {
            $this->error("Diretorio nao encontrado: {$origem}");
            return 1;
        }

        $relatorio = (bool) $this->option('relatorio');
        $dryRun = (bool) $this->option('dry-run') || $relatorio;
        $limite = $this->option('limite') ? (int) $this->option('limite') : null;

        if ($relatorio) {
            $this->warn('MODO RELATORIO: nada sera movido.');
        } elseif ($dryRun) {
            $this->warn('DRY-RUN: nada sera movido.');
        }

        $this->info("Varrendo {$origem} ...");

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($origem, \FilesystemIterator::SKIP_DOTS)
        );

        $processados = 0;
        foreach ($iterator as $arquivo) {
            if (!$arquivo->isFile() || strtolower($arquivo->getExtension()) !== 'xml') {
                continue;
            }
            if ($limite !== null && $processados >= $limite) {
                break;
            }
            $processados++;

            $this->processar($arquivo->getPathname(), $dryRun, $relatorio);

            if ($processados % 5000 === 0) {
                $this->line("  ... {$processados} arquivos");
            }
        }

        $this->relatorio($processados, $relatorio);

        return 0;
    }

    protected function processar(string $path, bool $dryRun, bool $relatorio): void
    {
        $cabecalho = @file_get_contents($path, false, null, 0, static::BYTES_CABECALHO);
        if ($cabecalho === false) {
            $this->registrar('erro-leitura', $path);
            $this->falhas++;
            return;
        }

        $c = $this->classificar($cabecalho);
        $this->registrar($c['tipo'], $path);

        if ($relatorio) {
            return;
        }

        $destino = $this->destino($c, $path);
        $paraLegado = false;

        // Reconheceu o tipo mas nao conseguiu enderecar: vai para o legado, preservando o
        // caminho relativo. So fica parado o que nem sequer foi interpretado.
        if ($destino === null && $this->conhecido($c['tipo'])) {
            $destino = $this->caminhoLegado($path);
            $paraLegado = true;
        }

        if ($destino === null) {
            $this->ficaram++;
            return;
        }

        if ($dryRun) {
            $this->line("  {$path}\n    -> {$destino}");
        }

        if ($paraLegado) {
            $this->paraLegado++;
        } else {
            $this->movidos++;
        }

        if (!$dryRun) {
            $this->mover($path, $destino, $paraLegado);
        }
    }

    /**
     * Tipos que sabemos o que sao. So estes vao para o legado quando nao dao para
     * enderecar — o resto fica parado, para analise manual.
     *
     * 'evento-desconhecido' fica de proposito: e um procEventoNFe com tpEvento fora do
     * mapa, e a decisao certa e olhar qual tpEvento e' e incluir no classificador, nao
     * arquivar as cegas.
     */
    protected function conhecido(string $tipo): bool
    {
        return in_array($tipo, [
            'autorizada',
            'assinada',
            'cancelamento',
            'cartacorrecao',
            'manifestacao',
            'inutilizacao',
            'pedido-ou-consulta',
        ], true);
    }

    /** Mesmo caminho relativo, so trocando a raiz Arquivos/ por legado/. */
    protected function caminhoLegado(string $path): string
    {
        $relativo = ltrim(substr($path, strlen($this->dirOrigem)), '/');
        return $this->dirLegado . '/' . $relativo;
    }

    /**
     * Classifica pelo conteudo. Devolve tipo, chave, tpEvento e sequencia quando houver.
     *
     * A ordem dos testes importa: procEventoNFe contem <evento>, e nfeProc contem <NFe>,
     * entao o envelope mais externo tem que ser testado primeiro.
     */
    protected function classificar(string $xml): array
    {
        $chave = $this->extrair('/(?:<chNFe>|Id="NFe)(\d{44})/', $xml);
        $tpEvento = $this->extrair('/<tpEvento>(\d+)<\/tpEvento>/', $xml);
        $seq = $this->extrair('/<nSeqEvento>(\d+)<\/nSeqEvento>/', $xml) ?? '1';
        $prot = $this->extrair('/<nProt>(\d+)<\/nProt>/', $xml);

        // --- Eventos com protocolo (procEventoNFe) --------------------------------
        if ($this->tem($xml, 'procEventoNFe') || ($tpEvento !== null && $prot !== null)) {
            $tipo = match (true) {
                $tpEvento === '110111' => 'cancelamento',
                $tpEvento === '110110' => 'cartacorrecao',
                $tpEvento !== null && str_starts_with($tpEvento, '2102') => 'manifestacao',
                default => 'evento-desconhecido',
            };
            return compact('tipo', 'chave', 'tpEvento', 'seq');
        }

        // --- Inutilizacao HOMOLOGADA ----------------------------------------------
        // Tres raizes reais no acervo, e o case varia: <ProcInutNFe> vem com P maiusculo
        // em parte dos arquivos. Nao tem chave de nota — e sobre uma FAIXA, e os dados
        // saem do proprio <infInut>.
        if ($this->tem($xml, 'ProcInutNFe') || $this->tem($xml, 'retInutNFe')
            || ($this->tem($xml, 'infInut') && $prot !== null)) {
            return [
                'tipo' => 'inutilizacao',
                'chave' => null,
                'tpEvento' => null,
                'seq' => null,
                'inut' => [
                    'modelo' => $this->extrair('/<mod>(\d+)<\/mod>/', $xml),
                    'serie' => $this->extrair('/<serie>(\d+)<\/serie>/', $xml),
                    'nIni' => $this->extrair('/<nNFIni>(\d+)<\/nNFIni>/', $xml),
                    'nFin' => $this->extrair('/<nNFFin>(\d+)<\/nNFFin>/', $xml),
                    'dh' => $this->extrair('/<dhRecbto>([^<]+)<\/dhRecbto>/', $xml),
                ],
            ];
        }

        // --- Cancelamento no formato pre-evento -----------------------------------
        if ($this->tem($xml, 'procCancNFe') || $this->tem($xml, 'retCancNFe')) {
            return ['tipo' => 'cancelamento', 'chave' => $chave, 'tpEvento' => '110111', 'seq' => '1'];
        }

        // --- NFe com protocolo de autorizacao -------------------------------------
        if ($this->tem($xml, 'nfeProc')) {
            return ['tipo' => 'autorizada', 'chave' => $chave, 'tpEvento' => null, 'seq' => null];
        }

        // --- NFe assinada, sem protocolo ------------------------------------------
        if ($this->tem($xml, 'infNFe')) {
            return ['tipo' => 'assinada', 'chave' => $chave, 'tpEvento' => null, 'seq' => null];
        }

        // Pedidos: SABEMOS o que sao, mas nao sao registro fiscal — e a requisicao que
        // gerou o documento, nao o documento protocolado. Ficam no legado, mas
        // classificados a parte, para nao se confundirem com o que nao entendemos.
        //   <inutNFe> sem protocolo  = -ped-inu.xml
        //   <envEvento> / <evento> sem protocolo = -ped-eve.xml
        //   <cancNFe> sem retorno = -ped-can.xml
        //   <consSitNFe> = consulta
        foreach (['inutNFe', 'envEvento', 'consSitNFe', 'cancNFe', 'evento'] as $raiz) {
            if ($this->tem($xml, $raiz)) {
                return ['tipo' => 'pedido-ou-consulta', 'chave' => $chave, 'tpEvento' => $tpEvento, 'seq' => $seq];
            }
        }

        return ['tipo' => 'nao-reconhecido', 'chave' => $chave, 'tpEvento' => $tpEvento, 'seq' => $seq];
    }

    /**
     * Monta o caminho de destino, ou null se o arquivo deve ficar no legado.
     */
    protected function destino(array $c, string $path): ?string
    {
        // Inutilizacao: nao depende de tblnotafiscal, e sobre faixa de numeracao.
        // A filial vem do caminho antigo; o resto, do proprio XML.
        if ($c['tipo'] === 'inutilizacao') {
            $filial = $this->filialDoCaminho($path);
            $i = $c['inut'] ?? [];
            if (!$filial || empty($i['modelo']) || empty($i['nIni'])) {
                return null;
            }
            return NFePHPPathService::pathInutilizacao(
                $filial,
                (int) $i['modelo'],
                (int) ($i['serie'] ?? 0),
                (int) $i['nIni'],
                (int) ($i['nFin'] ?? $i['nIni']),
                $this->data($i['dh'] ?? null, $path),
                true
            );
        }

        // Manifestacao: a chave e de TERCEIRO, entao nao ha nota nossa para consultar.
        // Filial do caminho, data do proprio evento.
        if ($c['tipo'] === 'manifestacao') {
            $filial = $this->filialDoCaminho($path);
            if (!$filial || empty($c['chave'])) {
                return null;
            }
            return NFePHPPathService::pathManifestacao(
                $filial,
                $c['chave'],
                (int) $c['tpEvento'],
                (int) $c['seq'],
                $this->data(null, $path),
                true
            );
        }

        // Documentos das NOSSAS notas: so migram se a nota existir em tblnotafiscal —
        // e dela que saem filial, modelo e emissao, que definem o caminho novo.
        if (empty($c['chave'])) {
            return null;
        }
        $nf = NotaFiscal::where('nfechave', $c['chave'])->first();
        if (!$nf) {
            return null;
        }

        return match ($c['tipo']) {
            'autorizada' => NFePHPPathService::pathNFeAutorizada($nf, true),
            'assinada' => NFePHPPathService::pathNFeAssinada($nf, true),
            'cancelamento' => NFePHPPathService::pathNFeCancelada($nf, true),
            'cartacorrecao' => NFePHPPathService::pathCartaCorrecao($nf, (int) $c['seq'], true),
            default => null,
        };
    }

    /** A filial esta no caminho antigo: .../{NFe|Mdfe|DFe}/{codfilial}/{ambiente}/... */
    protected function filialDoCaminho(string $path): ?Filial
    {
        if (!preg_match('#/(?:NFe|Mdfe|DFe)/(\d+)/#', $path, $m)) {
            return null;
        }
        $cod = (int) $m[1];
        if (!array_key_exists($cod, $this->filiais)) {
            $this->filiais[$cod] = Filial::find($cod);
        }
        return $this->filiais[$cod];
    }

    /**
     * Data para o caminho: a do documento quando o XML traz, senao a pasta AAAAMM do
     * caminho antigo, senao a data de modificacao do arquivo.
     */
    protected function data(?string $dh, string $path): Carbon
    {
        if (!empty($dh)) {
            try {
                return Carbon::parse($dh);
            } catch (\Throwable $e) {
                // cai para as alternativas abaixo
            }
        }
        if (preg_match('#/(\d{4})(\d{2})/#', $path, $m)) {
            return Carbon::createFromDate((int) $m[1], (int) $m[2], 1)->startOfDay();
        }
        return Carbon::createFromTimestamp(@filemtime($path) ?: time());
    }

    /**
     * O acervo mistura <procInutNFe> e <ProcInutNFe>, entao a comparacao e case-insensitive.
     */
    protected function tem(string $xml, string $elemento): bool
    {
        return stripos($xml, '<' . $elemento) !== false;
    }

    protected function extrair(string $regex, string $xml): ?string
    {
        return preg_match($regex, $xml, $m) ? $m[1] : null;
    }

    protected function mover(string $origem, string $destino, bool $paraLegado = false): void
    {
        if (file_exists($destino)) {
            // Destino ja existe: o documento ja foi migrado noutro formato de nome.
            // E duplicata, nao perda — fica no legado.
            $this->ficaram++;
            if ($paraLegado) {
                $this->paraLegado--;
            } else {
                $this->movidos--;
            }
            $this->registrar('duplicata-destino-existe', $origem);
            return;
        }

        try {
            $dir = dirname($destino);
            if (!is_dir($dir)) {
                @mkdir($dir, 0775, true);
            }
            if (!@rename($origem, $destino)) {
                throw new \Exception('rename() retornou false');
            }
            $this->movidos++;
        } catch (\Throwable $e) {
            $this->falhas++;
            Log::warning("classificar-legado: FALHA {$origem} -> {$destino}: " . $e->getMessage());
        }
    }

    protected function registrar(string $tipo, string $path): void
    {
        $this->contagem[$tipo] = ($this->contagem[$tipo] ?? 0) + 1;
        if (!isset($this->exemplos[$tipo])) {
            $this->exemplos[$tipo] = $path;
        }
    }

    protected function relatorio(int $processados, bool $relatorio): void
    {
        $this->newLine();
        $this->info("Arquivos XML lidos: {$processados}");
        $this->newLine();

        arsort($this->contagem);
        $linhas = [];
        foreach ($this->contagem as $tipo => $qtd) {
            $linhas[] = [$tipo, $qtd, basename($this->exemplos[$tipo])];
        }
        $this->table(['classificacao', 'qtd', 'exemplo'], $linhas);

        if (!$relatorio) {
            $this->newLine();
            $this->info("Para a estrutura nova: {$this->movidos}");
            $this->info("Para legado/:          {$this->paraLegado}");
            $this->info("Ficaram em Arquivos/:  {$this->ficaram}   <- o que NAO foi interpretado");
            $this->info("Falhas:                {$this->falhas}");
        }
    }
}
