<?php

namespace Mg\Pessoa;

use Mg\MgService;
use Mg\Cidade\Cidade;

class PessoaService extends MgService
{
    /**
     * Busca Autocomplete Quasar
     */
    public static function autocomplete ($params)
    {
        $nome = $params['pessoa'];

        $qry = Pessoa::query();

        $qry->select('codpessoa', 'pessoa', 'fantasia', 'cnpj', 'inativo', 'fisica');

        $qry->where('pessoa', 'ilike', $nome.'%')->orWhere('fantasia', 'ilike', $nome.'%');

        $ret = $qry->limit(50)->get();
//        $ret = $qry->toSql();

//        foreach (explode(' ', $params['pessoa']) as $palavra) {
//            if (!empty($palavra)) {
//                $qry->whereRaw("(tblpessoa.pessoa ilike '%{$palavra}%' or tblpessoa.fantasia ilike '%{$palavra}%')");
//            }
//        }
//
//        $numero = (int) preg_replace('/[^0-9]/', '', $params['pessoa']);
//		if ($numero > 0) {
//            $qry->orWhere('codpessoa', $numero)->orWhere('cnpj', $numero);
//		}
//
//        $ret = [];
//        foreach ($qry->get() as $item) {
//            $ret[] = [
//                'label' => $item->fantasia,
//                'value' => $item->fantasia,
//                'id' => $item->codpessoa,
//                'sublabel' => $item->pessoa,
//                'stamp' => $item->codpessoa . '</br>' . $item->cnpj
//            ];
//        }

        return $ret;
    }


    public static function pesquisar(array $filter = null, array $sort = null, array $fields = null)
    {
        $qry = Pessoa::query();

        if (!empty($filter['inativo'])) {
            $qry->AtivoInativo($filter['inativo']);
        }

        if (!empty($filter['filial'])) {
            $qry->palavras('filial', $filter['filial']);
        }

        $qry = self::qryOrdem($qry, $sort);
        $qry = self::qryColunas($qry, $fields);
        return $qry;
    }

    public static function novaPessoa(
                $pessoa,
                $fantasia,
                Carbon $inativo,
                bool $cliente,
                bool $fornecedor,
                $fisica,
                $codsexo,
                $cnpj,
                $ie,
                $consumidor,
                $contato,
                $codestadocivil,
                $conjuge,
                $endereco,
                $numero,
                $complemento,
                $codcidade,
                $bairro,
                $cep,
                $enderecocobranca,
                $numerocobranca,
                $complementocobranca,
                $codcidadecobranca,
                $bairrocobranca,
                $cepcobranca,
                $telefone1,
                $telefone2,
                $telefone3,
                $email,
                $emailnfe,
                $emailcobranca,
                $codformapagamento,
                $credito,
                $creditobloqueado,
                $observacoes,
                $mensagemvenda,
                $vendedor,
                $rg,
                $desconto,
                $notafiscal,
                $toleranciaatraso,
                $codgrupocliente

            ) {

        // Cria novo cadastro de pessoa
        $model = new Pessoa();
        $model->$pessoa = $pessoa;
        $model->$fantasia = $fantasia;
        $model->$inativo = $inativo;
        $model->$cliente = $cliente;
        $model->$fornecedor = $fornecedor;
        $model->$fisica = $fisica;
        $model->$codsexo = $codsexo;
        $model->$cnpj = $cnpj;
        $model->$ie = $ie;
        $model->$consumidor = $consumidor;
        $model->$contato = $contato;
        $model->$codestadocivil = $codestadocivil;
        $model->$conjuge = $conjuge;
        $model->$endereco = $endereco;
        $model->$numero = $numero;
        $model->$complemento = $complemento;
        $model->$codcidade = $codcidade;
        $model->$bairro = $bairro;
        $model->$cep = $cep;
        $model->$enderecocobranca = $enderecocobranca;
        $model->$numerocobranca = $numerocobranca;
        $model->$complementocobranca = $complementocobranca;
        $model->$codcidadecobranca = $codcidadecobranca;
        $model->$bairrocobranca = $bairrocobranca;
        $model->$cepcobranca = $cepcobranca;
        $model->$telefone1 = $telefone1;
        $model->$telefone2 = $telefone2;
        $model->$telefone3 = $telefone3;
        $model->$email = $email;
        $model->$emailnfe = $emailnfe;
        $model->$emailcobranca = $emailcobranca;
        $model->$codformapagamento = $codformapagamento;
        $model->$credito = $credito;
        $model->$creditobloqueado = $creditobloqueado;
        $model->$observacoes = $observacoes;
        $model->$mensagemvenda = $mensagemvenda;
        $model->$vendedor = $vendedor;
        $model->$rg = $rg;
        $model->$desconto = $desconto;
        $model->$notafiscal = $notafiscal;
        $model->$toleranciaatraso = $toleranciaatraso;
        $model->$codgrupocliente = $codgrupocliente;

        //$model->save();
        return $model;
    }

    public static function novoFornecedor($emit){

        $codcidade = Cidade::where('codigooficial', $emit->enderEmit->cMun)->first();

        $fornecedor = new Pessoa();
        $fornecedor->pessoa = $emit->xNome;
        $fornecedor->fantasia = $emit->xFant??$emit->xNome;
        $fornecedor->cliente = false;
        $fornecedor->fornecedor = true;
        $fornecedor->fisica = false;
        $fornecedor->cnpj = $emit->CNPJ;
        $fornecedor->ie = $emit->IE;
        $fornecedor->endereco = $emit->enderEmit->xLgr;
        $fornecedor->numero = $emit->enderEmit->nro;
        $fornecedor->complemento = $emit->enderEmit->xCpl??null;
        $fornecedor->codcidade = $codcidade->codcidade;
        $fornecedor->bairro = $emit->enderEmit->xBairro;
        $fornecedor->cep = $emit->enderEmit->CEP;
        $fornecedor->telefone1 = $emit->enderEmit->fone??null;
        $fornecedor->emailnfe = 'nfe@mgpapelaria.com.br';
        $fornecedor->notafiscal = 0;
        // dd($pessoa);
        $fornecedor->save();

        $novoForncedor = Pessoa::orderBy('criacao', 'DESC')->first();
        return $novoForncedor;
    }


}
