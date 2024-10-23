<script setup>
import { ref, onMounted, watch } from "vue";
import { api } from 'src/boot/axios';

const props = defineProps({
  modelValue: {
    type: Number,
  }
});

const emit = defineEmits(["update:modelValue"]);

const alterar = (value) => {
  emit("update:modelValue", value);
};

onMounted(async () => {
  if (!props.modelValue) {
    return;
  }
  buscarPeloCod(props.modelValue);
});

const buscarPeloCod = async () => {
  if (!props.modelValue) {
    return;
  }
  const ret = await api.get("/api/v1/select/cidade", {
    params: { codcidade: props.modelValue },
  });
  opcoes.value = ret.data;
};

const buscar = async (val, update, abort) => {
  if (val.length < 2) {
    abort();
    return;
  }

  update(async () => {
    const ret = await api.get("/api/v1/select/cidade", {
      params: {
        cidade: val
      },
    });
    opcoes.value = ret.data;
  });
};

const opcoes = ref([]);

watch(
  () => props.modelValue,
  (newValue) => {
    buscarPeloCod(newValue);
  }
);

</script>

<template>
  <q-select :options="opcoes" :model-value="modelValue" use-input @filter="buscar" emit-value map-options
    option-value="value" option-label="label" v-bind="$attrs" options-cover
    @update:model-value="(value) => alterar(value)" input-debounce="500" clearable>

    <template v-slot:no-option>
      <q-item>
        <q-item-section class="text-grey">
          Nenhum resultado encontrado.
        </q-item-section>
      </q-item>
    </template>
  </q-select>
</template>
