<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

use Mg\Filial\Filial;
use Mg\NFePHP\NFePHPPathService;
use Mg\NotaFiscal\NotaFiscalService;

/**
 * Move os arquivos da estrutura antiga para a nova.
 *
 * RODA EM PRODUCAO, EM FIM DE SEMANA, COMO ULTIMA ETAPA — depois do deploy do codigo novo.
 *
 * Nao ha fallback de leitura: o codigo novo ja le da arvore nova, entao um arquivo ainda
 * nao movido fica invisivel para consulta de XML/DANFE ate a migracao passar por ele. A
 * EMISSAO nao e afetada (o codigo novo escreve direto na arvore nova). Nao rodar a
 * exportacao da contabilidade (DominioXMLService) durante a janela.
 *
 * Varre o BANCO, nao o disco: e o banco que sabe chave, modelo, filial, emissao e a
 * sequencia da carta de correcao.
 *
 * Idempotente: se a origem nao existe ou o destino ja existe, pula.
 *
 * O QUE SOBRAR NA ARVORE ANTIGA E O RELATORIO DE ERRO. Cada falha vai para Log::warning
 * com origem, destino e o motivo.
 */
class NFePHPMigrarArquivosCommand extends Command
{
    protected $signature = 'nfe-php:migrar-arquivos
        {--limite= : maximo de notas a processar (para amostra)}
        {--modelo= : 55 ou 65}
        {--filial= : codfilial}
        {--desde= : so notas emitidas a partir desta data (AAAA-MM-DD)}
        {--fases : roda em janelas crescentes (7d, 15d, 30d, 90d, 1a, 2a, 5a, tudo)}
        {--apenas= : notas|mdfe|dfe|certificado}
        {--dry-run : so mostra o que faria}';

    protected $description = 'Move os arquivos de NFe/NFCe/MDFe/DFe da estrutura antiga para a nova';

    protected int $movidos = 0;
    protected int $pulados = 0;
    protected int $falhas = 0;

    /**
     * Janelas crescentes.
     *
     * Enquanto a migracao nao passa por um arquivo, a leitura dele da 404. Rodando em
     * janelas que crescem, as notas que as pessoas de fato pedem saem da zona de risco nos
     * primeiros segundos, e o acervo antigo (70% do volume tem mais de 3 anos) fica para o
     * fim, quando ninguem o procura.
     *
     * Cada fase e idempotente: o que ja foi movido nao existe mais na origem e e' pulado
     * de imediato, entao a sobreposicao entre janelas custa quase nada.
     */
    const FASES = [
        ['rotulo' => 'ultimos 7 dias',   'dias' => 7],
        ['rotulo' => 'ultimos 15 dias',  'dias' => 15],
        ['rotulo' => 'ultimos 30 dias',  'dias' => 30],
        ['rotulo' => 'ultimos 90 dias',  'dias' => 90],
        ['rotulo' => 'ultimo ano',       'dias' => 365],
        ['rotulo' => 'ultimos 2 anos',   'dias' => 730],
        ['rotulo' => 'ultimos 5 anos',   'dias' => 1825],
        ['rotulo' => 'todo o acervo',    'dias' => null],
    ];

    public function handle()
    {
        $dryRun = (bool) $this->option('dry-run');
        if ($dryRun) {
            $this->warn('DRY-RUN: nenhum arquivo sera movido.');
        }

        $apenas = $this->option('apenas');

        // CERTIFICADO SEMPRE PRIMEIRO. Sem ele instanciaTools() lanca excecao e NENHUMA
        // NFe e emitida — nao e degradacao de leitura, e parada total. Sao 7 arquivos.
        if (!$apenas || $apenas === 'certificado') {
            $this->migrarCertificados($dryRun);
            if ($apenas === 'certificado') {
                $this->resumo();
                return 0;
            }
        }

        if ($this->option('fases')) {
            foreach (static::FASES as $fase) {
                $this->newLine();
                $this->comment("=== FASE: {$fase['rotulo']} ===");
                $desde = $fase['dias'] ? Carbon::now()->subDays($fase['dias'])->format('Y-m-d') : null;
                $this->migrarNotasFiscais($dryRun, $desde);
            }
        } elseif (!$apenas || $apenas === 'notas') {
            $this->migrarNotasFiscais($dryRun, $this->option('desde'));
        }

        if (!$apenas || $apenas === 'mdfe') {
            $this->migrarMdfe($dryRun);
        }
        if (!$apenas || $apenas === 'dfe') {
            $this->migrarDfe($dryRun);
        }

        $this->resumo();

        return 0;
    }

