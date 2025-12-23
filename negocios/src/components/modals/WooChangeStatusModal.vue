<script setup>
import { computed, ref } from "vue";
import { wooStore } from "stores/woo";
import { Notify } from "quasar";
const sWoo = wooStore();

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

// Array com os status
const statusOptions = ref([...sWoo.opcoes.status]);

//Alterar Status
const changeStatus = async (statusValue) => {
  if (!props.pedido?.id) return;

  const ok = await sWoo.alterarStatus(props.pedido.id, statusValue);
  if (ok) {
    Notify.create({
      type: "positive",
      message:
        "Pedido " +
        props.pedido.id +
        " teve o status alterado para " +
        statusValue +
        ".",
      timeout: 3000,
      actions: [{ icon: "close", color: "white" }],
    });
    console.log("AQUI");

    close(); // fecha o modal após sucesso
    return;
  }
};
</script>

<template>
  <q-dialog v-model="show" persistent>
    <q-card style="height: 480px; width: 600px" class="column no-wrap">
      <q-card-section>
        <q-item-label class="text-h6 text-primary">
          Alterar Status do pedido:
        </q-item-label>
      </q-card-section>
      <q-separator />
      <q-card-section class="col q-pa-md text-h6">
        <q-item>
          <q-item-section>
            <q-card-label class="text-grey-7"
              >ID do pedido: #{{ pedido.id }}
            </q-card-label>
          </q-item-section>
        </q-item>
        <q-separator />
        <q-item>
          <q-item-section>
            <div class="text-subtitle2 q-mb-sm">
              Alterar status do pedido para:
            </div>
            <div class="row q-col-gutter-sm">
              <div
                v-for="opt in statusOptions"
                :key="opt.value"
                class="col-auto"
              >
                <q-btn
                  outline
                  size="sm"
                  color="primary"
                  :label="opt.label"
                  @click="changeStatus(opt.value)"
                />
              </div>
            </div>
          </q-item-section>
        </q-item>
      </q-card-section>
      <q-separator />
      <q-card-section>
        <q-card-actions align="right">
          <q-btn flat label="Fechar" color="primary" @click="close" />
        </q-card-actions>
      </q-card-section>
    </q-card>
  </q-dialog>
</template>
