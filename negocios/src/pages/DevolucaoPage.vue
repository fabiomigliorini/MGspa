<script setup>
import { ref, watch, computed, onMounted } from "vue";
import { debounce } from "quasar";
import { negocioStore } from "stores/negocio";

import moment from "moment/min/moment-with-locales";
// import { route } from "quasar/wrappers";
import { useRoute } from "vue-router";
import { produtoStore } from "src/stores/produto";
moment.locale("pt-br");

const sNegocio = negocioStore();
const sProduto = produtoStore();

const route = useRoute();
const negocio = ref({});
const separator = ref("cell");
const inputDevolucao = ref({
  quantidadeDevolucao: null,
});

onMounted(() => {
  itensDevolucao();
});

const itensDevolucao = () => {
  negocio.value = sNegocio.ultimos.find(
    (item) => item.codnegocio == route.params.codnegocio
  );
};

const validaInputDevolucao = (val) => {
  var NegocioItens = [];
  var inputQuantidade = [];

  inputQuantidade = Object.keys(inputDevolucao.value);

  NegocioItens = negocio.value.itens.filter((item) => {
    item.quantidadeDevolucao = val;
    console.log(val);
    return item;
  });

  console.log(NegocioItens);

  NegocioItens.find((el) => {
    return el.codproduto == inputQuantidade;
  });

  // if (val > quantidade) {
  //   console.log("caiu no if");
  //   return "Quantidade máxima para devolução é";
  // }
  // return true;
};

const salvarDevolucao = (value) => {};
</script>
<template>
  <q-page>
    <h3 class="q-pl-md">Selecione os produtos para Devolução!</h3>

    <div class="q-pa-md">
      <q-form @submit="salvarDevolucao()">
        <q-markup-table :separator="separator" bordered>
          <thead>
            <tr>
              <th></th>
              <th class="text-left">Barras</th>
              <th class="text-left">Descrição</th>
              <th class="text-left">Quantidade Devolução</th>
              <th class="text-left">Total</th>
              <th class="text-left">Quantidade Original</th>
              <th class="text-left">Preço</th>
              <th class="text-left">Total</th>
            </tr>
          </thead>
          <tbody>
            <tr
              v-for="produto in negocio.itens"
              v-bind:key="produto.codproduto"
            >
              <td>
                <img
                  :src="sProduto.urlImagem(produto.codimagem)"
                  style="width: 2cm; border-radius: 50%"
                />
              </td>
              <td class="text-left">{{ produto.barras }}</td>
              <td class="text-left">{{ produto.produto }}</td>
              <td>
                <q-input
                  v-model="inputDevolucao[produto.codproduto]"
                  type="number"
                  outlined
                  item-aligned
                  dense
                  :rules="[validaInputDevolucao]"
                  style="max-width: 150px"
                />
              </td>
              <td class="text-right">
                {{
                  new Intl.NumberFormat("pt-BR", {
                    style: "decimal",
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2,
                  }).format(produto.valorprodutos)
                }}
              </td>
              <td class="text-right">
                {{
                  new Intl.NumberFormat("pt-BR", {
                    style: "decimal",
                    minimumFractionDigits: 3,
                    maximumFractionDigits: 3,
                  }).format(produto.quantidade)
                }}
              </td>
              <td class="text-right">
                {{
                  new Intl.NumberFormat("pt-BR", {
                    style: "decimal",
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2,
                  }).format(produto.valorunitario)
                }}
              </td>
              <td class="text-right">
                {{
                  new Intl.NumberFormat("pt-BR", {
                    style: "decimal",
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2,
                  }).format(produto.valorprodutos)
                }}
              </td>
            </tr>
          </tbody>
        </q-markup-table>
        <q-btn
          type="submit"
          label="Confirmar"
          class="q-mt-md flex flex-center"
          color="primary"
        />
      </q-form>
    </div>
  </q-page>
</template>
