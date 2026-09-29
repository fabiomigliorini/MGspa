<?php

namespace AgroBateria\Cenarios;

use AgroBateria\Ambiente;
use AgroBateria\Cenario;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

/**
 * P — acesso ao agro (TASK-129, decidido: Administrador e Gerente). Um usuário
 * que só é Caixa tem que levar 403 em TODA rota dos controllers do agro — a
 * lista vem das rotas registradas, então ação nova entra sozinha.
 *
 * Escrita só é chamada com id inexistente (0) e sem FormRequest: com FormRequest
 * a validação (422) vem antes da autorização e não dá para testar sem gravar.
 */
final class Permissoes extends Cenario
{
    private const NAMESPACES = ['Mg\\Grao\\', 'Mg\\Contrato\\', 'Mg\\Safra\\', 'Mg\\Fazenda\\', 'Mg\\Cultura\\', 'Mg\\Classificacao\\'];

    public function rodar(): void
    {
        $this->r->camada('P — Acesso ao agro');
        $this->caso('P1', 'Quem não é Administrador nem Gerente leva 403', fn () => $this->p1());
    }

    private function p1(): void
    {
        $u = Ambiente::usuarioForaDoAgro();
        if (!$u) {
            $this->r->erro('P1', 'Quem não é Administrador nem Gerente leva 403', 'nenhum usuário ativo só de Caixa para testar');
            return;
        }
        $tok = Ambiente::token($u);
        try {
            $api = $this->api()->comToken($tok['token']);
            $ids = $this->ids();
            $abertas = [];
            $checadas = 0;
            $puladas = 0;
            foreach (Route::getRoutes() as $rota) {
                $acao = $rota->getActionName();
                if (!$this->doAgro($acao)) {
                    continue;
                }
                $metodo = collect($rota->methods())->first(fn ($m) => $m !== 'HEAD');
                $temParametro = (bool) $rota->parameterNames();
                if ($metodo !== 'GET' && (!$temParametro || $this->temFormRequest($acao))) {
                    $puladas++;
                    continue;
                }
                $uri = preg_replace_callback('/\{(\w+)\??\}/', fn ($m) => $metodo === 'GET' ? ($ids[$m[1]] ?? 0) : 0, $rota->uri());
                $resp = $api->chamar($metodo, preg_replace('#^api/#', '', $uri), $metodo === 'GET' ? null : []);
                $checadas++;
                if ($resp->status !== 403) {
                    $abertas[] = "{$metodo} " . preg_replace('#^api/#', '', $rota->uri()) . " → {$resp->status}";
                }
            }
        } finally {
            Ambiente::revogar($tok['id']);
        }
        $this->r->checar('P1', 'Quem não é Administrador nem Gerente leva 403', !$abertas,
            "usuário {$u->usuario}: " . count($abertas) . " de {$checadas} rotas abertas"
            . ($abertas ? ' (ex.: ' . implode('; ', array_slice($abertas, 0, 3)) . ')' : '')
            . "; {$puladas} de escrita com validação não testáveis sem gravar");
    }

    private function doAgro(string $acao): bool
    {
        foreach (static::NAMESPACES as $ns) {
            if (str_starts_with($acao, $ns)) {
                return true;
            }
        }
        return false;
    }

    private function temFormRequest(string $acao): bool
    {
        [$classe, $metodo] = explode('@', $acao) + [1 => '__invoke'];
        if (!method_exists($classe, $metodo)) {
            return false;
        }
        foreach ((new \ReflectionMethod($classe, $metodo))->getParameters() as $p) {
            $t = $p->getType();
            if ($t instanceof \ReflectionNamedType && !$t->isBuiltin() && is_subclass_of($t->getName(), FormRequest::class)) {
                return true;
            }
        }
        return false;
    }

    /** Ids da massa para as rotas de leitura (senão o 404 esconderia o 200). */
    private function ids(): array
    {
        $m = $this->m();
        $carga = DB::table('tblcarga')->where('codsafra', $m->safra['soja'])->value('codcarga') ?? 0;
        $contrato = DB::table('tblcontrato')->where('codcultura', $m->cultura['soja'])->value('codcontrato') ?? 0;
        return [
            'codcarga' => $carga,
            'codcontrato' => $contrato,
            'codsafra' => $m->safra['soja'],
            'codplantio' => $m->plantio['soja'][0],
            'codfazenda' => $m->fazenda,
            'codcultura' => $m->cultura['soja'],
            'codvariedade' => $m->variedade['soja'],
            'codtalhao' => DB::table('tblplantio')->where('codplantio', $m->plantio['soja'][0])->value('codtalhao') ?? 0,
            'codparametroclassificacao' => $m->codParametro('soja', 'Umidade'),
            'codunidadearmazenadora' => DB::table('tblunidadearmazenadora')->where('unidadearmazenadora', 'like', 'ZZTESTE%')->value('codunidadearmazenadora') ?? 0,
            'codfixacao' => DB::table('tblcontratofixacao')->where('codcontrato', $contrato)->value('codcontratofixacao') ?? 0,
        ];
    }
}
