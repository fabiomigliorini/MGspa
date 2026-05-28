<?php

namespace Mg\Pessoa;

use Illuminate\Http\Resources\Json\JsonResource;
use Mg\Pdv\PdvNegocioPrazoService;
use Mg\Usuario\Autorizador;

class PessoaResource extends JsonResource
{
    public function toArray($request): array
    {
        $ret = parent::toArray($request);

        // Saldo em Aberto
        $ret['aberto'] = PdvNegocioPrazoService::emAberto($this->resource);

        // Chave Extrangeira
        $ret['GrupoCliente'] = [
            'codgrupocliente' => $this->GrupoCliente->codgrupocliente ?? null,
            'grupocliente' => $this->GrupoCliente->grupocliente ?? null,
        ];
        $ret['GrupoEconomico'] = [
            'codgrupoeconomico' => $this->GrupoEconomico->codgrupoeconomico ?? null,
            'grupoeconomico' => $this->GrupoEconomico->grupoeconomico ?? null,
            'observacoes' => $this->GrupoEconomico->observacoes ?? null,
        ];
        $ret['FormaPagamento'] = [
            'codformapagamento' => $this->FormaPagamento->codformapagamento ?? null,
            'formapagamento' => $this->FormaPagamento->formapagamento ?? null,
        ];

        $ret['etnia'] = $this->Etnia->etnia ?? null;
        $ret['estadocivil'] = $this->EstadoCivil->estadocivil ?? null;
        $ret['grauinstrucao'] = $this->GrauInstrucao->grauinstrucao ?? null;

        $ret['cidadenascimento'] = $this->CidadeNascimento->cidade ?? null;
        $ret['ufnascimento'] = $this->CidadeNascimento->Estado->sigla ?? null;
        $ret['ufctpsS'] = $this->EstadoCtps->sigla ?? null;

        $ret['usuariocriacao'] = $this->UsuarioCriacao->usuario ?? null;
        $ret['usuarioalteracao'] = $this->UsuarioAlteracao->usuario ?? null;
        $ret['mercosId'] = $this->MercosClienteS()->orderBy('clienteid')->get()->pluck('clienteid');

        // Filhos
        $ret['RegistroSpc'] = RegistroSpcResource::collection(
            $this->RegistroSpcS()->orderBy('criacao', 'desc')->get()
        );
        $ret['PessoaCertidaoS'] = PessoaCertidaoResource::collection(
            $this->PessoaCertidaoS()->orderBy('validade', 'desc')->get()
        );
        $ret['PessoaTelefoneS'] = PessoaTelefoneResource::collection(
            $this->PessoaTelefoneS()->orderBy('ordem')->get()
        );
        $ret['PessoaEmailS'] = PessoaEmailResource::collection(
            $this->PessoaEmailS()->orderBy('ordem')->get()
        );
        $ret['PessoaEnderecoS'] = PessoaEnderecoResource::collection(
            $this->PessoaEnderecoS()->orderBy('ordem')->get()
        );
        $ret['PessoaContaS'] = PessoaContaResource::collection(
            $this->PessoaContaS()->orderBy('alteracao')->get()
        );
        $ret['DependenteS'] = DependenteResource::collection(
            $this->DependenteS()->orderBy('coddependente', 'desc')->get()
        );
        $ret['DependenteResponsavelS'] = DependenteResource::collection(
            $this->DependeteResponsavelS()->orderBy('coddependente', 'desc')->get()
        );
        $ret['UsuarioS'] = $this->UsuarioS()->orderBy('usuario')->get(['codusuario', 'usuario']);

        // Permissões para o frontend
        $ret['permissaoRH'] = Autorizador::pode(['Recursos Humanos']);
        $ret['permissaoFinanceiro'] = Autorizador::pode(['Financeiro', 'Recursos Humanos']);

        if (!$ret['permissaoFinanceiro']) {
            unset(
                $ret['RegistroSpc'],
                $ret['creditobloqueado'],
                $ret['toleranciaatraso'],
                $ret['consumidor'],
                $ret['codgrupocliente'],
                $ret['GrupoCliente'],
                $ret['desconto'],
                $ret['vendedor'],
                $ret['notafiscal'],
                $ret['fornecedor'],
                $ret['cliente'],
                $ret['credito'],
                $ret['codformapagamento'],
                $ret['FormaPagamento'],
            );
        }

        return $ret;
    }
}
