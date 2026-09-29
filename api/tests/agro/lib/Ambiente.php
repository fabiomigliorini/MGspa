<?php

namespace AgroBateria;

use Illuminate\Support\Facades\DB;
use Mg\Usuario\Usuario;

/**
 * Sobe o Laravel, trava a bateria no dev e cuida do token.
 *
 * A bateria ESCREVE no banco (massa ZZTESTE), entao a trava e dura: APP_ENV
 * diferente de production, APP_URL com "-dev" e o host do banco numa lista
 * conhecida (AGRO_BATERIA_DB_HOSTS, virgula). Qualquer um falhando, nada roda.
 */
final class Ambiente
{
    public const PASTA = 'agro-bateria'; // storage/app/agro-bateria
    public const NOME_TOKEN = 'agro-bateria';

    public static function iniciar(): void
    {
        $api = dirname(__DIR__, 3);
        require_once $api . '/vendor/autoload.php';
        $app = require $api . '/bootstrap/app.php';
        $app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();
        static::travarNoDev();
        // No dev o log de queries fica ligado; num estresse ele come a memoria.
        DB::connection()->disableQueryLog();
    }

    public static function travarNoDev(): void
    {
        $env = (string) app()->environment();
        $url = (string) config('app.url');
        $conexao = (string) config('database.default');
        $host = (string) config("database.connections.{$conexao}.host");
        $hosts = array_filter(array_map('trim', explode(',', getenv('AGRO_BATERIA_DB_HOSTS') ?: 'host.docker.internal')));

        $problemas = [];
        if ($env === 'production') {
            $problemas[] = "APP_ENV={$env}";
        }
        if (!str_contains($url, '-dev')) {
            $problemas[] = "APP_URL={$url} sem -dev";
        }
        if (!in_array($host, $hosts, true)) {
            $problemas[] = "DB_HOST={$host} fora da lista (" . implode(',', $hosts) . ')';
        }
        if ($problemas) {
            throw new \RuntimeException('Recusado, isto nao parece o dev: ' . implode('; ', $problemas));
        }
    }

    /** Arquivo de trabalho em storage/app/agro-bateria (manifesto, vetores, relatorios). */
    public static function arquivo(string $nome = ''): string
    {
        $dir = storage_path('app/' . static::PASTA);
        if (!is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }
        return $nome === '' ? $dir : $dir . '/' . $nome;
    }

    /** A mesma API que o app usa, pelo nginx (o /etc/hosts do container ja aponta api-dev pro host). */
    public static function urlApi(): string
    {
        return rtrim((string) config('app.url'), '/') . '/api/';
    }

    /**
     * Usuario da bateria. Sem --usuario, o primeiro Administrador ativo: depois
     * da TASK-129 o agro so abre para Administrador e Gerente.
     */
    public static function usuario(?string $quem = null): Usuario
    {
        $q = Usuario::query()->whereNull('inativo');
        if ($quem !== null && $quem !== '') {
            ctype_digit($quem) ? $q->where('codusuario', (int) $quem) : $q->where('usuario', $quem);
        } else {
            $q->whereExists(fn ($s) => static::noGrupo($s, ['Administrador']))->orderBy('codusuario');
        }
        $u = $q->first();
        if (!$u) {
            throw new \RuntimeException('Usuario da bateria nao encontrado' . ($quem ? ": {$quem}" : ''));
        }
        return $u;
    }

    /**
     * Usuario ativo SEM Administrador e SEM Gerente (e com Caixa, pra ser alguem
     * que existe de verdade no MG). E quem a TASK-129 tem que barrar.
     */
    public static function usuarioForaDoAgro(): ?Usuario
    {
        return Usuario::query()->whereNull('inativo')
            ->whereExists(fn ($s) => static::noGrupo($s, ['Caixa']))
            ->whereNotExists(fn ($s) => static::noGrupo($s, ['Administrador', 'Gerente']))
            ->orderByRaw("case when usuario like '%teste%' then 0 else 1 end")
            ->orderBy('codusuario')
            ->first();
    }

    private static function noGrupo($s, array $grupos): void
    {
        $s->selectRaw('1')
            ->from('tblgrupousuariousuario as guu')
            ->join('tblgrupousuario as g', 'g.codgrupousuario', '=', 'guu.codgrupousuario')
            ->whereColumn('guu.codusuario', 'tblusuario.codusuario')
            ->whereIn('g.grupousuario', $grupos);
    }

    /** Personal access token do Passport; apagado no fim com revogar(). */
    public static function token(Usuario $u): array
    {
        $r = $u->createToken(static::NOME_TOKEN);
        return ['token' => $r->accessToken, 'id' => $r->accessTokenId];
    }

    /** Sem id: apaga todo token que a bateria deixou (rodada interrompida). */
    public static function revogar(?string $id = null): int
    {
        $q = DB::table('oauth_access_tokens');
        return $id === null
            ? $q->where('name', static::NOME_TOKEN)->delete()
            : $q->where('id', $id)->delete();
    }
}
