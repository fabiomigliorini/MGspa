<?php

namespace Mg\Maquineta;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Mg\Saurus\SaurusPdv;
use Mg\Saurus\SaurusService;
use Mg\Usuario\Autorizador;
use SimpleSoftwareIO\QrCode\Generator;

class MaquinetaController extends Controller
{
    const GRUPOS = ['Financeiro', 'Gerente'];

    public function index(Request $request)
    {
        Autorizador::autoriza(static::GRUPOS);
        $paginator = MaquinetaService::listar($request->only([
            'codmaquineta', 'texto', 'codfilial', 'codpessoa', 'integracao', 'inativo',
        ]));
        // situacao dos periodos (conferencia do cartao, M9.8)
        $resumo = MaquinetaLoteService::resumo($paginator->getCollection()->pluck('codmaquineta')->all());
        $paginator->getCollection()->each(fn ($m) => $m->periodos = $resumo[$m->codmaquineta] ?? null);
        return MaquinetaResource::collection($paginator);
    }

    public function adquirentes()
    {
        Autorizador::autoriza(static::GRUPOS);
        return response()->json(MaquinetaService::adquirentes());
    }

    public function show($codmaquineta)
    {
        Autorizador::autoriza(static::GRUPOS);
        $maquineta = Maquineta::with(MaquinetaService::RELACOES)->findOrFail($codmaquineta);
        return new MaquinetaResource($maquineta);
    }

    public function store(MaquinetaStoreRequest $request)
    {
        $dados = $request->validated();
        MaquinetaService::autorizar($dados['codfilial']);
        $maquineta = DB::transaction(fn () => MaquinetaService::criar($dados));
        return new MaquinetaResource($maquineta);
    }

    public function update(MaquinetaUpdateRequest $request, $codmaquineta)
    {
        $maquineta = Maquineta::findOrFail($codmaquineta);
        $dados = $request->validated();
        MaquinetaService::autorizar($maquineta->codfilial);
        MaquinetaService::autorizar($dados['codfilial']);
        $maquineta = DB::transaction(fn () => MaquinetaService::atualizar($maquineta, $dados));
        return new MaquinetaResource($maquineta);
    }

    public function destroy($codmaquineta)
    {
        $maquineta = Maquineta::findOrFail($codmaquineta);
        MaquinetaService::autorizar($maquineta->codfilial);
        DB::transaction(fn () => MaquinetaService::excluir($maquineta));
        return response()->noContent();
    }

    public function inativar($codmaquineta)
    {
        $maquineta = Maquineta::findOrFail($codmaquineta);
        MaquinetaService::autorizar($maquineta->codfilial);
        $maquineta = DB::transaction(fn () => MaquinetaService::inativar($maquineta));
        return new MaquinetaResource($maquineta);
    }

    public function ativar($codmaquineta)
    {
        $maquineta = Maquineta::findOrFail($codmaquineta);
        MaquinetaService::autorizar($maquineta->codfilial);
        $maquineta = DB::transaction(fn () => MaquinetaService::ativar($maquineta));
        return new MaquinetaResource($maquineta);
    }

    public function juntar(Request $request, $codmaquineta)
    {
        $request->validate([
            'codmaquinetadestino' => 'required|integer|exists:tblmaquineta,codmaquineta',
        ]);
        $errada = Maquineta::findOrFail($codmaquineta);
        $certa = Maquineta::findOrFail($request->codmaquinetadestino);
        MaquinetaService::autorizar($errada->codfilial);
        $certa = DB::transaction(fn () => MaquinetaService::juntar($errada, $certa));
        return new MaquinetaResource($certa);
    }

    // Saurus, passo 1: registra o PDV Saurus e devolve o QR que o pinpad lê.
    // Com codmaquineta = parear de novo (mesmo PDV Saurus, pinpad novo).
    public function saurusQrCode(Request $request)
    {
        $request->validate([
            'codmaquineta' => 'nullable|integer|exists:tblmaquineta,codmaquineta',
            'pdv_uuid' => 'nullable|uuid',
            'apelido' => 'required_without:codmaquineta|nullable|string|min:3|max:50',
            'codfilial' => 'required_without:codmaquineta|nullable|integer|exists:tblfilial,codfilial',
        ]);

        $apelido = $request->apelido;
        $codfilial = $request->codfilial;
        $pdv_uuid = $request->pdv_uuid;

        if ($request->codmaquineta) {
            $maquineta = Maquineta::findOrFail($request->codmaquineta);
            if ($maquineta->integracao !== Maquineta::INTEGRACAO_SAURUS) {
                abort(422, 'Só maquineta SafraPay integrada é pareada pelo QR.');
            }
            $pdv = $maquineta->SaurusPinPad->SaurusPdv;
            $apelido = $pdv->apelido;
            $codfilial = $pdv->codfilial;
            $pdv_uuid = $pdv->id;
        }

        MaquinetaService::autorizar($codfilial);

        try {
            $pdv = SaurusService::registrarPdv($apelido, $codfilial, $pdv_uuid);
        } catch (\Exception $e) {
            abort(502, 'Falha ao acessar a API da Saurus. Tente de novo em instantes.');
        }

        $svg = (string) (new Generator)->format('svg')->size(220)->errorCorrection('H')
            ->generate($pdv->chavepublica);

        return response()->json([
            'pdv_uuid' => $pdv->id,
            'qrcode' => 'data:image/svg+xml;base64,' . base64_encode($svg),
        ]);
    }

    // Saurus, passo 2: depois que o pinpad leu o QR, grava o pinpad e a maquineta
    public function saurusConfirmar(Request $request)
    {
        $request->validate([
            'pdv_uuid' => 'required|uuid',
        ]);

        $pdv = SaurusPdv::where('id', $request->pdv_uuid)->firstOrFail();
        MaquinetaService::autorizar($pdv->codfilial);

        try {
            $pin = SaurusService::verificarLeitura($pdv);
        } catch (\Exception $e) {
            abort(502, 'Falha ao acessar a API da Saurus. Tente de novo em instantes.');
        }
        if (!$pin) {
            abort(422, 'O pinpad ainda não leu o QR Code. Leia na maquininha e confirme de novo.');
        }

        $maquineta = DB::transaction(fn () => MaquinetaService::parearSaurusPinPad($pin));
        return new MaquinetaResource($maquineta);
    }
}
