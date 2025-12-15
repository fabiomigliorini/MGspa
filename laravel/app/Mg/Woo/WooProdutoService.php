<?php

namespace Mg\Woo;

use Illuminate\Database\Eloquent\Collection;
use stdClass;
use Exception;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Mg\Produto\Produto;
use Mg\Produto\ProdutoVariacao;
use Mg\Produto\ProdutoBarra;

class WooProdutoService
{

    protected WooApi $api;
    protected Produto $prod;
    protected ?WooProduto $wp;
    protected float $fatorPreco;
    protected Carbon $exportacao;
    protected bool $variacao = false;
    protected Collection $variacoes;

    public function __construct(Produto $prod, float $fatorPreco = null)
    {
        $this->prod = $prod;
        $this->fatorPreco = $fatorPreco ?? ((float) env('WOO_FATOR_PRECO', 1));
        $this->wp = WooProduto::where('codproduto', $prod->codproduto)->whereNull('inativo')->whereNull('codprodutovariacao')->first();
        $this->api = new WooApi();
        $this->exportacao = Carbon::now();
        $this->variacoes = $prod->ProdutoVariacaoS()->whereNull('inativo')->get();
        $this->variacao = ($this->variacoes->count() > 1);
    }

    public function buildParcial()
    {
        $prod = $this->prod;
        $product = new stdClass();

        $product->regular_price = $this->preco($prod->preco, $this->wp->quantidadeembalagem, $this->wp->margemunidade);
        // $product->stock_quantity = "4750";
        if (empty($this->wp->codprodutovariacao)) {
            $pv = $prod->ProdutoVariacaoS[0];
        } else {
            $pv = $this->wp->ProdutoVariacao;
        }
        $product->stock_quantity = static::estoque($pv, $this->wp->quantidadeembalagem);
        // $product->stock_status = "instock";
        $product->purchasable = ($product->stock_quantity > 0);

        $product->dimensions = new stdClass();
        $product->dimensions->length = "{$prod->profundidade}";
        $product->dimensions->width = "{$prod->largura}";
        $product->dimensions->height = "{$prod->altura}";

        if ($product->dimensions->length <= $product->dimensions->width && $product->dimensions->length <= $product->dimensions->height) {
            $product->dimensions->length /= $this->wp->quantidadeembalagem;
            $product->dimensions->length = "{$product->dimensions->length}";
        } elseif ($product->dimensions->width < $product->dimensions->length && $product->dimensions->width < $product->dimensions->height) {
            $product->dimensions->width /= $this->wp->quantidadeembalagem;
            $product->dimensions->width = "{$product->dimensions->width}";
        } else {
            $product->dimensions->height /= $this->wp->quantidadeembalagem;
            $product->dimensions->height = "{$product->dimensions->height}";
        }
        $peso = $prod->peso / $this->wp->quantidadeembalagem;
        $product->weight = "{$peso}";

        $product->tiered_pricing_type = "fixed";
        $product->tiered_pricing_fixed_rules = new stdClass();
        if ($this->wp->quantidadepacote) {
            $product->tiered_pricing_fixed_rules->{$this->wp->quantidadepacote} = $this->preco($prod->preco, $this->wp->quantidadeembalagem, $this->wp->margempacote);
        }
        if ($this->wp->quantidadeembalagem > 1) {
            $product->tiered_pricing_fixed_rules->{$this->wp->quantidadeembalagem} = $this->preco($prod->preco, $this->wp->quantidadeembalagem);
        }
        return $product;
    }

