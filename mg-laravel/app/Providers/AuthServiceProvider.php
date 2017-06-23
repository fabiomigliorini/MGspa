<?php

namespace App\Providers;

use Illuminate\Support\Facades\Gate;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array
     */
    protected $policies = [

        // Policies Vinculadas ao Repository
        \App\Repositories\DominioRepository::class      => \App\Policies\DominioPolicy::class,

        // Policies Vinculadas ao Model
        \App\Models\GrupoUsuario::class                 => \App\Policies\GrupoUsuarioPolicy::class,
        \App\Models\Marca::class                        => \App\Policies\MarcaPolicy::class,
        \App\Models\Permissao::class                    => \App\Policies\PermissaoPolicy::class,
        \App\Models\UnidadeMedida::class                => \App\Policies\UnidadeMedidaPolicy::class,
        \App\Models\Usuario::class                      => \App\Policies\UsuarioPolicy::class,
        \App\Models\SecaoProduto::class                 => \App\Policies\SecaoProdutoPolicy::class,
        \App\Models\FamiliaProduto::class               => \App\Policies\FamiliaProdutoPolicy::class,
        \App\Models\GrupoProduto::class                 => \App\Policies\GrupoProdutoPolicy::class,
        \App\Models\SubGrupoProduto::class              => \App\Policies\SubGrupoProdutoPolicy::class,
        \App\Models\TipoProduto::class                  => \App\Policies\TipoProdutoPolicy::class,
        \App\Models\Banco::class                        => \App\Policies\BancoPolicy::class,
        \App\Models\Pais::class                         => \App\Policies\PaisPolicy::class,
        \App\Models\EstadoCivil::class                  => \App\Policies\EstadoCivilPolicy::class,
        \App\Models\ValeCompra::class                   => \App\Policies\ValeCompraPolicy::class,
        \App\Models\Feriado::class                      => \App\Policies\FeriadoPolicy::class,
        \App\Models\ValeCompraModelo::class             => \App\Policies\ValeCompraModeloPolicy::class,
        \App\Models\Cargo::class                        => \App\Policies\CargoPolicy::class,
        \App\Models\ChequeMotivoDevolucao::class        => \App\Policies\ChequeMotivoDevolucaoPolicy::class,
        \App\Models\Cheque::class                       => \App\Policies\ChequePolicy::class,
        \App\Models\Ncm::class                          => \App\Policies\NcmPolicy::class,
        \App\Models\ProdutoHistoricoPreco::class        => \App\Policies\ProdutoHistoricoPrecoPolicy::class,
        \App\Models\ProdutoBarra::class                 => \App\Policies\ProdutoBarraPolicy::class,
        \App\Models\EstoqueSaldoConferencia::class      => \App\Policies\EstoqueSaldoConferenciaPolicy::class,
        \App\Models\EstoqueSaldo::class                 => \App\Policies\EstoqueSaldoPolicy::class,
        \App\Models\TipoMovimentoTitulo::class          => \App\Policies\TipoMovimentoTituloPolicy::class,
        \App\Models\Meta::class                         => \App\Policies\MetaPolicy::class,
        \App\Models\Produto::class                      => \App\Policies\ProdutoPolicy::class,
        \App\Models\EstoqueMes::class                   => \App\Policies\EstoqueMesPolicy::class,
        \App\Models\NegocioProdutoBarra::class          => \App\Policies\NegocioProdutoBarraPolicy::class,
        \App\Models\EstoqueLocal::class                 => \App\Policies\EstoqueLocalPolicy::class,
        \App\Models\Cest::class                         => \App\Policies\CestPolicy::class,
        \App\Models\Imagem::class                       => \App\Policies\ImagemPolicy::class,
        \App\Models\ProdutoBarra::class                 => \App\Policies\ProdutoBarraPolicy::class,
        \App\Models\ProdutoVariacao::class              => \App\Policies\ProdutoVariacaoPolicy::class,
        \App\Models\EstoqueMovimento::class             => \App\Policies\EstoqueMovimentoPolicy::class,
        \App\Models\ChequeRepasse::class                => \App\Policies\ChequeRepassePolicy::class,
        \App\Models\Prancheta::class                    => \App\Policies\PranchetaPolicy::class,
        \App\Models\PranchetaProduto::class             => \App\Policies\PranchetaProdutoPolicy::class,
        \App\Models\NotaFiscalProdutoBarra::class       => \App\Policies\NotaFiscalProdutoBarraPolicy::class,
        \App\Models\ProdutoEmbalagem::class             => \App\Policies\ProdutoEmbalagemPolicy::class,
        \App\Models\Caixa::class                        => \App\Policies\CaixaPolicy::class,

    ];

    /**
     * Register any authentication / authorization services.
     *
     * @return void
     */
    public function boot()
    {
        $this->registerPolicies();

        //
    }

    public function getPolicies($model = null)
    {
        if (!empty($model)) {
            if (isset($this->policies[$model])) {
                return $this->policies[$model];
            } else {
                return false;
            }
        }
        return $this->policies;
    }
}
