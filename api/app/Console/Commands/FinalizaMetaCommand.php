<?php

namespace App\Console\Commands;

use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Mg\Meta\Meta;
use Mg\Meta\MetaService;
use Mg\Meta\Services\MetaReconstrucaoService;

class FinalizaMetaCommand extends Command
{
    protected $signature = 'meta:finalizar {codmeta}';
    protected $description = 'Finaliza uma meta bloqueada e cria a meta seguinte';

    public function handle(): int
    {
        $codmeta = intval($this->argument('codmeta'));

        try {
            DB::disableQueryLog();

            // Transação curta: verificar status e marcar processando
            DB::beginTransaction();
            $meta = Meta::where('codmeta', $codmeta)->lockForUpdate()->firstOrFail();

            if ($meta->status !== MetaService::META_STATUS_BLOQUEADA) {
                DB::rollBack();
                $this->error("Meta #{$codmeta} nao esta bloqueada. Status atual: {$meta->status}");
                return 1;
            }

            if ($meta->processando) {
                DB::rollBack();
                $this->error("Meta #{$codmeta} ja esta sendo processada. Aguarde.");
                return 1;
            }

            $meta->update(['processando' => true]);
            DB::commit();

            Log::info('FinalizaMetaCommand - Fechamento iniciado', [
                'codmeta' => $meta->codmeta,
                'status' => $meta->status,
            ]);

            // Reconciliar — gerencia próprias transações por chunk
            MetaReconstrucaoService::reconciliarMeta($meta);

            // Operações finais em transação única (rápida)
            DB::beginTransaction();
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
            try { DB::rollBack(); } catch (Exception $e) {}

            Meta::where('codmeta', $codmeta)->update(['processando' => false]);

            Log::error('FinalizaMetaCommand - Erro', [
                'codmeta' => $codmeta,
                'mensagem' => $exception->getMessage(),
            ]);

            $this->error("Erro ao finalizar meta {$codmeta}: {$exception->getMessage()}");

            return 1;
        }
    }
}
