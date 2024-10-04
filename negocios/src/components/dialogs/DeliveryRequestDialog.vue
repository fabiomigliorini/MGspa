<script setup>
import DeliveryRequestForm from "../forms/DeliveryRequestForm.vue";
import BaseDialog from "./BaseDialog.vue";
import { DeliveryModel } from "../models/DeliveryModel";

defineProps({
  dealId: {
    type: String,
    required: true,
  },
});

const model = defineModel();

const submit = async (formData) => {
  await DeliveryModel.request(formData);

  model.value = false;
};
</script>

<template>
  <BaseDialog v-model="model" title="Solicitar Entrega">
    <DeliveryRequestForm :submit-callback="submit" :deal-id="dealId" />
  </BaseDialog>
</template>
