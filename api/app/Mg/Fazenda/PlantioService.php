<?php

namespace Mg\Fazenda;

use Mg\MgService;
use Mg\Safra\Safra;

class PlantioService extends MgService
{
    public static function pesquisar(?array $filter = null, ?array $sort = null, ?array $fields = null)
    {
        $qry = Plantio::query()->with(['Safra.Cultura', 'Fazenda', 'Variedade']);

        if (!empty($filter['codplantio'])) {
            $qry->where('codplantio', $filter['codplantio']);
        }

        if (!empty($filter['codsafra'])) {
            $qry->where('codsafra', $filter['codsafra']);
        }

        if (!empty($filter['codfazenda'])) {
            $qry->where('codfazenda', $filter['codfazenda']);
        }

        if (!empty($filter['codvariedade'])) {
            $qry->where('codvariedade', $filter['codvariedade']);
        }

        if (!empty($filter['inativo'])) {
            $qry->AtivoInativo($filter['inativo']);
        }

        $qry = self::qryOrdem($qry, $sort ?: ['codfazenda', 'talhao']);
        $qry = self::qryColunas($qry, $fields);
        return $qry;
    }

    /**
     * Safra nova com o layout de outra (TASK-201): a origem diz QUAIS talhões;
     * nome, desenho, cor e área vêm do cadastro ATUAL do talhão na fazenda — a
     * fazenda quase não muda, e assim pega correções feitas depois. Variedade e
     * data ficam em branco para informar depois ("Variedade pendente").
     *
     * Talhão que o mesmo plantio ocupou com 2 variedades vira um só; talhão
     * inativado no cadastro fica de fora. Query direta: o pesquisar() pagina.
     */
    public static function copiarTalhoes(int $codsafraorigem, Safra $destino): int
    {
        $codtalhoes = Plantio::where('codsafra', $codsafraorigem)
            ->whereNull('inativo')
            ->whereNotNull('codtalhao')
            ->distinct()
            ->pluck('codtalhao');

        $talhoes = Talhao::whereIn('codtalhao', $codtalhoes)
            ->whereNull('inativo')
            ->where('area', '>', 0)
            ->orderBy('codfazenda')
            ->orderBy('talhao')
            ->get();

        foreach ($talhoes as $t) {
            Plantio::create([
                'codsafra' => $destino->codsafra,
                'codfazenda' => $t->codfazenda,
                'codtalhao' => $t->codtalhao,
                'talhao' => $t->talhao,
                'geometria' => $t->geometria,
                'cor' => $t->cor,
                'areaplantada' => $t->area,
                'latitude' => $t->latitude,
                'longitude' => $t->longitude,
                'hacolhido' => 0,
            ]);
        }

        return $talhoes->count();
    }

    /**
     * Talhão finalizado (colhido por completo). Não existe colheita parcial em ha:
     * o check grava hacolhido = área (marcado) ou 0 (desmarcado). Mesma regra do
     * SafraService::producaoPlantio.
     */
    public static function finalizado(Plantio $plantio): bool
    {
        $area = (float) $plantio->areaplantada;
        return $area > 0 && (float) $plantio->hacolhido >= $area;
    }
}
