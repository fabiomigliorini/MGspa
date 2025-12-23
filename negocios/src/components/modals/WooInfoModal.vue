<script setup>
import { computed, ref } from "vue";
import { formataNumero } from "src/utils/formatador";
import moment from "moment";

const tab = ref("detalhes");

const props = defineProps({
  modelValue: Boolean,
  pedido: Object,
});
const emit = defineEmits(["update:modelValue"]);

const show = computed({
  get: () => props.modelValue,
  set: (v) => emit("update:modelValue", v),
});

const close = () => emit("update:modelValue", false);
</script>

<template>
  <q-dialog v-model="show" persistent>
    <q-card style="height: 480px; width: 600px" class="column no-wrap">
      <q-card-section>
        <q-tabs
          v-model="tab"
          dense
          class="text-grey"
          active-color="primary"
          indicator-color="primary"
          align="justify"
          narrow-indicator
        >
          <q-tab name="detalhes" label="Detalhes" />
          <q-tab name="json" label="Retorno Woo" />
        </q-tabs>
      </q-card-section>
      <q-separator />

      <q-card-section class="col q-pa-md">
        <q-tab-panels v-model="tab" animated>
          <q-tab-panel name="detalhes">
            <div class="text-h6 text-primary">Detalhes do pedido</div>

            <q-item-label
              class="text-bold text-grey-9 ellipsis q-mb-sm q-mt-md"
            >
              Nome: {{ pedido.nome }}
            </q-item-label>
            <q-item-label class="text-bold text-grey-9 ellipsis q-mb-sm">
              ID: {{ pedido.id }}
            </q-item-label>
            <q-item-label class="text-bold text-grey-9 ellipsis q-mb-sm">
              Status: {{ pedido.status }}
            </q-item-label>
            <q-item-label class="text-bold text-grey-9 ellipsis q-mb-sm">
              Total: R${{ formataNumero(pedido.valortotal, 2) }}
            </q-item-label>
            <q-item-label class="text-bold text-grey-9 ellipsis q-mb-sm">
              Modo de pagamento: {{ pedido.pagamento }}
            </q-item-label>
            <q-item-label class="text-bold text-grey-9 ellipsis q-mb-sm">
              Criado em:
              {{ moment(pedido.criacaowoo).format("DD/MM/YYYY") }}
            </q-item-label>
            <q-item-label class="text-bold text-grey-9 q-mb-sm">
              Modo de entrega: {{ pedido.entrega }}
            </q-item-label>
            <q-item-label
              v-if="(pedido.valorfrete > 0) | null"
              class="text-bold text-grey-9 ellipsis q-mb-sm"
            >
              Valor do frete: R$ {{ pedido.valorfrete }}
            </q-item-label>
            <q-item-label
              v-if="pedido.alteracao"
              class="text-bold text-grey-9 ellipsis q-mb-sm"
            >
              Pedido teve alteração em:
              {{ moment(pedido.alteracao).format("DD/MM/YYYY") }}
            </q-item-label>
          </q-tab-panel>

          <q-tab-panel name="json">
            <div class="text-h6 text-primary q-mb-md">Alarms</div>
            <div class="ellipsis-3-lines">
              {{ pedido.jsonwoo }}
            </div>
          </q-tab-panel>
        </q-tab-panels>
      </q-card-section>
      <q-separator />

      <q-card-actions align="right">
        <q-btn flat label="Fechar" color="primary" @click="close" />
      </q-card-actions>
    </q-card>
  </q-dialog>
</template>
