<?php

namespace Mg\Mdfe;

use Illuminate\Http\Request;

use Mg\NotaFiscal\NotaFiscal;

class MdfeController
{

    public function index (Request $request)
    {
        $mdfes = Mdfe::orderBy('codmdfe', 'desc')->simplePaginate(100);
        return MdfeResource::collection($mdfes);
    }

    public function show (Request $request, $codmdfe)
    {
        $mdfe = Mdfe::findOrFail($codmdfe);
        return new MdfeResource($mdfe);
    }

    public function update (Request $request, $codmdfe)
    {
        $request->validate([
            'codfilial' => ['required'],
            'tipoemitente' => ['required'],
            'tipotransportador' => ['required'],
            'emissao' => ['required'],
            'inicioviagem' => ['required'],
            'codcidadecarregamento' => ['required'],
            'codestadofim' => ['required'],
            'MdfeVeiculoS' => ['required', 'array', 'min:1'],
            'MdfeVeiculoS.*.codveiculo' => ['required'],
        ]);

        $mdfe = Mdfe::findOrFail($codmdfe);

        if (!in_array($mdfe->codmdfestatus, [MdfeStatus::EM_DIGITACAO, MdfeStatus::TRANSMITIDA, MdfeStatus::NAO_AUTORIZADA])) {
            throw new \Exception("Esta MDFe não pode mais ser alterada!", 1);
        }

        $mdfe->fill($request->all());
        $mdfe->save();
        $cods = [];
        foreach ($request->MdfeVeiculoS as $veiculo) {
            if (empty($veiculo['codveiculo'])) {
                continue;
            }
            $cods[] = $veiculo['codveiculo'];
            $mdfeVeiculo = MdfeVeiculo::firstOrCreate([
                'codveiculo' => $veiculo['codveiculo'],
                'codmdfe' => $mdfe->codmdfe,
            ]);
            $mdfeVeiculo->update([
                'codpessoacondutor' => $veiculo['codpessoacondutor']
            ]);
        }
        MdfeVeiculo::where('codmdfe', $mdfe->codmdfe)->whereNotIn('codveiculo', $cods)->delete();
        return new MdfeResource($mdfe);
    }


    // Cria registro de MDFE baseado nos dados de uma Nota Fiscal
    public function criarDaNotaFiscal (Request $request, $codnotafiscal)
    {
        $nf = NotaFiscal::findOrFail($codnotafiscal);
        $mdfe = MdfeService::criarDaNotaFiscal($nf);
        return new MdfeResource($mdfe);
    }

    // Cria registro de MDFE baseado nos dados de uma Nota Fiscal pela Chave
    public function criarDaNfeChave (Request $request, $nfechave)
    {
        $nfechave = preg_replace('/[^0-9]/', '', $nfechave);
        $nf = NotaFiscal::where(['nfechave' => $nfechave])->first();
        if (!$nf) {
            throw new \Exception("NFe não localizada!", 1);
        }
        $mdfe = MdfeService::criarDaNotaFiscal($nf);
        return new MdfeResource($mdfe);
    }

    // Cria Arquivo XML da MDFe
    public function criarXml (Request $request, $codmdfe)
    {
        $mdfe = Mdfe::findOrFail($codmdfe);
        $xml = MdfeNfePhpService::criarXml($mdfe);
        return response($xml, 200)->header('Content-Type', 'text/xml');
    }

    // Cria Arquivo XML da MDFe
    public function enviar (Request $request, $codmdfe)
    {
        $mdfe = Mdfe::findOrFail($codmdfe);
        $ret = MdfeNfePhpService::enviar($mdfe);
        return $ret;
    }

    // Consultar Recibo na Sefaz
    public function consultarEnvio (Request $request, $codmdfe, $codmdfeenviosefaz = null)
    {
        if (!empty($codmdfeenviosefaz)) {
            $mdfeEnvioSefaz = MdfeEnvioSefaz::findOrFail($codmdfeenviosefaz);
        } else {
            $mdfe = Mdfe::findOrFail($codmdfe);
            $mdfeEnvioSefaz = $mdfe->MdfeEnvioSefazS()->orderBy('criacao', 'DESC')->first();
        }
        $ret = MdfeNfePhpService::consultarEnvio($mdfeEnvioSefaz);
        return $ret;
    }

    // Consultar MDFe na Sefaz
    public function consultar (Request $request, $codmdfe)
    {
        $mdfe = Mdfe::findOrFail($codmdfe);
        $ret = MdfeNfePhpService::consultar($mdfe);
        return $ret;
    }

    // Cancelar MDFe na Sefaz
    public function cancelar (Request $request, $codmdfe)
    {
        $request->validate([
            'justificativa' => ['required', 'min:15', 'max:100'],
        ]);
        $mdfe = Mdfe::findOrFail($codmdfe);
        $ret = MdfeNfePhpService::cancelar($mdfe, $request->justificativa);
        return $ret;
    }

    // Encerrar MDFe na Sefaz
    public function encerrar (Request $request, $codmdfe)
    {
        $mdfe = Mdfe::findOrFail($codmdfe);
        $ret = MdfeNfePhpService::encerrar($mdfe);
        return $ret;
    }

    // Emitir PDF do DAMDFe Documento Auxiliar do MDFe
    public function damdfe (Request $request, $codmdfe)
    {
        $mdfe = Mdfe::findOrFail($codmdfe);
        $res = MdfeNfePhpService::damdfe($mdfe);
        return response()->file($res);
    }

}
