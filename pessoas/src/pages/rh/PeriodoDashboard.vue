<script setup>
import { ref, computed, onMounted, onUnmounted, watch } from "vue";
import { useQuasar } from "quasar";
import { useRoute, useRouter } from "vue-router";
import { rhStore } from "src/stores/rh";
import { guardaToken } from "src/stores";
import { feriadoStore } from "src/stores/feriado";
import { formataDataSemHora } from "src/utils/formatador";
import Dashboard from "./Dashboard.vue";
import Colaboradores from "./Colaboradores.vue";
import Indicadores from "./Indicadores.vue";

const $q = useQuasar();
const route = useRoute();
const router = useRouter();
const sRh = rhStore();
const user = guardaToken();
const sFeriado = feriadoStore();

const loading = ref(false);
const tab = ref(route.query.tab || "resumo");
const indicadoresCarregados = ref(false);
const loadingIndicadores = ref(false);

const podeEditar = computed(() =>
  user.verificaPermissaoUsuario("Recursos Humanos")
);

const dash = computed(() => sRh.dashboard || {});
const periodo = computed(() => dash.value.periodo || {});
const totalColaboradores = computed(() => dash.value.totalcolaboradores || 0);
const colaboradoresEncerrados = computed(
  () => dash.value.colaboradoresencerrados || 0
);
const colaboradoresAbertos = computed(
  () => totalColaboradores.value - colaboradoresEncerrados.value
);
const totalSalario = computed(() => dash.value.totalsalario || 0);
const totalAdicional = computed(() => dash.value.totaladicional || 0);
const totalEncargos = computed(() => dash.value.totalencargos || 0);
const totalVariaveis = computed(() => dash.value.totalvariaveis || 0);
const custoTotal = computed(() => dash.value.total || 0);

const formataMoeda = (valor) => {
  return new Intl.NumberFormat("pt-BR", {
    style: "currency",
    currency: "BRL",
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
  }).format(parseFloat(valor) || 0);
};

// --- DIAS ÚTEIS ---

const diasUteisBanco = computed(() => periodo.value.diasuteis || 0);
const diasUteisCalculados = computed(() => periodo.value.diasuteiscalculados || 0);
const diasUteisDivergem = computed(() => diasUteisBanco.value !== diasUteisCalculados.value);

const editandoDiasUteis = ref(false);
const modelDiasUteis = ref(0);

const editarDiasUteis = () => {
  modelDiasUteis.value = diasUteisBanco.value;
  editandoDiasUteis.value = true;
};

const salvarDiasUteis = async () => {
  try {
    await sRh.atualizarPeriodo(route.params.codperiodo, {
      diasuteis: modelDiasUteis.value,
    });
    editandoDiasUteis.value = false;
    $q.notify({
      color: "green-5",
      textColor: "white",
      icon: "done",
      message: "Dias úteis atualizado",
    });
    await carregar(route.params.codperiodo);
  } catch (error) {
    $q.notify({
      color: "red-5",
      textColor: "white",
      icon: "error",
      message: extrairErro(error, "Erro ao atualizar dias úteis"),
    });
  }
};

const usarCalculado = () => {
  modelDiasUteis.value = diasUteisCalculados.value;
};

const feriadosDoPeriodo = computed(() => {
  if (!periodo.value.periodoinicial || !periodo.value.periodofinal) return [];
  const ini = periodo.value.periodoinicial.substring(0, 10);
  const fim = periodo.value.periodofinal.substring(0, 10);
  return (sFeriado.listagem || []).filter((f) => {
    if (f.inativo) return false;
    const d = f.data?.substring(0, 10);
    return d && d >= ini && d <= fim;
  });
});

const nomeMes = computed(() => {
  if (!periodo.value.periodoinicial) return "";
  const partes = periodo.value.periodoinicial.match(/^(\d{4})-(\d{2})/);
  if (!partes) return "";
  const meses = [
    "Janeiro",
    "Fevereiro",
    "Março",
    "Abril",
    "Maio",
    "Junho",
    "Julho",
    "Agosto",
    "Setembro",
    "Outubro",
    "Novembro",
    "Dezembro",
  ];
  return meses[parseInt(partes[2]) - 1] + " " + partes[1];
});

