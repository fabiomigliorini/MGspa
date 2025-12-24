<script setup>
import { computed, ref } from "vue";
import { wooStore } from "stores/woo";
import { Notify } from "quasar";
const sWoo = wooStore();

const props = defineProps({
  modelValue: Boolean,
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
  const ok = await sWoo.alterarStatus(sWoo.pedido.id, statusValue);
  if (ok) {
    Notify.create({
      type: "positive",
      message:
        "Pedido " +
        sWoo.pedido.id +
        " teve o status alterado para " +
        statusValue +
        ".",
      timeout: 3000,
      actions: [{ icon: "close", color: "white" }],
    });
    close(); // fecha o modal após sucesso
    return;
  }
};
</script>
<template>
  <q-dialog v-model="show" transition-show="scale" transition-hide="scale">
    <q-card style="width: 500px; max-width: 90vw">
      <q-card-section class="q-pa-md">
        <div class="text-grey-8 q-mb-md">
          Selecione o novo status para este pedido:
        </div>

        <div class="row q-col-gutter-sm">
          <div v-for="opt in statusOptions" :key="opt.value" class="col-6">
            <q-item
              clickable
              v-ripple
              @click="changeStatus(opt.value)"
              class="full-height"
              :class="
                sWoo.pedido.status === opt.value ? 'bg-blue-2' : 'bg-grey-3'
              "
            >
              <q-item-section avatar>
                <q-avatar :color="opt.cor.replace('bg-', '')">
                  <q-icon
                    name="check"
                    color="white"
                    size="md"
                    v-if="sWoo.pedido.status === opt.value"
                  />
                </q-avatar>
              </q-item-section>

              <q-item-section>
                <q-item-label class="text-weight-medium text-grey-9">
                  {{ opt.label }}
                </q-item-label>
              </q-item-section>
            </q-item>
          </div>
        </div>
      </q-card-section>

      <q-card-actions align="right" class="bg-grey-1 q-pa-sm">
        <q-btn flat label="Cancelar" color="negative" v-close-popup />
      </q-card-actions>
    </q-card>
  </q-dialog>
</template>
