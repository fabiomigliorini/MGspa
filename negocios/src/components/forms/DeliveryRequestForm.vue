<script setup>
import { reactive } from "vue";
import BaseForm from "../forms/BaseForm.vue";
import { requiredFormRule } from "src/utils/formRules/requiredFormRule";
import { useLoading } from "src/composables/useLoading";

const props = defineProps({
  dealId: {
    type: String,
    required: true,
  },
  submitCallback: {
    type: Function,
    default: () => {},
  },
});

const emits = defineEmits(["cancel"]);

const { loading: loadingSubmit, execute: executeSubmit } =
  useLoading(props.submitCallback);

const fieldsValues = reactive({
  name: null,
  phone: null,
  street: null,
  number: null,
  neighborhood: null,
  city: null,
  state: null,
  additional_info: null,
  payment_method: null,
});

const cancel = () => {
  emit('cancel');
}
</script>

<template>
  <BaseForm class="delivery-form" :submit-callback="executeSubmit">
    <template #fields>
      <q-input
        v-model="fieldsValues.name"
        name="name"
        outlined
        dense
        label="Nome"
        :rules="[requiredFormRule()]"
      />
      <q-input
        v-model="fieldsValues.phone"
        name="phone"
        outlined
        dense
        label="Telefone"
        :rules="[requiredFormRule()]"
      />
      <q-input
        v-model="fieldsValues.street"
        name="street"
        outlined
        dense
        label="Rua"
        :rules="[requiredFormRule()]"
      />
      <q-input
        v-model="fieldsValues.number"
        name="number"
        outlined
        dense
        label="Número da Casa"
        :rules="[requiredFormRule()]"
      />
      <q-input
        v-model="fieldsValues.neighborhood"
        name="neighborhood"
        outlined
        dense
        label="Bairro"
        :rules="[requiredFormRule()]"
      />
      <q-input
        v-model="fieldsValues.city"
        name="city"
        outlined
        dense
        label="Cidade"
        :rules="[requiredFormRule()]"
      />
      <q-input
        v-model="fieldsValues.state"
        name="state"
        outlined
        dense
        label="Estado"
        :rules="[requiredFormRule()]"
      />
      <q-input
        v-model="fieldsValues.additional_info"
        name="additional_info"
        outlined
        dense
        label="Informações Adicionais"
      />
      <q-select
        v-model="fieldsValues.payment_method"
        :options="['Cartão de Credito', 'Cartão de Debito', 'Dinheiro', 'PIX']"
        name="payment_method"
        outlined
        dense
        label="Método de Pagamento"
        :rules="[requiredFormRule()]"
      />
      <input type="hidden" name="deal_id" :value="dealId" />
    </template>
    <template #actions>
      <q-btn
        @click="cancel"
        class="text-none"
        outline
        label="Cancelar"
        :disable="loadingSubmit"
        close-popup
      />
      <q-btn
        :loading="loadingSubmit"
        type="submit"
        class="text-none"
        color="primary"
        unelevated
        label="Solicitar"
      />
    </template>
  </BaseForm>
</template>

<style>
.delivery-form {
  display: grid;
  grid-template-columns: auto auto;
  gap: 0px 12px;
}

@media screen and (min-width: 600px) {
  .delivery-form .base-form-actions {
    grid-column: span 2;
  }
}

@media screen and (max-width: 599.9px) {
  .delivery-form {
    grid-template-columns: auto;
  }
}
</style>