    public function build()
    {
        $prod = $this->prod;
        $variacoes = $this->variacoes;
        $variacao = $this->variacao;

        // Propriedades básicas
        $product = new stdClass;
        // $product->id = 9999; // IDs geralmente não são alterados, mas para demonstração
        $product->name = $prod->titulosite ?? $prod->produto;
        $product->sku = '#' . str_pad($prod->codproduto, 6, '0', STR_PAD_LEFT);
        // $product->slug = 'caderno-fortnite-max-edicao-2025';
        // $product->permalink = 'https://sinopel.mrxempresas.com.br/p/caderno-fortnite-max-edicao-2025/';
        // $product->date_created = '2025-06-04T10:00:00'; // Mudando a data para hoje
        // $product->date_created_gmt = '2025-06-04T14:00:00';
        // $product->date_modified = '2025-06-04T10:15:00'; // Mudando a data para hoje
        // $product->date_modified_gmt = '2025-06-04T14:15:00';
        if ($variacao) {
            $product->type = 'variable'; // Com Variacoes
        } else {
            $product->type = 'simple'; // Sem Variacoes
        }
        $product->status = 'publish'; // 'publish' ou 'draft'
        // $product->featured = true; // Mudar para destacado
        // $product->catalog_visibility = 'hidden'; // Mudar para escondido no catálogo
        $desc = $prod->descricaosite;
        $desc .= "<hr style='border: none; border-top: 1px solid #ccc; height: 0; margin: 15px 0;'><h2>Descrição Interna:</h2> {$product->sku} - {$prod->produto}";

        // Codigos de Barras
        if (!empty($desc)) {
            $desc .= "<hr style='border: none; border-top: 1px solid #ccc; height: 0; margin: 15px 0;'>";
        }
        $desc .= $this->gerarListagemBarrasCompacta();
        $product->description = $desc;

        // $product->short_description = 'Caderno premium com temática Fortnite.';
        // $product->price = $prod->preco;
        $product->regular_price = $this->preco($prod->preco);
        // $product->sale_price = '29.99';
        // $product->date_on_sale_from = '2025-06-01T00:00:00';
        // $product->date_on_sale_from_gmt = '2025-06-01T04:00:00';
        // $product->date_on_sale_to = '2025-06-30T23:59:59';
        // $product->date_on_sale_to_gmt = '2025-07-01T03:59:59';
        // $product->on_sale = true;
        // $product->purchasable = false; // Não comprável
        // $product->total_sales = 150;
        // $product->virtual = false;
        // $product->downloadable = false;
        // $product->downloads = []; // Limpando downloads
        // $product->download_limit = 5;
        // $product->download_expiry = 30;
        // $product->external_url = '';
        // $product->button_text = '';
        // $product->tax_status = 'taxable';
        // $product->tax_class = 'reduced-rate';
        $product->manage_stock = $prod->estoque;
        if ($prod->estoque) {
            if ($variacao) {
                // Fixo para poder aparecer na listagem. se produto pai não tiver estoque o woocommerce não lista
                $product->stock_quantity = 1;
            } else {
                $product->stock_quantity = static::estoque($prod->ProdutoVariacaoS[0]); // Novo estoque
            }
            $product->purchasable = ($product->stock_quantity > 0);
        }
        $product->backorders = 'no'; // Se Permite venda sem estoque
        // $product->low_stock_amount = 10;
        // $product->sold_individually = true;
        $product->weight = "{$prod->peso}"; // Novo peso

        // Dimensões (objeto aninhado)
        $product->dimensions = new stdClass;
        $product->dimensions->length = "{$prod->profundidade}";
        $product->dimensions->width = "{$prod->largura}";
        $product->dimensions->height = "{$prod->altura}";

        // $product->shipping_class = 'classe-premium';
        // $product->shipping_class_id = 123; // Novo ID de classe de envio
        $product->reviews_allowed = true;

        // $product->upsell_ids = [1600, 1601]; // Novos IDs de upsell
        // $product->cross_sell_ids = [1700, 1701]; // Novos IDs de cross-sell
        // $product->parent_id = 0;
        // $product->purchase_note = 'Obrigado por sua compra! Aproveite seu Caderno Fortnite MAX!';

        // Categorias (array de objetos) - Exemplo: mudar para uma nova categoria
        // $product->categories = [
        //     (object)['id' => 94, 'name' => 'Novidades', 'slug' => 'novidades']
        // ];

        // Tags (array) - Adicionar novas tags
        $product->tags = [(object) ['name' => $this->prod->Marca->marca]];
        $strings = preg_replace("/[^A-Za-z0-9 ]/", " ", $this->prod->produto) . ' ' . preg_replace("/[^A-Za-z0-9 ]/", " ", $this->prod->titulosite);
        $strings = trim(preg_replace('/[\s]+/mu', ' ', $strings));
        $strings = explode(' ', $strings);
        foreach ($strings as $str) {
            $product->tags[] = (object) ['name' => $str];
        }
        foreach ($this->prod->ProdutoVariacaoS as $pv) {
            if (empty($pv->variacao)) {
                continue;
            }
            $descr = $pv->variacao;
            if (empty($descr)) {
                $descr = $pv->codprodutovariacao;
            }
            $product->tags[] = (object) ['name' => "{$descr}"];
        }

        // Variacoes como atributos
        if ($variacao) {
            $attribute_id = env('WOO_ATTRIBUTE_ID');
            $product->attributes[0] = (object) [
                'id' => $attribute_id, // ID do atributo global no WooCommerce (se existir)
                'name' => 'Variação',
                'options' => [],
                'variation' => true,
                'visible' => true,
            ];
            foreach ($variacoes as $var) {
                $descr = $var->variacao;
                if (empty($descr)) {
                    $descr = $var->codprodutovariacao;
                }
                $product->attributes[0]->options[] = "{$descr}";
            }
        }

        // Default Attributes (para produtos variáveis)
        // $product->default_attributes = []; // Pode ser preenchido se o produto for variável

        // Variações (array vazio ou preencher para produtos variáveis)
        // $product->variations = [];

        // Grouped Products
        // $product->grouped_products = [];

        $res = DB::select(
            "SELECT ranking FROM MvRankingVendasProduto WHERE codproduto = ?",
            [$prod->codproduto]
        );
        $product->menu_order = 9999999999;
        if ($res != null) {
            $product->menu_order = $res[0]->ranking;
        }

        // TODO: Avaliar se pode colocar um destaque quando tem preco diferenciado por embalagem
        // TODO: Avaliar se pode colocar um destaque quando tem preco à vista
        // $product->price_html = '<span class="new-price">R$ 29,99</span>';

        // Related IDs
        // $product->related_ids = [1800, 1801, 1802]; // Novos IDs relacionados

        // Adicionar um novo meta_data
        // $product->meta_data[] = (object)[
        //     'id' => 99999, // Um ID hipotético, o WooCommerce atribui um real
        //     'key' => '_custom_field_new',
        //     'value' => 'Este é um novo campo customizado.'
        // ];

        // $product->stock_status = 'outofstock'; // Mudar para fora de estoque (apenas para exemplo)
        // $product->has_options = true;
        // $product->post_password = 'senhaforte'; // Definir uma senha
        // $product->global_unique_id = 'uniq-prod-xyz-123';

        // Tiered Pricing
        $product->tiered_pricing_type = 'fixed';
        $product->tiered_pricing_fixed_rules = (object)[];
        $ultimoPreco = $prod->preco;
        foreach ($prod->ProdutoEmbalagemS()->orderBy('quantidade')->get() as $pe) {
            if (empty($pe->preco)) {
                continue;
            }
            $preco = round($pe->preco / $pe->quantidade, 2);
            if ($ultimoPreco <= $preco) {
                continue;
            }
            $product->tiered_pricing_fixed_rules->{$pe->quantidade} = $this->preco($preco);
            $ultimoPreco = $preco;
        }


        // Brands (se houver)
        // Exemplo: Adicionar uma marca
        // $product->brands = [
        //     (object)['id' => 1, 'name' => 'Marca GameZone', 'slug' => 'marca-gamezone']
        // ];
        // TODO: Criar Marcas, nao cria automaticamente
        // $product->brands = [
        //     // (object)['name' => 'Marca GameZone']
        //     (object)['id'=> 61, 'name' => 'teste', 'slug'=> 'apple']
        // ];

        // Links (geralmente não são alterados manualmente, mas para demonstração)
        // $product->_links = new stdClass;
        // $product->_links->self[0] = new stdClass;
        // $product->_links->self[0]->href = 'https://sinopel.mrxempresas.com.br/wp-json/wc/v3/products/9999';
        // $product->_links->collection[0] = new stdClass;
        // $product->_links->collection[0]->href = 'https://sinopel.mrxempresas.com.br/wp-json/wc/v3/products';

        return $product;
    }

