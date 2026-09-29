<?php

namespace AgroBateria\Estresse;

use AgroBateria\Motor;
use AgroBateria\Resposta;

/**
 * Um aparelho virtual do pátio: um caminhão de cada vez, uma gravação por bloco
 * (o mesmo payload do app), e o pull do ciclo de sincronização de tempos em
 * tempos. Sem espera entre as ações: é o dia de colheita comprimido.
 *
 * Quando o servidor recusa uma gravação, o aparelho larga aquele caminhão (no
 * app ele fica marcado com erro) e segue para o próximo.
 */
final class Aparelho
{
    public int $caminhoes = 0;
    /** @var array<string, array{pbt: float, tara: float, sentido: string}> uuid => o que o aparelho gravou */
    public array $finalizadas = [];
    /** @var array<int, int> status => quantas */
    public array $recusas = [];
    /** @var string[] amostra das mensagens de recusa */
    public array $mensagens = [];

    private array $fila = [];
    private \Random\Randomizer $rng;

    public function __construct(public int $n, private Estresse $e, int $semente)
    {
        $this->rng = new \Random\Randomizer(new \Random\Engine\Mt19937($semente));
    }

    public function avancar(Motor $motor): void
    {
        if (!$this->fila) {
            if (!$this->e->podeComecar($this)) {
                return;
            }
            $this->fila = $this->e->caminhao($this, $this->rng);
            $this->caminhoes++;
        }
        $passo = array_shift($this->fila);

        if ($passo[0] === 'GET') {
            $motor->enviar('GET', $passo[1], null, $passo[2], function (Resposta $r) use ($motor) {
                $this->anotar($r);
                $this->avancar($motor);
            });
            return;
        }

        $estado = $passo[1];
        $motor->enviar('POST', 'v1/carga/sincronizar', $this->e->payload($estado), 'POST v1/carga/sincronizar',
            function (Resposta $r) use ($motor, $estado) {
                $this->anotar($r);
                if (!$r->ok()) {
                    $this->fila = array_values(array_filter($this->fila, fn ($p) => $p[0] === 'GET'));
                } elseif ($estado['etapa'] === 'FINALIZADO') {
                    $this->finalizadas[$estado['uuid']] = ['pbt' => $estado['pbt'], 'tara' => $estado['tara'], 'sentido' => $estado['sentido']];
                }
                $this->avancar($motor);
            });
    }

    private function anotar(Resposta $r): void
    {
        if ($r->ok()) {
            return;
        }
        $this->recusas[$r->status] = ($this->recusas[$r->status] ?? 0) + 1;
        $msg = $r->status . ' ' . mb_substr($r->mensagem(), 0, 90);
        if (count($this->mensagens) < 5 && !in_array($msg, $this->mensagens, true)) {
            $this->mensagens[] = $msg;
        }
    }
}
