<?php

namespace Mg\Fazenda;

use Illuminate\Http\Resources\Json\JsonResource as Resource;
use Mg\Grao\CargaRelatorioService;

class PlantioResource extends Resource
{
    public function toArray($request)
    {
        $ret = parent::toArray($request);

        // remove relações em snake_case que o parent injeta (Laravel serializa
        // relação carregada em snake por padrão) — reexpostas em PascalCase abaixo.
        unset(
            $ret['safra'],
            $ret['fazenda'],
            $ret['talhao'],
            $ret['variedade'],
            $ret['movimento_grao_s'],
        );

        // auditoria (quem criou/alterou)
        $ret['usuariocriacao'] = $this->usuariocriacao;
        $ret['usuarioalteracao'] = $this->usuarioalteracao;

        // `talhao` é COLUNA (nome/numero do talhão nesta safra) E também o nome da
        // relação Talhao() — colidem na mesma chave. O unset acima tira a relação;
        // aqui restauramos a STRING da coluna (o front usa p.talhao como rótulo).
        $ret['talhao'] = $this->resource->talhao;

        // Check "Talhão finalizado?": gravado como hacolhido = área (desmarcado, 0).
        $ret['finalizado'] = PlantioService::finalizado($this->resource);

        // relações em PascalCase (whenLoaded — chaves ausentes somem do JSON)
        $ret['Safra'] = $this->whenLoaded('Safra');
        $ret['Fazenda'] = $this->whenLoaded('Fazenda');
        $ret['Talhao'] = $this->whenLoaded('Talhao');
        $ret['Variedade'] = $this->whenLoaded('Variedade');

        // Colheita do talhão (card "Cargas deste plantio"): cada movimento do
        // extrato, com a PARTE deste talhão — romaneio dividido entre talhões vem
        // rateado (CargaService::gerarMovimento), igual ao KPI Colhido.
        if ($this->relationLoaded('MovimentoGraoS')) {
            $pesosaca = (float) ($this->Safra->Cultura->pesosaca ?? 60) ?: 60;
            $ret['MovimentoGraoS'] = $this->MovimentoGraoS
                ->map(function ($m) use ($pesosaca) {
                    $kg = (float) $m->liquido;
                    $carga = $m->Carga;
                    return [
                        'codmovimentograo' => (int) $m->codmovimentograo,
                        'codcarga' => $m->codcarga !== null ? (int) $m->codcarga : null,
                        'manual' => (bool) $m->manual,
                        // Hora local de parede, como o MgModel::serializeDate — o
                        // Carbon cru sairia em UTC ("…Z") e a madrugada viraria
                        // o dia anterior na tela.
                        'data' => $m->data?->format('Y-m-d H:i:s'),
                        'quantidadekg' => $kg,
                        'quantidadesc' => round($kg / $pesosaca, 2),
                        'observacao' => $m->observacao,
                        'placa' => $carga?->placa,
                        'motorista' => $carga?->motorista,
                        'destino' => $carga ? (CargaRelatorioService::rotulosDoPapel($carga, 'DESTINO') ?: null) : null,
                    ];
                })
                ->values();
        }

        return $ret;
    }
}
