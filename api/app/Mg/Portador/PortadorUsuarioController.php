<?php

namespace Mg\Portador;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;

/**
 * A lista de usuarios do portador e o papel de cada um (doc-4, redefinicao
 * do dinheiro): o cadeado ao lado do editar. Ve e edita o gestor do portador
 * ou o Administrador. Rotas v1/portador/{cod}/usuario.
 */
class PortadorUsuarioController extends Controller
{
    private function portador(int $codportador): Portador
    {
        $portador = Portador::findOrFail($codportador);
        PortadorAutorizador::autorizar($portador, PortadorUsuario::PAPEL_GESTOR, 'Usuários');
        return $portador;
    }

    private function lista(Portador $portador): array
    {
        PortadorAutorizador::esquecer();
        return ['data' => PortadorUsuario::where('tblportadorusuario.codportador', $portador->codportador)
            ->join('tblusuario as u', 'u.codusuario', '=', 'tblportadorusuario.codusuario')
            ->orderByRaw("position(papel in 'GOD')")
            ->orderBy('u.usuario')
            ->get(['tblportadorusuario.*', 'u.usuario', 'u.inativo as usuarioinativo'])
            ->map(fn ($pu) => [
                'codportadorusuario' => $pu->codportadorusuario,
                'codusuario' => $pu->codusuario,
                'usuario' => $pu->usuario,
                'usuarioinativo' => $pu->usuarioinativo,
                'papel' => $pu->papel,
                'papeldescricao' => PortadorUsuario::PAPEIS[$pu->papel],
            ])->all()];
    }

    public function index(int $codportador)
    {
        return $this->lista($this->portador($codportador));
    }

    // a lista inteira de uma vez (o Salvar do dialog): quem saiu da lista e' excluido, quem ficou
    // tem o papel trocado e quem entrou e' incluido
    public function update(Request $request, int $codportador)
    {
        $dados = $request->validate([
            'usuarios' => 'present|array',
            'usuarios.*.codusuario' => 'required|integer|distinct|exists:tblusuario,codusuario',
            'usuarios.*.papel' => 'required|in:' . implode(',', array_keys(PortadorUsuario::PAPEIS)),
        ]);
        $portador = $this->portador($codportador);
        $papeis = collect($dados['usuarios'])->pluck('papel', 'codusuario');
        DB::transaction(function () use ($portador, $papeis) {
            PortadorUsuario::where('codportador', $portador->codportador)
                ->whereNotIn('codusuario', $papeis->keys())
                ->get()
                ->each->delete();
            foreach ($papeis as $codusuario => $papel) {
                PortadorUsuario::updateOrCreate(
                    ['codportador' => $portador->codportador, 'codusuario' => $codusuario],
                    ['papel' => $papel]
                );
            }
        });
        return $this->lista($portador);
    }
}