    protected function resumo(): void
    {
        $this->newLine();
        $this->info("Movidos: {$this->movidos}  Pulados: {$this->pulados}  Falhas: {$this->falhas}");
        $this->line('Confira o que sobrou na arvore antiga:');
        $this->line('  find ' . $this->raizLegado() . '/{NFe,Mdfe,DFe,Certs} -type f | wc -l');
    }

    /** O --limite vale para as tres arvores, senao a "amostra pequena" processa tudo. */
    protected function sqlLimite(): string
    {
        return $this->option('limite') ? 'limit ' . (int) $this->option('limite') : '';
    }

    /** Destino: a raiz nova, sem o 'Arquivos/' redundante. */
    protected function raiz(): string
    {
        return rtrim(config('mg.paths.nfe_php'), '/');
    }

    /**
     * Origem: a arvore legada vivia num subdiretorio 'Arquivos/' dentro de NFePHP/, o que
     * era redundante (NFePHP/Arquivos/NFe/...). O NFE_PHP_PATH passou a apontar para
     * NFePHP/ direto, entao o legado fica um nivel abaixo do destino.
     */
    protected function raizLegado(): string
    {
        return $this->raiz() . '/Arquivos';
    }

    protected function migrarNotasFiscais(bool $dryRun, ?string $desde = null): void
    {
        $where = ['nf.emitida = true', 'nf.nfechave is not null'];
        $params = [];
        if ($m = $this->option('modelo')) {
            $where[] = 'nf.modelo = :modelo';
            $params['modelo'] = $m;
        }
        if ($f = $this->option('filial')) {
            $where[] = 'nf.codfilial = :codfilial';
            $params['codfilial'] = $f;
        }
        if ($desde) {
            $where[] = 'nf.emissao >= :desde';
            $params['desde'] = $desde;
        }
        $limite = $this->sqlLimite();

        // A sequencia da carta de correcao vem por LEFT JOIN, nao por query-por-nota:
        // com 3,4 milhoes de notas o N+1 sozinho levaria horas.
        //
        // Ordem por emissao DESC de proposito: enquanto a migracao nao passa por um
        // arquivo, a leitura dele da 404. Migrando do mais recente para o mais antigo, as
        // notas que as pessoas realmente pedem (DANFE, XML, e-mail) saem da zona de risco
        // nos primeiros minutos; as de 2015 ficam para o fim, quando ninguem as procura.
        $sql = '
            select nf.codnotafiscal, nf.codfilial, nf.modelo, nf.nfechave, nf.emissao,
                   f.nfeambiente, cce.seq cceseq, cce.qtd cceqtd
            from tblnotafiscal nf
            inner join tblfilial f on (f.codfilial = nf.codfilial)
            left join (
                select codnotafiscal, max(sequencia) seq, count(*) qtd
                from tblnotafiscalcartacorrecao group by codnotafiscal
            ) cce on (cce.codnotafiscal = nf.codnotafiscal)
            where ' . implode(' and ', $where) . "
            order by nf.emissao desc, nf.codnotafiscal desc
            {$limite}
        ";

        $notas = DB::select($sql, $params);
        $this->info('Notas fiscais: ' . count($notas));
        $barra = $this->output->createProgressBar(count($notas));

        $filiais = [];
        foreach ($notas as $nota) {
            if (!isset($filiais[$nota->codfilial])) {
                $filiais[$nota->codfilial] = Filial::find($nota->codfilial);
            }
            $filial = $filiais[$nota->codfilial];
            $emissao = Carbon::parse($nota->emissao);
            $ambiente = ($nota->nfeambiente == 1) ? 'producao' : 'homologacao';
            $ym = $emissao->format('Ym');
            $antigo = "{$this->raizLegado()}/NFe/{$nota->codfilial}/{$ambiente}";
            $tipo = ($nota->modelo == NotaFiscalService::MODELO_NFCE)
                ? NFePHPPathService::TIPO_NFCE
                : NFePHPPathService::TIPO_NFE;
            $novoDir = NFePHPPathService::pathDiretorio($tipo, $filial, $emissao);

            $mapa = [
                "{$antigo}/assinadas/{$ym}/{$nota->nfechave}-NFe.xml" => "{$nota->nfechave}-assinado.xml",
                "{$antigo}/enviadas/aprovadas/{$ym}/{$nota->nfechave}-protNFe.xml" => "{$nota->nfechave}-proc.xml",
                "{$antigo}/enviadas/denegadas/{$ym}/{$nota->nfechave}-NFe.xml" => "{$nota->nfechave}-denegado.xml",
                "{$antigo}/canceladas/{$ym}/{$nota->nfechave}-NFe.xml" => "{$nota->nfechave}-cancelado.xml",
                "{$antigo}/pdf/{$ym}/{$nota->nfechave}-NFe.pdf" => "{$nota->nfechave}.pdf",
            ];

            // Carta de correcao: o arquivo antigo NAO tinha sequencia e se sobrescrevia,
            // entao so a ULTIMA CC-e de cada nota existe em disco — as anteriores ja se
            // perderam antes deste PR. Atribui a maior sequencia, que e justamente a que
            // sobreviveu (as CC-e sao emitidas em ordem crescente).
            if ($nota->cceseq) {
                $mapa["{$antigo}/cartacorrecao/{$ym}/{$nota->nfechave}-CCe.xml"] = "{$nota->nfechave}-cce-{$nota->cceseq}.xml";
                if ($nota->cceqtd > 1) {
                    Log::warning(
                        "migrar-arquivos: NF#{$nota->codnotafiscal} tem {$nota->cceqtd} cartas de correcao no banco " .
                        'mas so 1 arquivo em disco (sobrescrita anterior a este PR). Migrando como sequencia ' . $nota->cceseq
                    );
                }
            }

            foreach ($mapa as $origem => $arquivo) {
                $this->mover($origem, "{$novoDir}/{$arquivo}", $novoDir, $dryRun);
            }
            $barra->advance();
        }
        $barra->finish();
        $this->newLine();
    }

