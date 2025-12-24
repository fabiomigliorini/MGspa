<script setup>
import { wooStore } from "src/stores/woo";
import { ref } from "vue";
import { negocioStore } from "stores/negocio";
import WooInfoModal from "src/components/modals/WooInfoModal.vue";
const sWoo = wooStore();

const sNegocio = negocioStore();

const showPedidoModal = ref(false);

// Reprocessar o pedido
function openPedido(p) {
  sWoo.pedido = p;
  showPedidoModal.value = true;
}
</script>
<template>
  <template v-if="sNegocio.negocio">
    <woo-info-modal v-model="showPedidoModal" />
    <q-list>
      <template
        v-for="wp in sNegocio.negocio.WooPedidoS"
        :key="wp.codwoopedido"
      >
        <q-separator class="q-mb-sm" />
        <q-item clickable v-ripple @click="openPedido(wp)">
          <q-item-section avatar top>
            <q-avatar
              round
              :class="sWoo.statusColor(wp.status)"
              class="text-white"
              icon="mdi-list-box-outline"
            />
          </q-item-section>
          <q-item-section>
            <q-item-label lines="1">
              #{{ wp.id }} - {{ sWoo.statusLabel(wp.status) }}
            </q-item-label>
            <q-item-label caption v-if="wp.pagamento">
              {{ wp.pagamento }}
            </q-item-label>
            <q-item-label caption v-if="wp.entrega">
              {{ wp.entrega }}
            </q-item-label>
          </q-item-section>
        </q-item>
      </template>
    </q-list>
  </template>
</template>
