<script setup>
import { computed } from "vue";
import { sincronizacaoStore } from "stores/sincronizacao";
import moment from "moment";

moment.locale("pt-br");
const sSinc = sincronizacaoStore();

const btnSincronizarColor = computed({
  get() {
    if (!sSinc.ultimaSincronizacao.completa) {
      return "red-4";
    }
    if (
      moment(sSinc.ultimaSincronizacao.completa).isAfter(
        moment().subtract(4, "hours")
      )
    ) {
      return null;
    }
    return "red-4";
  },
});
</script>
<template>
  <!-- Dialog Sincronizacao  -->
  <q-dialog v-model="sSinc.importacao.dialog" persistent>
    <q-card class="q-pa-md">
      <q-card-section>
        <div class="text-h4 text-center">Sincronizar...</div>

        <!-- <div class="text-h4 text-center">Sincronizando...</div> -->
        <div class="flex flex-center q-my-md">
          <q-circular-progress
            show-value
            :indeterminate="
              sSinc.importacao.totalRegistros == 0 && sSinc.importacao.rodando
            "
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
        <div class="q-pa-md">
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
        </div>
      </q-card-section>
      <q-card-actions align="center">
        <q-btn
          flat
          label="Sincronizar"
          color="primary"
          @click="sSinc.sincronizar()"
          autofocus
          :disable="sSinc.importacao.rodando"
        />
        <q-btn
          flat
          label="Cancelar"
          color="negative"
          @click="sSinc.abortarSincronizacao()"
          tabindex="-1"
          :disable="!sSinc.importacao.rodando"
        />
      </q-card-actions>
    </q-card>
  </q-dialog>
  <q-btn
    round
    dense
    flat
    icon="refresh"
    :loading="sSinc.importacao.rodando"
    :percentage="sSinc.importacao.progresso"
    @click="sSinc.importacao.dialog = true"
    class="q-mr-sm"
    :color="btnSincronizarColor"
  >
    <template v-slot:loading>
      <q-spinner-dots />
    </template>
    <q-tooltip class="bg-accent">
      <template v-if="!sSinc.ultimaSincronizacao.completa">
        Sem Registro de Sincronização
      </template>
      <template v-else>
        Ultima Sincronização
        {{ moment(sSinc.ultimaSincronizacao.completa).fromNow() }}
      </template>
    </q-tooltip>
  </q-btn>
</template>
