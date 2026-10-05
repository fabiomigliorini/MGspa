<?php

namespace Mg\Caixa;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Mg\Conferencia\SessaoResource;
use Mg\Pdv\Pdv;
use Mg\Pdv\PdvRequest;
use Mg\Pdv\PdvService;
use Mg\Portador\PortadorPeriodo;
use Mg\Usuario\Autorizador;

/**
 * Caixa no PDV (M9 doc-3): abrir e fechar o dinheiro da gaveta com
 * contagem e imprimir o bordero do caixa. Rotas v1/pdv/caixa (o dispositivo
 * autoriza; quem opera: Caixa ou Gerente da filial, Administrador).
 */
class CaixaController extends Controller
{
    private function autorizar(PdvRequest $request): Pdv
    {
        $pdv = PdvService::autoriza($request->pdv);
        if (!Autorizador::pode([]) && !Autorizador::pode(['Caixa', 'Gerente'], $pdv->codfilial)) {
            abort(403, 'Abrir e fechar o caixa: só Caixa ou Gerente da filial, ou Administrador!');
        }
        return $pdv;
    }

    public function status(PdvRequest $request)
    {
        $pdv = PdvService::autoriza($request->pdv);
        $gaveta = $pdv->Portador;
        if (!$gaveta) {
            return ['data' => ['gaveta' => null, 'sessao' => null, 'ultima' => null]];
        }
        $aberta = CaixaService::sessaoAberta($gaveta->codportador);
        $ultima = $aberta ? null : CaixaService::ultimaSessao($gaveta->codportador);
        return ['data' => [
            'gaveta' => ['codportador' => $gaveta->codportador, 'portador' => $gaveta->portador],
            'sessao' => $aberta ? new SessaoResource($aberta) : null,
            'ultima' => $ultima ? new SessaoResource($ultima) : null,
        ]];
    }

    private function contagem(PdvRequest $request): array
    {
        return $request->validate([
            'moedas' => 'required|numeric|min:0',
            'cedulas' => 'required|numeric|min:0',
            'observacoes' => 'nullable|string|max:250',
        ]);
    }

    public function abrir(PdvRequest $request)
    {
        $pdv = $this->autorizar($request);
        $dados = $this->contagem($request);
        $sessao = DB::transaction(fn () => CaixaService::abrir(
            $pdv,
            (float) $dados['moedas'],
            (float) $dados['cedulas'],
            $dados['observacoes'] ?? null
        ));
        return new SessaoResource($sessao);
    }

    public function fechar(PdvRequest $request)
    {
        $pdv = $this->autorizar($request);
        $dados = $this->contagem($request);
        $request->validate(['impressora' => 'nullable|string']);
        $sessao = DB::transaction(fn () => CaixaService::fechar(
            $pdv,
            (float) $dados['moedas'],
            (float) $dados['cedulas'],
            $dados['observacoes'] ?? null
        ));
        if (!empty($request->impressora)) {
            CaixaBorderoService::imprimir($sessao, $request->impressora);
        }
        return new SessaoResource($sessao);
    }

    public function imprimirBordero(PdvRequest $request, int $id, string $impressora)
    {
        $pdv = PdvService::autoriza($request->pdv);
        $sessao = PortadorPeriodo::findOrFail($id);
        if ($sessao->codportador != $pdv->codportador) {
            abort(403, 'Sessão de outra gaveta!');
        }
        CaixaBorderoService::imprimir($sessao, $impressora);
    }

    // PDF do bordero (rota assinada, aberta pela impressora e pelo PDV)
    public function bordero(int $id)
    {
        $sessao = PortadorPeriodo::findOrFail($id);
        return response()->make(CaixaBorderoService::pdf($sessao), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="Bordero' . $sessao->codportadorperiodo . '.pdf"',
        ]);
    }
}
