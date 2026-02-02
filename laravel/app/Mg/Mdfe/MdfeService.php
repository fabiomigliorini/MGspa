<?php

namespace Mg\Mdfe;

use Illuminate\Support\Facades\DB;
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
        $mdfe->tipotransportador = $nf->Filial->Pessoa->tipotransportador ?? Mdfe::TIPO_TRANSPORTADOR_TAC;
        $mdfe->modelo = Mdfe::MODELO;
        $mdfe->serie = $nf->Filial->nfeserie;
        $mdfe->modal = Mdfe::MODAL_RODOVIARIO;
        $mdfe->emissao = Carbon::now();
        $mdfe->inicioviagem = $mdfe->emissao;
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
        $mdfeNfe->peso = $nf->pesobruto ?? $nf->pesoliquido ?? $nf->NotaFiscalProdutoBarraS()->sum('quantidade');
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
        if (isset($res[0])) {
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

    public static function atribuirNumero(Mdfe $mdfe)
    {
        // Caso ja tenha numero atribuido, aborta
        if (!empty($mdfe->numero)) {
            return $mdfe;
        }

        // Monta nome da Sequence
        $sequence = "tblmdfe_numero_{$mdfe->codfilial}_{$mdfe->serie}_{$mdfe->modelo}_seq";

        // Cria Sequence se nao existir
        $sql = "CREATE SEQUENCE IF NOT EXISTS {$sequence}";
        DB::statement($sql);

        // Busca proximo numero da sequence
        $sql = 'SELECT NEXTVAL(:sequence) AS numero';
        $res = DB::select($sql, ['sequence' => $sequence]);
        $emissao = Carbon::now();
        $inicioviagem = $mdfe->inicioviagem;
        if (empty($inicioviagem)) {
            $inicioviagem = $emissao;
        } elseif ($inicioviagem->lessThan($emissao)) {
            $inicioviagem = $emissao;
        }
        $mdfe->update([
            'numero' => $res[0]->numero,
            'emissao' => $emissao,
            'inicioviagem' => $inicioviagem,
        ]);
        return $mdfe->fresh();
    }
}
