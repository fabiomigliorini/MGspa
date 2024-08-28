<script setup>
import { reactive, ref } from "vue";
import { useRequestDelivery } from "src/composables/useRequestDelivery";
import BaseForm from "../forms/BaseForm.vue";
import { requiredFormRule } from "src/utils/formRules/requiredFormRule";
import { useLoading } from "src/composables/useLoading";

const dialog = ref(false);

const { status, request, cancel } = useRequestDelivery();

const { loading: loadingRequest, execute: executeRequest } =
  useLoading(request);
const { loading: loadingCancel, execute: executeCancel } = useLoading(cancel);

const submit = async (formData) => {
  await executeRequest(formData);
  dialog.value = false;
};

const fieldsValues = reactive({
  name: null,
  phone: null,
  address: null,
});
</script>

<template>
  <div>
    <q-chip
      v-if="status"
      :color="status.color"
      :text-color="status.textColor"
      :icon="status.icon"
    >
      {{ status.name }}
    </q-chip>
    <template v-if="(status && status.name != 'Entregue') || !status">
      <q-btn
        v-if="!status || status.name === 'Cancelado'"
        @click="dialog = true"
        class="text-none"
        icon="mdi-truck-delivery-outline"
        size="md"
        color="primary"
        label="Solicitar Entrega"
        unelevated
      >
      </q-btn>
      <q-btn
        v-else
        @click="executeCancel"
        :loading="loadingCancel"
        class="text-none"
        icon="mdi-close"
        size="md"
        text-color="red"
        label="Cancelar Entrega"
        outline
        unelevated
      >
      </q-btn>
    </template>

    <q-dialog v-model="dialog">
      <q-card style="min-width: 350px">
        <q-card-section class="q-pb-none q-pt-sm">
          <div class="text-h6">Solicitar Entrega</div>
        </q-card-section>

        <BaseForm :submit-callback="submit">
          <template #fields>
            <q-input
              v-model="fieldsValues.name"
              outlined
              dense
              label="Nome"
              :rules="[requiredFormRule()]"
            />
            <q-input
              v-model="fieldsValues.phone"
              outlined
              dense
              label="Telefone"
              :rules="[requiredFormRule()]"
            />
            <q-input
              v-model="fieldsValues.address"
              outlined
              dense
              label="Endereço"
              :rules="[requiredFormRule()]"
            />
          </template>
          <template #actions>
            <q-btn class="text-none" flat label="Cancelar" :disable="loadingRequest" close-popup />
            <q-btn
              :loading="loadingRequest"
              type="submit"
              class="text-none"
              color="primary"
              unelevated
              label="Solicitar"
            />
          </template>
        </BaseForm>
      </q-card>
    </q-dialog>
  </div>
</template>

<style>
.text-none {
  text-decoration: none !important;
  text-transform: none !important;
}
</style>
