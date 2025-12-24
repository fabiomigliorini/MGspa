<script setup>
import { computed, ref } from "vue";
import { formataNumero } from "src/utils/formatador";
import { debounce } from "quasar";
import { wooStore } from "src/stores/woo";
import WooChangeStatusModal from "src/components/modals/WooChangeStatusModal.vue";
import { Notify } from "quasar";
import moment from "moment";
moment.locale("pt_br");

const sWoo = wooStore();
const tab = ref("resumo");

const props = defineProps({
  modelValue: Boolean,
  codwoopedido: Object,
});

const emit = defineEmits(["update:modelValue"]);

const show = computed({
  get: () => props.modelValue,
  set: (v) => emit("update:modelValue", v),
});

const showChangeStatus = ref(false);

function openStatus() {
  showChangeStatus.value = true;
}

const reprocessarPedido = debounce(async () => {
  const ret = await sWoo.reprocessarPedido(sWoo.pedido.id);
  if (ret) {
    Notify.create({
      type: "positive",
      message: `Pedido ${sWoo.pedido.id} reprocessado!`,
      timeout: 3000,
      actions: [{ icon: "close", color: "white" }],
    });
  }
}, 500);

// Computed para formatar o JSON bonitinho com indentação
const jsonFormatado = computed(() => {
  try {
    const obj =
      typeof sWoo.pedido.jsonwoo === "string"
        ? JSON.parse(sWoo.pedido.jsonwoo)
        : sWoo.pedido.jsonwoo;
    return JSON.stringify(obj, null, 2);
  } catch (e) {
    return sWoo.pedido.jsonwoo;
  }
});

// Converte o jsonwoo para objeto real para facilitar o uso nas abas
const pedidoData = computed(() => {
  try {
    return typeof sWoo.pedido.jsonwoo === "string"
      ? JSON.parse(sWoo.pedido.jsonwoo)
      : sWoo.pedido.jsonwoo;
  } catch (e) {
    return {};
  }
});
</script>

