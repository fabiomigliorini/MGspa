<script setup>
import { ref, onMounted } from "vue";
import { db } from "boot/db";

const opcoes = ref([]);
const filtrado = ref([]);

onMounted(async () => {
  opcoes.value = await db.impressora.orderBy("impressora").toArray();
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
      (item) => item.impressora.toLowerCase().indexOf(pesquisa) > -1 || item.nome.toLowerCase().indexOf(pesquisa) > -1
    );
  });
};
</script>
<template>
  <q-select :options="filtrado" use-input @filter="pesquisa" emit-value map-options option-value="impressora"
    option-label="nome" v-bind="$attrs" options-cover>
    <template v-slot:option="scope">
      <q-item v-bind="scope.itemProps">
        <q-item-section avatar>
          <q-icon name="print" />
        </q-item-section>
        <q-item-section>
          <q-item-label>
            {{ scope.opt.nome }}
          </q-item-label>
          <q-item-label caption>
            {{ scope.opt.impressora }}
          </q-item-label>
        </q-item-section>
      </q-item>
    </template>
  </q-select>
</template>
