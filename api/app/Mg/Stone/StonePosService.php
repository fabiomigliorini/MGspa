<?php

namespace Mg\Stone;

use Illuminate\Support\Facades\Storage;

use Mg\Stone\Connect\ApiService;
use Mg\Stone\StonePos;

use Carbon\Carbon;

class StonePosService
{

    // Cria ou Atualiza Filial na Stone
    public static function create (
        StoneFilial $stoneFilial,
        $serialnumber,
        $apelido
    )
    {

        $token = StoneFilialService::verificaTokenValido($stoneFilial);

        $ret = ApiService::posControlConfigurationCreate(
            $token,
            $stoneFilial->establishmentid
        );

        // registra nova Pre Transacao na Stone
        $ret = ApiService::activatePosLink(
            $token,
            $serialnumber,
            $apelido,
            $stoneFilial->Filial->Pessoa->fantasia
        );

        if (!$ret['success']) {
            throw new \Exception($ret['msg'], 1);
        }

        $stonePos = StonePos::firstOrNew([
            'codstonefilial' => $stoneFilial->codstonefilial,
            'serialnumber' => $serialnumber
        ]);
        $stonePos->apelido = $apelido;
        $stonePos->referenceid = $ret['pos_link']['pos_reference_id'];
        $stonePos->islinked = true;
        $stonePos->inativo = null;
        $stonePos->save();

        return $stonePos;
    }

    public static function destroy (StonePos $stonePos)
    {
        $token = StoneFilialService::verificaTokenValido($stonePos->stoneFilial);

        $ret = ApiService::deactivatePosLink(
            $token,
            $stonePos->referenceid
        );

        if (!$ret['success']) {
            throw new \Exception($ret['msg'], 1);
        }

        $stonePos->update([
            'inativo' => Carbon::Now()
        ]);

        return $stonePos;
    }

}
