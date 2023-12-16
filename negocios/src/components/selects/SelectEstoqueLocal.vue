<script setup>
import { ref, onMounted } from "vue";
import { db } from "boot/db";

const props = defineProps({
  somenteAtivos: {
    type: Boolean,
    default: true,
  },
});

const opcoes = ref([]);
const filtrado = ref([]);

onMounted(async () => {
  let regs = db.estoqueLocal.orderBy("[codfilial+sigla]");
  if (props.somenteAtivos) {
    opcoes.value = await regs
      .filter((item) => {
        return item.inativo == null;
      })
      .toArray();
  } else {
    opcoes.value = await regs.toArray();
  }
  filtrado.value = opcoes.value;
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
    use-input
    @filter="pesquisa"
    emit-value
    map-options
    option-value="codestoquelocal"
    option-label="sigla"
    v-bind="$attrs"
    options-cover
  >
    <template v-slot:option="scope">
      <q-item v-bind="scope.itemProps">
        <q-item-section avatar>
          <q-icon name="warehouse" />
        </q-item-section>
        <q-item-section>
          <q-item-label>{{ scope.opt.sigla }}</q-item-label>
          <q-item-label caption>{{ scope.opt.estoquelocal }}</q-item-label>
        </q-item-section>
      </q-item>
    </template>
  </q-select>
</template>
