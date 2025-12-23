<script setup>
import { ref, onMounted, watch } from "vue";
import { wooStore } from "src/stores/woo";
import { formataNumero } from "src/utils/formatador";
import { Notify, debounce } from "quasar";
import moment from "moment/min/moment-with-locales";
import PedidoModal from "src/components/modals/WooInfoModal.vue";
import ChangeStatus from "src/components/modals/WooChangeStatusModal.vue";
moment.locale("pt-br");

const sWoo = wooStore();
const scrollRef = ref(null);

const onLoad = async (index, done) => {
  await sWoo.getPedidosPaginacao();
  if (sWoo.paginacao.current_page >= sWoo.paginacao.last_page) {
    done(true);
  } else {
    done(false);
  }
};

const inicializa = debounce(async () => {
  try {
    await sWoo.getPedidos();
    scrollRef.value.reset();
    scrollRef.value.resume();
  } catch (error) {}
});

const reprocessarPedido = debounce(async (pedido) => {
  const ret = await sWoo.reprocessarPedido(pedido.id);
  if (ret) {
    Notify.create({
      type: "positive",
      message: "Pedido " + pedido.id + " reprocessado!",
      timeout: 3000, // 3 segundos
      actions: [{ icon: "close", color: "white" }],
    });
  }
});

watch(
  () => sWoo.filtro,
  () => {
    inicializa();
  },
  { deep: true }
);

onMounted(() => {
  inicializa();
});

// controle do modal
const selectedPedido = ref(null);
const showPedidoModal = ref(false);
const showChangeStatus = ref(false);

// Reprocessar o pedido
function openPedido(p) {
  selectedPedido.value = p;
  showPedidoModal.value = true;
}

// Mudar o Status do pedido
function openStatus(p) {
  selectedPedido.value = p;
  showChangeStatus.value = true;
}

const classPeloStatus = (status) => {
  switch (status) {
    case "cancelled":
    case "refunded":
    case "failed":
    case "trash":
      return "bg-negative text-white";

    case "pending":
      return "bg-accent";

    case "on-hold":
      return "bg-warning";

    case "processing":
      return "bg-info text-white";

    case "completed":
      return "bg-secondary text-white";

    default:
      return "bg-grey-6 text-white";
  }
};
</script>

<template>
  <q-page class="q-pa-md bg-grey-4">
    <div
      v-if="sWoo.pedidos.length == 0"
      class="absolute-center text-grey text-center"
    >
      <q-icon name="do_not_disturb" color="" size="300px" />
      <h3>Nenhum registro localizado!</h3>
    </div>
    <q-list v-else>
      <q-infinite-scroll @load="onLoad" ref="scrollRef">
        <div class="row q-col-gutter-sm">
          <template
            v-for="pedido in sWoo.pedidosPorIdDesc"
            :key="pedido.codwoopedido"
          >
            <!--Paginação inicial col-4-->
            <div class="col-xs-12 col-sm-4 col-md-3 col-lg-2">
              <!--CARDS-->
              <q-card flat bordered>
                <!--TITULO E DESTAQUES-->
                <q-item class="full-width q-mb-none" style="height: 60px">
                  <q-item-section avatar>
                    <q-avatar :class="classPeloStatus(pedido.status)" />
                  </q-item-section>
                  <q-item-section side class="ellipsis text-grey-10 text-right">
                    <q-item-label>
                      R$ {{ formataNumero(pedido.valortotal, 2) }}
                    </q-item-label>
                  </q-item-section>
                </q-item>
                <q-item
                  class="full-width q-mt-none q-pt-none"
                  style="height: 60px"
                >
                  <q-item-section>
                    <q-item-label class="text-bold text-grey-9 ellipsis">
                      {{ pedido.nome }}
                    </q-item-label>
                    <q-item-label caption class="text-grey-8">
                      {{ sWoo.statusLabel(pedido.status) }}
                      {{ moment(pedido.alteracaowoo).fromNow() }}
                    </q-item-label>
                  </q-item-section>
                </q-item>
                <q-separator />

                <!--DESCRIÇÃO DO PEDIDO-->
                <q-card-section style="min-height: 180px">
                  <q-item-label class="text-caption text-grey-7">
                    Criado {{ moment(pedido.criacaowoo).fromNow() }} ({{
                      moment(pedido.criacaowoo).format("LLL")
                    }})
                  </q-item-label>
                  <q-item-label class="text-caption text-grey-7">
                    ID: {{ pedido.id }}
                  </q-item-label>
                  <q-item-label class="text-caption text-grey-7">
                    {{ pedido.pagamento }}
                  </q-item-label>
                  <q-item-label class="text-caption text-grey-7">
                    {{ pedido.entrega }}
                  </q-item-label>
                </q-card-section>
                <q-separator />
                <template
                  v-for="negocio in pedido.negocios"
                  :key="negocio.codnegocio"
                >
                  <q-item
                    :to="'/negocio/' + negocio.codnegocio"
                    class="text-caption text-grey-7 q-pa-md"
                  >
                    Negocio: #{{ negocio.codnegocio }} |
                    {{ negocio.negociostatus }} |
                    {{ formataNumero(negocio.valor, 2) }}
                  </q-item>
                </template>
                <q-separator />

                <!--AÇÕES DO PEDIDO-->
                <q-card-actions
                  class="justify-center items-center q-gutter-lg"
                  style="padding: 5px"
                >
                  <q-btn
                    flat
                    round
                    color="primary"
                    icon="mdi-web"
                    :href="
                      'https://sinopel.mrxempresas.com.br/wp-admin/admin.php?page=wc-orders&action=edit&id=' +
                      pedido.id
                    "
                    target="_blank"
                  ></q-btn>
                  <q-btn
                    flat
                    round
                    color="primary"
                    icon="mdi-compare-horizontal"
                    @click="openStatus(pedido)"
                  />
                  <q-btn
                    flat
                    round
                    color="primary"
                    icon="mdi-refresh"
                    @click="reprocessarPedido(pedido)"
                  />
                  <q-btn
                    flat
                    round
                    color="primary"
                    icon="mdi-information-outline"
                    @click="openPedido(pedido)"
                  />
                </q-card-actions>
              </q-card>
            </div>
          </template>
        </div>
      </q-infinite-scroll>

      <!--Abertura dos modais-->
      <pedido-modal v-model="showPedidoModal" :pedido="selectedPedido" />
      <change-status v-model="showChangeStatus" :pedido="selectedPedido" />
    </q-list>
  </q-page>
</template>
