<?php

namespace Mg\Feriado;

use Carbon\Carbon;
use Illuminate\Support\Facades\Http;

class FeriadoService
{
    protected static array $feriadosMoveis = [
        'carnaval',
        'paixão',
        'sexta-feira santa',
        'páscoa',
        'corpus christi',
    ];

    public static function gerarFeriados(int $ano): array
    {
        $anoAnterior = $ano - 1;

        // Busca feriados ativos do ano anterior
        $feriadosAnoAnterior = Feriado::whereNull('inativo')
            ->whereRaw("EXTRACT(YEAR FROM data) = ?", [$anoAnterior])
            ->get();

        if ($feriadosAnoAnterior->isEmpty()) {
            throw new \Exception("Não existem feriados cadastrados para o ano {$anoAnterior}.");
        }

        // Busca feriados já existentes no ano destino
        $existentes = Feriado::whereNull('inativo')
            ->whereRaw("EXTRACT(YEAR FROM data) = ?", [$ano])
            ->get();

        // Consulta BrasilAPI ANTES de duplicar para já usar datas corretas dos móveis
        $datasMoveisBrasilApi = [];
        $moveisAtualizados = [];
        try {
            $response = Http::timeout(10)->get("https://brasilapi.com.br/api/feriados/v1/{$ano}");

            if ($response->successful()) {
                foreach ($response->json() as $feriadoApi) {
                    if (static::isFeriadoMovel($feriadoApi['name'])) {
                        $datasMoveisBrasilApi[] = $feriadoApi;
                    }
                }
            }
        } catch (\Exception $e) {
            // Se a BrasilAPI falhar, mantém as datas do ano anterior ajustadas
        }

        // Marca quais existentes foram reconhecidos
        $existentesReconhecidos = [];

        // Duplica feriados, pulando os que já têm correspondente no ano destino
        $duplicados = [];
        foreach ($feriadosAnoAnterior as $feriado) {
            $movel = static::isFeriadoMovel($feriado->feriado);
            $mesDiaEsperado = Carbon::parse($feriado->data)->format('m-d');

            // Busca correspondente no ano destino
            $encontrado = null;
            foreach ($existentes as $existente) {
                if ($movel) {
                    if (static::nomesCorrespondem($feriado->feriado, $existente->feriado)) {
                        $encontrado = $existente;
                        break;
                    }
                } else {
                    if ($existente->data->format('m-d') === $mesDiaEsperado) {
                        $encontrado = $existente;
                        break;
                    }
                }
            }

            if ($encontrado) {
                $existentesReconhecidos[] = $encontrado->codferiado;
                continue;
            }

            // Calcula data final: para móveis, usa BrasilAPI se disponível
            $novaData = Carbon::parse($feriado->data)->year($ano)->format('Y-m-d');
            if ($movel) {
                $apiMatch = static::encontrarCorrespondente($feriado->feriado, $datasMoveisBrasilApi, 'name');
                if ($apiMatch) {
                    $dataOriginal = $novaData;
                    $novaData = $apiMatch['date'];
                    if ($dataOriginal !== $novaData) {
                        $moveisAtualizados[] = [
                            'feriado' => $feriado->feriado,
                            'data_anterior' => $dataOriginal,
                            'data_nova' => $novaData,
                        ];
                    }
                }
            }

            // firstOrCreate evita duplicatas por segurança
            $novo = Feriado::firstOrCreate(
                ['data' => $novaData, 'feriado' => $feriado->feriado],
            );
            $duplicados[] = $novo;
        }

        // Preexistentes: feriados do ano destino que não foram reconhecidos
        $preexistentes = [];
        foreach ($existentes as $existente) {
            if (!in_array($existente->codferiado, $existentesReconhecidos)) {
                $preexistentes[] = [
                    'codferiado' => $existente->codferiado,
                    'feriado' => $existente->feriado,
                    'data' => $existente->data->format('Y-m-d'),
                ];
            }
        }

        return [
            'ano' => $ano,
            'duplicados' => count($duplicados),
            'moveis_atualizados' => count($moveisAtualizados),
            'moveis' => $moveisAtualizados,
            'preexistentes' => $preexistentes,
        ];
    }

    protected static function isFeriadoMovel(string $nome): bool
    {
        $nomeLower = mb_strtolower($nome);
        foreach (static::$feriadosMoveis as $palavra) {
            if (str_contains($nomeLower, $palavra)) {
                return true;
            }
        }
        return false;
    }

    protected static function nomesCorrespondem(string $nomeA, string $nomeB): bool
    {
        $a = mb_strtolower(trim($nomeA));
        $b = mb_strtolower(trim($nomeB));

        // Match direto
        if ($a === $b) {
            return true;
        }

        // Match por palavras-chave de feriados móveis
        foreach (static::$feriadosMoveis as $palavra) {
            if (str_contains($a, $palavra) && str_contains($b, $palavra)) {
                return true;
            }
        }

        // "paixão" e "sexta-feira santa" são o mesmo feriado
        $sinonimos = [['paixão', 'sexta-feira santa', 'sexta feira santa']];
        foreach ($sinonimos as $grupo) {
            $aMatch = false;
            $bMatch = false;
            foreach ($grupo as $sin) {
                if (str_contains($a, $sin)) $aMatch = true;
                if (str_contains($b, $sin)) $bMatch = true;
            }
            if ($aMatch && $bMatch) {
                return true;
            }
        }

        return false;
    }

    protected static function encontrarCorrespondente(string $nome, array $lista, string $campoNome = 'feriado'): mixed
    {
        foreach ($lista as $item) {
            $nomeItem = is_array($item) ? ($item[$campoNome] ?? '') : $item->$campoNome;
            if (static::nomesCorrespondem($nome, $nomeItem)) {
                return $item;
            }
        }
        return null;
    }
}