    public function gerarListagemBarrasCompacta()
    {
        $sql = "
            SELECT
                pv.codprodutovariacao,
                pv.variacao,
                pb.barras,
                um.unidademedida,
                pe.quantidade 
            FROM
                tblproduto p
            INNER JOIN
                tblprodutobarra pb ON (pb.codproduto = p.codproduto)
            INNER JOIN
                tblprodutovariacao pv ON (pv.codprodutovariacao = pb.codprodutovariacao)
            LEFT JOIN
                tblprodutoembalagem pe ON (pe.codprodutoembalagem = pb.codprodutoembalagem)
            INNER JOIN
                tblunidademedida um ON (um.codunidademedida = coalesce(pe.codunidademedida, p.codunidademedida))
            WHERE
                p.codproduto = :codproduto
            ORDER BY
                pv.variacao NULLS FIRST,
                pe.quantidade NULLS FIRST,
                pb.barras
        ";

        $colecao = collect(DB::select($sql, ['codproduto' => $this->prod->codproduto]));

        $htmlListagem = '<h2>Códigos de Barras:</h2>';

        if ($colecao->isNotEmpty()) {

            // Agrupa por Variação e depois por Unidade/Quantidade
            $dadosAgrupados = $colecao->groupBy('variacao');

            // Usa <ul> para a lista principal de Variações
            $htmlListagem .= '<ul style="list-style: none; ">';

            foreach ($dadosAgrupados as $variacao => $items) {

                // 1. Título do Bloco (Variação)
                $tituloVariacao = empty($variacao) ? $this->prod->produto : htmlspecialchars($variacao);

                // Abre o item da lista principal e usa o título como um forte marcador
                $htmlListagem .= '<li style="margin-top: 10px;">';
                $htmlListagem .= "<strong>{$tituloVariacao}</strong>";

                // 2. Lista de Detalhes (Unidade/Caixa)
                $htmlListagem .= '<ul style="margin-top: 5px; list-style: disc; padding-left: 20px;">';

                // Agrupa os itens da variação por Unidade/Quantidade
                $itemsAgrupados = $items
                    ->map(function ($item) {
                        return [
                            'display_name' => "{$item->unidademedida} (" . ($item->quantidade ? number_format($item->quantidade, 0, ',', '.') : 'Avulso') . ")",
                            'barras' => $item->barras,
                            'quantidade_ordem' => $item->quantidade ?? -1,
                        ];
                    })
                    ->sortBy('quantidade_ordem')
                    ->groupBy('display_name');

                foreach ($itemsAgrupados as $header => $barrasData) {
                    $codigos = $barrasData->pluck('barras')->map('htmlspecialchars')->implode(', ');

                    // Exibe a informação como um item de lista simples
                    $htmlListagem .= "<li>{$header}: {$codigos}</li>";
                }

                $htmlListagem .= '</ul>';
                $htmlListagem .= '</li>';
            }

            $htmlListagem .= '</ul>';
        } else {
            $htmlListagem .= '<p>Nenhum código de barras encontrado.</p>';
        }

        return $htmlListagem;
    }

