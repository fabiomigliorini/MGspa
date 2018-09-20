<?php

namespace Mg\NotaFiscalTerceiro;
use Mg\MgRepository;

use Mg\Filial\Filial;
use Mg\Pessoa\Pessoa;
use Mg\Pessoa\PessoaRepository;
use Mg\NotaFiscal\NotaFiscal;
use Mg\Estoque\EstoqueLocal;

use Carbon\Carbon;
use DB;

class NotaFiscalTerceiroRepositoryCarregaXml extends MgRepository
{

    public static function armazenaDadosNFe (Filial $filial, $res){

        // BUSCA NA BASE DE DADOS A PESSOA
        $codpessoa = Pessoa::where(
            [['ie', $res->NFe->infNFe->emit->IE],
            ['cnpj', $res->NFe->infNFe->emit->CNPJ]])
            ->first();

        if($codpessoa == null){
            $codpessoa = PessoaRepository::novoFornecedor($res->NFe->infNFe->emit);
        }

        // BUSCA NA BASE DE DADOS O coddistribuicaodfe DA DFE CONSULTADA
        $coddistribuicaodfe = NotaFiscalTerceiroDistribuicaoDfe::where(
            [['nfechave', $res->protNFe->infProt->chNFe],['schema', 'like', 'procNFe' . '%']])
            ->orWhere([['nfechave', $res->protNFe->infProt->chNFe],['schema', 'like', 'resNFe' . '%']])
            ->first();

        // BUSCA NA BASE DE DADOS O codestoquelocal
        $codestoquelocal = EstoqueLocal::where('codfilial', $filial->codfilial)->first();

        DB::beginTransaction();
        // echo "<script>console.log( 'Debug Objects:" . $res->protNFe->infProt->chNFe . "' );</script>";

        // // INSERE NA tblnotafiscal
        // $NF = NotaFiscal::firstOrNew([
        //     'nfechave' => $res->protNFe->infProt->chNFe,
        //     'numero' => $res->NFe->infNFe->ide->nNF
        // ]);
        // $NF->codnaturezaoperacao = 1; // rever este campo
        // $NF->emitida = false; // rever este campo
        // $NF->nfechave = $res->protNFe->infProt->chNFe;
        // $NF->nfeimpressa = 0; //$res->NFe->infNFe->ide->tpImp; rever este campo
        // $NF->serie = $res->NFe->infNFe->ide->serie;
        // $NF->numero = $res->NFe->infNFe->ide->nNF;
        // $NF->emissao = Carbon::parse($res->NFe->infNFe->ide->dhEmi);
        // $NF->saida = Carbon::now(); // rever este campo
        // $NF->codfilial = $filial->codfilial;
        // $NF->codpessoa = $codpessoa->codpessoa;
        //
        // // houve uma nota em que as observaçoes, o comprimento da string é maior que 1500, na tabaela o limite
        // // é 1500 esta validacao resolve este problema, mas rever este trecho
        // //35180659400853000759550010000028241524108507
        // $NFeObservacoes = null;
        // if(isset($res->NFe->infNFe->infAdic->infCpl) && strlen($res->NFe->infNFe->infAdic->infCpl) > 1500){
        //     $NFeObservacoes = substr($res->NFe->infNFe->infAdic->infCpl,0,1500);
        // }else{
        //     if(isset($res->NFe->infNFe->infAdic->infCpl)){
        //         $NFeObservacoes = $res->NFe->infNFe->infAdic->infCpl;
        //     }
        // }
        // $NF->observacoes = $NFeObservacoes;
        // $NF->volumes = $res->NFe->infNFe->transp->vol->qVol??0;
        //
        // // houve uma nota em que o codoperacao é 0'saida', mas atualmente na base é 1'entrada'  ou 2'saida'
        // //rever este trecho
        // // 51180605372531000129550010010254021111025063
        // $NFeCodOperacao = $res->NFe->infNFe->ide->tpNF;
        // if($NFeCodOperacao == 0){
        //     $NFeCodOperacao = 2;
        // }
        // $NF->codoperacao = $NFeCodOperacao;
        // $NF->nfereciboenvio = Carbon::parse($res->protNFe->infProt->dhRecbto);
        // $NF->nfedataenvio = Carbon::parse($res->NFe->infNFe->ide->dhEmi);
        // $NF->nfeautorizacao = $res->protNFe->infProt->xMotivo;
        // $NF->nfedataautorizacao = Carbon::parse($res->protNFe->infProt->dhRecbto);
        // $NF->valorfrete = $res->NFe->infNFe->total->ICMSTot->vFrete;
        // $NF->valorseguro = $res->NFe->infNFe->total->ICMSTot->vSeg;
        // $NF->valordesconto = $res->NFe->infNFe->total->ICMSTot->vDesc;
        // $NF->valoroutras = $res->NFe->infNFe->total->ICMSTot->vOutro;
        // $NF->nfecancelamento = null;
        // $NF->nfedatacancelamento = null;
        // $NF->nfeinutilizacao = null;
        // $NF->nfedatainutilizacao = null;
        // $NF->justificativa = $res->NFe->infNFe->ide->xJust??null;
        // $NF->modelo = $res->NFe->infNFe->ide->mod;
        // $NF->valorprodutos = $res->NFe->infNFe->total->ICMSTot->vProd;
        // $NF->valortotal = $res->NFe->infNFe->total->ICMSTot->vNF;
        // $NF->icmsbase = $res->NFe->infNFe->total->ICMSTot->vBC;
        // $NF->icmsvalor = $res->NFe->infNFe->total->ICMSTot->vICMS;
        // $NF->icmsstbase = $res->NFe->infNFe->total->ICMSTot->vBCST;
        // $NF->icmsstvalor = $res->NFe->infNFe->total->ICMSTot->vST;
        // $NF->ipibase = $res->NFe->infNFe->total->ICMSTot->vBC;
        // $NF->ipivalor = $res->NFe->infNFe->total->ICMSTot->vIPI;
        // $NF->frete = $res->NFe->infNFe->transp->modFrete;
        // $NF->tpemis = $res->NFe->infNFe->ide->tpEmis;
        // $NF->codestoquelocal = $codestoquelocal->codestoquelocal;
        // // dd($NF);
        // $NF->save();// FIM DO CRIA NOTAFISCAL
        //
        // // BUSCA NA tblnotafiscal O codnotafiscal
        // $codnotafiscal = NotaFiscal::where(
        //     [['nfechave', $res->protNFe->infProt->chNFe],
        //     ['numero', $res->NFe->infNFe->ide->nNF]])
        //     ->value('codnotafiscal')
        //     ->first();


        // SALVA NA tblnotafiscalterceiro OS DADOS DA NOTA
        $NFeTerceiro = NotaFiscalTerceiro::firstOrNew([
        'nfechave' => $res->protNFe->infProt->chNFe,
        'numero' => $res->NFe->infNFe->ide->nNF
        ]);
        $NFeTerceiro->coddistribuicaodfe = $coddistribuicaodfe->coddistribuicaodfe;
        $NFeTerceiro->codnotafiscal = $codnotafiscal->codnotafiscal??null;
        $NFeTerceiro->codnegocio = null; // rever este campo, como gerar um negocio??
        $NFeTerceiro->codfilial = $filial->codfilial;
        $NFeTerceiro->codoperacao = $res->NFe->infNFe->ide->tpNF;
        $NFeTerceiro->codnaturezaoperacao = null;
        $NFeTerceiro->codpessoa = $codpessoa->codpessoa??null;
        $NFeTerceiro->natop = $res->NFe->infNFe->ide->natOp??null;
        $NFeTerceiro->emitente = $res->NFe->infNFe->emit->xNome;
        $NFeTerceiro->cnpj = $res->NFe->infNFe->emit->CNPJ;
        $NFeTerceiro->ie = $res->NFe->infNFe->emit->IE;
        $NFeTerceiro->emissao = Carbon::parse($res->NFe->infNFe->ide->dhEmi);
        $NFeTerceiro->ignorada = false; // rever este campo
        $NFeTerceiro->indsituacao = $res->cSitNFe??$res->protNFe->infProt->cStat??3;
        $NFeTerceiro->justificativa = $res->protNFe->infProt->xMotivo;
        $NFeTerceiro->indmanifestacao = null;
        $NFeTerceiro->nfechave = $res->protNFe->infProt->chNFe;
        $NFeTerceiro->modelo = $res->NFe->infNFe->ide->mod;
        $NFeTerceiro->serie = $res->NFe->infNFe->ide->serie;
        $NFeTerceiro->numero = $res->NFe->infNFe->ide->nNF;
        $NFeTerceiro->entrada = null;
        $NFeTerceiro->valortotal = $res->NFe->infNFe->total->ICMSTot->vNF;
        $NFeTerceiro->icmsbase = $res->NFe->infNFe->total->ICMSTot->vBC;
        $NFeTerceiro->icmsvalor = $res->NFe->infNFe->total->ICMSTot->vICMS;
        $NFeTerceiro->icmsstbase = $res->NFe->infNFe->total->ICMSTot->vBCST;
        $NFeTerceiro->icmsstvalor = $res->NFe->infNFe->total->ICMSTot->vST;
        $NFeTerceiro->ipivalor = $res->NFe->infNFe->total->ICMSTot->vIPI;
        $NFeTerceiro->valorprodutos = $res->NFe->infNFe->total->ICMSTot->vProd;
        $NFeTerceiro->valorfrete = $res->NFe->infNFe->total->ICMSTot->vFrete;
        $NFeTerceiro->valorseguro = $res->NFe->infNFe->total->ICMSTot->vSeg;
        $NFeTerceiro->valordesconto = $res->NFe->infNFe->total->ICMSTot->vDesc;
        $NFeTerceiro->valoroutras = $res->NFe->infNFe->total->ICMSTot->vOutro;
        $NFeTerceiro->download = true;
        // dd($NFeTerceiro);
        $NFeTerceiro->save(); // FIM DO CRIA NOTA FISCAL TERCEIRO

        // BUSCA NA tblnotafiscalterceiro o codnotafiscalterceiro
        $codnotafiscalterceiro = NotaFiscalTerceiro::where(
            'coddistribuicaodfe', $coddistribuicaodfe->coddistribuicaodfe)
            ->first();

        //SALVA NA TABELA GRUPO
        $grupo = NotaFiscalTerceiroGrupo::firstOrNew([
        'codnotafiscalterceiro' => $codnotafiscalterceiro->codnotafiscalterceiro
        ]);
        $grupo->codnotafiscalterceiro = $codnotafiscalterceiro->codnotafiscalterceiro;
        $grupo->save();

        // BUSCA NA tblnotafiscalterceirogrupo o codnotafiscalterceirogrupo
        $codGrupo = NotaFiscalTerceiroGrupo::where(
            'codnotafiscalterceiro', $codnotafiscalterceiro->codnotafiscalterceiro)
            ->first();

        // ARMAZENA OS DADOS DOS ITENS DA NOTA
        // $loop = 0;
        foreach ($res->NFe->infNFe->det as $key => $item) {
          // $loop++;
          // echo "<script>console.log( 'Debug Objects:".$loop." " . $res->protNFe->infProt->chNFe . "' );</script>";
          // echo "<script>console.log( 'Debug Objects:".$loop." " . $res->NFe->infNFe->det[1] . "' );</script>";

          $NFeItem = NotaFiscalTerceiroItem::firstOrNew([
          'referencia' => $item->prod->cProd??$res->NFe->infNFe->det->prod->cProd,
          'codnotafiscalterceirogrupo' => $codGrupo->codnotafiscalterceirogrupo
          ]);
          $NFeItem->codnotafiscalterceirogrupo = $codGrupo->codnotafiscalterceirogrupo;
          $NFeItem->numero = $item->attributes->nItem??$res->NFe->infNFe->det->attributes->nItem;
          $NFeItem->referencia = $item->prod->cProd??$res->NFe->infNFe->det->prod->cProd;
          $NFeItem->produto = $item->prod->xProd??$res->NFe->infNFe->det->prod->xProd;
          $NFeItem->ncm = $item->prod->NCM??$res->NFe->infNFe->det->prod->NCM;
          $NFeItem->cfop = $item->prod->CFOP??$res->NFe->infNFe->det->prod->CFOP;

          // VERIFICA SE EXISTE UM CODIGO DE BARRAS
          $barras = null;
          if (isset($item->prod->cEAN) && is_string($item->prod->cEAN)){
              $barras = $item->prod->cEAN;
          }else{
              if(isset($res->NFe->infNFe->det->prod->cEAN) && is_string($res->NFe->infNFe->det->prod->cEAN)){
                  $barras = $res->NFe->infNFe->det->prod->cEAN;
              }
          }
          $NFeItem->barras = $barras;

          // VERIFICA SE EXISTE UM CODIGO DE BARRAS TRIBUTAVEL
          $barrasTrib = null;
          if (isset($item->prod->cEANTrib) && is_string($item->prod->cEANTrib)){
              $barrasTrib = $item->prod->cEANTrib;
          }else{
              if(isset($res->NFe->infNFe->det->prod->cEANTrib) && is_string($res->NFe->infNFe->det->prod->cEANTrib)){
                  $barrasTrib = $res->NFe->infNFe->det->prod->cEANTrib;
              }
          }
          $NFeItem->barrastributavel =  $barrasTrib;

          $NFeItem->quantidadetributavel = $item->prod->qTrib??$res->NFe->infNFe->det->prod->qTrib??null;
          $NFeItem->valorunitariotributavel = $item->prod->vUnTrib??$res->NFe->infNFe->det->prod->vUnTrib;
          $NFeItem->unidademedida = $item->prod->uCom??$res->NFe->infNFe->det->prod->uCom;
          $NFeItem->quantidade = $item->prod->qCom??$res->NFe->infNFe->det->prod->qCom;
          $NFeItem->valorunitario = $item->prod->vUnCom??$res->NFe->infNFe->det->prod->vUnCom;
          $NFeItem->valorproduto = $item->prod->vProd??$res->NFe->infNFe->det->prod->vProd;
          $NFeItem->valorfrete = $res->NFe->infNFe->total->ICMSTot->vFrete;
          $NFeItem->valorseguro = $res->NFe->infNFe->total->ICMSTot->vSeg;
          $NFeItem->valordesconto = $res->NFe->infNFe->total->ICMSTot->vDesc;
          $NFeItem->valoroutras = $res->NFe->infNFe->total->ICMSTot->vOutro;
          $NFeItem->valortotal = $res->NFe->infNFe->total->ICMSTot->vNF;
          $NFeItem->compoetotal = $item->prod->indTot??$res->NFe->infNFe->det->prod->indTot;
          $NFeItem->csosn = $item->imposto->ICMS->ICMSSN102->CSOSN??$res->NFe->infNFe->det->imposto->ICMS->ICMSSN102->CSOSN??null; // rever este campo

          // VALIDA QUAL O TIPO DE ICMS QUE ESTA NA NOTA ICMS00, ICMS10, ICMSSN102
          $icmsOrigem = null;
          if(isset($item->imposto->ICMS->ICMS00->orig)){
              $icmsOrigem = $item->imposto->ICMS->ICMS00->orig;
          }else{
              if(isset($res->NFe->infNFe->det->ICMS->ICMS00->orig)){
                  $icmsOrigem = $res->NFe->infNFe->det->imposto->ICMS->ICMS00->orig;
              }
          }

          if(isset($item->imposto->ICMS->ICMSSN102->orig)){
              $icmsOrigem = $item->imposto->ICMS->ICMSSN102->orig;
          }else{
              if(isset($res->NFe->infNFe->det->imposto->ICMS->ICMSSN102->orig)){
                  $icmsOrigem = $res->NFe->infNFe->det->imposto->ICMS->ICMSSN102->orig;
              }
          }

          if(isset($item->imposto->ICMS->ICMS10->orig)){
              $icmsOrigem = $item->imposto->ICMS->ICMS10->orig;
          }else{
              if(isset($res->NFe->infNFe->det->ICMS->ICMS10->orig)){
                  $icmsOrigem = $res->NFe->infNFe->det->imposto->ICMS->ICMS10->orig;
              }
          }

          $NFeItem->origem = $icmsOrigem;
          $NFeItem->icmsbasemodalidade = $item->imposto->ICMS->ICMS00->modBC??null;
          $NFeItem->icmsbase = $item->imposto->ICMS->ICMS00->vBC??0;
          $NFeItem->icmspercentual = $item->imposto->ICMS->ICMS00->pICMS??0;
          $NFeItem->icmsvalor = $item->imposto->ICMS->ICMS00->vICMS??0;
          $NFeItem->icmscst = $item->imposto->ICMS->ICMS00->CST??0;
          $NFeItem->icmsstbasemodalidade = $item->imposto->ICMS->ICMS90->modBCST??null;
          $NFeItem->icmsstbase = $item->imposto->ICMS->ICMS90->vBCST??0;
          $NFeItem->icmsstpercentual = $item->imposto->ICMS->ICMS90->pICMSST??0;
          $NFeItem->icmsstvalor = $item->imposto->ICMS->ICMS90->vICMSST??0;
          $NFeItem->ipicst = $item->imposto->IPI->IPITrib->CST??0;
          $NFeItem->ipibase = $item->imposto->IPI->IPITrib->vBC??0;
          $NFeItem->ipipercentual = $item->imposto->IPI->IPITrib->pIPI??0;
          $NFeItem->ipivalor = $item->imposto->IPI->IPITrib->vIPI??0;
          $NFeItem->piscst = $item->imposto->PIS->PISAliq->CST??0;
          $NFeItem->pisbase = $item->imposto->PIS->PISAliq->vBC??0;
          $NFeItem->pispercentual = $item->imposto->PIS->PISAliq->pPIS??0;
          $NFeItem->pisvalor = $item->imposto->PIS->PISAliq->vPIS??0;
          $NFeItem->cofinscst = $item->imposto->COFINS->COFINSAliq->CST??0;
          $NFeItem->cofinsbase = $item->imposto->COFINS->COFINSAliq->vBC??0;
          $NFeItem->cofinspercentual = $item->imposto->COFINS->COFINSAliq->pCOFINS??0;
          $NFeItem->cofinsvalor = $item->imposto->COFINS->COFINSAliq->vCOFINS??0;
          // dd($NFeItem);
          $NFeItem->save();

          // SALVA OS DADOS NA tblnotafiscalterceiroprodutobarra
          $produtobarra = NotaFiscalTerceiroProdutoBarra::firstOrNew([
          'codnotafiscalterceirogrupo' => $codGrupo->codnotafiscalterceirogrupo
          ]);
          $produtobarra->codnotafiscalterceirogrupo = $codGrupo->codnotafiscalterceirogrupo;;
          $produtobarra->codprodutobarra = null;
          $produtobarra->margem = null; // rever este campo
          $produtobarra->complemento = null; // rever este campo
          $produtobarra->quantidade = $item->prod->qTrib??$res->NFe->infNFe->det->prod->qTrib;
          $produtobarra->valorproduto = $item->prod->vProd??$res->NFe->infNFe->det->prod->vProd;
          // dd($produtobarra);
          $produtobarra->save();
        } // FIM DO ARMAZENA ITENS

        //SE HOUVER DUPLICATA ARMAZENA OS DADOS
        if (isset($res->NFe->infNFe->cobr)){

          if(isset($res->NFe->infNFe->cobr->dup) && count($res->NFe->infNFe->cobr->dup) > 1){
              foreach ($res->NFe->infNFe->cobr->dup as $key => $duplicata) {
                  $NFeDuplicata = NotaFiscalTerceiroDuplicata::firstOrNew([
                      'codnotafiscalterceiro' => $codnotafiscalterceiro->codnotafiscalterceiro,
                      'duplicata' => $duplicata->nDup??1
                  ]);
                  $NFeDuplicata->codnotafiscalterceiro = $codnotafiscalterceiro->codnotafiscalterceiro;
                  $NFeDuplicata->codtitulo = null; // rever este campo
                  $NFeDuplicata->duplicata = $duplicata->nDup??1;
                  $NFeDuplicata->vencimento = Carbon::parse($duplicata->dVenc);
                  $NFeDuplicata->valor = $duplicata->vDup;
                  $NFeDuplicata->ndup = $duplicata->nDup??1;
                  $NFeDuplicata->dvenc = Carbon::parse($duplicata->dVenc);
                  $NFeDuplicata->vdup = $duplicata->vDup;
                  $NFeDuplicata->save();
              }
          }
          else{
              if(isset($res->NFe->infNFe->cobr->dup)){
                  $NFeDuplicata = NotaFiscalTerceiroDuplicata::firstOrNew([
                      'codnotafiscalterceiro' => $codnotafiscalterceiro->codnotafiscalterceiro,
                      'duplicata' => $res->NFe->infNFe->cobr->dup->nDup??1
                  ]);
                  $NFeDuplicata->codnotafiscalterceiro = $codnotafiscalterceiro->codnotafiscalterceiro;
                  $NFeDuplicata->codtitulo = null; // rever este campo
                  $NFeDuplicata->duplicata = $res->NFe->infNFe->cobr->dup->nDup??1;
                  $NFeDuplicata->vencimento = Carbon::parse($res->NFe->infNFe->cobr->dup->dVenc);
                  $NFeDuplicata->valor = $res->NFe->infNFe->cobr->dup->vDup;
                  $NFeDuplicata->ndup = $res->NFe->infNFe->cobr->dup->nDup??1;
                  $NFeDuplicata->dvenc = Carbon::parse($res->NFe->infNFe->cobr->dup->dVenc);
                  $NFeDuplicata->vdup = $res->NFe->infNFe->cobr->dup->vDup;
                  $NFeDuplicata->save();
              }
          }
        }

      DB::commit();

      return true;
    } // FIM DO armazenaDadosNFe


}
