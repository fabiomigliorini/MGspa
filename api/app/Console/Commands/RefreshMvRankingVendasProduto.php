<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB; // Importar a fachada DB
use Illuminate\Support\Facades\Log;

/* View utilizada pra exportacao do woocommerce */
class RefreshMvRankingVendasProduto extends Command
{
    /**
     * O nome e a assinatura do comando Artisan.
     * Use um nome único e descritivo.
     *
     * @var string
     */
    protected $signature = 'ranking-produto:refresh';

    /**
     * A descrição do console command.
     *
     * @var string
     */
    protected $description = 'Atualiza a Materialized View MvRanking    .';

    /**
     * Execute o console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info('Iniciando o refresh da Materialized View...');

        try {
            // O comando SQL que você deseja executar
            DB::statement('REFRESH MATERIALIZED VIEW MvRankingVendasProduto');
            $this->info('Materialized View MvRankingVendasProduto atualizada com sucesso!');
            Log::info('Materialized View MvRankingVendasProduto atualizada com sucesso!');
            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error('Erro ao atualizar a Materialized View: ' . $e->getMessage());
            // Opcional: registrar o erro
            Log::error('Erro no refresh da MV: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}