const formataDataCurta = (data) => {
  if (!data) return "";
  const d = data.substring(0, 10).split("-");
  return d[2] + "/" + d[1];
};

const extrairErro = (error, fallback) => {
  const data = error.response?.data;
  if (!data) return fallback;
  if (data.errors) {
    const primeiro = Object.values(data.errors).flat()[0];
    if (primeiro) return primeiro;
  }
  return data.mensagem || data.message || fallback;
};

// --- DIALOG EDITAR PERÍODO ---

const dialogPeriodo = ref(false);
const modelPeriodo = ref({});

const editarPeriodo = () => {
  modelPeriodo.value = {
    periodoinicial: periodo.value.periodoinicial?.substring(0, 10) || "",
    periodofinal: periodo.value.periodofinal?.substring(0, 10) || "",
    observacoes: periodo.value.observacoes || "",
  };
  dialogPeriodo.value = true;
};

const salvarPeriodo = async () => {
  dialogPeriodo.value = false;
  try {
    await sRh.atualizarPeriodo(route.params.codperiodo, modelPeriodo.value);
    $q.notify({
      color: "green-5",
      textColor: "white",
      icon: "done",
      message: "Período atualizado",
    });
    await sRh.getPeriodos();
    await carregar(route.params.codperiodo);
  } catch (error) {
    $q.notify({
      color: "red-5",
      textColor: "white",
      icon: "error",
      message: extrairErro(error, "Erro ao atualizar período"),
    });
  }
};

// --- AÇÕES DO PERÍODO ---

const fecharPeriodo = () => {
  $q.dialog({
    title: "Fechar Período",
    message: "Tem certeza que deseja fechar este período?",
    cancel: true,
  }).onOk(async () => {
    try {
      await sRh.fecharPeriodo(route.params.codperiodo);
      $q.notify({
        color: "green-5",
        textColor: "white",
        icon: "done",
        message: "Período fechado",
      });
      await sRh.getPeriodos();
      await carregar(route.params.codperiodo);
    } catch (error) {
      $q.notify({
        color: "red-5",
        textColor: "white",
        icon: "error",
        message: extrairErro(error, "Erro ao fechar período"),
      });
    }
  });
};

const reabrirPeriodo = () => {
  $q.dialog({
    title: "Reabrir Período",
    message: "Tem certeza que deseja reabrir este período?",
    cancel: true,
  }).onOk(async () => {
    try {
      await sRh.reabrirPeriodo(route.params.codperiodo);
      $q.notify({
        color: "green-5",
        textColor: "white",
        icon: "done",
        message: "Período reaberto",
      });
      await sRh.getPeriodos();
      await carregar(route.params.codperiodo);
    } catch (error) {
      $q.notify({
        color: "red-5",
        textColor: "white",
        icon: "error",
        message: extrairErro(error, "Erro ao reabrir período"),
      });
    }
  });
};

const duplicarPeriodo = () => {
  $q.dialog({
    title: "Duplicar Período",
    message:
      "Será criado um novo período com a mesma configuração (colaboradores, setores e rubricas recorrentes).",
    cancel: true,
  }).onOk(async () => {
    try {
      const ret = await sRh.duplicarPeriodo(route.params.codperiodo);
      $q.notify({
        color: "green-5",
        textColor: "white",
        icon: "done",
        message: "Período duplicado",
      });
      await sRh.getPeriodos();
      router.push({
        name: "rhDashboard",
        params: { codperiodo: ret.data.data.codperiodo },
      });
    } catch (error) {
      $q.notify({
        color: "red-5",
        textColor: "white",
        icon: "error",
        message: extrairErro(error, "Erro ao duplicar período"),
      });
    }
  });
};

const excluirPeriodo = () => {
  $q.dialog({
    title: "Excluir Período",
    message:
      "Tem certeza que deseja excluir este período? Todos os colaboradores, setores, rubricas e indicadores serão removidos.",
    cancel: true,
  }).onOk(async () => {
    try {
      await sRh.excluirPeriodo(route.params.codperiodo);
      $q.notify({
        color: "green-5",
        textColor: "white",
        icon: "done",
        message: "Período excluído",
      });
      await sRh.getPeriodos();
      if (sRh.periodos.length > 0) {
        router.push({
          name: "rhDashboard",
          params: { codperiodo: sRh.periodos[0].codperiodo },
        });
      } else {
        router.push({ name: "rhIndex" });
      }
    } catch (error) {
      $q.notify({
        color: "red-5",
        textColor: "white",
        icon: "error",
        message: extrairErro(error, "Erro ao excluir período"),
      });
    }
  });
};

