<script setup>
import { ref, onMounted, watch } from "vue";
import { wooStore } from "src/stores/woo";
import { formataNumero } from "src/utils/formatador";
import { Notify, debounce } from "quasar";
import moment from "moment/min/moment-with-locales";
import WooInfoModal from "src/components/modals/WooInfoModal.vue";
moment.locale("pt-br");

const sWoo = wooStore();

// Função para filtrar pedidos por status
const filtrarPedidos = (status) => {
  return sWoo.pedidos
    .filter((p) => p.status === status)
    .sort((a, b) => b.id - a.id); // Já aproveitando a ordenação que você pediu
};

onMounted(() => {
  sWoo.getPedidosPainel();
});

// controle do modal
const showPedidoModal = ref(false);

// Reprocessar o pedido
function openPedido(p) {
  sWoo.pedido = p;
  showPedidoModal.value = true;
}

const buscarNovos = async () => {
  const ret = await sWoo.buscarNovos();
  Notify.create({
    type: "positive",
    message: ret + " Pedido(s) encontrados!",
    timeout: 3000, // 3 segundos
    actions: [{ icon: "close", color: "white" }],
  });
  sWoo.getPedidosPainel();
};

const buscarPorAlteracao = async () => {
  const ret = await sWoo.buscarPorAlteracao();
  Notify.create({
    type: "positive",
    message: ret + " Pedido(s) encontrados!",
    timeout: 3000, // 3 segundos
    actions: [{ icon: "close", color: "white" }],
  });
  sWoo.getPedidosPainel();
};

const refresh = async () => {
  const ret = await sWoo.getPedidosPainel();
  Notify.create({
    type: "positive",
    message: ret + " Pedido(s) encontrados!",
    timeout: 3000, // 3 segundos
    actions: [{ icon: "close", color: "white" }],
  });
};
</script>

<template>
  <q-page class="q-pa-md bg-grey-4 flex no-wrap">
    <div
      v-if="sWoo.pedidos.length == 0"
      class="absolute-center text-grey text-center"
    >
      <q-icon name="do_not_disturb" size="300px" />
      <h3>Nenhum registro localizado!</h3>
    </div>

    <div v-else>
      <div class="row q-mb-sm">
        <q-btn
          flat
          dense
          size="small"
          color="primary"
          icon="mdi-cart-plus"
          @click="buscarPorAlteracao(pedido)"
        >
          <q-tooltip>
            Busca novos pedidos no Woo pela data de alteração.
          </q-tooltip>
        </q-btn>

        <q-btn
          flat
          dense
          size="small"
          color="primary"
          icon="mdi-cart-arrow-down"
          @click="buscarNovos(pedido)"
        >
          <q-tooltip>
            Busca pedidos no Woo pelo status de Pendente/Processando/Aguardando.
          </q-tooltip>
        </q-btn>

        <q-btn
          flat
          dense
          size="small"
          color="primary"
          icon="mdi-refresh"
          @click="refresh()"
        >
          <q-tooltip> Atualizar listagem </q-tooltip>
        </q-btn>

        <q-btn
          flat
          dense
          size="small"
          color="primary"
          icon="mdi-magnify"
          to="/woo"
        >
          <q-tooltip> Pesquisar um pedido </q-tooltip>
        </q-btn>
      </div>
      <div class="row q-col-gutter-md">
        <div
          v-for="col in sWoo.colunasKanban()"
          :key="col.value"
          class="col-xs-12 col-sm-4 col-md-3 col-lg-2"
        >
          <q-item class="rounded-borders shadow-2" :class="col.cor">
            <q-item-section>
              <q-item-label
                class="text-bold text-uppercase"
                style="height: 50px"
              >
                {{ col.label }}
              </q-item-label>
            </q-item-section>
            <q-item-section side>
              <q-badge
                color="white"
                text-color="black"
                :label="filtrarPedidos(col.value).length"
              />
            </q-item-section>
          </q-item>

          <q-scroll-area
            class="col shadow-1 bg-grey-3 q-pa-sm rounded-borders"
            style="height: 60vh; max-height: 60vw"
          >
            <div class="q-gutter-y-sm">
              <template
                v-for="pedido in filtrarPedidos(col.value)"
                :key="pedido.codwoopedido"
              >
                <q-card flat bordered class="q-hoverable cursor-pointer">
                  <q-card-section class="q-pa-sm" @click="openPedido(pedido)">
                    <div class="row justify-between items-start">
                      <div class="text-bold text-grey-9 text-caption">
                        #{{ pedido.id }}
                      </div>
                      <div class="text-caption text-weight-bolder text-primary">
                        R$ {{ formataNumero(pedido.valortotal, 2) }}
                      </div>
                    </div>
                    <div class="text-subtitle2 q-mt-xs ellipsis">
                      {{ pedido.nome }}
                    </div>
                    <div class="text-caption text-grey-7">
                      {{ moment(pedido.alteracaowoo).fromNow() }}
                    </div>
                  </q-card-section>
                </q-card>
              </template>
            </div>
          </q-scroll-area>
        </div>
      </div>
    </div>

    <woo-info-modal v-model="showPedidoModal" />
  </q-page>
</template>
