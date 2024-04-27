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
  const ret = await api.get("/api/v1/select/portador", {
    params: { codportador: props.modelValue },
  });
  opcoes.value = ret.data;
};

const buscar = async (val, update, abort) => {
  // if (val.length < 1) {
  //   abort();
  //   return;
  // }

  update(async () => {
    const ret = await api.get("/api/v1/select/portador", {
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
    option-value="codportador"
    option-label="portador"
    v-bind="$attrs"
    options-cover
    @update:model-value="(value) => alterar(value)"
    input-debounce="500"
    clearable
  >
    <template v-slot:option="scope">
      <q-item
        v-bind="scope.itemProps"
        :class="scope.opt.inativo ? 'text-red' : ''"
      >
        <q-item-section avatar>
          <q-icon name="mdi-bank" v-if="scope.opt.codbanco" />
          <q-icon name="point_of_sale" v-else />
        </q-item-section>
        <q-item-section>
          <q-item-label>
            {{ scope.opt.portador }}
          </q-item-label>
          <q-item-label caption v-if="scope.opt.codbanco">
            {{ scope.opt.banco }}
            {{ scope.opt.agencia }}-{{ scope.opt.agenciadigito }}
            {{ scope.opt.conta }}-{{ scope.opt.contadigito }}
          </q-item-label>
        </q-item-section>
        <q-item-section side>
          <q-item-label caption>
            {{ scope.opt.filial }}
          </q-item-label>
        </q-item-section>
      </q-item>
    </template>
  </q-select>
</template>