// --- REPROCESSAMENTO ---

const reprocessando = ref(false);
const progresso = ref(null);
let pollingTimer = null;

const verificarProgresso = async () => {
  try {
    const data = await sRh.progressoReprocessamento(route.params.codperiodo);
    if (!data.status) {
      pararPolling();
      return;
    }
    progresso.value = data;
    if (data.status === 'concluido') {
      pararPolling();
      $q.notify({
        color: "green-5",
        textColor: "white",
        icon: "done",
        message: data.mensagem || "Reprocessamento concluído",
      });
      await carregar(route.params.codperiodo);
    } else if (data.status === 'erro') {
      pararPolling();
      $q.notify({
        color: "red-5",
        textColor: "white",
        icon: "error",
        message: data.mensagem || "Erro no reprocessamento",
      });
    } else if (data.status === 'cancelado') {
      pararPolling();
      $q.notify({
        color: "orange",
        textColor: "white",
        icon: "cancel",
        message: "Reprocessamento cancelado",
      });
      await carregar(route.params.codperiodo);
    }
  } catch {
    pararPolling();
  }
};

const iniciarPolling = () => {
  reprocessando.value = true;
  progresso.value = { status: 'processando', progresso: 0, mensagem: 'Iniciando...' };
  pollingTimer = setInterval(verificarProgresso, 3000);
};

const pararPolling = () => {
  reprocessando.value = false;
  if (pollingTimer) {
    clearInterval(pollingTimer);
    pollingTimer = null;
  }
};

const reprocessarPeriodo = () => {
  $q.dialog({
    title: "Reprocessar Indicadores",
    message: "Reprocessar todas as vendas do período nos indicadores?",
    options: {
      type: "checkbox",
      model: [],
      items: [
        { label: "Limpar lançamentos automáticos antes de reprocessar", value: "limpar" },
      ],
    },
    cancel: true,
    persistent: true,
  }).onOk(async (opcoes) => {
    try {
      await sRh.reprocessarPeriodo(
        route.params.codperiodo,
        opcoes.includes("limpar")
      );
      iniciarPolling();
    } catch (error) {
      $q.notify({
        color: "red-5",
        textColor: "white",
        icon: "error",
        message: extrairErro(error, "Erro ao iniciar reprocessamento"),
      });
    }
  });
};

const cancelarReprocessamento = () => {
  $q.dialog({
    title: "Cancelar Reprocessamento",
    message: "Tem certeza que deseja cancelar o reprocessamento em andamento?",
    cancel: true,
  }).onOk(async () => {
    try {
      await sRh.cancelarReprocessamento(route.params.codperiodo);
    } catch (error) {
      $q.notify({
        color: "red-5",
        textColor: "white",
        icon: "error",
        message: extrairErro(error, "Erro ao cancelar"),
      });
    }
  });
};

// --- LIFECYCLE ---

const carregar = async (codperiodo) => {
  if (!codperiodo) return;
  loading.value = true;
  try {
    await Promise.all([
      sRh.getDashboard(codperiodo),
      sRh.getColaboradores(codperiodo),
      sFeriado.listagem.length === 0
        ? sFeriado.getListagem()
        : Promise.resolve(),
    ]);
  } catch (error) {
    $q.notify({
      color: "red-5",
      textColor: "white",
      icon: "error",
      message: extrairErro(error, "Erro ao carregar período"),
    });
  } finally {
    loading.value = false;
  }
};

onMounted(async () => {
  carregar(route.params.codperiodo);
  // Verificar se já há reprocessamento em andamento
  try {
    const data = await sRh.progressoReprocessamento(route.params.codperiodo);
    if (data.status === 'processando') {
      iniciarPolling();
      progresso.value = data;
    }
  } catch {
    // ignora
  }
});

