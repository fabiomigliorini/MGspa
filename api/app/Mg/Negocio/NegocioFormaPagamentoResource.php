<?php

namespace Mg\Negocio;

use Illuminate\Http\Resources\Json\JsonResource as Resource;
use Mg\Pdv\PdvService;
use Mg\Titulo\TituloService;

class NegocioFormaPagamentoResource extends Resource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        $ret = parent::toArray($request);
        $ret['formapagamento'] = $this->FormaPagamento->formapagamento;
        $ret['parceiro'] = $this->Pessoa->fantasia??null;
        // logo do banco na listagem do PDV (public/bancos/{codbanco}.svg)
        $ret['codbanco'] = $this->PixCob->Portador->codbanco ?? null;
        $ret['nomebandeira'] = NegocioFormaPagamentoService::BANDEIRAS[$ret['bandeira']]?? null;
        $ret['nometipo'] = NegocioFormaPagamentoService::TIPOS[$ret['tipo']]?? null;
        // vale compras usado no pagamento: card do Contra Vale (saldo atual do titulo)
        if (!empty($this->codtitulo) && $this->Titulo->codtipotitulo == TituloService::TIPO_VALE) {
            $ret['valenumero'] = $this->Titulo->numero;
            $ret['valefavorecido'] = $this->Titulo->Pessoa->fantasia;
            $ret['valesaldo'] = round(-$this->Titulo->saldo, 2);
        }
        return $ret;
    }
}
