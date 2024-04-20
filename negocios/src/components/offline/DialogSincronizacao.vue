<script setup>
import { sincronizacaoStore } from "stores/sincronizacao";
const sSinc = sincronizacaoStore();
</script>
<template>
  <!-- Sincronizacao  -->
  <q-dialog v-model="sSinc.importacao.rodando" persistent>
    <q-card class="q-pa-md">
      <q-card-section>
        <div class="text-h4 text-center">Sincronizando...</div>
        <div class="flex flex-center q-my-md">
          <q-circular-progress
            show-value
            :indeterminate="sSinc.importacao.totalRegistros == 0"
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
          {{
            new Intl.NumberFormat("pt-BR").format(
              sSinc.importacao.totalSincronizados
            )
          }}
          /
          {{
            new Intl.NumberFormat("pt-BR").format(
              sSinc.importacao.totalRegistros
            )
          }}
          {{ sSinc.labelSincronizacao }}
        </div>
        <div class="text-center text-grey">
          {{ sSinc.importacao.tempoTotal }} Segundos
        </div>
      </q-card-section>
      <q-card-actions align="center">
        <q-btn
          flat
          label="Cancelar"
          color="negative"
          v-close-popup
          @click="sSinc.abortarSincronizacao()"
          tabindex="-1"
        />
      </q-card-actions>
    </q-card>
  </q-dialog>
</template>