    protected function migrarMdfe(bool $dryRun): void
    {
        if (!DB::getSchemaBuilder()->hasTable('tblmdfe')) {
            return;
        }

        $mdfes = DB::select('
            select m.codmdfe, m.codfilial, m.chmdfe, m.emissao, f.nfeambiente
            from tblmdfe m
            inner join tblfilial f on (f.codfilial = m.codfilial)
            where m.chmdfe is not null
            order by m.codmdfe
            ' . $this->sqlLimite());
        $this->info('MDF-e: ' . count($mdfes));

        $filiais = [];
        foreach ($mdfes as $mdfe) {
            if (!isset($filiais[$mdfe->codfilial])) {
                $filiais[$mdfe->codfilial] = Filial::find($mdfe->codfilial);
            }
            $emissao = Carbon::parse($mdfe->emissao);
            $ambiente = ($mdfe->nfeambiente == 1) ? 'producao' : 'homologacao';
            $ym = $emissao->format('Y/m');
            $antigo = "{$this->raizLegado()}/Mdfe/{$mdfe->codfilial}/{$ambiente}";
            $novoDir = NFePHPPathService::pathDiretorio(
                NFePHPPathService::TIPO_MDFE,
                $filiais[$mdfe->codfilial],
                $emissao
            );

            $mapa = [
                "{$antigo}/criado/{$ym}/{$mdfe->chmdfe}-MDFe.xml" => "{$mdfe->chmdfe}-assinado.xml",
                "{$antigo}/autorizado/{$ym}/{$mdfe->chmdfe}-MDFe.xml" => "{$mdfe->chmdfe}-proc.xml",
                "{$antigo}/damdfe/{$ym}/{$mdfe->chmdfe}-MDFe.pdf" => "{$mdfe->chmdfe}.pdf",
            ];
            foreach ($mapa as $origem => $arquivo) {
                $this->mover($origem, "{$novoDir}/{$arquivo}", $novoDir, $dryRun);
            }
        }
    }

    protected function migrarDfe(bool $dryRun): void
    {
        $dfes = DB::select('
            select d.coddistribuicaodfe, d.codfilial, d.nsu, d.nfechave, d.criacao, f.nfeambiente
            from tbldistribuicaodfe d
            inner join tblfilial f on (f.codfilial = d.codfilial)
            order by d.coddistribuicaodfe
            ' . $this->sqlLimite());
        $this->info('DFe: ' . count($dfes));

        $filiais = [];
        foreach ($dfes as $dfe) {
            if (!isset($filiais[$dfe->codfilial])) {
                $filiais[$dfe->codfilial] = Filial::find($dfe->codfilial);
            }
            $criacao = Carbon::parse($dfe->criacao);
            $ambiente = ($dfe->nfeambiente == 1) ? 'producao' : 'homologacao';
            $origem = "{$this->raizLegado()}/DFe/{$dfe->codfilial}/{$ambiente}/"
                . $criacao->format('Y/m') . "/{$dfe->coddistribuicaodfe}.xml.gz";

            $nsu = number_format((float) $dfe->nsu, 0, '', '');
            $arquivo = empty($dfe->nfechave) ? "{$nsu}.xml.gz" : "{$nsu}-{$dfe->nfechave}.xml.gz";
            $novoDir = NFePHPPathService::pathDiretorio(
                NFePHPPathService::TIPO_DFE,
                $filiais[$dfe->codfilial],
                $criacao
            );

            $this->mover($origem, "{$novoDir}/{$arquivo}", $novoDir, $dryRun);
        }
    }

    /**
     * Certificados: COPIA, nao move.
     *
     * O codigo antigo le de Certs/ e o novo de certificado/. Copiando, as duas arvores
     * ficam validas durante a transicao e a emissao nao para em nenhum momento — inclusive
     * se for preciso reverter o deploy. Os Certs/ antigos saem na limpeza manual, no fim.
     */
    protected function migrarCertificados(bool $dryRun): void
    {
        $destinoDir = $this->raiz() . '/' . NFePHPPathService::DIR_CERTIFICADO;
        $this->info('Certificados (copia, nao move):');

        foreach (Filial::all() as $filial) {
            $destino = "{$destinoDir}/{$filial->codfilial}.pfx";
            if (file_exists($destino)) {
                $this->pulados++;
                continue;
            }
            // O caminho antigo tinha barra dupla: config termina em '/' e concatenava '/Certs/'
            foreach ([
                "{$this->raizLegado()}//Certs/{$filial->codfilial}.pfx",
                "{$this->raizLegado()}/Certs/{$filial->codfilial}.pfx",
                "{$this->raizLegado()}/certificado/{$filial->codfilial}.pfx",
            ] as $origem) {
                if (!file_exists($origem)) {
                    continue;
                }
                if ($dryRun) {
                    $this->line("  copiaria {$origem}\n    -> {$destino}");
                    $this->movidos++;
                    break;
                }
                try {
                    if (!is_dir($destinoDir)) {
                        @mkdir($destinoDir, 0775, true);
                    }
                    if (!@copy($origem, $destino)) {
                        throw new \Exception('copy() retornou false');
                    }
                    $this->movidos++;
                } catch (\Throwable $e) {
                    $this->falhas++;
                    Log::warning("migrar-arquivos: FALHA copia {$origem} -> {$destino}: " . $e->getMessage());
                }
                break;
            }
        }

        $faltando = Filial::all()->filter(fn($f) => !file_exists("{$destinoDir}/{$f->codfilial}.pfx"));
        if ($faltando->isNotEmpty() && !$dryRun) {
            $this->warn('  ATENCAO: filiais SEM certificado no destino: '
                . $faltando->pluck('codfilial')->implode(', '));
            $this->warn('  Essas filiais NAO conseguirao emitir. Confira antes de liberar o sistema.');
        }
    }

    protected function mover(string $origem, string $destino, string $destinoDir, bool $dryRun): void
    {
        if (!file_exists($origem)) {
            return;
        }
        if (file_exists($destino)) {
            $this->pulados++;
            return;
        }
        if ($dryRun) {
            $this->line("  {$origem}\n    -> {$destino}");
            $this->movidos++;
            return;
        }

        try {
            if (!is_dir($destinoDir)) {
                @mkdir($destinoDir, 0775, true);
            }
            if (!@rename($origem, $destino)) {
                throw new \Exception('rename() retornou false');
            }
            $this->movidos++;
        } catch (\Throwable $e) {
            $this->falhas++;
            Log::warning("migrar-arquivos: FALHA {$origem} -> {$destino}: " . $e->getMessage());
        }
    }
}
