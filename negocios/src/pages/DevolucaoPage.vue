<script setup>
import { ref, watch, computed, onMounted } from "vue";
import { negocioStore } from "stores/negocio";
import { useRoute } from "vue-router";
import { produtoStore } from "src/stores/produto";
import { Dialog, Notify } from "quasar";
import { useRouter } from "vue-router";
import moment from "moment/min/moment-with-locales";
moment.locale("pt-br");

const route = useRoute();
const router = useRouter();

const sNegocio = negocioStore();
const sProduto = produtoStore();

const itensDevolucao = ref([]);
const totalDevolucao = ref(null);

onMounted(() => {
  sNegocio.carregarPeloUuid(route.params.uuid).then(() => {
    itensDevolucao.value = [...sNegocio.negocio.itens];
    itensDevolucao.value = itensDevolucao.value.filter((i) => {
      return !i.inativo;
    });
    itensDevolucao.value.forEach((i) => {
      i.devolvido = 0;
      i.devolucoes.forEach((d) => {
        i.devolvido += d.quantidade;
      });
      i.disponivelDevolucao = i.quantidade - i.devolvido;
      i.quantidadeDevolucao = null;
      i.valorDevolucao = null;
    });
  });
});

const calcularTotalDevolucao = () => {
  if (sNegocio.negocio.codpessoa !== 1) {
    totalDevolucao.value = itensDevolucao.value.reduce(
      (n, { valorDevolucao }) => n + valorDevolucao,
      0
    );
  }
};

const calcularValorDevolucao = (item) => {
  let valoruitario = item.valortotal / item.quantidade;
  item.valorDevolucao =
    Math.round(valoruitario * item.quantidadeDevolucao * 100) / 100;
  calcularTotalDevolucao();
};

const marcarTodos = () => {
  itensDevolucao.value.forEach((i) => {
    if (i.disponivelDevolucao > 0) {
      i.quantidadeDevolucao = i.disponivelDevolucao;
      calcularValorDevolucao(i);
    }
  });
};

const marcarNenhum = () => {
  itensDevolucao.value.forEach((i) => {
    i.quantidadeDevolucao = null;
    calcularValorDevolucao(i);
  });
};

const salvarDevolucao = async () => {
  Dialog.create({
    title: "Devolução",
    message: "Tem certeza que deseja devolver esses produtos?",
    cancel: true,
  }).onOk(async () => {
    try {
      const ret = await sNegocio.Devolucao(itensDevolucao.value);
      if (ret.data.data) {
        router.push("/negocio/" + ret.data.data.codnegocio);
      }
    } catch (error) {}
  });
};
</script>
<template>
  <q-page>
    <div class="flex flex-center">
      <q-card
        style="max-width: 700px"
        class="q-pa-md q-ma-md"
        v-if="sNegocio.negocio"
      >
        <h4 class="q-ma-md">Selecione os produtos para Devolução!</h4>

        <q-banner
          inline-actions
          class="text-white bg-red"
          v-if="sNegocio.negocio.codpessoa == 1"
        >
          Informe o cadastro da pessoa para fazer uma devolução!
        </q-banner>

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
            label="Confirmar"
            class="q-mt-md flex flex-center"
            color="primary"
            @click="salvarDevolucao"
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
                  <q-item-label
                    overline
                    class="text-orange-7"
                    v-if="item.devolvido > 0"
                  >
                    {{
                      new Intl.NumberFormat("pt-BR", {
                        style: "decimal",
                        minimumFractionDigits: 3,
                        maximumFractionDigits: 3,
                      }).format(item.devolvido)
                    }}
                    já devolvido anteriormente
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
                    :disable="item.disponivelDevolucao == 0"
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
