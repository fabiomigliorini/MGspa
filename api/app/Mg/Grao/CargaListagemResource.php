<?php

namespace Mg\Grao;

use Illuminate\Http\Resources\Json\JsonResource as Resource;

/**
 * Linha da listagem de romaneios (GET v1/carga/listagem).
 *
 * Separado do CargaResource de proposito: aquele e o CONTRATO DO SYNC OFFLINE
 * (o patio depende de `classificacao` e da arvore inteira dos pontos), e enxuga-lo
 * quebraria o cache do patio em silencio. Aqui vai so o que a lista desenha:
 * cabecalho da carga, pesos, cultura (pra saca) e os pontos com o rotulo pronto.
 *
 * A leitura da classificacao NAO vem — quem quiser ve na ficha (GET carga/{id}).
 */
class CargaListagemResource extends Resource
{
    public function toArray($request)
    {
        $ret = parent::toArray($request);

        unset(
            $ret['safra'],
            $ret['veiculo'],
            $ret['pessoa_motorista'],
            $ret['tabela_classificacao'],
            $ret['carga_ponto_s'],
            $ret['carga_classificacao_s'],
            $ret['movimento_grao_s'],
        );

        // `pesosaca` da cultura converte kg em sacas no front sem outra chamada.
        $ret['Safra'] = $this->whenLoaded('Safra');

        if ($this->relationLoaded('CargaPontoS')) {
            $ret['CargaPontoS'] = CargaPontoResource::collection($this->CargaPontoS);
        }

        return $ret;
    }
}
