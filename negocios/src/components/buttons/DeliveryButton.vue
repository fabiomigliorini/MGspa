<script setup>
import { computed, ref } from "vue";

import DeliveryDetailsDialog from "../dialogs/DeliveryDetailsDialog.vue";
import DeliveryRequestDialog from "../dialogs/DeliveryRequestDialog.vue";
import { DeliveryStatusEnum } from "src/enums/DeliveryStatusEnum";

const props = defineProps({
  dealId: {
    type: String,
    required: true,
  },
  deliveries: {
    type: Array,
    default: () => [],
  },
});

const deliveryRequestDialog = ref(false);
const deliveryDetailsDialog = ref(false);


const deliveryInProgress = computed(() => {
  return props.deliveries.find((delivery) =>
  [
    DeliveryStatusEnum.PENDING.value,
    DeliveryStatusEnum.IN_TRANSIT.value,
  ].includes(delivery.status)
);
});

const status = computed(() => {
  if (!deliveryInProgress.value) {
    return null
  }

  return DeliveryStatusEnum[deliveryInProgress.value.status]
});
</script>

<template>
  <div class="delivery-button">
    <q-btn
      v-if="status && status.name !== 'Cancelado'"
      @click="deliveryDetailsDialog = true"
      class="text-none"
      :color="status.color"
      :text-color="status.textColor"
      :icon="status.icon"
      unelevated
      size="small"
    >
      {{ status.name }}
    </q-btn>
    <q-btn
      v-if="!status || status.name === 'Cancelado'"
      @click="deliveryRequestDialog = true"
      class="text-none"
      icon="mdi-truck-delivery-outline"
      size="md"
      color="primary"
      label="Solicitar Entrega"
      unelevated
    >
    </q-btn>

    <DeliveryDetailsDialog
      v-model="deliveryDetailsDialog"
      v-if="deliveryInProgress"
      :delivery="deliveryInProgress"
    />
    <DeliveryRequestDialog v-model="deliveryRequestDialog" :deal-id="dealId" />
  </div>
</template>

<style>
.delivery-button .q-btn {
  padding: 4px 8px;
}

.delivery-button .q-icon {
  font-size: 16px;
  margin-right: 4px;
}
</style>
