<?php

namespace Mg\Pdv;

use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use DB;

use Mg\Negocio\Negocio;
use Mg\Negocio\NegocioFormaPagamento;
use Mg\Negocio\NegocioProdutoBarra;

class PdvNegocioService
{

    public static function negocio($data, $pdv)
    {
        // abre transacao no banco
        DB::beginTransaction();

        // Procura se já existe um negocio com o uuid na base
        $negocio = Negocio::firstOrNew(['uuid' => $data['uuid']]);
        if ($negocio->codnegociostatus == 2) {
            throw new \Exception("Tentando atualizar um negocio Fechado {$negocio->codnegocio}!", 1);
        }
        if ($negocio->codnegociostatus == 3) {
            throw new \Exception("Tentando atualizar um negocio Cancelado {$negocio->codnegocio}!", 1);
        }

        // importa os dados do negocio
        $negocio->fill($data);
        $negocio->codpdv = $pdv->codpdv;
        $negocio->codfilial = $negocio->EstoqueLocal->codfilial;
        $negocio->codusuario = Auth::user()->codusuario;
        $negocio->save();

        // importa os itens
        foreach ($data['itens'] as $item) {
            $npb = NegocioProdutoBarra::firstOrNew(['uuid' => $item['uuid']]);
            if (!empty($npb->codnegocio) && $npb->codnegocio != $negocio->codnegocio) {
                throw new \Exception("Tentando atualizar um item de outro negocio {$npb->codnegocio}/{$negocio->codnegocio}!", 1);
            }
            $npb->fill($item);
            $npb->codnegocio = $negocio->codnegocio;
            $npb->save();
        }

        // importa os pagamentos
        foreach ($data['pagamentos'] as $pagto) {
            $nfp = NegocioFormaPagamento::firstOrNew(['uuid' => $pagto['uuid']]);
            if (!empty($nfp->codnegocio) && $nfp->codnegocio != $negocio->codnegocio) {
                throw new \Exception("Tentando atualizar um pagamento de outro negocio {$nfp->codnegocio}/{$negocio->codnegocio}!", 1);
            }
            $nfp->fill($pagto);
            $nfp->codnegocio = $negocio->codnegocio;
            $nfp->save();
        }

        // exclui pagamentos que nao vieram no post
        $uuids = array_column($data['pagamentos'], 'uuid');
        NegocioFormaPagamento::where('codnegocio', $negocio->codnegocio)->whereNotIn('uuid', $uuids)->delete();

        // salva no banco
        DB::commit();
        return $negocio;
    }
}
