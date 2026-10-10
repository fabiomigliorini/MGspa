<script setup>
// Uma ocorrência do livro (TASK-205) como linha de q-list: o que aconteceu, quem, onde, quando,
// o valor e a conferência. É a mesma em toda tela que mostra ocorrência. Do negócio, o botão abre
// o negócio em outra aba; outras ações de quem usa entram pelo slot `acoes`, embaixo do valor.
import { formataNumero, formataTimestamp } from '@components/formatters'
import { visual } from '@components/ocorrencia.js'

defineProps({
  ocorrencia: { type: Object, required: true },
})

const urlNegocio = (codnegocio) => `${process.env.NEGOCIOS_URL}/negocio/${codnegocio}`
</script>

<template>
  <q-item>
    <q-item-section avatar top>
      <q-avatar
        :icon="visual(ocorrencia).icone"
        :color="visual(ocorrencia).cor"
        text-color="white"
      />
    </q-item-section>

    <q-item-section>
      <q-item-label class="text-weight-medium">{{ ocorrencia.descricao }}</q-item-label>
      <q-item-label caption>
        {{ ocorrencia.tipodescricao }}
        <template v-if="ocorrencia.justificativa"> · “{{ ocorrencia.justificativa }}”</template>
      </q-item-label>
      <q-item-label caption>
        {{ ocorrencia.usuario ?? 'sem usuário' }}
        <template v-if="ocorrencia.pdv"> · {{ ocorrencia.pdv }}</template>
        · {{ ocorrencia.filial }} · {{ formataTimestamp(ocorrencia.criacao) }}
      </q-item-label>
      <q-item-label v-if="ocorrencia.conferencia" caption class="text-green-8">
        <q-icon name="task_alt" />
        Conferida por {{ ocorrencia.usuarioconferencia }} em
        {{ formataTimestamp(ocorrencia.conferencia) }}
        <template v-if="ocorrencia.observacao"> · “{{ ocorrencia.observacao }}”</template>
      </q-item-label>
    </q-item-section>

    <q-item-section side top>
      <q-item-label class="text-weight-bold text-grey-9">
        R$ {{ formataNumero(ocorrencia.valor) }}
      </q-item-label>
      <div v-if="ocorrencia.codnegocio || $slots.acoes" class="q-mt-xs">
        <q-btn
          v-if="ocorrencia.codnegocio"
          flat
          round
          size="sm"
          color="grey-7"
          icon="open_in_new"
          :href="urlNegocio(ocorrencia.codnegocio)"
          target="_blank"
          @click.stop
        >
          <q-tooltip>Abrir o negócio #{{ ocorrencia.codnegocio }}</q-tooltip>
        </q-btn>
        <slot name="acoes" />
      </div>
    </q-item-section>
  </q-item>
</template>
