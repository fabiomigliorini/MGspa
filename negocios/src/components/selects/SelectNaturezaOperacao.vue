<script setup>
import { ref, onMounted } from "vue";
import { db } from "boot/db";

const opcoes = ref([]);
const filtrado = ref([]);

onMounted(async () => {
  opcoes.value = await db.naturezaOperacao
    .orderBy("[codoperacao+naturezaoperacao]")
    .toArray();
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
    filtrado.value = opcoes.value.filter(
      (item) => item.naturezaoperacao.toLowerCase().indexOf(pesquisa) > -1
    );
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
    option-value="codnaturezaoperacao"
    option-label="naturezaoperacao"
    v-bind="$attrs"
    options-cover
  >
    <template v-slot:option="scope">
      <q-item v-bind="scope.itemProps">
        <q-item-section avatar>
          <q-icon v-if="scope.opt.codoperacao == 1" name="login" color="red" />
          <q-icon v-else name="logout" color="green" />
        </q-item-section>
        <q-item-section>
          <q-item-label>{{ scope.opt.naturezaoperacao }}</q-item-label>
          <q-item-label caption v-if="scope.opt.codoperacao == 1"
            >Entradavenda</q-item-label
          >
          <q-item-label caption v-else>Saída</q-item-label>
        </q-item-section>
      </q-item>
    </template>
  </q-select>
</template>
