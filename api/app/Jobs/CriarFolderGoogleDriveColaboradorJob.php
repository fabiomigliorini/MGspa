<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Mg\Colaborador\Colaborador;
use Mg\Colaborador\ColaboradorService;

class CriarFolderGoogleDriveColaboradorJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $backoff = 60;
    public $timeout = 120;

    public function __construct(
        public Colaborador $colaborador,
    ) {
    }

    public function handle(): void
    {
        ColaboradorService::criarFolderGoogleDrive($this->colaborador);
    }
}
