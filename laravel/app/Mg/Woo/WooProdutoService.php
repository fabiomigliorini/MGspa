<?php

namespace Mg\Woo;

use Mg\Produto\Produto;
use Mg\Produto\ProdutoVariacao;
use stdClass;
use Illuminate\Support\Facades\DB;

class WooProdutoService
{

    protected WooApi $api;
    protected Produto $prod;
    // protected WooProduto $wp;
    protected $wp;

    public function __construct(Produto $prod)
    {
        $this->prod = $prod;
        $this->wp = WooProduto::where('codproduto', $prod->codproduto)->whereNull('codprodutovariacao')->first();
        $this->api = new WooApi();
    }

    public function build()
    {
        $prod = $this->prod;
        $variacoes = $prod->ProdutoVariacaoS()->whereNull('inativo')->get();
        $variacao = ($variacoes->count() > 1);

        // Propriedades básicas
        $product = new stdClass;
        // $product->id = 9999; // IDs geralmente não são alterados, mas para demonstração
        $product->name = $prod->produto;
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
        $product->description = $prod->descricaosite;
        // $product->short_description = 'Caderno premium com temática Fortnite.';
        $product->sku = '#' . str_pad($prod->codproduto, 6, '0', STR_PAD_LEFT);
        // $product->price = $prod->preco;
        $product->regular_price = "{$prod->preco}";
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
        if ($prod->estoque && !$variacao) {
            $product->stock_quantity = static::estoque($prod->ProdutoVariacaoS[0]); // Novo estoque
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

        // TODO: Pegar produtos mais vendidos em conjunto pra colocar aqui
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
        $strings = preg_replace("/[^A-Za-z0-9 ]/", " ", $this->prod->produto);
        $strings = trim(preg_replace('/[\s]+/mu', ' ', $strings));
        $strings = explode(' ', $strings);
        foreach ($strings as $str) {
            $product->tags[] = (object) ['name' => $str];
        }
        foreach ($this->prod->ProdutoVariacaoS as $pv) {
            if (empty($pv->variacao)) {
                continue;
            }
            $product->tags[] = (object) ['name' => $pv->variacao];
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
                $product->attributes[0]->options[] = $var->variacao;
            }
        }


        // Default Attributes (para produtos variáveis)
        // $product->default_attributes = []; // Pode ser preenchido se o produto for variável

        // Variações (array vazio ou preencher para produtos variáveis)
        // $product->variations = [];

        // Grouped Products
        // $product->grouped_products = [];

        // $product->menu_order = 1;
        // TODO: Avaliar se pode colocar um destaque quando tem preco diferenciado por embalagem
        // TODO: Avaliar se pode colocar um destaque quando tem preco à vista
        // $product->price_html = '<span class="new-price">R$ 29,99</span>';

        // Related IDs
        // $product->related_ids = [1800, 1801, 1802]; // Novos IDs relacionados

        // Meta Data (array de objetos) - Modificar valores existentes ou adicionar novos
        // TODO: Preco por embalagem
        // Exemplo: Modificando 'fixed_price_rules' e 'teste_opcao'
        // $product->meta_data = new stdClass;
        // foreach ($product->meta_data as $key => $meta_item) {
        //     if ($meta_item->key === '_fixed_price_rules') {
        //         $product->meta_data[$key]->value = (object)['20' => '25', '50' => '20']; // Novas regras de preço
        //     } elseif ($meta_item->key === '_infixs_correios_automatico_ncm') {
        //         $product->meta_data[$key]->value = '1234567890'; // Novo NCM
        //     } elseif ($meta_item->key === 'teste_opcao') {
        //         $product->meta_data[$key]->value = 'capa nova, capa exclusiva'; // Novo valor para teste_opcao
        //     }
        // }

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
        // TODO Preco por embalagem
        // $product->tiered_pricing_type = 'percentage'; // Mudar o tipo
        // $product->tiered_pricing_fixed_rules = (object)['20' => '25', '50' => '20']; // Atualizando regras de preço escalonado
        // $product->tiered_pricing_product_settings = new stdClass;
        // $product->tiered_pricing_product_settings->layout = 'compact'; // Alterando layout
        // $product->tiered_pricing_product_settings->base_unit_name = new stdClass;
        // $product->tiered_pricing_product_settings->base_unit_name->singular = 'unidade';
        // $product->tiered_pricing_product_settings->base_unit_name->plural = 'unidades';
        // $product->tiered_pricing_minimum_quantity = 5;
        // $product->tiered_pricing_roles_data = []; // Pode ser preenchido
        // $product->tiered_pricing_percentage_rules = (object)['10' => '5', '20' => '10']; // Exemplo de regras percentuais

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

    public static function estoque(ProdutoVariacao $pv)
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
        return (float) $data[0]->saldoquantidade;
    }

    public function exportar()
    {

        $this->exportarProduto();
        $this->exportarImagens();
        $this->exportarVariacoes();
    }

