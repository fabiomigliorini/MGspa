<script setup>
import { sincronizacaoStore } from "stores/sincronizacao";
const sSinc = sincronizacaoStore();
</script>
<template>
  <!-- Sincronizacao  -->
  <q-dialog v-model="sSinc.importacao.rodando" seamless position="bottom">
    <q-card style="width: 350px">
      <q-linear-progress :value="sSinc.importacao.progresso" color="pink" />
      <q-card-section class="row items-center no-wrap">
        <div>
          <div class="text-weight-bold">
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
          <div class="text-grey">
            {{ sSinc.importacao.tempoTotal }} Segundos
          </div>
        </div>

        <q-space />

        <q-btn
          flat
          round
          icon="close"
          v-close-popup
          @click="sSinc.abortarSincronizacao()"
        />
      </q-card-section>
    </q-card>
  </q-dialog>
</template>
