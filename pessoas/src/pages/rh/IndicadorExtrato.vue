<script setup>
import { ref, computed, onMounted } from "vue";
import { useQuasar } from "quasar";
import { useRoute } from "vue-router";
import { rhStore } from "src/stores/rh";
import moment from "moment";

const $q = useQuasar();
const route = useRoute();
const sRh = rhStore();

const loading = ref(false);
const indicador = ref(null);
const lancamentos = ref([]);

// --- HELPERS ---

const formataMoeda = (valor) => {
  return new Intl.NumberFormat("pt-BR", {
    style: "currency",
    currency: "BRL",
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
  }).format(parseFloat(valor) || 0);
};

const formataPercentual = (valor) => {
  if (valor == null) return "—";
  return (
    new Intl.NumberFormat("pt-BR", {
      minimumFractionDigits: 1,
      maximumFractionDigits: 1,
    }).format(parseFloat(valor) || 0) + "%"
  );
};

const corProgresso = (percentual) => {
  if (!percentual) return "grey";
  if (percentual >= 100) return "green";
  if (percentual >= 70) return "orange";
  return "red";
};

const tipoIndicadorLabel = (tipo) => {
  const map = { U: "Unidade", S: "Setor", V: "Vendedor", C: "Caixa" };
  return map[tipo] || tipo;
};

const tipoIndicadorColor = (tipo) => {
  const map = { V: "blue", C: "purple", U: "orange", S: "teal" };
  return map[tipo] || "grey";
};

const negocioUrl = (codnegocio) =>
  process.env.APP_NEGOCIOS_URL + "/negocio/" + codnegocio;

// --- COMPUTED ---

const titulo = computed(() => {
  if (!indicador.value) return "";
  const ind = indicador.value;
  let t = tipoIndicadorLabel(ind.tipo);
  const un = ind.unidade_negocio?.descricao;
  const setor = ind.setor?.setor;
  if (un || setor) {
    t += " — " + [un, setor].filter(Boolean).join(" / ");
  }
  return t;
});

const nomeColaborador = computed(() => {
  if (!indicador.value?.colaborador) return null;
  return indicador.value.colaborador.pessoa?.fantasia || null;
});

const valorAcumulado = computed(
  () => parseFloat(indicador.value?.valoracumulado) || 0
);

const meta = computed(() => parseFloat(indicador.value?.meta) || null);

const atingimento = computed(() => {
  if (!valorAcumulado.value || !meta.value) return null;
  return (valorAcumulado.value / meta.value) * 100;
});

const totalLancamentos = computed(() => lancamentos.value.length);

// --- LIFECYCLE ---

const carregar = async () => {
  loading.value = true;
  try {
    const data = await sRh.getExtrato(route.params.codindicador);
    indicador.value = data.indicador;
    lancamentos.value = data.lancamentos;
  } catch (error) {
    const msg =
      error.response?.data?.message ||
      error.response?.data?.erro ||
      "Erro ao carregar extrato";
    $q.notify({
      color: "red-5",
      textColor: "white",
      icon: "error",
      message: msg,
    });
  } finally {
    loading.value = false;
  }
};

onMounted(() => {
  carregar();
});
</script>

