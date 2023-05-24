<?php

namespace Mg\Pessoa;

use Illuminate\Http\Resources\Json\JsonResource;
use Carbon\Carbon;

class PessoaResource extends JsonResource
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

        // Chave Extrangeira
        $ret['GrupoCliente'] = [
            'codgrupocliente' => @$this->GrupoCliente->codgrupocliente,
            'grupocliente' => @$this->GrupoCliente->grupocliente,
        ];
        $ret['GrupoEconomico'] = [
            'codgrupoeconomico' => @$this->GrupoEconomico->codgrupoeconomico,
            'grupoeconomico' => @$this->GrupoEconomico->grupoeconomico,
        ];

        // Filhos
        $ret['PessoaCertidaoS'] = [];
        // foreach ($this->PessoaCertidaoS()->where('validade', '>=', Carbon::now()) as $pc)
        foreach ($this->PessoaCertidaoS()->orderBy('validade', 'desc')->get() as $pc) {
            $ret['PessoaCertidaoS'][] = $pc->toArray();
        }

        $ret['PessoaTelefoneS'] = [];
        foreach ($this->PessoaTelefoneS()->orderBy('ordem')->get() as $pt) {
            $ret['PessoaTelefoneS'][] = $pt->toArray();
        }

        $ret['PessoaEmailS'] = [];
        foreach ($this->PessoaEmailS()->orderBy('ordem')->get() as $pe) {
            $ret['PessoaEmailS'][] = $pe->toArray();
        }

        $ret['PessoaEnderecoS'] = [];
        foreach ($this->PessoaEnderecoS()->orderBy('ordem')->get() as $pen) {
            $ret['PessoaEnderecoS'][] = $pen->toArray();
        }

        $ret['PessoaContaS'] = [];
        foreach ($this->PessoaContaS()->orderBy('alteracao')->get() as $pc) {
            $ret['PessoaContaS'][] = $pc->toArray();
        }

        return $ret;
    }
}
