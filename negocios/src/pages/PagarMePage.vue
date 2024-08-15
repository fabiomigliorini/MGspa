<script setup>
import { onMounted, ref } from "vue";
import { Notify } from "quasar";
import { pagarMeStore } from "src/stores/pagar-me";
import moment from "moment/min/moment-with-locales";
moment.locale("pt-br");

const sPagarMe = pagarMeStore();
const rodando = ref(false);

const consultar = async (ped) => {
  rodando.value = true;
  try {
    sPagarMe.pedido = ped;
    await sPagarMe.consultarPedido();
    await sPagarMe.consultarPedidosPendentes();
  } catch (error) {
    Notify.create({
      type: "negative",
      message: error.response.data.message,
      timeout: 3000, // 3 segundos
      actions: [{ icon: "close", color: "white" }],
    });
  }
  rodando.value = false;
};

const cancelar = async (ped) => {
  rodando.value = true;
  try {
    sPagarMe.pedido = ped;
    await sPagarMe.cancelarPedido();
    await sPagarMe.consultarPedidosPendentes();
  } catch (error) {
    Notify.create({
      type: "negative",
      message: error.response.data.message,
      timeout: 3000, // 3 segundos
      actions: [{ icon: "close", color: "white" }],
    });
  }
  rodando.value = false;
};

const atualizar = async () => {
  rodando.value = true;
  try {
    await sPagarMe.consultarPedidosPendentes();
  } catch (error) {
    Notify.create({
      type: "negative",
      message: error.response.data.message,
      timeout: 3000, // 3 segundos
      actions: [{ icon: "close", color: "white" }],
    });
  }
  rodando.value = false;
};

const importar = async () => {
  rodando.value = true;
  try {
    await sPagarMe.importarPedidosPendentes();
  } catch (error) {
    Notify.create({
      type: "negative",
      message: error.response.data.message,
      timeout: 3000, // 3 segundos
      actions: [{ icon: "close", color: "white" }],
    });
  }
  rodando.value = false;
};

onMounted(() => {
  atualizar();
});
</script>
<template>
  <q-page>
    <q-page-sticky position="bottom-right" :offset="[18, 18]">
      <q-btn
        @click="atualizar()"
        fab
        icon="refresh"
        color="secondary"
        :loading="rodando"
      >
        <q-tooltip class="" :offset="[10, 10]"> Atualizar Listagem </q-tooltip>
      </q-btn>
      &nbsp;
      <q-btn
        @click="importar()"
        fab
        icon="cloud_sync"
        color="accent"
        :loading="rodando"
      >
        <q-tooltip class="" :offset="[10, 10]">
          Buscar Listagem da PagarMe
        </q-tooltip>
      </q-btn>
    </q-page-sticky>
    <div class="row q-pa-md q-pb-xl justify-center">
      <q-list bordered class="rounded-borders" style="max-width: 650px">
        <template
          v-for="ped in sPagarMe.pedidosPendentes"
          :key="ped.codpagarmepedido"
        >
          <q-item>
            <q-item-section avatar>
              <q-avatar color="primary" text-color="white">
                {{ ped.apelido.charAt(0) }}
              </q-avatar>
            </q-item-section>

            <q-item-section>
              <q-item-label>
                R$
                <span class="text-weight-bold">
                  {{
                    new Intl.NumberFormat("pt-BR", {
                      style: "decimal",
                      minimumFractionDigits: 2,
                      maximumFractionDigits: 2,
                    }).format(ped.valor)
                  }}
                </span>
                {{ ped.tipodescricao }}
                <template v-if="ped.parcelas > 1">
                  {{ ped.parcelas }}
                  parcelas
                </template>
              </q-item-label>
              <q-item-label caption>
                {{ ped.idpedido }}
              </q-item-label>
              <q-item-label caption>
                POS {{ ped.pos }} ({{ ped.codfilial }} - {{ ped.apelido }})
              </q-item-label>
              <q-item-label caption v-if="ped.codnegocio">
                Negócio {{ ped.codnegocio }}
              </q-item-label>
              <q-item-label caption>
                {{ moment(ped.criacao).fromNow() }}
                {{ moment(ped.criacao).format("llll") }}
              </q-item-label>
            </q-item-section>

            <q-item-section side>
              <div class="text-grey-8">
                <q-btn
                  dense
                  size="15px"
                  flat
                  round
                  icon="point_of_sale"
                  :to="'/negocio/' + ped.codnegocio"
                  v-if="ped.codnegocio"
                />
                <q-btn
                  dense
                  size="15px"
                  flat
                  round
                  icon="refresh"
                  @click="consultar(ped)"
                  :loading="rodando"
                />
                <q-btn
                  dense
                  size="15px"
                  flat
                  round
                  icon="cancel"
                  @click="cancelar(ped)"
                  :loading="rodando"
                />
              </div>
            </q-item-section>
          </q-item>
          <q-separator />
        </template>
      </q-list>
    </div>
  </q-page>
</template>
