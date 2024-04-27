<script setup>
import { ref, onMounted, watch } from "vue";
import { api } from "src/boot/axios";

const props = defineProps({
  modelValue: {
    type: Number,
  },
  somenteAtivos: {
    type: Boolean,
    default: true,
  },
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
  const ret = await api.get("/api/v1/select/usuario", {
    params: { codusuario: props.modelValue },
  });
  opcoes.value = ret.data;
};

const buscar = async (val, update, abort) => {
  if (val.length < 2) {
    abort();
    return;
  }

  update(async () => {
    const ret = await api.get("/api/v1/select/usuario", {
      params: {
        busca: val,
        somenteAtivos: props.somenteAtivos,
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
  <q-select
    :options="opcoes"
    :model-value="modelValue"
    use-input
    @filter="buscar"
    emit-value
    map-options
    option-value="codusuario"
    option-label="usuario"
    v-bind="$attrs"
    options-cover
    @update:model-value="(value) => alterar(value)"
    input-debounce="500"
    clearable
  >
    <template v-slot:option="scope">
      <q-item v-bind="scope.itemProps">
        <q-item-section :class="scope.opt.inativo ? 'text-red' : ''">
          <q-item-label>
            {{ scope.opt.usuario }}
          </q-item-label>
          <q-item-label lines="1">
            {{ scope.opt.fantasia }}
          </q-item-label>
        </q-item-section>
        <!--
        <q-item-section side>
          <q-item-label caption>
            #{{ String(scope.opt.codusuario).padStart(8, "0") }}
          </q-item-label>
          <q-item-label caption>
            {{ scope.opt.filial }}
          </q-item-label>
        </q-item-section>
        -->
      </q-item>
    </template>
  </q-select>
</template>
