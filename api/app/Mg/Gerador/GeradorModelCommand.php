<?php

namespace Mg\Gerador;

use Illuminate\Console\Command;

class GeradorModelCommand extends Command
{
    protected $signature = 'gerador:model {tabela} {--no-test : Pula os testes de count/instanciar/relacionamentos}';
    protected $description = 'Gera o codigo fonte de um model. Use --no-interaction (-n) pra rodar sem prompts e --no-test pra pular validações.';

    public function handle()
    {
        $service = new GeradorModelService($this);
        $service->gerar($this->argument('tabela'));
    }
}
