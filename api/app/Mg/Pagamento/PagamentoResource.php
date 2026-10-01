<?php

namespace Mg\Pagamento;

use Illuminate\Http\Resources\Json\JsonResource;

class PagamentoResource extends JsonResource
{
    public function toArray($request)
    {
        $ret = parent::toArray($request);
        $ret['meiodescricao'] = PagamentoService::MEIOS[$this->meio] ?? null;
        $ret['estadodescricao'] = PagamentoService::ESTADOS[$this->estado] ?? null;
        $ret['nomebandeira'] = PagamentoService::BANDEIRAS[$this->bandeira] ?? null;
        $ret['integrado'] = $this->ehIntegrado();
        $ret['saida'] = $this->ehSaida();
        $ret['parceiro'] = $this->Pessoa->fantasia ?? null;
        $ret['maquineta'] = $this->Maquineta->apelido ?? null;
        $ret['portadororigem'] = $this->PortadorOrigem->portador ?? null;
        $ret['portadordestino'] = $this->PortadorDestino->portador ?? null;
        return $ret;
    }
}
