<script setup>
import { computed } from 'vue'
import { formataCodigo } from '@components/formatters'
import { DESTINO, PRIORIDADE, statusDe, trajeto, legenda } from 'stores/requisicao'

// A linha da requisição nas listas (painel e listagem, TASK-261): o ícone do destino na coluna
// dos ícones, no mesmo recuo do título do card, e colunas fixas para tudo ficar alinhado de uma
// linha para a outra: código (com quem pediu embaixo), lojas (com a legenda embaixo), situação,
// prioridade e quando. A situação é ícone e texto na cor dela, e a prioridade, etiqueta, para as
// duas não se confundirem quando a cor coincide. No celular, situação, prioridade e quando descem
// para uma linha própria, e a situação perde o ícone para o "Saiu para entrega" caber.
const props = defineProps({
  requisicao: { type: Object, required: true },
  // já formatado: há quanto tempo no painel, data e hora na listagem
  quando: { type: String, default: '' },
})

const status = computed(() => statusDe(props.requisicao))
const prioridade = computed(() => PRIORIDADE[props.requisicao.prioridade] || PRIORIDADE[2])
</script>

<template>
  <q-item clickable :to="`/requisicao/${requisicao.codrequisicao}`" class="q-py-xs">
    <q-item-section avatar>
      <q-icon :name="DESTINO[requisicao.destino].icone" :color="DESTINO[requisicao.destino].cor" />
    </q-item-section>
    <q-item-section>
      <div class="row items-start full-width">
        <div class="col-4 col-sm-2 q-pr-sm">
          <div class="text-body2 text-weight-medium ellipsis">
            {{ formataCodigo(requisicao.codrequisicao) }}
          </div>
          <div class="text-caption text-grey-7 ellipsis">{{ requisicao.usuariocriacao }}</div>
        </div>
        <div class="col-8 col-sm-3 q-pr-sm">
          <div class="text-body2 ellipsis">{{ trajeto(requisicao) }}</div>
          <div class="text-caption text-grey-7 ellipsis">{{ legenda(requisicao) }}</div>
        </div>
        <div class="col-5 col-sm-3 q-pr-sm text-body2 ellipsis" :class="`text-${status.cor}`">
          <q-icon :name="status.icone" size="xs" class="q-mr-xs gt-xs" />{{ status.label }}
        </div>
        <div class="col-3 col-sm-2 q-pr-sm text-body2">
          <q-badge :color="prioridade.cor" :label="prioridade.label" />
        </div>
        <div class="col-4 col-sm-2 text-body2 text-right ellipsis">{{ quando }}</div>
      </div>
    </q-item-section>
  </q-item>
</template>