onUnmounted(() => {
  pararPolling();
});

watch(
  () => route.params.codperiodo,
  async (novoId) => {
    if (!novoId || route.name !== "rhDashboard") return;
    pararPolling();
    progresso.value = null;
    carregar(novoId);
    try {
      const data = await sRh.progressoReprocessamento(novoId);
      if (data.status === 'processando') {
        iniciarPolling();
        progresso.value = data;
      }
    } catch {
      // ignora
    }
  }
);

watch(
  () => route.query.tab,
  (newTab) => {
    if (newTab) tab.value = newTab;
  }
);

watch(tab, async (newTab) => {
  if (route.query.tab !== newTab) {
    router.replace({ query: { ...route.query, tab: newTab } });
  }
  const codperiodo = route.params.codperiodo;
  if (!codperiodo) return;
  try {
    if (newTab === "resumo") {
      await sRh.getDashboard(codperiodo);
    } else if (newTab === "colaboradores") {
      await sRh.getColaboradores(codperiodo);
    } else if (newTab === "indicadores") {
      loadingIndicadores.value = true;
      await sRh.getIndicadores(codperiodo);
      indicadoresCarregados.value = true;
    }
  } catch (error) {
    $q.notify({
      color: "red-5",
      textColor: "white",
      icon: "error",
      message: extrairErro(error, "Erro ao carregar dados"),
    });
  } finally {
    if (newTab === "indicadores") {
      loadingIndicadores.value = false;
    }
  }
}, { immediate: true });
</script>

