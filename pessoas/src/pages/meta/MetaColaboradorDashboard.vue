<script setup>
import { ref, computed, onMounted, watch } from "vue";
import { useQuasar } from "quasar";
import { useRoute } from "vue-router";
import { metaStore } from "src/stores/meta";
import { guardaToken } from "src/stores";
import MGLayout from "layouts/MGLayout.vue";
import { getTipo } from "src/config/bonificacaoTipos";

const $q = useQuasar();
const route = useRoute();
const sMeta = metaStore();
const user = guardaToken();

const loading = ref(false);
const eventos = ref([]);
const paginaEventos = ref(1);
const loadingEventos = ref(false);

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

const formataMoedaPrecisa = (valor) => {
  const v = parseFloat(valor) || 0;
  const base = new Intl.NumberFormat("pt-BR", {
    style: "currency",
    currency: "BRL",
    minimumFractionDigits: 5,
    maximumFractionDigits: 5,
  }).format(v);
  const idx = base.length - 3;
  return { principal: base.substring(0, idx), extra: base.substring(idx) };
};


const negocioUrl = (codnegocio) =>
  process.env.APP_NEGOCIOS_URL + "/negocio/" + codnegocio;

const carregarEventos = async () => {
  paginaEventos.value = 1;
  eventos.value = [];
  loadingEventos.value = false;
  try {
    const ret = await sMeta.getDashboardColaboradorEventos(
      route.params.codmeta,
      route.params.codpessoa,
      1
    );
    eventos.value = ret.data.data;
    if (ret.data.meta.last_page <= 1) loadingEventos.value = true;
  } catch {
    // silencioso - extrato é complementar
  }
};

const scrollEventos = async (index, done) => {
  paginaEventos.value++;
  try {
    const ret = await sMeta.getDashboardColaboradorEventos(
      route.params.codmeta,
      route.params.codpessoa,
      paginaEventos.value
    );
    eventos.value.push(...ret.data.data);
    if (paginaEventos.value >= ret.data.meta.last_page) {
      loadingEventos.value = true;
    }
    done();
  } catch {
    done(true);
  }
};

const formataData = (data) => {
  if (!data) return "";
  const d = new Date(data);
  return d.toLocaleDateString("pt-BR") + " " + d.toLocaleTimeString("pt-BR", { hour: "2-digit", minute: "2-digit" });
};

const carregar = async (codmeta, codpessoa) => {
  if (!codmeta || !codpessoa) return;
  loading.value = true;
  try {
    await sMeta.getDashboardColaborador(codmeta, codpessoa);
    await carregarEventos();
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
                      <q-icon
                        :name="getTipo(ev.tipo).icon"
                        :color="getTipo(ev.tipo).color"
                        size="xs"
                        class="q-mr-xs"
                      />
                      <span :class="'text-' + getTipo(ev.tipo).color">
                        {{ getTipo(ev.tipo).label }}
                      </span>
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

            <!-- EXTRATO DETALHADO -->
            <q-card bordered flat class="q-mt-md">
              <q-card-section
                class="text-grey-9 text-overline row items-center"
              >
                EXTRATO DETALHADO
              </q-card-section>

              <q-scroll-area style="height: 400px">
                <q-infinite-scroll
                  @load="scrollEventos"
                  :disable="loadingEventos"
                  :offset="100"
                >
                  <q-list separator>
                    <q-item v-for="ev in eventos" :key="ev.codbonificacaoevento">
                      <q-item-section avatar>
                        <q-icon
                          :name="getTipo(ev.tipo).icon"
                          :color="getTipo(ev.tipo).color"
                          size="sm"
                        />
                      </q-item-section>
                      <q-item-section>
                        <q-item-label :class="'text-' + getTipo(ev.tipo).color">
                          {{ getTipo(ev.tipo).label }}
                        </q-item-label>
                        <q-item-label caption>
                          <q-btn
                            v-if="ev.codnegocio"
                            flat
                            dense
                            no-caps
                            size="sm"
                            color="primary"
                            :href="negocioUrl(ev.codnegocio)"
                            target="_blank"
                            :label="'#' + String(ev.codnegocio).padStart(8, '0')"
                            type="a"
                            class="q-pa-none"
                          />
                          {{ formataData(ev.lancamento) }}
                          <q-badge
                            v-if="ev.manual"
                            color="orange"
                            label="manual"
                            class="q-ml-xs"
                          />
                        </q-item-label>
                      </q-item-section>
                      <q-item-section side>
                        <span
                          class="text-weight-medium"
                          :class="ev.valor < 0 ? 'text-red' : 'text-green-8'"
                        >
                          {{ formataMoedaPrecisa(ev.valor).principal
                          }}<span style="font-size: 0.7em; vertical-align: super; opacity: 0.5">{{
                            formataMoedaPrecisa(ev.valor).extra
                          }}</span>
                        </span>
                      </q-item-section>
                    </q-item>
                  </q-list>

                  <template v-slot:loading>
                    <div class="row justify-center q-my-md">
                      <q-spinner color="primary" size="30px" />
                    </div>
                  </template>
                </q-infinite-scroll>

                <div
                  v-if="eventos.length === 0 && loadingEventos"
                  class="q-pa-md text-center text-grey"
                >
                  Nenhum evento registrado
                </div>
              </q-scroll-area>
            </q-card>
          </template>
        </div>
      </q-page>
    </template>
  </MGLayout>
</template>
