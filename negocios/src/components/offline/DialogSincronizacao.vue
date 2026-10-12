<script setup>
import { formataNumero } from '@components/formatters'
import { sincronizacaoStore } from 'stores/sincronizacao'

// Janela "Sincronizar...": abre com sSinc.importacao.dialog = true
// (botao do cabecalho do PDV e menu do quiosque).
const sSinc = sincronizacaoStore()
</script>

<template>
  <q-dialog v-model="sSinc.importacao.dialog">
    <q-card class="q-pa-md">
      <q-card-section>
        <div class="text-h4 text-center">Sincronizar...</div>

        <!-- <div class="text-h4 text-center">Sincronizando...</div> -->
        <div class="flex flex-center q-my-md">
          <q-circular-progress
            show-value
            :indeterminate="sSinc.importacao.totalRegistros == 0 && sSinc.importacao.rodando"
            rounded
            size="200px"
            color="secondary"
            class="q-ma-md"
            center-color="green-1"
            :value="sSinc.importacao.progresso"
          >
            {{ sSinc.importacao.progresso }}%
          </q-circular-progress>
        </div>
        <div class="text-center text-weight-bold">
          {{ formataNumero(sSinc.importacao.totalSincronizados, 0) }}
          /
          {{ formataNumero(sSinc.importacao.totalRegistros, 0) }}
          {{ sSinc.labelSincronizacao }}
        </div>
        <div class="text-center text-grey">{{ sSinc.importacao.tempoTotal }} Segundos</div>
        <div class="q-pa-md flex flex-center">
          <q-toggle
            v-model="sSinc.sincronizacao.config"
            label="Configurações"
            :disable="sSinc.importacao.rodando"
          />
          <q-toggle
            v-model="sSinc.sincronizacao.pessoa"
            label="Pessoas"
            :disable="sSinc.importacao.rodando"
          />
          <q-toggle
            v-model="sSinc.sincronizacao.produto"
            label="Produtos"
            :disable="sSinc.importacao.rodando"
          />
          <q-toggle
            v-model="sSinc.sincronizacao.prancheta"
            label="Prancheta"
            :disable="sSinc.importacao.rodando"
          />
          <q-toggle
            v-model="sSinc.sincronizacao.valeModelo"
            label="Modelos de Vale"
            :disable="sSinc.importacao.rodando"
          />
        </div>
      </q-card-section>
      <q-card-actions align="center">
        <q-btn
          flat
          label="Cancelar"
          color="grey-8"
          @click="sSinc.abortarSincronizacao()"
          tabindex="-1"
        />
        <q-btn
          flat
          label="Sincronizar"
          color="primary"
          @click="sSinc.sincronizar()"
          autofocus
          :disable="sSinc.importacao.rodando"
        />
      </q-card-actions>
    </q-card>
  </q-dialog>
</template>
