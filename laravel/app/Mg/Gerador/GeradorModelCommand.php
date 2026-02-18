<?php

namespace Mg\Gerador;

use Illuminate\Console\Command;

class GeradorModelCommand extends Command
{
    protected $signature = 'gerador:model {tabela}';
    protected $description = 'Gera o codigo fonte de um model';

    public function handle()
    {
        $service = new GeradorModelService($this);
        $service->gerar($this->argument('tabela'));
    }
}
