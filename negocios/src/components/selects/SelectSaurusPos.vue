<script setup>
import { ref, onMounted, watch } from "vue";
import { db } from "boot/db";

const props = defineProps({
  modelValue: {
    type: Number,
  },
  codestoquelocal: {
    type: Number,
    default: null,
  },
});

const emit = defineEmits(["update:modelValue"]);

const opcoes = ref([]);
const filtrado = ref([]);

const alterar = (value) => {
  emit("update:modelValue", value);
};

const buscarOpcoes = async () => {
  if (!props.codestoquelocal) {
    return;
  }
  const loc = await db.estoqueLocal.get(props.codestoquelocal);
  loc.SaurusPosS.sort((a, b) => {
    if (a.apelido > b.apelido) {
      return 1;
    }
    return -1;
  });
  opcoes.value = loc.SaurusPosS;
  filtrado.value = opcoes.value;
};

watch(
  () => props.codestoquelocal,
  (newValue, oldValue) => {
    buscarOpcoes();
  }
);

onMounted(async () => {
  buscarOpcoes();
});

const pesquisa = (val, update) => {
  if (val === "") {
    update(() => {
      filtrado.value = opcoes.value;
    });
    return;
  }
  update(() => {
    const pesquisa = val.toLowerCase();
    filtrado.value = opcoes.value.filter((item) => {
      return (
        item.estoquelocal.toLowerCase().indexOf(pesquisa) > -1 ||
        item.sigla.toLowerCase().indexOf(pesquisa) > -1
      );
    });
  });
};
</script>
<template>
  <q-select
    :options="filtrado"
    :model-value="modelValue"
    use-input
    @filter="pesquisa"
    emit-value
    map-options
    option-value="codsauruspdv"
    option-label="apelido"
    v-bind="$attrs"
    options-cover
    @update:model-value="(value) => alterar(value)"
  >
    <template v-slot:option="scope">
      <q-item v-bind="scope.itemProps">
        <q-item-section avatar>
          <q-icon name="print" />
        </q-item-section>
        <q-item-section>
          <q-item-label>
            {{ scope.opt.apelido }}
          </q-item-label>
          <q-item-label caption>
            {{ scope.opt.serial }}
          </q-item-label>
        </q-item-section>
      </q-item>
    </template>
  </q-select>
</template>
