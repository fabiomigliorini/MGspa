<script setup>
import { ref, onMounted } from "vue";
import { db } from "src/boot/db";
import { produtoStore } from "src/stores/produto";
import emitter from "src/utils/emitter";

const sProduto = produtoStore();
const prancheta = ref({});
const categorias = ref([]);
const produtos = ref([]);
const historico = ref([]);

onMounted(() => {
  carregarPrancheta();
});

const carregarPrancheta = async () => {
  prancheta.value = await db.prancheta.orderBy("ordem").toArray();
  inicio();
};

const inicio = () => {
  categorias.value = prancheta.value;
  produtos.value = [];
  historico.value = [];
};

const selecionarCategoria = (cat) => {
  historico.value.push(cat);
  categorias.value = cat.categorias;
  produtos.value = cat.produtos;
};

const adicionarProduto = (prod) => {
  emitter.emit("adicionarProduto", prod);
};

const voltar = (i) => {
  categorias.value = historico.value[i].categorias;
  produtos.value = historico.value[i].produtos;
  historico.value = historico.value.slice(0, i + 1);
};
</script>
<template>
  <div class="q-pa-md">
    <q-breadcrumbs>
      <q-breadcrumbs-el icon="home" @click="inicio()" class="cursor-pointer" />
      <q-breadcrumbs-el
        v-for="(cat, i) in historico"
        :key="i"
        :label="cat.categoria"
        @click="voltar(i)"
        class="cursor-pointer"
      />
    </q-breadcrumbs>
  </div>
  <q-list>
    <q-separator />

    <template v-for="cat in categorias" :key="cat.codpranchetacategoria">
      <q-item clickable v-ripple @click="selecionarCategoria(cat)">
        <q-item-section avatar>
          <q-avatar rounded size="90px">
            <img :src="cat.imagem" v-if="cat.imagem" />
            <q-icon name="mdi-bookshelf" color="grey" />
          </q-avatar>
        </q-item-section>
        <q-item-section>
          <q-item-label>
            {{ cat.categoria }}
          </q-item-label>
        </q-item-section>
      </q-item>
      <q-separator />
    </template>
    <template v-for="prod in produtos" :key="prod.codprancheta">
      <q-item clickable v-ripple @click="adicionarProduto(prod)">
        <q-item-section avatar>
          <q-avatar rounded size="90px">
            <img :src="sProduto.urlImagem(prod.codimagem)" />
          </q-avatar>
        </q-item-section>
        <q-item-section>
          <q-item-label>
            {{ prod.descricao }}
          </q-item-label>
          <q-item-label caption>
            {{ prod.barras }}
          </q-item-label>
        </q-item-section>
        <q-item-section side>
          {{
            new Intl.NumberFormat("pt-BR", {
              style: "decimal",
              minimumFractionDigits: 2,
              maximumFractionDigits: 2,
            }).format(prod.preco)
          }}
        </q-item-section>
      </q-item>
      <q-separator />
    </template>
  </q-list>
</template>