    public function preco(float $preco, float $quantidade = 1, float $margemadicional = 0)
    {
        $ret = round(($preco * $this->fatorPreco * (1 + ($margemadicional / 100))) / $quantidade, 4);
        return "{$ret}";
    }

    public static function estoque(ProdutoVariacao $pv, float $multiplicador = 1)
    {
        $locais = env('WOO_API_CODESTOQUELOCAL_DISPONIVEL');
        $sql = '
            select sum(es.saldoquantidade) as saldoquantidade
            from tblestoquelocalprodutovariacao elpv
            inner join tblestoquesaldo es on (es.codestoquelocalprodutovariacao = elpv.codestoquelocalprodutovariacao)
            where elpv.codprodutovariacao = :codprodutovariacao
            and elpv.codestoquelocal in (' . $locais . ')
            and es.fiscal = false
        ';
        $data = DB::select($sql, [
            'codprodutovariacao' => $pv->codprodutovariacao,
        ]);
        return floor($data[0]->saldoquantidade * $multiplicador);
    }

    public function exportar()
    {
        if (!$this->wp) {
            return $this->exportarCompleto();
        }
        if ($this->wp->integracao == 'P') {
            Log::debug('Parcial');
            return $this->exportarParcial();
        }
        return $this->exportarCompleto();
    }

