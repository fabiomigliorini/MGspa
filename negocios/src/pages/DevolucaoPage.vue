<script setup>
import { ref, watch, computed, onMounted } from "vue";
import { debounce } from "quasar";
import { negocioStore } from "stores/negocio";

import moment from "moment/min/moment-with-locales";
import { useRoute } from "vue-router";
import { produtoStore } from "src/stores/produto";
moment.locale("pt-br");

const route = useRoute();

const sNegocio = negocioStore();
const sProduto = produtoStore();

const itensDevolucao = ref([]);
const totalDevolucao = ref(null);

onMounted(() => {
  sNegocio.carregarPeloUuid(route.params.uuid).then(() => {
    itensDevolucao.value = [...sNegocio.negocio.itens];
    itensDevolucao.value.forEach((i) => {
      i.disponivelDevolucao = i.quantidade;
      i.quantidadeDevolucao = null;
      i.valorDevolucao = null;
    });
  });
});

const calcularTotalDevolucao = () => {
  totalDevolucao.value = itensDevolucao.value.reduce(
    (n, { valorDevolucao }) => n + valorDevolucao,
    0
  );
};

const calcularValorDevolucao = (item) => {
  let valoruitario = item.valortotal / item.quantidade;
  item.valorDevolucao =
    Math.round(valoruitario * item.quantidadeDevolucao * 100) / 100;
  calcularTotalDevolucao();
};

const marcarTodos = () => {
  itensDevolucao.value.forEach((i) => {
    i.quantidadeDevolucao = i.disponivelDevolucao;
    calcularValorDevolucao(i);
  });
};

const marcarNenhum = () => {
  itensDevolucao.value.forEach((i) => {
    i.quantidadeDevolucao = null;
    calcularValorDevolucao(i);
  });
};

const salvarDevolucao = (value) => {
  console.log("chama a devolucao bebe!");
};
</script>
<template>
  <q-page>
    <div class="flex flex-center">
      <q-card style="max-width: 700px" class="q-pa-md q-ma-md">
        <h4 class="q-ma-md">Selecione os produtos para Devolução!</h4>

        <div class="row">
          <q-btn
            label="Marcar Todos"
            class="q-mt-md flex flex-center"
            color="secondary"
            @click="marcarTodos()"
            flat
          />
          <q-btn
            label="Marcar Nenhum"
            class="q-mt-md flex flex-center"
            color="secondary"
            @click="marcarNenhum()"
            flat
          />
          <q-btn
            type="salvar"
            label="Confirmar"
            class="q-mt-md flex flex-center"
            color="primary"
            flat
            v-if="totalDevolucao"
          />
        </div>

        <q-form @submit="salvarDevolucao()">
          <q-list class="rounded-borders">
            <template
              v-for="item in itensDevolucao"
              :key="item.codnegocioprodutobarra"
            >
              <q-item>
                <q-item-section avatar>
                  <q-avatar>
                    <img :src="sProduto.urlImagem(item.codimagem)" />
                  </q-avatar>
                </q-item-section>

                <q-item-section class="col">
                  <q-item-label lines="1">{{ item.produto }}</q-item-label>
                  <q-item-label caption lines="1">
                    <span class="text-weight-bold">{{ item.barras }}</span>
                  </q-item-label>
                  <q-item-label caption lines="3">
                    {{
                      new Intl.NumberFormat("pt-BR", {
                        style: "decimal",
                        minimumFractionDigits: 3,
                        maximumFractionDigits: 3,
                      }).format(item.quantidade)
                    }}
                    de R$
                    {{
                      new Intl.NumberFormat("pt-BR", {
                        style: "decimal",
                        minimumFractionDigits: 2,
                        maximumFractionDigits: 2,
                      }).format(item.valorunitario)
                    }}
                    <template v-if="item.valordesconto">
                      - R$
                      {{
                        new Intl.NumberFormat("pt-BR", {
                          style: "decimal",
                          minimumFractionDigits: 2,
                          maximumFractionDigits: 2,
                        }).format(item.valordesconto)
                      }}
                      (desconto)
                    </template>
                    <template v-if="item.valorfrete">
                      + R$
                      {{
                        new Intl.NumberFormat("pt-BR", {
                          style: "decimal",
                          minimumFractionDigits: 2,
                          maximumFractionDigits: 2,
                        }).format(item.valorfrete)
                      }}
                      (Frete)
                    </template>
                    <template v-if="item.valorseguro">
                      + R$
                      {{
                        new Intl.NumberFormat("pt-BR", {
                          style: "decimal",
                          minimumFractionDigits: 2,
                          maximumFractionDigits: 2,
                        }).format(item.valorseguro)
                      }}
                      (Seguro)
                    </template>
                    <template v-if="item.valoroutras">
                      + R$
                      {{
                        new Intl.NumberFormat("pt-BR", {
                          style: "decimal",
                          minimumFractionDigits: 2,
                          maximumFractionDigits: 2,
                        }).format(item.valoroutras)
                      }}
                      (Outras)
                    </template>
                    = R$
                    {{
                      new Intl.NumberFormat("pt-BR", {
                        style: "decimal",
                        minimumFractionDigits: 2,
                        maximumFractionDigits: 2,
                      }).format(item.valortotal)
                    }}
                  </q-item-label>
                </q-item-section>
                <q-item-section style="max-width: 160px">
                  <q-input
                    v-model="item.quantidadeDevolucao"
                    type="number"
                    min="1"
                    step="0.001"
                    :max="item.disponivelDevolucao"
                    outlined
                    item-aligned
                    label="Quantidade"
                    input-class="text-right"
                    @change="calcularValorDevolucao(item)"
                    :rules="[(val) => val <= item.disponivelDevolucao]"
                  />
                </q-item-section>
                <q-item-section class="text-right" style="max-width: 90px">
                  <template v-if="item.valorDevolucao">
                    R$
                    {{
                      new Intl.NumberFormat("pt-BR", {
                        style: "decimal",
                        minimumFractionDigits: 2,
                        maximumFractionDigits: 2,
                      }).format(item.valorDevolucao)
                    }}
                  </template>
                </q-item-section>
              </q-item>

              <q-separator inset="item" />
            </template>
          </q-list>

          <template v-if="totalDevolucao">
            <h6 class="text-right q-pa-md q-ma-sm">
              Total Devolução: R$
              {{
                new Intl.NumberFormat("pt-BR", {
                  style: "decimal",
                  minimumFractionDigits: 2,
                  maximumFractionDigits: 2,
                }).format(totalDevolucao)
              }}
            </h6>
          </template>
        </q-form>
      </q-card>
    </div>
  </q-page>
</template>
