<?php

namespace Mg\Pessoa;

use Illuminate\Http\Resources\Json\JsonResource;
use Mg\Usuario\Autorizador;

/**
 * Versão simplificada do PessoaResource — sem `PdvNegocioPrazoService::emAberto`
 * (Pdv ainda não migrado). O campo `aberto` é retornado como 0 até a
 * migração de Pdv. Os demais sub-recursos (PessoaTelefone, PessoaEmail,
 * PessoaEndereco, PessoaConta, PessoaCertidao, Dependente, RegistroSpc)
 * são carregados normalmente.
 */
class PessoaResource extends JsonResource
{
    public function toArray($request): array
    {
        $ret = parent::toArray($request);

        // Saldo em Aberto — TODO: portar PdvNegocioPrazoService::emAberto
        $ret['aberto'] = 0;

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

        $ret['PessoaCertidaoS'] = PessoaCertidaoResource::collection(
            $this->PessoaCertidaoS()->orderBy('validade', 'desc')->get()
        );
        $ret['RegistroSpc'] = RegistroSpcResource::collection(
            $this->RegistroSpcS()->orderBy('criacao', 'desc')->get()
        );

        // PessoaTelefone/Email/Endereco/Conta — usando shape enxuto direto do model
        // (Resources ainda não migrados nesta sessão — TODO criar quando necessário)
        $ret['PessoaTelefoneS'] = $this->PessoaTelefoneS()->orderBy('ordem')->get();
        $ret['PessoaEmailS'] = $this->PessoaEmailS()->orderBy('ordem')->get();
        $ret['PessoaEnderecoS'] = $this->PessoaEnderecoS()->with('Cidade.Estado')->orderBy('ordem')->get();
        $ret['PessoaContaS'] = PessoaContaResource::collection(
            $this->PessoaContaS()->orderBy('alteracao')->get()
        );

        $ret['DependenteS'] = $this->DependenteS()->orderBy('coddependente', 'desc')->get();
        $ret['DependenteResponsavelS'] = $this->DependeteResponsavelS()->orderBy('coddependente', 'desc')->get();
        $ret['UsuarioS'] = $this->UsuarioS()->orderBy('usuario')->get(['codusuario', 'usuario']);

        $ret['permissaoRH'] = Autorizador::pode(['Recursos Humanos']);
        $ret['permissaoFinanceiro'] = Autorizador::pode(['Financeiro', 'Recursos Humanos']);

        if (!$ret['permissaoFinanceiro']) {
            unset(
                $ret['RegistroSpc'], $ret['creditobloqueado'], $ret['toleranciaatraso'],
                $ret['consumidor'], $ret['codgrupocliente'], $ret['GrupoCliente'],
                $ret['desconto'], $ret['vendedor'], $ret['notafiscal'], $ret['fornecedor'],
                $ret['cliente'], $ret['credito'], $ret['codformapagamento'], $ret['FormaPagamento']
            );
        }

        return $ret;
    }
}
