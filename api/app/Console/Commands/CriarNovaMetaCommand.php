<?php

namespace App\Console\Commands;

use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Mg\Meta\Meta;
use Mg\Meta\MetaService;

class CriarNovaMetaCommand extends Command
{
    protected $signature = 'meta:criar-nova {codmeta}';
    protected $description = 'Cria nova meta com periodo seguinte e copia de configuracao';

    public function handle(): int
    {
        $codmeta = intval($this->argument('codmeta'));

        try {
            DB::beginTransaction();

            $metaAnterior = Meta::query()
                ->where('codmeta', $codmeta)
                ->lockForUpdate()
                ->firstOrFail();

            $metaAberta = Meta::query()
                ->where('status', MetaService::META_STATUS_ABERTA)
                ->lockForUpdate()
                ->first();

            if (!empty($metaAberta)) {
                throw new Exception("Ja existe meta aberta ({$metaAberta->codmeta}).");
            }

            $novaMeta = MetaService::criarNovaMeta($metaAnterior);

            DB::commit();

            $this->info("Meta {$novaMeta->codmeta} criada com sucesso.");

            return 0;
        } catch (Exception $exception) {
            DB::rollBack();

            Log::error('CriarNovaMetaCommand - Erro', [
                'codmeta' => $codmeta,
                'mensagem' => $exception->getMessage(),
            ]);

            $this->error("Erro ao criar nova meta: {$exception->getMessage()}");

            return 1;
        }
    }
}