    public function exportarParcial()
    {
        // monta objeto do produto
        $product = static::buildParcial($this->prod);

        Log::debug(json_encode($product));

        if ($this->wp->idvariation) {
            Log::debug("Product {$this->wp->id}, Variation {$this->wp->idvariation}");
            $this->api->putProductVariations($this->wp->id, $this->wp->idvariation, $product);
        } else {
            Log::debug('Product');
            $this->api->putProduto($this->wp->id, $product);
        }

        // marca data da exportacao
        $this->wp->update([
            'exportacao' => $this->exportacao
        ]);
    }

    public function exportarCompleto()
    {
        $this->exportarProduto();
        $this->exportarImagens();
        $this->exportarVariacoes();
        return true;
    }

    public function exportarProduto()
    {
        // monta objeto do produto
        $product = static::build($this->prod);

        // decide se altera ou cria
        if ($this->wp) {
            $this->api->putProduto($this->wp->id, $product);
        } else {
            // POST
            $this->api->postProduto($product);
        }
        $ro = $this->api->responseObject;

        // salva na tabela de de/para
        $this->wp = WooProduto::firstOrCreate([
            'codproduto' => $this->prod->codproduto,
            'codprodutovariacao' => null,
            'id' => $ro->id,
        ]);

        // marca data da exportacao
        $this->wp->update([
            'exportacao' => $this->exportacao
        ]);

        // retorna 
        return true;
    }

    public function exportarImagens()
    {
        // limpa da tabela de/para local as imagens que foram excluidas no woo
        $this->api->getProduto($this->wp->id);
        $ids = collect($this->api->responseObject->images)->pluck('id')->toArray();
        WooProdutoImagem::where('codwooproduto', $this->wp->codwooproduto)->whereNotIn('id', $ids)->delete();

        // montar um array com todas as imagens que não foram enviadas ainda
        $images = [];
        $codprodutoimagem = [];
        $pis = $this->wp->Produto->ProdutoImagemS()->orderBy('codprodutoimagem', 'ASC')->get();
        foreach ($pis as $pi) {
            $wpi = WooProdutoImagem::where(['codprodutoimagem' => $pi->codprodutoimagem, 'codwooproduto' => $this->wp->codwooproduto])->first();
            if ($wpi) {
                continue;
            }
            Log::info("Imagem nova para o Woo: {$pi->codprodutoimagem} - {$pi->Imagem->arquivo}");
            $codprodutoimagem[] = $pi->codprodutoimagem;
            $images[] =  (object) [
                'src' => 'https://sistema.mgpapelaria.com.br/MGLara/public/imagens/' . $pi->Imagem->arquivo
            ];
        }

        // se tem imagem nova, faz upload das novas
        if (sizeof($codprodutoimagem) > 0) {

            // quebra o array de imagens pra enviar em blocos (chunks) de 10 em 10 imagens
            $cImages = array_chunk($images, 10);
            $cCodprodutoimagem = array_chunk($codprodutoimagem, 10);

            // percorre os blocos
            foreach ($cImages as $iChunk => $chunk) {

                // envia todas as novas imagens pro Woo
                $product = ['images' => $chunk];
                $this->api->putProduto($this->wp->id, $product);
                $ro = $this->api->responseObject;

                // percorre as imagens criadas salvando na tabela de/para
                for ($i = 0; $i < sizeof($chunk); $i++) {
                    $wpi = WooProdutoImagem::firstOrCreate([
                        'codprodutoimagem' => $cCodprodutoimagem[$iChunk][$i],
                        'codwooproduto' => $this->wp->codwooproduto,
                        'id' => $ro->images[$i]->id,
                    ]);
                }
            }
        }

        // apaga imagens excluidas
        WooProdutoImagem::where('codwooproduto', $this->wp->codwooproduto)->whereNull('codprodutoimagem')->delete();

        // faz um novo put com todos os id de imagens
        $product = new stdClass(['images' => []]);
        $wpis = WooProdutoImagem::where('codwooproduto', $this->wp->codwooproduto)->orderBy('codprodutoimagem')->get();
        $i = 1;
        $codprodutoimagem = null;
        if ($pv = $this->prod->ProdutoVariacaoS[0]) {
            $codprodutoimagem = $pv->codprodutoimagem;
        }
        foreach ($wpis as $wpi) {
            if ($wpi->codprodutoimagem == $codprodutoimagem) {
                $position = 0;
            } else {
                $position = $i;
                $i++;
            }
            $product->images[] =  (object) [
                'id' => $wpi->id,
                'position' => $position
            ];
        }
        $this->api->putProduto($this->wp->id, $product);

        // retorna 
        return true;
    }

