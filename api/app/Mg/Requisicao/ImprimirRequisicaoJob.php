<?php

namespace Mg\Requisicao;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

// Imprime sozinho as duas vias da requisicao que acabou de nascer (TASK-55, doc-6):
// a do cliente no PDV que registrou e a de separacao no local que atende.
class ImprimirRequisicaoJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $codrequisicao;

    public function __construct(int $codrequisicao)
    {
        $this->codrequisicao = $codrequisicao;
    }

    public function handle(): void
    {
        $req = Requisicao::with(['Pdv.Impressora', 'EstoqueLocalRequisitada.Impressora'])->find($this->codrequisicao);
        if ($req) {
            RequisicaoImpressaoService::imprimirAutomatico($req);
        }
    }
}
