<script setup>
import { ref } from "vue";
import MgInputValor from "@components/MgInputValor.vue";

const emit = defineEmits(["submit"]);

const dialog = ref(false);
const isNovo = ref(false);
const model = ref({});

const abrirNovo = () => {
  model.value = { cargo: "", salario: null, adicional: null };
  isNovo.value = true;
  dialog.value = true;
};

const editar = (cargo) => {
  model.value = {
    codcargo: cargo.codcargo,
    cargo: cargo.cargo,
    salario: cargo.salario,
    adicional: cargo.adicional,
  };
  isNovo.value = false;
  dialog.value = true;
};

const submit = () => {
  dialog.value = false;
  emit("submit", { ...model.value }, isNovo.value);
};

defineExpose({ abrirNovo, editar });
</script>

<template>
  <q-dialog v-model="dialog">
    <q-card bordered flat style="width: 400px; max-width: 90vw">
      <q-form @submit="submit()">
        <q-card-section class="text-grey-9 text-overline">
          <template v-if="isNovo">NOVO CARGO</template>
          <template v-else>EDITAR CARGO</template>
        </q-card-section>

        <q-separator inset />

        <q-card-section>
          <div class="row q-col-gutter-md">
            <div class="col-12">
              <q-input
                outlined
                v-model="model.cargo"
                label="Nome do Cargo"
                autofocus
                :rules="[
                  (val) => (val && val.length > 0) || 'Cargo obrigatório!',
                ]"
              />
            </div>
            <div class="col-8">
              <MgInputValor
                v-model="model.salario"
                label="Salário Base"
                prefix="R$"
                :rules="[(val) => (val && val > 1) || 'Salário obrigatório!']"
                :min="1"
              />
            </div>
            <div class="col-4">
              <MgInputValor
                v-model="model.adicional"
                label="Adicional"
                suffix="%"
                :min="0.01"
              />
            </div>
          </div>
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
