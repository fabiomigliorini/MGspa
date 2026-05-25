<?php

namespace App\Passport;

use Laravel\Passport\Bridge\ClientRepository as BaseClientRepository;

/**
 * Aceita client_secret em plain text OU bcrypt.
 *
 * O Passport 13 sempre faz `Hash::check($plain, $stored)` — que retorna
 * false se `$stored` não for hash bcrypt. As tabelas oauth_clients do
 * banco mgsis estão com secrets em PLAIN TEXT (40 chars), porque foram
 * geradas pelo Passport 10/12 antigos sem hash. MGsis/MGLara/MGAuth
 * mandam o secret plain e esperam comparação literal.
 *
 * Esta implementação:
 *   1. Se o stored começa com $2y$ ou $2a$ -> trata como bcrypt (Hash::check)
 *   2. Senão -> comparação constante-no-tempo (hash_equals) com plain
 *
 * Ficamos compatíveis com:
 *   - secrets antigos plain (já no banco) — MGsis/MGLara/Negocios funcionam
 *   - secrets novos hashados (caso alguém rode passport:hash no futuro)
 */
class PlainOrHashedClientRepository extends BaseClientRepository
{
    public function validateClient(string $clientIdentifier, ?string $clientSecret, ?string $grantType): bool
    {
        $record = $this->clients->findActive($clientIdentifier);

        if (! $record || empty($clientSecret)) {
            return false;
        }

        $stored = $record->getAttributes()['secret'] ?? null;
        if ($stored === null || $stored === '') {
            return false;
        }

        // bcrypt hash?
        if (str_starts_with($stored, '$2y$') || str_starts_with($stored, '$2a$') || str_starts_with($stored, '$2b$')) {
            return $this->hasher->check($clientSecret, $stored);
        }

        // plain text legacy — comparação em tempo constante
        return hash_equals($stored, $clientSecret);
    }
}
