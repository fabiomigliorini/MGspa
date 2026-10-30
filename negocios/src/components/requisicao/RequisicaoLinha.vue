<script setup>
import { formataCodigo } from '@components/formatters'
import { DESTINO, trajeto, legenda } from 'stores/requisicao'

// A linha da requisição nas listas (painel e listagem, TASK-261): o ícone do destino na coluna
// dos ícones, no mesmo recuo dos títulos do painel, e colunas fixas para tudo ficar alinhado de
// uma linha para a outra: código, lojas (com a legenda embaixo), quem pediu, a marca e quando.
// No celular, o usuário, a marca e o quando descem para uma linha própria.
defineProps({
  requisicao: { type: Object, required: true },
  // { label, cor }: a prioridade no painel, a situação na listagem
  marca: { type: Object, required: true },
  // já formatado: há quanto tempo no painel, data e hora na listagem
  quando: { type: String, default: '' },
})
</script>

<template>
  <q-item clickable :to="`/requisicao/${requisicao.codrequisicao}`" class="q-py-xs">
    <q-item-section avatar>
      <q-icon :name="DESTINO[requisicao.destino].icone" :color="DESTINO[requisicao.destino].cor" />
    </q-item-section>
    <q-item-section>
      <div class="row items-start full-width">
        <div class="col-4 col-sm-2 q-pr-sm text-body2 text-weight-medium ellipsis">
          {{ formataCodigo(requisicao.codrequisicao) }}
        </div>
        <div class="col-8 col-sm-4 q-pr-sm">
          <div class="text-body2 ellipsis">{{ trajeto(requisicao) }}</div>
          <div class="text-caption text-grey-7 ellipsis">{{ legenda(requisicao) }}</div>
        </div>
        <div class="col-4 col-sm-2 q-pr-sm text-body2 text-grey-8 ellipsis">
          {{ requisicao.usuariocriacao }}
        </div>
        <div class="col-4 col-sm-2 q-pr-sm text-body2">
          <q-badge :color="marca.cor" :label="marca.label" />
        </div>
        <div class="col-4 col-sm-2 text-caption text-grey-7 text-right ellipsis">
          {{ quando }}
        </div>
      </div>
    </q-item-section>
  </q-item>
</template>
