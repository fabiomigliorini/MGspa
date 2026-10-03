<?php

namespace Mg\Caixa;

use Illuminate\Http\Resources\Json\JsonResource as Resource;
use Mg\Portador\PortadorAutorizador;
use Mg\Portador\PortadorLancamentoService;
use Mg\Portador\PortadorMovimento;
use Mg\Portador\PortadorUsuario;

// O periodo da gaveta na tela do caixa do PDV (MgCaixaSessao; sera'
// refatorada): contagens, dinheiro do sistema, itens, ajustes e
// transferencias.
class SessaoResource extends Resource
{
    public function toArray($request)
    {
        return array_merge([
            'codportadorperiodo' => $this->codportadorperiodo,
            'codportador' => $this->codportador,
            'portador' => optional($this->Portador)->portador,
            'codfilial' => optional($this->Portador)->codfilial,
            'inicio' => $this->inicio,
            'fim' => $this->fim,
            'aberta' => $this->aberto(),
            'usuarioabertura' => optional($this->UsuarioAbertura)->usuario,
            'usuariofechamento' => optional($this->UsuarioFechamento)->usuario,
            'saldoinicial' => $this->saldoinicial,
            'saldofinal' => $this->saldofinal,
            'diferenca' => $this->diferenca,
            'contagemabertura' => $this->contageminicial ?? (object) [],
            'contagemfechamento' => $this->contagemfinal ?? (object) [],
            'observacoes' => $this->observacoes,
            'podeOperar' => true,
            'podeReabrir' => $this->fechado()
                && PortadorAutorizador::pode($this->codportador, PortadorUsuario::PAPEL_GESTOR),
            'transferencias' => $this->transferencias(),
            'criacao' => $this->criacao,
            'codusuariocriacao' => $this->codusuariocriacao,
            'usuariocriacao' => optional($this->UsuarioCriacao)->usuario,
            'alteracao' => $this->alteracao,
            'codusuarioalteracao' => $this->codusuarioalteracao,
            'usuarioalteracao' => optional($this->UsuarioAlteracao)->usuario,
        ], CaixaService::painel($this->resource));
    }

    // as transferencias do periodo (a linha deste portador)
    private function transferencias(): array
    {
        return PortadorMovimento::where('codportadorperiodo', $this->codportadorperiodo)
            ->where('tipo', PortadorMovimento::TIPO_TRANSFERENCIA)
            ->with(['Par.Portador', 'Portador', 'UsuarioCriacao:codusuario,usuario'])
            ->orderBy('transacao', 'desc')
            ->get()
            ->map(function (PortadorMovimento $m) {
                $saida = $m->valor < 0;
                $outro = optional(optional($m->Par)->Portador)->portador;
                return [
                    'codportadormovimento' => $m->codportadormovimento,
                    'codportadororigem' => $saida ? $m->codportador : optional($m->Par)->codportador,
                    'codportadordestino' => $saida ? optional($m->Par)->codportador : $m->codportador,
                    'portadororigem' => $saida ? $m->Portador->portador : $outro,
                    'portadordestino' => $saida ? $outro : $m->Portador->portador,
                    'total' => abs((float) $m->valor),
                    'transacao' => $m->transacao,
                    'estado' => $m->estado,
                    'observacoes' => $m->observacoes,
                    'justificativa' => $m->justificativa,
                    'usuariocriacao' => optional($m->UsuarioCriacao)->usuario,
                    'podeConfirmar' => PortadorLancamentoService::podeConfirmar($m),
                    'podeCancelar' => PortadorLancamentoService::podeCancelar($m, $this->codportador),
                ];
            })->all();
    }
}
