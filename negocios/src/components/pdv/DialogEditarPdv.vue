<script setup>
import { ref, watch } from "vue";
import SelectFilial from "components/selects/SelectFilial.vue";
import SelectSetor from "src/components/selects/SelectSetor.vue";

const props = defineProps({
  modelValue: Boolean,
  pdv: Object,
  titulo: {
    type: String,
    required: true,
  },
});

const form = ref(null);

const onSubmit = () => {
  if (form.value && form.value.validate()) {
    salvar();
  }
};

const emit = defineEmits(["update:modelValue", "salvar"]);

const model = ref({});

watch(
  () => props.modelValue,
  (val) => {
    if (val) {
      model.value = { ...props.pdv };
    }
  }
);

const salvar = () => {
  emit("salvar", model.value);
  emit("update:modelValue", false);
};
</script>
<template>
  <q-dialog
    :model-value="modelValue"
    @update:model-value="emit('update:modelValue', $event)"
  >
    <q-card style="min-width: 350px">
      <q-form ref="form" @submit.prevent="onSubmit">
        <q-card-section class="text-h6">
          {{ titulo }}
        </q-card-section>
        <q-card-section class="q-gutter-md">
          <q-input outlined v-model="model.apelido" autofocus label="Apelido" />
          <select-filial
            outlined
            v-model="model.codfilial"
            :rules="[(val) => !!val || 'Filial é obrigatória']"
            hide-bottom-space
          />
          <select-setor
            outlined
            v-model="model.codsetor"
            label="Setor"
            :rules="[(val) => !!val || 'Setor é obrigatório']"
            hide-bottom-space
          />
          <q-input
            outlined
            autogrow
            v-model="model.observacoes"
            label="Observações"
            type="textarea"
            class="q-mb-md"
          />
        </q-card-section>

        <q-card-actions align="right" class="text-primary">
          <q-btn flat label="Cancelar" v-close-popup />
          <q-btn flat label="Salvar" type="submit" />
        </q-card-actions>
      </q-form>
    </q-card>
  </q-dialog>
</template>