<template>
  <woo-change-status-modal v-model="showChangeStatus" />

  <q-dialog v-model="show" transition-show="fade" transition-hide="fade">
    <q-card
      style="width: 700px; max-width: 90vw; height: 600px"
      class="column no-wrap"
    >
      <q-card-section class="bg-primary text-white q-pb-none">
        <div class="row items-center no-wrap">
          <div class="col">
            <div class="text-h6">
              Pedido #{{ sWoo.pedido.id }} | {{ sWoo.pedido.nome }}
            </div>
          </div>
        </div>

        <q-tabs
          v-model="tab"
          align="left"
          class="q-mt-md"
          active-color="white"
          indicator-color="white"
          dense
          no-caps
        >
          <q-tab name="resumo" label="Resumo" icon="info" />
          <q-tab name="detalhes" label="Detalhes" icon="list" />
          <q-tab name="json" label="Retorno" icon="code" />
        </q-tabs>
      </q-card-section>

      <q-separator />

      <q-card-section class="col scroll q-pa-none">
        <q-tab-panels v-model="tab" animated swipeable class="bg-grey-1">
          <!-- RESUMO -->
          <q-tab-panel name="resumo" class="q-pa-md">
            <div class="row q-col-gutter-md">
              <div class="col-12 col-sm-6">
                <q-list bordered separator class="bg-white rounded-borders">
                  <q-item v-ripple clickable @click="openStatus()">
                    <q-item-section>
                      <q-item-label caption>Status</q-item-label>
                      <q-item-label>
                        {{ sWoo.statusLabel(sWoo.pedido.status) }}
                      </q-item-label>
                      <q-tooltip>Mudar Status do Pedido</q-tooltip>
                    </q-item-section>
                    <q-item-section avatar>
                      <q-avatar :class="sWoo.statusColor(sWoo.pedido.status)">
                      </q-avatar>
                    </q-item-section>
                  </q-item>
                  <q-item>
                    <q-item-section>
                      <q-item-label caption>Data do Pedido (Woo)</q-item-label>
                      <q-item-label>{{
                        moment(sWoo.pedido.criacaowoo).format("LLLL")
                      }}</q-item-label>
                    </q-item-section>
                  </q-item>
                  <q-item>
                    <q-item-section>
                      <q-item-label caption>Pagamento</q-item-label>
                      <q-item-label>{{ sWoo.pedido.pagamento }}</q-item-label>
                    </q-item-section>
                  </q-item>
                </q-list>
              </div>

              <div class="col-12 col-sm-6">
                <q-list bordered separator class="bg-white rounded-borders">
                  <q-item>
                    <q-item-section>
                      <q-item-label caption>Valor Total</q-item-label>
                      <q-item-label class="text-h6 text-primary">
                        R$ {{ formataNumero(sWoo.pedido.valortotal, 2) }}
                      </q-item-label>
                    </q-item-section>
                  </q-item>
                  <q-item>
                    <q-item-section>
                      <q-item-label caption>Frete</q-item-label>
                      <q-item-label>{{
                        sWoo.pedido.valorfrete > 0
                          ? "R$ " + formataNumero(sWoo.pedido.valorfrete, 2)
                          : "Grátis/Não informado"
                      }}</q-item-label>
                    </q-item-section>
                  </q-item>
                  <q-item>
                    <q-item-section>
                      <q-item-label caption>Localidade</q-item-label>
                      <q-item-label>{{ sWoo.pedido.cidade }}</q-item-label>
                    </q-item-section>
                  </q-item>
                </q-list>
              </div>

              <div class="col-12">
                <q-banner dense class="bg-blue-1 text-blue-9 rounded-borders">
                  <template v-slot:avatar>
                    <q-icon name="local_shipping" color="blue-9" />
                  </template>
                  <div class="text-weight-medium">Método de Entrega:</div>
                  {{ sWoo.pedido.entrega }}
                </q-banner>
              </div>

              <div class="col-12" v-if="sWoo.pedido.negocios?.length">
                <div class="text-subtitle2 q-mb-xs text-grey-7">
                  Negócios Vinculados
                </div>
                <q-list bordered separator class="bg-white rounded-borders">
                  <q-item
                    v-for="negocio in sWoo.pedido.negocios"
                    :key="negocio.codnegocio"
                    clickable
                    v-ripple
                    :to="'/negocio/' + negocio.codnegocio"
                  >
                    <q-item-section avatar>
                      <q-icon name="handshake" color="grey-7" />
                    </q-item-section>
                    <q-item-section>
                      <q-item-label
                        >Negócio #{{ negocio.codnegocio }}</q-item-label
                      >
                      <q-item-label caption>{{
                        negocio.negociostatus
                      }}</q-item-label>
                    </q-item-section>
                    <q-item-section side>
                      <q-item-label class="text-weight-bold"
                        >R$ {{ formataNumero(negocio.valor, 2) }}</q-item-label
                      >
                    </q-item-section>
                  </q-item>
                </q-list>
              </div>
            </div>
          </q-tab-panel>

          <!-- DETALHES -->
          <q-tab-panel name="detalhes" class="q-pa-md bg-grey-1">
            <div class="row q-col-gutter-md">
              <div class="col-12 col-md-6">
                <q-list bordered class="bg-white rounded-borders full-height">
                  <q-item-label
                    header
                    class="text-weight-bold text-primary row items-center"
                  >
                    <q-icon name="person" size="xs" class="q-mr-xs" />
                    {{ pedidoData.billing?.first_name }}
                    {{ pedidoData.billing?.last_name }}
                  </q-item-label>
                  <q-separator />
                  <q-item>
                    <q-item-section>
                      <q-item-label class="text-caption">
                        {{ pedidoData.billing?.email }}
                      </q-item-label>
                      <q-item-label class="text-caption">
                        {{ pedidoData.billing?.phone }} /
                        {{ pedidoData.billing?.cellphone }}
                      </q-item-label>
                      <q-item-label
                        class="text-caption"
                        v-if="
                          pedidoData.billing?.cpf || pedidoData.billing?.cnpj
                        "
                      >
                        {{
                          pedidoData.billing?.cpf || pedidoData.billing?.cnpj
                        }}
                      </q-item-label>
                      <q-item-label class="text-caption">
                        {{ pedidoData.billing?.address_1 }},
                        {{ pedidoData.billing?.number }}<br />
                        {{ pedidoData.billing?.neighborhood }} -
                        {{ pedidoData.billing?.city }}/{{
                          pedidoData.billing?.state
                        }}
                      </q-item-label>
                    </q-item-section>
                  </q-item>
                </q-list>
              </div>

              <div class="col-12 col-md-6">
                <q-list bordered class="bg-white rounded-borders full-height">
                  <q-item-label
                    header
                    class="text-weight-bold text-orange-9 row items-center"
                  >
                    <q-icon name="local_shipping" size="xs" class="q-mr-xs" />
                    Entrega
                  </q-item-label>
                  <q-separator />

                  <q-item dense>
                    <q-item-section>
                      <q-item-label caption>
                        {{ pedidoData.shipping?.first_name }}
                        {{ pedidoData.shipping?.last_name }}
                      </q-item-label>
                    </q-item-section>
                  </q-item>
                  <q-item dense>
                    <q-item-section avatar>
                      <q-icon name="place" color="grey-7" size="xs" />
                    </q-item-section>
                    <q-item-section>
                      <q-item-label class="text-caption">
                        {{ pedidoData.shipping?.address_1 }},
                        {{ pedidoData.shipping?.number }}<br />
                        {{ pedidoData.shipping?.neighborhood }}<br />
                        {{ pedidoData.shipping?.city }} -
                        {{ pedidoData.shipping?.state }}<br />
                        CEP: {{ pedidoData.shipping?.postcode }}
                      </q-item-label>
                    </q-item-section>
                  </q-item>
                  <q-separator />
                  <q-item
                    v-for="(ship, index) in pedidoData.shipping_lines"
                    :key="'ship-' + index"
                    class="q-pb-sm q-pt-sm"
                    dense
                  >
                    <q-item-section>
                      <q-item-label caption>Método Escolhido</q-item-label>
                      <q-item-label class="text-caption">{{
                        ship.method_title
                      }}</q-item-label>
                    </q-item-section>
                  </q-item>
                </q-list>
              </div>

              <div class="col-12">
                <q-list
                  dense
                  bordered
                  separator
                  class="bg-white rounded-borders"
                >
                  <q-item-label
                    header
                    class="text-weight-bold text-primary row items-center"
                  >
                    <q-icon name="shopping_basket" size="xs" class="q-mr-xs" />
                    Itens do Pedido
                  </q-item-label>

                  <q-separator />

                  <q-item v-for="item in pedidoData.line_items" :key="item.id">
                    <q-item-section avatar>
                      <q-avatar square size="40px" class="bg-grey-2 q-my-sm">
                        <img v-if="item.image?.src" :src="item.image.src" />
                        <q-icon v-else name="shopping_bag" color="grey-4" />
                      </q-avatar>
                    </q-item-section>

                    <q-item-section>
                      <q-item-label class="text-caption text-grey-7">
                        SKU: {{ item.sku || "N/A" }}
                      </q-item-label>
                      <q-item-label class="text-weight-bold">
                        {{ item.name }}
                      </q-item-label>
                    </q-item-section>

                    <q-item-section side>
                      <q-item-label class="text-weight-bold text-grey-7">
                        {{ item.quantity }} x R$
                        {{ formataNumero(item.price, 2) }}
                      </q-item-label>
                      <q-item-label class="text-weight-bold text-black">
                        {{ formataNumero(item.total, 2) }}
                      </q-item-label>
                    </q-item-section>
                  </q-item>
                </q-list>
              </div>

              <div class="col-12">
                <q-list bordered class="bg-white rounded-borders full-height">
                  <q-item-label
                    header
                    class="text-weight-bold text-green-9 row items-center"
                  >
                    <q-icon name="payments" size="xs" class="q-mr-xs" />
                    Forma de Pagamento
                  </q-item-label>
                  <q-separator />
                  <q-item dense>
                    <q-item-section class="q-pt-sm">
                      <q-item-label
                        >{{ pedidoData.payment_method_title }} ({{
                          pedidoData.currency
                        }})</q-item-label
                      >
                    </q-item-section>
                  </q-item>
                  <q-item dense>
                    <q-item-section>
                      <div class="row justify-between">
                        <span>Subtotal:</span>
                        <span>
                          {{
                            formataNumero(
                              pedidoData.total - pedidoData.shipping_total,
                              2
                            )
                          }}</span
                        >
                      </div>
                      <div class="row justify-between text-orange-9">
                        <span>Frete:</span>
                        <span>
                          {{ formataNumero(pedidoData.shipping_total, 2) }}
                        </span>
                      </div>
                      <div
                        class="row justify-between text-weight-bold text-subtitle2 q-mt-xs border-top"
                      >
                        <span>Total:</span>
                        <span>{{ formataNumero(pedidoData.total, 2) }}</span>
                      </div>
                    </q-item-section>
                  </q-item>
                  <q-separator />
                  <q-item>
                    <q-item-section>
                      <q-item-label caption>Dados Técnicos</q-item-label>
                      <q-item-label style="font-size: 10px" class="text-grey-7">
                        IP: {{ pedidoData.customer_ip_address }}<br />
                        Versão: {{ pedidoData.version }} | Via:
                        {{ pedidoData.created_via }}<br />
                        Key: {{ pedidoData.order_key }}
                      </q-item-label>
                    </q-item-section>
                  </q-item>
                </q-list>
              </div>

              <!-- Metadata -->
              <div class="col-12" v-if="pedidoData.meta_data?.length">
                <q-expansion-item
                  dense
                  icon="terminal"
                  label="Metadados Adicionais"
                  header-class="text-grey-7 text-caption bg-white"
                  style="border: 1px solid #e0e0e0; border-radius: 8px"
                >
                  <q-card>
                    <q-card-section class="q-pa-sm">
                      <div class="row q-col-gutter-xs">
                        <div
                          v-for="meta in pedidoData.meta_data"
                          :key="meta.id"
                          class="col-12 col-sm-4"
                        >
                          <div
                            class="bg-grey-2 q-pa-xs rounded-borders text-caption ellipsis"
                          >
                            <span class="text-weight-bold"
                              >{{ meta.key }}:</span
                            >
                            {{ meta.value }}
                          </div>
                        </div>
                      </div>
                    </q-card-section>
                  </q-card>
                </q-expansion-item>
              </div>

              <div class="col-12" v-if="pedidoData.customer_note">
                <q-banner
                  dense
                  class="bg-amber-1 text-amber-10 rounded-borders"
                >
                  <template v-slot:avatar><q-icon name="chat" /></template>
                  <strong>Nota do Cliente:</strong>
                  {{ pedidoData.customer_note }}
                </q-banner>
              </div>
            </div>
          </q-tab-panel>

          <q-tab-panel name="json" class="q-pa-none">
            <pre
              class="q-pa-md bg-dark text-lime-13 text-caption scroll"
              style="height: 440px; margin: 0; font-family: monospace"
              >{{ jsonFormatado }}</pre
            >
          </q-tab-panel>
        </q-tab-panels>
      </q-card-section>

      <q-separator />

      <q-card-actions align="right" class="bg-white">
        <q-btn
          flat
          color="primary"
          icon="open_in_new"
          type="a"
          target="_blank"
          :href="
            'https://sinopel.mrxempresas.com.br/wp-admin/admin.php?page=wc-orders&action=edit&id=' +
            sWoo.pedido.id
          "
        >
          <q-tooltip>Forçar reprocessamento do pedido</q-tooltip>
        </q-btn>
        <q-btn flat color="primary" icon="refresh" @click="reprocessarPedido">
          <q-tooltip>Forçar reprocessamento do pedido</q-tooltip>
        </q-btn>
        <q-btn flat color="primary" v-close-popup label="fechar" />
      </q-card-actions>
    </q-card>
  </q-dialog>
</template>

<style scoped>
pre {
  white-space: pre-wrap;
  word-wrap: break-word;
}
</style>
