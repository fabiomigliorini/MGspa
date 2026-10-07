<?php

namespace Mg\Maquineta;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Mg\Conferencia\ConferenciaAutorizador;

/**
 * A maquineta e seus periodos no contas (TASK-188 M9.8), no padrao do
 * portador (doc-4): a tela /maquineta/{cod}/{codperiodo} e o que muda o
 * periodo (conferir, reabrir, inicio e fim, dividir, unificar, foto). Quem
 * pode: Gerente da filial (compartilhada: qualquer gerente), Financeiro e
 * Administrador (ConferenciaAutorizador). O que muda devolve a tela.
 */
class MaquinetaPeriodoController extends Controller
{
    private function periodo(int $id): MaquinetaLote
    {
        $lote = MaquinetaLote::with('Maquineta')->findOrFail($id);
        ConferenciaAutorizador::autorizarMaquineta($lote->Maquineta);
        return $lote;
    }

    // a maquineta, as abas (todos os periodos, sem lancamentos) e o periodo
    // escolhido (sem ele, o mais novo)
    private function dados(Maquineta $maquineta, ?int $codmaquinetalote): array
    {
        $lotes = MaquinetaLote::where('codmaquineta', $maquineta->codmaquineta)
            ->with('UsuarioFechamento:codusuario,usuario')
            ->orderBy('abertura')
            ->orderBy('codmaquinetalote')
            ->get();
        $lote = $codmaquinetalote ? $lotes->firstWhere('codmaquinetalote', $codmaquinetalote) : $lotes->last();
        if ($codmaquinetalote && !$lote) {
            abort(404, 'Período não é desta maquineta.');
        }
        // o cadastro inteiro: o cabecalho da tela edita, inativa, pareia, junta e exclui
        $maquineta->loadMissing(MaquinetaService::RELACOES);
        return ['data' => [
            'maquineta' => (new MaquinetaResource($maquineta))->resolve(),
            'periodos' => MaquinetaLoteResource::lista(
                $lotes,
                MaquinetaLoteService::totais($maquineta->codmaquineta),
                MaquinetaLoteService::comFoto()
            ),
            'periodo' => $lote ? (new MaquinetaLoteResource($lote))->comLancamentos() : null,
        ]];
    }

    public function tela(int $codmaquineta, ?int $codmaquinetalote = null)
    {
        $maquineta = Maquineta::findOrFail($codmaquineta);
        ConferenciaAutorizador::autorizarMaquineta($maquineta);
        return $this->dados($maquineta, $codmaquinetalote);
    }

    // periodos nao conferidos (destino do "mover" na correcao do lancamento)
    public function lista(int $codmaquineta)
    {
        $maquineta = Maquineta::findOrFail($codmaquineta);
        ConferenciaAutorizador::autorizarMaquineta($maquineta);
        $lotes = MaquinetaLote::where('codmaquineta', $codmaquineta)
            ->whereNull('fechamento')
            ->orderBy('abertura', 'desc')
            ->get();
        return ['data' => MaquinetaLoteResource::lista($lotes, [], [])];
    }

    private function resposta(MaquinetaLote $lote)
    {
        return $this->dados($lote->Maquineta, $lote->exists ? $lote->codmaquinetalote : null);
    }

    public function conferir(Request $request, int $id)
    {
        $dados = $request->validate([
            'creditoinformado' => 'required|numeric',
            'debitoinformado' => 'required|numeric',
            'observacoes' => 'nullable|string|max:500',
        ]);
        $lote = $this->periodo($id);
        DB::transaction(fn () => MaquinetaLoteService::conferir(
            $lote,
            (float) $dados['creditoinformado'],
            (float) $dados['debitoinformado'],
            $dados['observacoes'] ?? null
        ));
        return $this->resposta($lote->fresh('Maquineta'));
    }

    public function reabrir(int $id)
    {
        $lote = $this->periodo($id);
        DB::transaction(fn () => MaquinetaLoteService::reabrir($lote));
        return $this->resposta($lote->fresh('Maquineta'));
    }

    public function datas(Request $request, int $id)
    {
        $dados = $request->validate([
            'inicio' => 'required|date',
            'fim' => 'nullable|date',
            'observacoes' => 'nullable|string|max:500',
        ]);
        $lote = $this->periodo($id);
        DB::transaction(fn () => MaquinetaLoteService::editarDatas(
            $lote,
            Carbon::parse($dados['inicio']),
            empty($dados['fim']) ? null : Carbon::parse($dados['fim']),
            $dados['observacoes'] ?? null
        ));
        return $this->resposta($lote->fresh('Maquineta'));
    }

    // devolve a tela na segunda parte
    public function dividir(Request $request, int $id)
    {
        $dados = $request->validate(['corte' => 'required|date']);
        $lote = $this->periodo($id);
        $segunda = DB::transaction(fn () => MaquinetaLoteService::dividir($lote, Carbon::parse($dados['corte'])));
        return $this->resposta($segunda->fresh('Maquineta'));
    }

    // devolve a tela no anterior, que ficou com tudo
    public function unificar(int $id)
    {
        $lote = $this->periodo($id);
        $anterior = DB::transaction(fn () => MaquinetaLoteService::unificar($lote));
        return $this->resposta($anterior->fresh('Maquineta'));
    }

    // anexar vale tambem no conferido (a foto que faltou)
    public function foto(Request $request, int $id)
    {
        $request->validate(['anexoBase64' => 'required|string']);
        $lote = $this->periodo($id);
        MaquinetaLoteService::anexarFoto($lote, $request->anexoBase64);
        return $this->resposta($lote);
    }

    // excluir tambem vale no conferido (a foto errada); sem nenhuma, o periodo
    // volta a "sem bordero"
    public function excluirFoto(int $id, string $arquivo)
    {
        $lote = $this->periodo($id);
        MaquinetaLoteService::excluirFoto($lote, $arquivo);
        return $this->resposta($lote);
    }

    public function mostrarFoto(int $id, string $arquivo)
    {
        $lote = $this->periodo($id);
        return MaquinetaLoteService::mostrarFoto($lote, $arquivo);
    }
}
