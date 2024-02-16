<?php

namespace Mg\Pessoa;

use Illuminate\Http\Resources\Json\JsonResource;
use Mg\Pdv\PdvNegocioPrazoService;

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

        // Saldo em Aberto
        $ret['aberto'] = PdvNegocioPrazoService::emAberto($this->resource);

        // Chave Extrangeira
        $ret['GrupoCliente'] = [
            'codgrupocliente' => @$this->GrupoCliente->codgrupocliente,
            'grupocliente' => @$this->GrupoCliente->grupocliente,
        ];
        $ret['GrupoEconomico'] = [
            'codgrupoeconomico' => @$this->GrupoEconomico->codgrupoeconomico,
            'grupoeconomico' => @$this->GrupoEconomico->grupoeconomico,
            'observacoes' => @$this->GrupoEconomico->observacoes,
        ];        
        $ret['FormaPagamento'] = [
            'codformapagamento' => @$this->FormaPagamento->codformapagamento,
            'formapagamento' => @$this->FormaPagamento->formapagamento,
        ];

        $ret['cidadenascimento'] = @$this->CidadeNascimento->cidade;
        $ret['ufnascimento'] = @$this->CidadeNascimento->Estado->sigla;
        $ret['ufctpsS'] = @$this->EstadoCtps->sigla;

        $ret['usuariocriacao'] = @$this->UsuarioCriacao->usuario;
        $ret['usuarioalteracao'] = @$this->UsuarioAlteracao->usuario;
        $ret['mercosId'] = $this->MercosClienteS()->orderBy('clienteid')->get()->pluck('clienteid');
        // Filhos
        $ret['PessoaCertidaoS'] = [];
        // foreach ($this->PessoaCertidaoS()->where('validade', '>=', Carbon::now()) as $pc)
       
        // dd($this->RegistroSpcS()->orderBy('criacao', 'desc')->get());

        $ret['RegistroSpc'] = RegistroSpcResource::collection($this->RegistroSpcS()->orderBy('criacao', 'desc')->get());

        $ret['PessoaCertidaoS'] = PessoaCertidaoResource::collection($this->PessoaCertidaoS()->orderBy('validade', 'desc')->get());
        
        $ret['PessoaTelefoneS'] = PessoaTelefoneResource::collection($this->PessoaTelefoneS()->orderBy('ordem')->get());

        $ret['PessoaEmailS'] = PessoaEmailResource::collection($this->PessoaEmailS()->orderBy('ordem')->get());
    
        $ret['PessoaEnderecoS'] = PessoaEnderecoResource::collection($this->PessoaEnderecoS()->orderBy('ordem')->get());
        
        $ret['PessoaContaS'] = PessoaContaResource::collection($this->PessoaContaS()->orderBy('alteracao')->get());
       
        return $ret;
    }
}
