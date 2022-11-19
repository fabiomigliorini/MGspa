<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

use Mg\PagarMe\PagarMeWebhookJob;

class PagarMeProcessarArquivoCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'pagar-me:processar-arquivo {arquivo}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Processa Json recebido pelo Webhook da PagarMe';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $arquivo = $this->argument('arquivo');
        PagarMeWebhookJob::dispatch($arquivo);
    }
}
