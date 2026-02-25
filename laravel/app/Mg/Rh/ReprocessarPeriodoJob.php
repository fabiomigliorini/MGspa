<?php

namespace Mg\Rh;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ReprocessarPeriodoJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 1800;
    public $tries = 1;

    public int $codperiodo;
    public bool $limpar;

    public function __construct(int $codperiodo, bool $limpar = false)
    {
        $this->codperiodo = $codperiodo;
        $this->limpar = $limpar;
    }

    public function handle(): void
    {
        ReprocessarPeriodoService::reprocessar(
            $this->codperiodo,
            $this->limpar
        );
    }
}