<template>
  <div style="max-width: 1280px; margin: auto">
    <q-inner-loading :showing="loading" />

    <template v-if="!loading && indicador">
      <!-- HEADER -->
      <q-item class="q-pt-lg q-pb-sm">
        <q-item-section avatar>
          <q-avatar
            :color="tipoIndicadorColor(indicador.tipo)"
            text-color="white"
            size="80px"
            :icon="
              indicador.tipo === 'V'
                ? 'person'
                : indicador.tipo === 'C'
                ? 'point_of_sale'
                : indicador.tipo === 'S'
                ? 'store'
                : 'business'
            "
          />
        </q-item-section>
        <q-item-section>
          <div class="text-h5 text-grey-9">
            {{ titulo }}
          </div>
          <div class="text-body2 text-grey-7" v-if="nomeColaborador">
            {{ nomeColaborador }}
          </div>
          <div class="text-caption text-grey">
            #{{ indicador.codindicador }}
          </div>
        </q-item-section>
        <q-item-section side>
          <q-btn
            flat
            dense
            round
            icon="arrow_back"
            color="grey-7"
            @click="$router.back()"
          >
            <q-tooltip>Voltar</q-tooltip>
          </q-btn>
        </q-item-section>
      </q-item>

      <div class="q-pa-md">
        <!-- CARDS RESUMO -->
        <div class="row q-col-gutter-md q-mb-md items-stretch">
          <div class="col-xs-6 col-sm-3">
            <q-card bordered flat class="full-height">
              <q-card-section class="text-center">
                <div class="text-caption text-grey">Valor Acumulado</div>
                <div class="text-h6 text-grey-9">
                  {{ formataMoeda(valorAcumulado) }}
                </div>
              </q-card-section>
            </q-card>
          </div>
          <div class="col-xs-6 col-sm-3">
            <q-card bordered flat class="full-height">
              <q-card-section class="text-center">
                <div class="text-caption text-grey">Meta</div>
                <div class="text-h6 text-grey-9">
                  {{ meta ? formataMoeda(meta) : "—" }}
                </div>
              </q-card-section>
            </q-card>
          </div>
          <div class="col-xs-6 col-sm-3">
            <q-card bordered flat class="full-height">
              <q-card-section class="text-center">
                <div class="text-caption text-grey">Atingimento</div>
                <div
                  class="text-h6"
                  :class="
                    atingimento
                      ? 'text-' + corProgresso(atingimento)
                      : 'text-grey-9'
                  "
                >
                  {{ atingimento ? formataPercentual(atingimento) : "—" }}
                </div>
                <q-linear-progress
                  v-if="atingimento"
                  :value="Math.min(atingimento / 100, 1)"
                  size="6px"
                  stripe
                  rounded
                  class="q-mt-xs"
                  :color="corProgresso(atingimento)"
                />
              </q-card-section>
            </q-card>
          </div>
          <div class="col-xs-6 col-sm-3">
            <q-card bordered flat class="full-height">
              <q-card-section class="text-center">
                <div class="text-caption text-grey">Lançamentos</div>
                <div class="text-h6 text-grey-9">
                  {{ totalLancamentos }}
                </div>
              </q-card-section>
            </q-card>
          </div>
        </div>

        <!-- TABELA DE LANÇAMENTOS -->
        <q-card bordered flat>
          <q-card-section class="text-grey-9 text-overline">
            EXTRATO
          </q-card-section>

          <q-markup-table
            flat
            separator="horizontal"
            v-if="lancamentos.length > 0"
          >
            <thead>
              <tr>
                <th class="text-left">Data</th>
                <th class="text-left">Negócio</th>
                <th class="text-left">Cliente</th>
                <th class="text-right">Valor</th>
                <th class="text-center"></th>
              </tr>
            </thead>
            <tbody>
              <tr
                v-for="l in lancamentos"
                :key="l.codindicadorlancamento"
                :class="l.estorno ? 'text-grey-5' : ''"
              >
                <td>
                  {{ moment(l.negocio?.lancamento || l.criacao).format("DD/MM/YYYY HH:mm:ss") }}
                </td>
                <td>
                  <q-btn
                    v-if="l.codnegocio"
                    flat
                    dense
                    no-caps
                    size="sm"
                    color="primary"
                    :href="negocioUrl(l.codnegocio)"
                    target="_blank"
                    :label="'#' + String(l.codnegocio).padStart(8, '0')"
                    type="a"
                    class="q-pa-none"
                  />
                  <span v-else class="text-grey">—</span>
                </td>
                <td>
                  {{ l.negocio?.pessoa?.fantasia || l.descricao || "—" }}
                </td>
                <td
                  class="text-right text-weight-medium"
                  :class="l.valor < 0 || l.estorno ? 'text-red' : 'text-green-8'"
                >
                  {{ formataMoeda(l.valor) }}
                </td>
                <td class="text-center">
                  <q-badge
                    v-if="l.estorno"
                    color="red"
                    label="estorno"
                    class="q-mr-xs"
                  />
                  <q-badge
                    v-if="l.manual"
                    color="orange"
                    label="manual"
                  />
                </td>
              </tr>
            </tbody>
          </q-markup-table>
          <div v-else class="q-pa-md text-center text-grey">
            Nenhum lançamento encontrado
          </div>
        </q-card>
      </div>
    </template>
  </div>
</template>