    public function exportarVariacoes()
    {

        $pvs = $this->prod->ProdutoVariacaoS()->whereNull('inativo')->orderBy('variacao', 'desc')->get();
        if (sizeof($pvs) == 1) {
            return;
        }

        // codigo do atributo generico
        $attribute_id = env('WOO_ATTRIBUTE_ID');
        $ids = [];

        // percorre todas variacoes
        foreach ($pvs as $pv) {

            // decide a imagem
            $image = null;
            if ($pv->codprodutoimagem) {
                $wpi = WooProdutoImagem::where('codprodutoimagem', $pv->codprodutoimagem)->first();
                if ($wpi) {
                    $image = [
                        'id' => $wpi->id
                    ];
                }
            }

            // se nao tem descricao, coloca o codigo dela como descricao
            $descr = $pv->variacao;
            if (empty($descr)) {
                $descr = $pv->codprodutovariacao;
            }

            $stock_quantity = static::estoque($pv);

            // monta o objeto pro json
            $var = (object) [
                "regular_price" => $this->preco($this->prod->preco),
                "image" => $image,
                "attributes" => [
                    (object) [
                        "id" => $attribute_id,
                        // "name" => "Cor",
                        "option" => "{$descr}"
                    ]
                ],
                "stock_quantity" => $stock_quantity,
                "purchasable" => ($stock_quantity > 0),
                "manage_stock" => true,
                "sku" => '#' . str_pad($pv->codproduto, 6, '0', STR_PAD_LEFT) . '-' . str_pad($pv->codprodutovariacao, 8, '0', STR_PAD_LEFT),
            ];

            // busca o id do woo se já foi exportado
            $wpv = WooProduto::where([
                'codproduto' => $this->prod->codproduto,
                'codprodutovariacao' => $pv->codprodutovariacao,
            ])->first();

            // tenta fazer o put ou post
            if ($wpv) {
                try {
                    $this->api->putProductVariations($this->wp->id, $wpv->id, $var);
                } catch (\Throwable $th) {
                    if ($this->api->responseObject->data->status == 404) {
                        $wpv->delete();
                        $this->api->postProductVariations($this->wp->id, $var);
                    }
                }
            } else {
                $this->api->postProductVariations($this->wp->id, $var);
            }

            // salva na tabela local o id criado
            $id = $this->api->responseObject->id;
            $wpv = WooProduto::firstOrCreate([
                'codproduto' => $this->prod->codproduto,
                'codprodutovariacao' => $pv->codprodutovariacao,
                'id' => $id,
            ]);

            // marca data da exportacao
            $wpv->update([
                'exportacao' => $this->exportacao
            ]);
        }

        // faz um put relacionando as variacoes
        $ids = WooProduto::where('codproduto', $this->prod->codproduto)->whereNotNull('codprodutovariacao')->select('id')->get()->pluck('id')->toArray();
        $this->api->putProduto($this->wp->id, (object) [
            'variations' => $ids
        ]);

        // retorna
        return true;
    }

    public static function descobrirProdutoBarra($product_id, $variation_id)
    {
        if (!empty($variation_id)) {
            $wp = WooProduto::where('id', $product_id)
                ->where('idvariation', $variation_id)
                ->first();
        } else {
            $wp = WooProduto::where('id', $product_id)
                ->whereNull('idvariation')
                ->first();
        }
        if (!$wp) {
            return ProdutoBarra::findOrFail(env('WOO_CODPRODUTOBARRA_NAO_CADASTRADO'));
        }
        if (!empty($wp->codprodutobarraunidade)) {
            return $wp->ProdutoBarraUnidade;
        }
        if (empty($wp->codprodutovariacao)) {
            $pv = $wp->Produto->ProdutoVariacaoS()->first();
        } else {
            $pv = $wp->ProdutoVariacao;
        }
        if (!$pv) {
            return ProdutoBarra::findOrFail(env('WOO_CODPRODUTOBARRA_NAO_CADASTRADO'));
            // throw new Exception("Impossivel localizar variação do produto!", 1);
        }
        $pb = $pv->ProdutoBarraS()->where(   'codprodutoembalagem', null)->first();
        return $pb;
    }
}
