<script setup>
import { ref, computed, onMounted, watch } from "vue";
import { useQuasar } from "quasar";
import { useRoute } from "vue-router";
import { metaStore } from "src/stores/meta";
import { guardaToken } from "src/stores";
import MGLayout from "layouts/MGLayout.vue";

const $q = useQuasar();
const route = useRoute();
const sMeta = metaStore();
const user = guardaToken();

const loading = ref(false);

const dash = computed(() => sMeta.dashboardColaborador || {});

const eventosLista = computed(() => {
  const ev = dash.value.eventos;
  if (!ev || typeof ev !== "object") return [];
  return Object.entries(ev).map(([tipo, total]) => ({
    tipo,
    total: parseFloat(total) || 0,
  }));
});

const formataMoeda = (valor) => {
  return new Intl.NumberFormat("pt-BR", {
    style: "currency",
    currency: "BRL",
  }).format(parseFloat(valor) || 0);
};

const tipoLabel = (tipo) => {
  const labels = {
    VENDA_VENDEDOR: "Comissão Vendas",
    VENDA_CAIXA: "Comissão Caixa",
    VENDA_SUBGERENTE: "Comissão Subgerente",
    VENDA_XEROX: "Comissão Xerox",
    META_ATINGIDA: "Meta Atingida",
    PREMIO_RANKING: "Prêmio Ranking",
    BONUS_FIXO: "Bônus Fixo",
    PREMIO_META: "Prêmio Meta",
    PREMIO_META_XEROX: "Prêmio Meta Xerox",
    PREMIO_META_SUBGERENTE: "Prêmio Meta Subgerente",
  };
  return labels[tipo] || tipo;
};

const tipoColor = (tipo) => {
  if (tipo.startsWith("PREMIO") || tipo === "META_ATINGIDA") return "green";
  if (tipo === "BONUS_FIXO") return "blue";
  return "grey-8";
};

const carregar = async (codmeta, codpessoa) => {
  if (!codmeta || !codpessoa) return;
  loading.value = true;
  try {
    await sMeta.getDashboardColaborador(codmeta, codpessoa);
  } catch (error) {
    $q.notify({
      color: "red-5",
      textColor: "white",
      icon: "error",
      message:
        error.response?.data?.mensagem || "Erro ao carregar dashboard",
    });
  } finally {
    loading.value = false;
  }
};

onMounted(() => {
  carregar(route.params.codmeta, route.params.codpessoa);
});

watch(
  () => [route.params.codmeta, route.params.codpessoa],
  ([novaMeta, novaPessoa]) => {
    if (novaMeta && novaPessoa) carregar(novaMeta, novaPessoa);
  }
);
</script>

<template>
  <MGLayout back-button>
    <template #tituloPagina>
      <span class="q-pl-sm">Dashboard do Colaborador</span>
    </template>

    <template #botaoVoltar>
      <q-btn
        flat
        dense
        round
        :to="{
          name: 'metaDashboard',
          params: { codmeta: route.params.codmeta },
        }"
        icon="arrow_back"
        aria-label="Voltar"
      />
    </template>

    <template #content>
      <q-page>
        <div style="max-width: 1100px; margin: auto" class="q-pa-md">
          <q-inner-loading :showing="loading" />

          <template v-if="dash.codpessoa && !loading">
            <!-- CABEÇALHO COLABORADOR -->
            <q-item class="q-pb-md">
              <q-item-section avatar>
                <q-avatar color="grey-8" text-color="grey-4" size="60px">
                  {{ dash.pessoa?.charAt(0) }}
                </q-avatar>
              </q-item-section>
              <q-item-section>
                <div class="text-h5 text-grey-9">
                  {{ dash.pessoa }}
                </div>
                <div class="text-caption text-grey" v-if="dash.cargo">
                  {{ dash.cargo }}
                </div>
              </q-item-section>
            </q-item>

            <!-- TOTAL GERAL -->
            <div class="row q-col-gutter-md q-mb-md">
              <div class="col-xs-12 col-sm-6">
                <q-card bordered flat>
                  <q-card-section class="text-center">
                    <div class="text-caption text-grey">
                      Total Acumulado
                    </div>
                    <div class="text-h5 text-grey-9">
                      {{ formataMoeda(dash.totalGeral) }}
                    </div>
                  </q-card-section>
                </q-card>
              </div>
              <div class="col-xs-12 col-sm-6">
                <q-card bordered flat>
                  <q-card-section class="text-center">
                    <div class="text-caption text-grey">
                      Tipos de Evento
                    </div>
                    <div class="text-h5 text-grey-9">
                      {{ eventosLista.length }}
                    </div>
                  </q-card-section>
                </q-card>
              </div>
            </div>

            <!-- AVISO -->
            <q-banner
              class="bg-yellow-2 text-grey-8 q-mb-md"
              rounded
              dense
            >
              <template v-slot:avatar>
                <q-icon name="info" color="orange" />
              </template>
              Valores sujeitos a alteração até o fechamento da meta.
            </q-banner>

            <!-- EVENTOS POR TIPO -->
            <q-card bordered flat>
              <q-card-section
                class="text-grey-9 text-overline row items-center"
              >
                RESUMO POR TIPO
              </q-card-section>

              <q-markup-table
                flat
                separator="horizontal"
                v-if="eventosLista.length > 0"
              >
                <thead>
                  <tr class="text-left">
                    <th>Tipo</th>
                    <th class="text-right">Valor</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="ev in eventosLista" :key="ev.tipo">
                    <td>
                      <q-badge
                        :color="tipoColor(ev.tipo)"
                        :label="tipoLabel(ev.tipo)"
                      />
                    </td>
                    <td
                      class="text-right"
                      :class="ev.total < 0 ? 'text-red' : ''"
                    >
                      {{ formataMoeda(ev.total) }}
                    </td>
                  </tr>
                  <tr class="text-weight-bold">
                    <td>Total</td>
                    <td class="text-right">
                      {{ formataMoeda(dash.totalGeral) }}
                    </td>
                  </tr>
                </tbody>
              </q-markup-table>
              <div v-else class="q-pa-md text-center text-grey">
                Nenhum evento registrado
              </div>
            </q-card>
          </template>
        </div>
      </q-page>
    </template>
  </MGLayout>
</template>
