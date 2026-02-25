<script setup>
import { ref, watch } from "vue";
import { useQuasar } from "quasar";
import { rhStore } from "src/stores/rh";

const $q = useQuasar();
const sRh = rhStore();

const props = defineProps({
  modelValue: { type: Boolean, default: false },
  indicador: { type: Object, default: null },
});

const emit = defineEmits(["update:modelValue", "salvo"]);

const meta = ref(null);

watch(
  () => props.indicador,
  (ind) => {
    meta.value = ind?.meta ?? null;
  }
);

const dialog = ref(false);
watch(
  () => props.modelValue,
  (v) => {
    dialog.value = v;
  }
);
watch(dialog, (v) => {
  emit("update:modelValue", v);
});

const salvar = async () => {
  dialog.value = false;
  try {
    await sRh.atualizarMeta(props.indicador.codindicador, { meta: meta.value });
    $q.notify({
      color: "green-5",
      textColor: "white",
      icon: "done",
      message: "Meta atualizada",
    });
    emit("salvo");
  } catch (error) {
    const data = error.response?.data;
    const msg =
      (data?.errors && Object.values(data.errors).flat()[0]) ||
      data?.mensagem ||
      data?.message ||
      data?.erro ||
      "Erro ao atualizar meta";
    $q.notify({
      color: "red-5",
      textColor: "white",
      icon: "error",
      message: msg,
    });
  }
};
</script>

<template>
  <q-dialog v-model="dialog">
    <q-card bordered flat style="width: 400px; max-width: 90vw">
      <q-form @submit="salvar()">
        <q-card-section class="text-grey-9 text-overline">
          EDITAR META
        </q-card-section>

        <q-separator inset />

        <q-card-section>
          <q-input
            outlined
            v-model.number="meta"
            label="Meta (R$)"
            type="number"
            step="0.01"
            autofocus
          />
        </q-card-section>

        <q-separator inset />

        <q-card-actions align="right" class="text-primary">
          <q-btn
            flat
            label="Cancelar"
            v-close-popup
            tabindex="-1"
            color="grey-8"
          />
          <q-btn flat label="Salvar" type="submit" />
        </q-card-actions>
      </q-form>
    </q-card>
  </q-dialog>
</template>
