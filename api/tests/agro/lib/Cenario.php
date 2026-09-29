<?php

namespace AgroBateria;

/**
 * Base das camadas de cenários: cada caso roda isolado (exceção vira ERRO e o
 * resto segue) e as comparações com a regra decidida saem num detalhe legível.
 */
abstract class Cenario
{
    public function __construct(protected Patio $p, protected Relatorio $r, protected array $opc = [])
    {
    }

    abstract public function rodar(): void;

    protected function m(): Massa
    {
        return $this->p->m;
    }

    protected function api(): Api
    {
        return $this->p->api;
    }

    protected function caso(string $id, string $titulo, callable $fn): void
    {
        try {
            $fn();
        } catch (\Throwable $e) {
            $onde = basename($e->getFile()) . ':' . $e->getLine();
            $this->r->erro($id, $titulo, get_class($e) . ": " . mb_substr($e->getMessage(), 0, 240) . " ({$onde})");
        }
    }

    /** Bruto/desconto/líquido gravados × referência em kg (e o que a fórmula dá em gramas). */
    protected function difPesos(object $b, array $refKg, ?array $refG = null): array
    {
        $dif = [];
        foreach (['bruto', 'desconto', 'liquido'] as $campo) {
            $obtido = (float) $b->{$campo};
            if (abs($obtido - $refKg[$campo]) > 0.0005) {
                $dif[] = "{$campo} " . Relatorio::kg($obtido) . ' (regra: ' . Relatorio::kg($refKg[$campo]) . ')';
            }
        }
        if ($dif && $refG !== null) {
            $formula = abs((float) $b->liquido - $refG['liquido'] / 1000) <= 0.001 * max(1, count($refG['itens']));
            $dif[] = $formula ? 'a fórmula confere em gramas: falta só o kg inteiro' : 'nem em gramas confere (' . Relatorio::kg($refG['liquido'] / 1000) . ')';
        }
        return $dif;
    }

    /** Extrato automático gravado × linhas que a regra manda gerar, por papel/tipo/conta. */
    protected function difExtrato(object $b, array $esperado): array
    {
        $chave = fn ($papel, $tipo, $conta) => "{$tipo} {$papel} {$conta}";
        $obtido = [];
        foreach ($b->movimentos as $mv) {
            $k = $chave($mv->papel, $mv->contatipo, $mv->codplantio ?? $mv->codunidadearmazenadora ?? $mv->codcontrato);
            $obtido[$k] = ($obtido[$k] ?? 0) + (float) $mv->liquido;
        }
        $esp = [];
        foreach ($esperado as $e) {
            $k = $chave($e['papel'], $e['contatipo'], $e['conta']);
            $esp[$k] = ($esp[$k] ?? 0) + $e['liquido'];
        }
        $dif = [];
        foreach (array_keys($esp + $obtido) as $k) {
            $o = $obtido[$k] ?? 0.0;
            $e = $esp[$k] ?? 0.0;
            if (abs($o - $e) > 0.0005) {
                $dif[] = 'extrato ' . strtolower($k) . ': ' . Relatorio::kg($o) . ' (regra: ' . Relatorio::kg($e) . ')';
            }
        }
        return $dif;
    }

    /** OK sem diferença; FALHA com as 4 primeiras no detalhe. */
    protected function resultado(string $id, string $titulo, array $dif, string $okDetalhe = ''): void
    {
        if (!$dif) {
            $this->r->ok($id, $titulo, $okDetalhe);
            return;
        }
        $mais = count($dif) > 4 ? ' (+' . (count($dif) - 4) . ')' : '';
        $this->r->falha($id, $titulo, implode(' · ', array_slice($dif, 0, 4)) . $mais);
    }

    protected function kg(null|int|float|string $v): string
    {
        return Relatorio::kg($v);
    }
}
