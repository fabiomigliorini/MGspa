<script setup>
// Lançamentos de uma conferência (venda): a linha da listagem única com o
// que a conferência corrige. Com `editavel`, cada linha tem Corrigir e Registro indevido.
import { formataTimestamp, formataNumero } from '@components/formatters'
import { visualPagamento } from '@components/cobranca/pagamento.js'
import LogoPagamento from '@components/cobranca/LogoPagamento.vue'
import MgEmptyState from '@components/MgEmptyState.vue'

defineProps({
  lancamentos: { type: Array, default: () => [] },
  editavel: { type: Boolean, default: false },
})

const emit = defineEmits(['corrigir', 'indevido'])

const COR_ESTADO = { P: 'amber-8', E: 'green-7', C: 'red-7' }

// contrário (cancelamento parcial) entra negativo
const valor = (l) => (l.operacao === 'DB' ? -l.total : l.total)

const legenda = (l) =>
  [
    l.pdv || 'Escritório',
    formataTimestamp(l.transacao, 2),
    l.maquineta,
    l.bandeiradescricao,
    l.parcelas > 1 ? `${l.parcelas}x` : null,
    l.autorizacao ? `aut. ${l.autorizacao}` : null,
  ]
    .filter(Boolean)
    .join(' · ')
</script>

<template>
  <q-list v-if="lancamentos.length" bordered separator class="rounded-borders">
    <q-item
      v-for="l in lancamentos"
      :key="l.codpagamento"
      :class="l.estado === 'C' ? 'bg-red-1' : ''"
      :to="{ name: 'pagamento-detalhe', params: { id: l.codpagamento } }"
    >
      <q-item-section avatar>
        <LogoPagamento v-bind="visualPagamento(l)" size="36px" />
      </q-item-section>
      <q-item-section style="min-width: 0">
        <q-item-label class="ellipsis">
          {{ l.meiodescricao }} · {{ l.documento || l.origemdescricao }}
        </q-item-label>
        <q-item-label caption class="ellipsis">{{ legenda(l) }}</q-item-label>
        <q-item-label v-if="l.estado === 'C'" caption class="text-red-8 ellipsis">
          {{ l.indevido ? 'Registro indevido' : 'Cancelado' }}: {{ l.justificativa }}
        </q-item-label>
        <q-item-label v-if="l.correcoes" caption class="text-orange-9">
          Corrigido na conferência
        </q-item-label>
      </q-item-section>
      <q-item-section side>
        <q-item-label :class="valor(l) < 0 ? 'text-red-8' : 'text-grey-9'">
          {{ formataNumero(valor(l)) }}
        </q-item-label>
        <q-badge :color="COR_ESTADO[l.estado]" :label="l.estadodescricao" />
      </q-item-section>
      <q-item-section v-if="editavel && l.estado !== 'C'" side>
        <div class="row no-wrap">
          <q-btn
            flat
            round
            size="sm"
            color="grey-7"
            icon="edit"
            @click.prevent.stop="emit('corrigir', l)"
          >
            <q-tooltip>Corrigir</q-tooltip>
          </q-btn>
          <q-btn
            v-if="!l.origem || l.origem !== 'T'"
            flat
            round
            size="sm"
            color="grey-7"
            icon="block"
            @click.prevent.stop="emit('indevido', l)"
          >
            <q-tooltip>Registro indevido</q-tooltip>
          </q-btn>
        </div>
      </q-item-section>
    </q-item>
  </q-list>
  <MgEmptyState v-else plain icon="payments">Nenhum lançamento.</MgEmptyState>
</template>
