<?php

namespace App\Console\Commands;

use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Mg\Meta\Meta;
use Mg\Meta\Services\MetaReconstrucaoService;

class ReprocessaMetaCommand extends Command
{
    protected $signature = 'meta:reprocessar {codmeta}';
    protected $description = 'Reprocessa eventos automaticos da meta';

    public function handle(): int
    {
        $codmeta = intval($this->argument('codmeta'));

        try {
            DB::disableQueryLog();

            // Transação curta: verificar e marcar processando
            DB::beginTransaction();
            $meta = Meta::where('codmeta', $codmeta)->lockForUpdate()->firstOrFail();

            if ($meta->processando) {
                DB::rollBack();
                $this->error("Meta #{$codmeta} ja esta sendo processada. Aguarde.");
                return 1;
            }

            $meta->update(['processando' => true]);
            DB::commit();

            Log::info('ReprocessaMetaCommand - Reprocessamento iniciado', [
                'codmeta' => $meta->codmeta,
                'status' => $meta->status,
            ]);

            // Reconciliar — gerencia próprias transações por chunk
            MetaReconstrucaoService::reconciliarMeta($meta);

            $meta->update(['processando' => false]);

            $this->info("Meta {$meta->codmeta} reprocessada com sucesso.");

            return 0;
        } catch (Exception $exception) {
            try { DB::rollBack(); } catch (Exception $e) {}

            Meta::where('codmeta', $codmeta)->update(['processando' => false]);

            Log::error('ReprocessaMetaCommand - Erro', [
                'codmeta' => $codmeta,
                'mensagem' => $exception->getMessage(),
            ]);

            $this->error("Erro ao reprocessar meta {$codmeta}: {$exception->getMessage()}");

            return 1;
        }
    }
}
