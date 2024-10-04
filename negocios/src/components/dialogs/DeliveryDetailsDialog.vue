<script setup>
import BaseDialog from "./BaseDialog.vue";
import { useLoading } from "src/composables/useLoading";
import { DeliveryModel } from "../models/DeliveryModel";
import { DeliveryStatusEnum } from "src/enums/DeliveryStatusEnum";

const props = defineProps({
  delivery: {
    type: Object,
    required: true,
  },
});

const model = defineModel();

const cancelCallback = async () => {
  await DeliveryModel.cancel(props.delivery.id);
  model.value = false;
};

const { loading: loadingCancel, execute: executeCancel } =
  useLoading(cancelCallback);
</script>

<template>
  <BaseDialog class="delivery-details-dialog" v-model="model" title="Detalhes da Entrega">
    <table>
      <tr>
        <td>Estado</td>
        <td>{{ DeliveryStatusEnum[delivery.status].name }}</td>
      </tr>
      <tr>
        <td>Nome</td>
        <td>{{ delivery.name }}</td>
      </tr>
      <tr>
        <td>Telefone</td>
        <td>{{ delivery.phone }}</td>
      </tr>
      <tr>
        <td>Rua</td>
        <td>{{ delivery.street }}</td>
      </tr>
      <tr>
        <td>Número</td>
        <td>{{ delivery.number }}</td>
      </tr>
      <tr>
        <td>Bairro</td>
        <td>{{ delivery.neighborhood }}</td>
      </tr>
      <tr>
        <td>Cidade</td>
        <td>{{ delivery.city }}</td>
      </tr>
      <tr>
        <td>Estado</td>
        <td>{{ delivery.state }}</td>
      </tr>
      <tr>
        <td>Informações Adicionais</td>
        <td>{{ delivery.additional_info }}</td>
      </tr>
      <tr>
        <td>Método de Pagamento</td>
        <td>{{ delivery.payment_method }}</td>
      </tr>
      <tr>
        <td>Observações</td>
        <td>{{ delivery.observations }}</td>
      </tr>
    </table>

    <div class="q-mt-sm">
      <q-btn
        @click="executeCancel"
        :loading="loadingCancel"
        class="text-none"
        icon="mdi-truck-delivery-outline"
        size="md"
        color="red"
        label="Cancelar Entrega"
        outline
      />
    </div>
  </BaseDialog>
</template>

<style lang="scss">
.delivery-details-dialog table {
  border-collapse: collapse;
  width: 100%;
  tr:nth-child(2n + 1) {
    background-color: #f2f2f2;

  }

  td {
    padding: 8px 8px 8px 8px;

    &:first-child {
      font-weight: bold;
    }
  }
}
</style>
