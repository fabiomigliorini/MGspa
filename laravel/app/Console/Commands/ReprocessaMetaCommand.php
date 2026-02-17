<?php

namespace App\Console\Commands;

use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Mg\Meta\Meta;
use Mg\Meta\Services\ReprocessamentoMetaService;

class ReprocessaMetaCommand extends Command
{
    protected $signature = 'meta:reprocessar {codmeta}';
    protected $description = 'Reprocessa eventos automaticos da meta';

    public function handle(): int
    {
        $codmeta = intval($this->argument('codmeta'));

        try {
            DB::beginTransaction();

            $meta = Meta::query()
                ->where('codmeta', $codmeta)
                ->lockForUpdate()
                ->firstOrFail();

            Log::info('ReprocessaMetaCommand - Reprocessamento iniciado', [
                'codmeta' => $meta->codmeta,
                'status' => $meta->status,
            ]);

            $meta->update(['processando' => true]);

            ReprocessamentoMetaService::reprocessar($meta);

            $meta->update(['processando' => false]);

            DB::commit();

            $this->info("Meta {$meta->codmeta} reprocessada com sucesso.");

            return 0;
        } catch (Exception $exception) {
            DB::rollBack();

            Log::error('ReprocessaMetaCommand - Erro', [
                'codmeta' => $codmeta,
                'mensagem' => $exception->getMessage(),
            ]);

            $this->error("Erro ao reprocessar meta {$codmeta}: {$exception->getMessage()}");

            return 1;
        }
    }
}
