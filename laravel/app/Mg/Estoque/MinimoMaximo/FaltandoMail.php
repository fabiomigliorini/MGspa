<?php

namespace Mg\Estoque\MinimoMaximo;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;

use DB;

use Mg\Marca\Marca;

class FaltandoMail extends Mailable
{
    use Queueable, SerializesModels;

    protected $marca;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct(Marca $marca = null)
    {
        $this->marca = $marca;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {


      // $marca = Marca::findOrFail(10000064);

        // $produtos = ComprasService::buscarProdutos($marca);
        $produtos = ComprasService::buscarProdutos();
        $marcas = collect();
        foreach ($produtos as $key => $prod) {
            if (empty($prod->comprar)) {
                continue;
            }
            if (!isset($marcas[$prod->codmarca])) {
                $marcas[$prod->codmarca] = (object) [
            'codmarca' => $prod->codmarca,
            'marca' => $prod->marca,
            'total' => 0,
            'comprar' => 0,
            'critico' => 0,
            'abaixominimo' => 0,
            'produtos' => collect()
          ];
            }
            $marcas[$prod->codmarca]->total += ($prod->comprar * $prod->custoultimacompra);
            $marcas[$prod->codmarca]->comprar++;
            if ((($prod->estoque + $prod->chegando) > $prod->estoqueminimo) && ($prod->estoqueminimo > 0)) {
                continue;
            }
            $marcas[$prod->codmarca]->abaixominimo++;
            if ($prod->critico) {
                $marcas[$prod->codmarca]->critico++;
            }
            $marcas[$prod->codmarca]->produtos[] = $prod;
        }

        $marcas = $marcas->sortByDesc('total');
        // dd($marcas);
        //$marcas = $produtos->groupBy('marca');
        // dd($marcas);

        return $this
        ->subject("Produtos Faltando")
        ->view('faltando-mail.faltando')
        ->with(['marcas' => $marcas]);

        /*
          $pathNFeAutorizada = NFePHPPathService::pathNFeAutorizada($this->marca);
          if (!file_exists($pathNFeAutorizada)) {
              throw new \Exception("Arquivo XML não localizado ($pathNFeAutorizada)!");
          }

          $pathDanfe = NFePHPPathService::pathDanfe($this->marca);
          if (!file_exists($pathDanfe)) {
              NFePHPService::danfe($this->marca);
              if (!file_exists($pathDanfe)) {
                  throw new \Exception("Erro ao gerar arquivo PDF ($pathDanfe)!");
              }
          }

            */
    }
}
