<?php

namespace App\Console\Commands;

use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Mg\Meta\Meta;
use Mg\Meta\MetaService;
use Mg\Meta\Services\ReprocessamentoMetaService;

class FinalizaMetaCommand extends Command
{
    protected $signature = 'meta:finalizar {codmeta}';
    protected $description = 'Finaliza uma meta bloqueada e cria a meta seguinte';

    public function handle(): int
    {
        $codmeta = intval($this->argument('codmeta'));

        try {
            DB::beginTransaction();

            $meta = Meta::query()
                ->where('codmeta', $codmeta)
                ->lockForUpdate()
                ->firstOrFail();

            if ($meta->status !== MetaService::META_STATUS_BLOQUEADA) {
                throw new Exception("Meta {$meta->codmeta} nao esta bloqueada.");
            }

            Log::info('FinalizaMetaCommand - Fechamento iniciado', [
                'codmeta' => $meta->codmeta,
                'status' => $meta->status,
            ]);

            $meta->update(['processando' => true]);

            ReprocessamentoMetaService::reprocessar($meta);
            MetaService::apurarMovimentosFinais($meta);

            $meta->update([
                'status' => MetaService::META_STATUS_FECHADA,
                'processando' => false,
            ]);

            $novaMeta = MetaService::criarNovaMeta($meta);

            DB::commit();

            $this->info("Meta {$meta->codmeta} finalizada. Nova meta {$novaMeta->codmeta} criada.");

            return 0;
        } catch (Exception $exception) {
            DB::rollBack();

            Log::error('FinalizaMetaCommand - Erro', [
                'codmeta' => $codmeta,
                'mensagem' => $exception->getMessage(),
            ]);

            $this->error("Erro ao finalizar meta {$codmeta}: {$exception->getMessage()}");

            return 1;
        }
    }
}
