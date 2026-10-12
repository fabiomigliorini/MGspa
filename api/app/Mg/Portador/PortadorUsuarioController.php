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

    // inclui ou troca o papel
    public function store(Request $request, int $codportador)
    {
        $dados = $request->validate([
            'codusuario' => 'required|integer|exists:tblusuario,codusuario',
            'papel' => 'required|in:' . implode(',', array_keys(PortadorUsuario::PAPEIS)),
        ]);
        $portador = $this->portador($codportador);
        DB::transaction(fn () => PortadorUsuario::updateOrCreate(
            ['codportador' => $portador->codportador, 'codusuario' => $dados['codusuario']],
            ['papel' => $dados['papel']]
        ));
        return $this->lista($portador);
    }

    public function destroy(int $codportador, int $codportadorusuario)
    {
        $portador = $this->portador($codportador);
        DB::transaction(fn () => PortadorUsuario::where('codportador', $portador->codportador)
            ->findOrFail($codportadorusuario)
            ->delete());
        return $this->lista($portador);
    }
}
