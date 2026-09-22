<?php

namespace Mg\Grao;

use Mg\MgService;

/**
 * Helpers de apresentacao do ponto (origem/destino) da carga.
 *
 * O rotulo existe aqui porque as telas ONLINE (listagem de romaneios, ficha e
 * relatorio PDF) nao tem o cache Dexie do patio. No app offline quem monta
 * "Talhao 12 - TMG 7262" e a store (agro/src/stores/carga.js, rotuloPonto),
 * a partir das tabelas locais; sem esse cache o front so teria o codigo cru.
 * As duas implementacoes precisam bater — se mudar o formato aqui, mudar la.
 */
class CargaPontoService extends MgService
{
    /**
     * Nome legivel da conta apontada pelo ponto, conforme o `contatipo`:
     *
     *   PLANTIO  "Talhao 12 - TMG 7262"
     *   UNIDADE  "Armazem Sede"
     *   CONTRATO "CT-0042 - Bunge"
     *
     * Fallback pro codigo quando a relacao nao veio carregada ou o cadastro
     * sumiu — nunca devolve string vazia num ponto que existe.
     */
    public static function rotulo(CargaPonto $p): ?string
    {
        return match ($p->contatipo) {
            'PLANTIO' => static::rotuloPlantio($p),
            'UNIDADE' => $p->UnidadeArmazenadora->unidadearmazenadora
                ?? ($p->codunidadearmazenadora ? "Unidade {$p->codunidadearmazenadora}" : null),
            'CONTRATO' => static::rotuloContrato($p),
            default => null,
        };
    }

    /**
     * `Plantio.talhao` e COLUNA (o nome que o produtor deu ao talhao naquela
     * safra), nao a relacao `Talhao`. O Eloquent resolve o atributo antes da
     * relacao, entao $plantio->talhao e sempre o texto — cuidado ao mexer.
     */
    protected static function rotuloPlantio(CargaPonto $p): ?string
    {
        if (!$p->codplantio) {
            return null;
        }
        $plantio = $p->Plantio;
        if (!$plantio) {
            return "Talhao {$p->codplantio}";
        }
        $nome = $plantio->talhao ?: "Talhao {$p->codplantio}";
        $variedade = $plantio->Variedade->variedade ?? null;
        return $variedade ? "{$nome} — {$variedade}" : $nome;
    }

    protected static function rotuloContrato(CargaPonto $p): ?string
    {
        if (!$p->codcontrato) {
            return null;
        }
        $contrato = $p->Contrato;
        if (!$contrato) {
            return "Contrato {$p->codcontrato}";
        }
        $nome = $contrato->contrato ?: "Contrato {$p->codcontrato}";
        $pessoa = $contrato->Pessoa;
        $quem = $pessoa ? ($pessoa->fantasia ?: $pessoa->pessoa) : null;
        return $quem ? "{$nome} — {$quem}" : $nome;
    }
}
