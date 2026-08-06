<script setup>
import { useAtualizacaoApp } from './pwaAtualizacao'

// Montado no App.vue de cada app, NÃO num layout: assim a verificação de versão
// roda em toda rota, independente de qual layout a tela usa. Ficava no
// MgUserMenu antes, e telas sem menu de usuário (ex.: /quiosque no negocios)
// nunca verificavam versão nem mostravam a tarja.
const { temAtualizacao, falhouAtualizar, aplicarAtualizacao, forcarAtualizacao } =
  useAtualizacaoApp()
</script>

<template>
  <q-banner v-if="temAtualizacao" inline-actions class="bg-negative text-white fixed-top z-max">
    <template #avatar>
      <q-icon name="system_update" />
    </template>
    <span v-if="falhouAtualizar" class="text-subtitle1 text-weight-bold">
      Não foi possível atualizar automaticamente.
    </span>
    <span v-else class="text-subtitle1 text-weight-bold"> Nova versão disponível. </span>
    <template #action>
      <q-btn
        v-if="falhouAtualizar"
        flat
        label="Forçar atualização"
        icon="system_update"
        @click="forcarAtualizacao"
      />
      <q-btn v-else flat label="Atualizar" icon="refresh" @click="aplicarAtualizacao" />
    </template>
  </q-banner>
</template>
