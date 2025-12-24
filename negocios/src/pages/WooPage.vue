<script setup>
import { ref, onMounted, watch } from "vue";
import { wooStore } from "src/stores/woo";
import { formataNumero } from "src/utils/formatador";
import { Notify, debounce } from "quasar";
import moment from "moment/min/moment-with-locales";
import WooInfoModal from "src/components/modals/WooInfoModal.vue";
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
const showPedidoModal = ref(false);

// Reprocessar o pedido
function openPedido(p) {
  sWoo.pedido = p;
  showPedidoModal.value = true;
}
</script>

<template>
  <q-page class="q-pa-md bg-grey-4">
    <div
      v-if="sWoo.pedidos.length == 0"
      class="absolute-center text-grey text-center"
    >
      <q-icon name="do_not_disturb" size="200px" />
      <h4 class="q-ma-none">Nenhum registro localizado!</h4>
    </div>

    <q-infinite-scroll v-else @load="onLoad" ref="scrollRef">
      <div class="row q-col-gutter-sm">
        <template
          v-for="pedido in sWoo.pedidosPorIdDesc()"
          :key="pedido.codwoopedido"
        >
          <div class="col-xs-12 col-sm-6 col-md-3 col-lg-2">
            <q-card
              flat
              bordered
              v-ripple
              @click="openPedido(pedido)"
              class="full-height cursor-pointer relative-position column no-wrap"
            >
              <q-item class="q-pb-none">
                <q-item-section avatar>
                  <q-avatar
                    :class="sWoo.statusColor(pedido.status)"
                    size="24px"
                  />
                </q-item-section>
                <q-item-section
                  side
                  class="text-grey-10 text-bold text-subtitle1"
                >
                  {{ formataNumero(pedido.valortotal, 2) }}
                </q-item-section>
              </q-item>

              <q-item class="q-pt-none q-mt-xs">
                <q-item-section>
                  <q-item-label class="text-bold text-grey-9 ellipsis">
                    {{ pedido.nome }}
                  </q-item-label>
                  <q-item-label caption class="text-grey-8">
                    {{ sWoo.statusLabel(pedido.status) }} •
                    {{ moment(pedido.alteracaowoo).fromNow() }}
                  </q-item-label>
                </q-item-section>
              </q-item>

              <q-separator inset class="q-mx-md" />

              <q-card-section class="q-py-sm text-caption text-grey-7">
                <div class="row q-col-gutter-xs">
                  <div class="col-12">#{{ pedido.id }}</div>
                  <div class="col-12">
                    {{ moment(pedido.criacaowoo).format("LLLL") }}
                  </div>
                  <div class="col-12">
                    {{ pedido.pagamento }}
                  </div>
                  <div class="col-12 ellipsis-2-lines">
                    {{ pedido.entrega }}
                  </div>
                </div>
              </q-card-section>

              <q-list dense class="q-pb-sm">
                <template v-if="pedido.negocios && pedido.negocios.length > 0">
                  <template
                    v-for="negocio in pedido.negocios"
                    :key="negocio.codnegocio"
                  >
                    <q-separator inset class="q-my-xs" />
                    <q-item
                      :to="'/negocio/' + negocio.codnegocio"
                      @click.stop
                      clickable
                      class="text-caption"
                    >
                      <q-item-section>
                        <q-item-label caption>
                          #{{ negocio.codnegocio }}
                        </q-item-label>
                        <q-item-label caption>
                          {{ negocio.negociostatus }}
                        </q-item-label>
                      </q-item-section>
                      <q-item-section side>
                        {{ formataNumero(negocio.valor, 2) }}
                      </q-item-section>
                    </q-item>
                  </template>
                </template>
                <q-item v-else>
                  <q-item-section class="text-caption text-italic text-grey-5">
                    Nenhum vínculo
                  </q-item-section>
                </q-item>
              </q-list>

              <q-space />

              <q-separator inset class="q-mx-md" />
            </q-card>
          </div>
        </template>
      </div>
    </q-infinite-scroll>

    <woo-info-modal v-model="showPedidoModal" />
  </q-page>
</template>
