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
        {--dry-run : so mostra o que faria}';

    protected $description = 'Move os arquivos de NFe/NFCe/MDFe/DFe da estrutura antiga para a nova';

    protected int $movidos = 0;
    protected int $pulados = 0;
    protected int $falhas = 0;

    public function handle()
    {
        $dryRun = (bool) $this->option('dry-run');
        if ($dryRun) {
            $this->warn('DRY-RUN: nenhum arquivo sera movido.');
        }

        $this->migrarNotasFiscais($dryRun);
        $this->migrarMdfe($dryRun);
        $this->migrarDfe($dryRun);
        $this->migrarCertificados($dryRun);

        $this->newLine();
        $this->info("Movidos: {$this->movidos}  Pulados: {$this->pulados}  Falhas: {$this->falhas}");
        $this->line('Confira o que sobrou na arvore antiga:');
        $this->line('  find ' . rtrim(config('mg.paths.nfe_php'), '/') . '/{NFe,Mdfe,DFe,Certs} -type f | wc -l');

        return 0;
    }

    /** O --limite vale para as tres arvores, senao a "amostra pequena" processa tudo. */
    protected function sqlLimite(): string
    {
        return $this->option('limite') ? 'limit ' . (int) $this->option('limite') : '';
    }

    protected function raiz(): string
    {
        return rtrim(config('mg.paths.nfe_php'), '/');
    }

    protected function migrarNotasFiscais(bool $dryRun): void
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
        $limite = $this->sqlLimite();

        $sql = '
            select nf.codnotafiscal, nf.codfilial, nf.modelo, nf.nfechave, nf.emissao,
                   f.nfeambiente
            from tblnotafiscal nf
            inner join tblfilial f on (f.codfilial = nf.codfilial)
            where ' . implode(' and ', $where) . "
            order by nf.codnotafiscal
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
            $antigo = "{$this->raiz()}/NFe/{$nota->codfilial}/{$ambiente}";
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
            $cce = DB::selectOne(
                'select max(sequencia) seq, count(*) qtd from tblnotafiscalcartacorrecao where codnotafiscal = ?',
                [$nota->codnotafiscal]
            );
            if ($cce && $cce->seq) {
                $mapa["{$antigo}/cartacorrecao/{$ym}/{$nota->nfechave}-CCe.xml"] = "{$nota->nfechave}-cce-{$cce->seq}.xml";
                if ($cce->qtd > 1) {
                    Log::warning(
                        "migrar-arquivos: NF#{$nota->codnotafiscal} tem {$cce->qtd} cartas de correcao no banco " .
                        'mas so 1 arquivo em disco (sobrescrita anterior a este PR). Migrando como sequencia ' . $cce->seq
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
            $antigo = "{$this->raiz()}/Mdfe/{$mdfe->codfilial}/{$ambiente}";
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
            $origem = "{$this->raiz()}/DFe/{$dfe->codfilial}/{$ambiente}/"
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

    protected function migrarCertificados(bool $dryRun): void
    {
        $destinoDir = "{$this->raiz()}/certificado";
        foreach (Filial::all() as $filial) {
            // O caminho antigo tinha barra dupla: config termina em '/' e concatenava '/Certs/'
            foreach (["{$this->raiz()}//Certs/{$filial->codfilial}.pfx", "{$this->raiz()}/Certs/{$filial->codfilial}.pfx"] as $origem) {
                $this->mover($origem, "{$destinoDir}/{$filial->codfilial}.pfx", $destinoDir, $dryRun);
            }
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
