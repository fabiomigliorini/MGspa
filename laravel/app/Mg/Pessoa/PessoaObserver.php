<?php

/**
 * Observer para o model Pessoa.
 *
 * Registrar em AppServiceProvider ou EventServiceProvider:
 * Pessoa::observe(PessoaObserver::class);
 */

namespace Mg\Pessoa;

use Mg\Colaborador\ColaboradorObserver;

class PessoaObserver
{
    /**
     * Após atualizar uma Pessoa, propaga a sincronização
     * de eventos de calendário para Colaboradores e Dependentes vinculados.
     * Reage apenas a mudanças no campo nascimento.
     */
    public function updated(Pessoa $pessoa): void
    {
        // Só reage se o nascimento mudou
        if (!array_key_exists('nascimento', $pessoa->getChanges())) {
            return;
        }

        // Instancia observers responsáveis pela sincronização
        $colaboradorObserver = new ColaboradorObserver();
        $dependenteObserver = new DependenteObserver();

        // Sincronizar eventos dos Colaboradores vinculados
        foreach ($pessoa->ColaboradorS as $colaborador) {
            $colaboradorObserver->created($colaborador);
        }

        // Sincronizar eventos dos Dependentes vinculados
        foreach ($pessoa->DependenteS as $dependente) {
            $dependenteObserver->created($dependente);
        }
    }
}
