<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Mg\Filial\Filial;
use Mg\NotaFiscalServico\NfseDistribuicaoService;

/**
 * Le a caixa de DF-e da NFS-e (distribuicao do ADN) das filiais que emitem NFS-e
 * (TASK-279), como o nfe-php:dist-dfe faz com a NF-e.
 */
class NfseCommandDistDfe extends Command
{
    protected $signature = 'nfse:dist-dfe {--codfilial=}';

    protected $description = 'Le a distribuicao de DF-e da NFS-e no ADN (Sistema Nacional)';

    public function handle()
    {
        $qry = Filial::where('emitenfse', true)->whereNull('inativo')->orderBy('codfilial');
        if ($this->option('codfilial')) {
            $qry->where('codfilial', $this->option('codfilial'));
        }
        foreach ($qry->get() as $filial) {
            try {
                $total = NfseDistribuicaoService::consultarTudo($filial);
                $this->info("{$filial->filial}: {$total} documento(s) novo(s).");
            } catch (\Throwable $e) {
                $this->error("{$filial->filial}: " . $e->getMessage());
            }
        }
        return 0;
    }
}