<template>
  <!-- DIALOG EDITAR PERÍODO -->
  <q-dialog v-model="dialogPeriodo">
    <q-card bordered flat style="width: 400px; max-width: 90vw">
      <q-form @submit="salvarPeriodo()">
        <q-card-section class="text-grey-9 text-overline">
          EDITAR PERÍODO
        </q-card-section>

        <q-separator inset />

        <q-card-section>
          <div class="row q-col-gutter-md">
            <div class="col-6">
              <q-input
                outlined
                v-model="modelPeriodo.periodoinicial"
                label="Período Inicial"
                type="date"
                :rules="[(val) => !!val || 'Obrigatório']"
              />
            </div>
            <div class="col-6">
              <q-input
                outlined
                v-model="modelPeriodo.periodofinal"
                label="Período Final"
                type="date"
                :rules="[(val) => !!val || 'Obrigatório']"
              />
            </div>
            <div class="col-12">
              <q-input
                outlined
                v-model="modelPeriodo.observacoes"
                label="Observações"
                type="textarea"
                rows="2"
                autogrow
              />
            </div>
          </div>
        </q-card-section>

        <q-separator inset />

        <q-card-actions align="right" class="text-primary">
          <q-btn
            flat
            label="Cancelar"
            v-close-popup
            tabindex="-1"
            color="grey-8"
          />
          <q-btn flat label="Salvar" type="submit" />
        </q-card-actions>
      </q-form>
    </q-card>
  </q-dialog>

  <div style="max-width: 1280px; margin: auto">
    <q-inner-loading :showing="loading" />

    <template v-if="!loading && periodo.codperiodo">
      <!-- HEADER -->
      <q-item class="q-pt-lg q-pb-sm">
        <q-item-section avatar>
          <q-avatar
            color="amber"
            text-color="white"
            size="80px"
            icon="event_note"
          />
        </q-item-section>
        <q-item-section>
          <div class="text-h4 text-grey-9">
            {{ formataDataSemHora(periodo.periodoinicial) }} a
            {{ formataDataSemHora(periodo.periodofinal) }}
            <q-badge
              :color="periodo.status === 'A' ? 'green' : 'grey'"
              :label="periodo.status === 'A' ? 'Aberto' : 'Fechado'"
              class="q-ml-sm"
            />
          </div>
          <div class="text-caption text-grey" v-if="periodo.observacoes">
            {{ periodo.observacoes }}
          </div>
        </q-item-section>
        <q-item-section side top v-if="podeEditar">
          <div>
            <q-btn
              v-if="periodo.status === 'A'"
              flat
              dense
              round
              icon="edit"
              size="sm"
              color="grey-7"
              @click="editarPeriodo()"
            >
              <q-tooltip>Editar Período</q-tooltip>
            </q-btn>
            <q-btn
              v-if="periodo.status === 'A'"
              flat
              dense
              round
              icon="lock"
              size="sm"
              color="orange-7"
              :disable="reprocessando"
              @click="fecharPeriodo()"
            >
              <q-tooltip>Fechar Período</q-tooltip>
            </q-btn>
            <q-btn
              v-if="periodo.status === 'F'"
              flat
              dense
              round
              icon="lock_open"
              size="sm"
              color="grey-7"
              @click="reabrirPeriodo()"
            >
              <q-tooltip>Reabrir Período</q-tooltip>
            </q-btn>
            <q-btn
              flat
              dense
              round
              icon="content_copy"
              size="sm"
              color="grey-7"
              @click="duplicarPeriodo()"
            >
              <q-tooltip>Duplicar Período</q-tooltip>
            </q-btn>
            <q-btn
              v-if="periodo.status === 'A'"
              flat
              dense
              round
              icon="sync"
              size="sm"
              color="grey-7"
              :disable="reprocessando"
              @click="reprocessarPeriodo()"
            >
              <q-tooltip>Reprocessar Indicadores</q-tooltip>
            </q-btn>
            <q-btn
              flat
              dense
              round
              icon="delete"
              size="sm"
              color="red-7"
              :disable="reprocessando"
              @click="excluirPeriodo()"
            >
              <q-tooltip>Excluir Período</q-tooltip>
            </q-btn>
          </div>
        </q-item-section>
      </q-item>

      <div class="q-pa-md">
      <!-- CARDS RESUMO -->
      <div class="row q-col-gutter-md q-mb-md q-mt-sm items-stretch">
        <div class="col-xs-4 col-sm">
          <q-card
            bordered
            flat
            class="full-height"
            :class="diasUteisDivergem && !editandoDiasUteis ? 'bg-red-1' : ''"
          >
            <q-card-section class="text-center" style="cursor: help" v-if="!editandoDiasUteis">
              <div class="text-caption" :class="diasUteisDivergem ? 'text-red' : 'text-grey'">
                Dias Úteis
                <q-icon name="info_outline" size="14px" />
              </div>
              <div class="text-h5" :class="diasUteisDivergem ? 'text-red' : 'text-grey-9'">
                {{ diasUteisBanco }}
                <q-btn
                  v-if="podeEditar && periodo.status === 'A'"
                  flat
                  dense
                  round
                  icon="edit"
                  size="xs"
                  :color="diasUteisDivergem ? 'red' : 'grey-7'"
                  @click="editarDiasUteis()"
                />
              </div>
              <q-tooltip>
                <div>Calculado: {{ diasUteisCalculados }} dias</div>
                <div class="text-caption">Seg a Sáb, excluindo feriados</div>
              </q-tooltip>
            </q-card-section>
            <q-card-section v-else class="text-center q-py-sm">
              <div class="text-caption text-grey q-mb-xs">Dias Úteis</div>
              <div class="row items-center justify-center no-wrap q-gutter-xs">
                <q-input
                  v-model.number="modelDiasUteis"
                  type="number"
                  dense
                  outlined
                  style="max-width: 70px"
                  input-class="text-center"
                  @keyup.enter="salvarDiasUteis()"
                />
                <q-btn flat dense round icon="done" size="sm" color="green" @click="salvarDiasUteis()" />
                <q-btn flat dense round icon="close" size="sm" color="grey" @click="editandoDiasUteis = false" />
              </div>
              <q-btn
                v-if="diasUteisBanco !== diasUteisCalculados"
                flat
                dense
                size="xs"
                :label="'Usar calculado (' + diasUteisCalculados + ')'"
                color="primary"
                class="q-mt-xs"
                @click="usarCalculado()"
              />
            </q-card-section>
          </q-card>
        </div>
        <div class="col-xs-4 col-sm">
          <q-card bordered flat class="full-height">
            <q-card-section class="text-center" :style="feriadosDoPeriodo.length > 0 ? 'cursor: help' : ''">
              <div class="text-caption text-grey">
                Feriados
                <q-icon v-if="feriadosDoPeriodo.length > 0" name="info_outline" size="14px" />
              </div>
              <div class="text-h5 text-grey-9">
                {{ feriadosDoPeriodo.length }}
              </div>
              <q-tooltip v-if="feriadosDoPeriodo.length > 0">
                <div v-for="f in feriadosDoPeriodo" :key="f.codferiado">
                  {{ formataDataCurta(f.data) }} — {{ f.feriado }}
                </div>
              </q-tooltip>
            </q-card-section>
          </q-card>
        </div>
        <div class="col-xs-4 col-sm">
          <q-card bordered flat class="full-height">
            <q-card-section class="text-center">
              <div class="text-caption text-grey">Colaboradores</div>
              <div class="text-h5 text-grey-9">{{ totalColaboradores }}</div>
            </q-card-section>
          </q-card>
        </div>
        <div class="col-xs-4 col-sm">
          <q-card bordered flat class="full-height">
            <q-card-section class="text-center">
              <div class="text-caption text-grey">Abertos</div>
              <div class="text-h5 text-grey-9">{{ colaboradoresAbertos }}</div>
            </q-card-section>
          </q-card>
        </div>
        <div class="col-xs-4 col-sm">
          <q-card bordered flat class="full-height">
            <q-card-section class="text-center">
              <div class="text-caption text-grey">Encerrados</div>
              <div class="text-h5 text-grey-9">
                {{ colaboradoresEncerrados }}
              </div>
            </q-card-section>
          </q-card>
        </div>
        <div class="col-xs-4 col-sm">
          <q-card bordered flat class="full-height">
            <q-card-section class="text-center" style="cursor: help">
              <div class="text-caption text-grey">
                Custo Total
                <q-icon name="info_outline" size="14px" />
              </div>
              <div class="text-h5 text-grey-9">
                {{ formataMoeda(custoTotal) }}
              </div>
              <q-tooltip>
                <div>Salários: {{ formataMoeda(totalSalario) }}</div>
                <div>Adicional: {{ formataMoeda(totalAdicional) }}</div>
                <div>Encargos: {{ formataMoeda(totalEncargos) }}</div>
                <div>Variáveis: {{ formataMoeda(totalVariaveis) }}</div>
              </q-tooltip>
            </q-card-section>
          </q-card>
        </div>
      </div>

      <!-- BARRA DE REPROCESSAMENTO -->
      <q-card bordered flat class="q-mb-md" v-if="reprocessando && progresso">
        <q-card-section class="q-py-sm">
          <div class="row items-center q-gutter-sm">
            <q-spinner color="primary" size="20px" />
            <span class="text-body2 text-grey-8">{{ progresso.mensagem }}</span>
            <q-space />
            <q-btn
              flat
              dense
              round
              icon="cancel"
              size="sm"
              color="red-7"
              @click="cancelarReprocessamento()"
            >
              <q-tooltip>Cancelar</q-tooltip>
            </q-btn>
          </div>
          <q-linear-progress
            :value="(progresso.progresso || 0) / 100"
            size="8px"
            stripe
            animated
            rounded
            color="primary"
            class="q-mt-sm"
          />
        </q-card-section>
      </q-card>

      <!-- TABS -->
      <q-tabs
        v-model="tab"
        align="left"
        active-color="primary"
        indicator-color="primary"
        class="text-grey-7"
      >
        <q-tab name="resumo" label="Resumo" />
        <q-tab name="colaboradores" label="Colaboradores" />
        <q-tab name="indicadores" label="Indicadores" />
      </q-tabs>
      <q-separator />

      <q-tab-panels v-model="tab" animated class="bg-grey-2">
        <q-tab-panel name="resumo" class="q-pa-none q-mt-md">
          <Dashboard />
        </q-tab-panel>

        <q-tab-panel name="colaboradores" class="q-pa-none q-mt-md">
          <Colaboradores />
        </q-tab-panel>

        <q-tab-panel name="indicadores" class="q-pa-none q-mt-md">
          <q-inner-loading :showing="loadingIndicadores" />
          <Indicadores v-if="indicadoresCarregados" />
        </q-tab-panel>
      </q-tab-panels>
      </div>
    </template>
  </div>
</template>
