<?php

namespace Mg\Mdfe;

use DB;
use Carbon\Carbon;

use Mg\NotaFiscal\NotaFiscal;
use Mg\Veiculo\Veiculo;
use Mg\Veiculo\VeiculoConjunto;

class MdfeService
{
    public static function criarDaNotaFiscal(NotaFiscal $nf)
    {
        // Cria MDFe
        $mdfe = new Mdfe();
        $mdfe->codmdfestatus = MdfeStatus::EM_DIGITACAO;
        $mdfe->codfilial = $nf->codfilial;
        $mdfe->tipoemitente = Mdfe::TIPO_EMITENTE_CARGA_PROPRIA;
        $mdfe->tipotransportador = $nf->Filial->Pessoa->tipotransportador;
        $mdfe->modelo = Mdfe::MODELO;
        $mdfe->serie = $nf->Filial->nfeserie;
        $mdfe->modal = Mdfe::MODAL_RODOVIARIO;
        $mdfe->emissao = Carbon::now();
        $mdfe->tipoemissao = Mdfe::TIPO_EMISSAO_NORMAL;
        $mdfe->codcidadecarregamento = $nf->Filial->Pessoa->codcidade;
        $mdfe->codestadofim = $nf->Pessoa->Cidade->codestado;
        $mdfe->save();

        // Relaciona MDFe com Nota Fiscal
        $mdfeNfe = new MdfeNfe();
        $mdfeNfe->codmdfe = $mdfe->codmdfe;
        $mdfeNfe->codcidadedescarga = $nf->Pessoa->codcidade;
        $mdfeNfe->nfechave = $nf->nfechave;
        $mdfeNfe->codnotafiscal = $nf->codnotafiscal;
        $mdfeNfe->valor = $nf->valortotal;
        $mdfeNfe->peso = $nf->pesobruto??$nf->pesoliquido??$nf->NotaFiscalProdutoBarraS()->sum('quantidade');
        $mdfeNfe->save();

        // Busca Conjunto de Veiculo com a Placa da Nota Fiscal
        $sql = '
            select vc.codveiculoconjunto
            from tblveiculo v
            inner join tblveiculoconjuntoveiculo vcv on (vcv.codveiculo = v.codveiculo)
            inner join tblveiculoconjunto vc on (vc.codveiculoconjunto = vcv.codveiculoconjunto)
            where v.placa = :placa
            and vc.inativo is null
            order by vc.criacao
            limit 1
        ';
        $res = DB::select($sql, ['placa' => $nf->placa]);

        // Se achou algum conjunto, adiciona todos os veiculos do conjunto no mdfe
        if ($res[0]) {
            $conjunto = VeiculoConjunto::findOrFail($res[0]->codveiculoconjunto);
            foreach ($conjunto->VeiculoConjuntoVeiculoS as $vcv) {
                $mdfeVeiculo = new MdfeVeiculo();
                $mdfeVeiculo->codmdfe = $mdfe->codmdfe;
                $mdfeVeiculo->codveiculo = $vcv->codveiculo;
                $mdfeVeiculo->save();
            }
        }
        
        return $mdfe;
    }

}
