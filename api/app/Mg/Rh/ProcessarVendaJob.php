<?php

namespace Mg\Rh;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;

class ProcessarVendaJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $codnegocio;

    public function __construct(int $codnegocio)
    {
        $this->codnegocio = $codnegocio;
    }

    public function handle(): void
    {
        DB::beginTransaction();
        try {
            ProcessarVendaService::processar($this->codnegocio);
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
}