    public function exportarProduto()
    {
        $product = static::build($this->prod);

        if ($this->wp) {
            // PUT
            $this->api->putProduto($this->wp->id, $product);
        } else {
            // POST
            $this->api->postProduto($product);
        }
        $ro = $this->api->responseObject;
        dd($ro);
        $this->wp = WooProduto::firstOrCreate([
            'codproduto' => $this->prod->codproduto,
            'codprodutovariacao' => null,
            'id' => $ro->id,
        ]);
    }

    public function exportarImagens()
    {
        // limpa da tabela de/para local as imagens que foram excluidas no woo
        $this->api->getProduto($this->wp->id);
        $ids = collect($this->api->responseObject->images)->pluck('id')->toArray();
        WooProdutoImagem::where('codwooproduto', $this->wp->codwooproduto)->whereNotIn('id', $ids)->delete();

        // montar um array com todas as imagens que não foram enviadas ainda
        $product = new stdClass(['images' => []]);
        $codprodutoimagem = [];
        foreach ($this->wp->Produto->ProdutoImagemS()->orderBy('codprodutoimagem', 'ASC')->get() as $pi) {
            $wpi = WooProdutoImagem::where(['codprodutoimagem' => $pi->codprodutoimagem])->first();
            if ($wpi) {
                continue;
            }
            $codprodutoimagem[] = $pi->codprodutoimagem;
            $product->images[] =  (object) [
                'src' => 'https://sistema.mgpapelaria.com.br/MGLara/public/imagens/' . $pi->Imagem->arquivo
            ];
        }

        // se nao tem nenhuma imagem nova, cai fora
        if (sizeof($codprodutoimagem) == 0) {
            return;
        }

        // envia todas as novas imagens pro Woo
        $this->api->putProduto($this->wp->id, $product);
        $ro = $this->api->responseObject;

        // percorre as imagens criadas salvando na tabela de/para
        for ($i = 0; $i < sizeof($codprodutoimagem); $i++) {
            $wpi = WooProdutoImagem::firstOrCreate([
                'codprodutoimagem' => $codprodutoimagem[$i],
                'codwooproduto' => $this->wp->codwooproduto,
                'id' => $ro->images[$i]->id,
            ]);
        }

        // faz um novo put com todos os id de imagens
        $product = new stdClass(['images' => []]);
        $wpis = WooProdutoImagem::where('codwooproduto', $this->wp->codwooproduto)->orderBy('codprodutoimagem')->get();
        foreach ($wpis as $wpi) {
            $product->images[] =  (object) [
                'id' => $wpi->id
            ];
        }
        $this->api->putProduto($this->wp->id, $product);
    }

    public function exportarVariacoes()
    {
        // $this->api->getProduto($this->wp->id);
        // $this->api->getProduto(1669);
        // $this->api->getProduto(1646);
        // $this->api->getProduto(1683);
        // dd($this->api->responseObject);

        $pvs = $this->prod->ProdutoVariacaoS()->whereNull('inativo')->orderBy('variacao', 'desc')->get();
        if (sizeof($pvs) == 1) {
            return;
        }

        // codigo do atributo generico
        $attribute_id = env('WOO_ATTRIBUTE_ID');
        $ids = [];

        foreach ($pvs as $pv) {
            $image = null;
            if ($pv->codprodutoimagem) {
                $wpi = WooProdutoImagem::where('codprodutoimagem', $pv->codprodutoimagem)->first();
                if ($wpi) {
                    $image = [
                        'id' => $wpi->id
                    ];
                }
            }
            $var = (object) [
                "regular_price" => "55.00",
                "image" => $image,
                "attributes" => [
                    (object) [
                        "id" => $attribute_id,
                        // "name" => "Cor",
                        "option" => $pv->variacao
                    ]
                ],
                "stock_quantity" => static::estoque($pv),
                "manage_stock" => true,
                "sku" => '#' . str_pad($pv->codprodutovariacao, 8, '0', STR_PAD_LEFT),
            ];

            $wpv = WooProduto::where([
                'codproduto' => $this->prod->codproduto,
                'codprodutovariacao' => $pv->codprodutovariacao,
            ])->first();
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
            $id = $this->api->responseObject->id;
            $wpv = WooProduto::firstOrCreate([
                'codproduto' => $this->prod->codproduto,
                'codprodutovariacao' => $pv->codprodutovariacao,
                'id' => $id,
            ]);
        }

        $ids = WooProduto::where('codproduto', $this->prod->codproduto)->whereNotNull('codprodutovariacao')->select('id')->get()->pluck('id')->toArray();

        $this->api->putProduto($this->wp->id, (object) [
            'variations' => $ids
        ]);

        dd($this->api->responseObject);
    }

    public function criarTermo($attribute_id, $name)
    {
        $term = (object) [
            'name' => $name
        ];
        $this->api->postAttributeTerms($attribute_id, $term);
        return $this->api->responseObject;
    }
}
