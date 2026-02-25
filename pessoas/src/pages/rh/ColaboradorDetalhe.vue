<script setup>
import { ref, computed, onMounted, watch } from "vue";
import { useQuasar } from "quasar";
import { useRoute, useRouter } from "vue-router";
import { rhStore } from "src/stores/rh";
import { guardaToken } from "src/stores";
import { api } from "src/boot/axios";
import { formataDataSemHora, formataFromNow } from "src/utils/formatador";
import SelectSetor from "src/components/select/SelectSetor.vue";
import DialogEditarMeta from "./DialogEditarMeta.vue";

const $q = useQuasar();
const route = useRoute();
const router = useRouter();
const sRh = rhStore();
const user = guardaToken();

const loading = ref(false);
const podeEditar = computed(() =>
  user.verificaPermissaoUsuario("Recursos Humanos")
);

// --- DADOS DO COLABORADOR ---

const colaborador = ref(null);

const nome = computed(
  () => colaborador.value?.colaborador?.pessoa?.fantasia || "—"
);
const cargo = computed(() => {
  const cargos = colaborador.value?.colaborador?.colaborador_cargo_s || [];
  return cargos.length > 0 ? cargos[0].cargo?.cargo : null;
});
const setores = computed(
  () => colaborador.value?.periodo_colaborador_setor_s || []
);
const rubricas = computed(() => colaborador.value?.colaborador_rubrica_s || []);
const indicadores = computed(() => colaborador.value?.indicadores || []);

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

const tipoValorLabel = (tipo) => {
  return tipo === "P" ? "%" : "Fixo";
};

const condicaoLabel = (rubrica) => {
  if (!rubrica.tipocondicao) return "—";
  const tipo = rubrica.tipocondicao === "M" ? "Meta" : "Rank";
  const ind = rubrica.indicador_condicao;
  const indicadorLabel = ind ? tipoIndicadorLabel(ind.tipo) : "";
  return tipo + " " + indicadorLabel;
};

const condicaoAtingida = (rubrica) => {
  if (!rubrica.tipocondicao) return null;
  return rubrica.valorcalculado > 0 || rubrica.concedido === false
    ? null
    : false;
};

const linkTitulo = computed(() => {
  if (!colaborador.value?.codtitulo) return "";
  return (
    process.env.MGSIS_URL +
    "index.php?r=titulo/view&id=" +
    colaborador.value.codtitulo
  );
});

const diasUteisPeriodo = computed(() => {
  const p = (sRh.periodos || []).find(
    (per) => String(per.codperiodo) === String(route.params.codperiodo)
  );
  return p?.diasuteis || 0;
});

const extrairErro = (error, fallback) => {
  const data = error.response?.data;
  if (!data) return fallback;
  if (data.errors) {
    const primeiro = Object.values(data.errors).flat()[0];
    if (primeiro) return primeiro;
  }
  return data.mensagem || data.message || fallback;
};

// --- DIAS ÚTEIS ---

const calcularDiasUteis = (inicio, fim, feriados) => {
  const feriadoSet = new Set(feriados.map((f) => f.data?.substring(0, 10)));
  let count = 0;
  const d = new Date(inicio.substring(0, 10) + "T12:00:00");
  const end = new Date(fim.substring(0, 10) + "T12:00:00");
  while (d <= end) {
    const day = d.getDay();
    const dateStr = d.toISOString().substring(0, 10);
    if (day !== 0 && !feriadoSet.has(dateStr)) count++;
    d.setDate(d.getDate() + 1);
  }
  return count;
};

// --- DIALOG SETOR ---

const dialogSetor = ref(false);
const isNovoSetor = ref(false);
const modelSetor = ref({});

const abrirNovoSetor = async () => {
  isNovoSetor.value = true;
  let diasUteis = 22;
  try {
    if (sRh.periodos.length === 0) await sRh.getPeriodos();
    const periodo = sRh.periodos.find(
      (p) => String(p.codperiodo) === String(route.params.codperiodo)
    );
    if (periodo?.periodoinicial && periodo?.periodofinal) {
      const ret = await api.get("v1/feriado");
      const feriados = (ret.data.data || []).filter((f) => !f.inativo);
      diasUteis = calcularDiasUteis(
        periodo.periodoinicial,
        periodo.periodofinal,
        feriados
      );
    }
  } catch (e) {
    // fallback 22
  }
  modelSetor.value = {
    codsetor: null,
    percentualrateio: 100,
    diastrabalhados: diasUteis,
  };
  dialogSetor.value = true;
};

const editarSetor = (pcs) => {
  isNovoSetor.value = false;
  modelSetor.value = {
    codperiodocolaboradorsetor: pcs.codperiodocolaboradorsetor,
    codsetor: pcs.codsetor,
    percentualrateio: pcs.percentualrateio,
    diastrabalhados: pcs.diastrabalhados,
  };
  dialogSetor.value = true;
};

const salvarSetor = async () => {
  dialogSetor.value = false;
  try {
    if (isNovoSetor.value) {
      await sRh.criarSetor(
        colaborador.value.codperiodocolaborador,
        modelSetor.value
      );
      $q.notify({
        color: "green-5",
        textColor: "white",
        icon: "done",
        message: "Setor vinculado",
      });
    } else {
      await sRh.atualizarSetor(
        modelSetor.value.codperiodocolaboradorsetor,
        modelSetor.value
      );
      $q.notify({
        color: "green-5",
        textColor: "white",
        icon: "done",
        message: "Setor atualizado",
      });
    }
    await recarregar();
  } catch (error) {
    $q.notify({
      color: "red-5",
      textColor: "white",
      icon: "error",
      message: extrairErro(error, "Erro ao salvar setor"),
    });
  }
};

const excluirSetor = (pcs) => {
  $q.dialog({
    title: "Remover Setor",
    message:
      "Tem certeza? Rubricas vinculadas a este setor também serão removidas.",
    cancel: true,
  }).onOk(async () => {
    try {
      await sRh.excluirSetor(pcs.codperiodocolaboradorsetor);
      $q.notify({
        color: "green-5",
        textColor: "white",
        icon: "done",
        message: "Setor removido",
      });
      await recarregar();
    } catch (error) {
      $q.notify({
        color: "red-5",
        textColor: "white",
        icon: "error",
        message: extrairErro(error, "Erro ao remover setor"),
      });
    }
  });
};

const submitSetor = () => {
  salvarSetor();
};

// --- DIALOG RUBRICA ---

const dialogRubrica = ref(false);
const isNovaRubrica = ref(false);
const modelRubrica = ref({});

const abrirNovaRubrica = () => {
  isNovaRubrica.value = true;
  modelRubrica.value = {
    descricao: "",
    codperiodocolaboradorsetor:
      setores.value.length === 1
        ? setores.value[0].codperiodocolaboradorsetor
        : null,
    tipovalor: "P",
    percentual: null,
    valorfixo: null,
    codindicador: null,
    tipocondicao: null,
    codindicadorcondicao: null,
    concedido: true,
    recorrente: true,
    descontaabsenteismo: false,
  };
  dialogRubrica.value = true;
};

const editarRubrica = (r) => {
  isNovaRubrica.value = false;
  modelRubrica.value = {
    codcolaboradorrubrica: r.codcolaboradorrubrica,
    descricao: r.descricao,
    codperiodocolaboradorsetor: r.codperiodocolaboradorsetor,
    tipovalor: r.tipovalor,
    percentual: r.percentual,
    valorfixo: r.valorfixo,
    codindicador: r.codindicador,
    tipocondicao: r.tipocondicao,
    codindicadorcondicao: r.codindicadorcondicao,
    concedido: r.concedido,
    recorrente: r.recorrente,
    descontaabsenteismo: r.descontaabsenteismo,
  };
  dialogRubrica.value = true;
};

const salvarRubrica = async () => {
  dialogRubrica.value = false;
  try {
    if (isNovaRubrica.value) {
      await sRh.criarRubrica(
        colaborador.value.codperiodocolaborador,
        modelRubrica.value
      );
      $q.notify({
        color: "green-5",
        textColor: "white",
        icon: "done",
        message: "Rubrica criada",
      });
    } else {
      await sRh.atualizarRubrica(
        modelRubrica.value.codcolaboradorrubrica,
        modelRubrica.value
      );
      $q.notify({
        color: "green-5",
        textColor: "white",
        icon: "done",
        message: "Rubrica atualizada",
      });
    }
    await recarregar();
  } catch (error) {
    $q.notify({
      color: "red-5",
      textColor: "white",
      icon: "error",
      message: extrairErro(error, "Erro ao salvar rubrica"),
    });
  }
};

const excluirRubrica = (r) => {
  $q.dialog({
    title: "Excluir Rubrica",
    message: "Tem certeza que deseja excluir esta rubrica?",
    cancel: true,
  }).onOk(async () => {
    try {
      await sRh.excluirRubrica(r.codcolaboradorrubrica);
      $q.notify({
        color: "green-5",
        textColor: "white",
        icon: "done",
        message: "Rubrica excluída",
      });
      await recarregar();
    } catch (error) {
      $q.notify({
        color: "red-5",
        textColor: "white",
        icon: "error",
        message: extrairErro(error, "Erro ao excluir rubrica"),
      });
    }
  });
};

const toggleConcedido = async (r) => {
  try {
    await sRh.toggleConcedido(r.codcolaboradorrubrica);
    await recarregar();
  } catch (error) {
    $q.notify({
      color: "red-5",
      textColor: "white",
      icon: "error",
      message: extrairErro(error, "Erro ao alterar concedido"),
    });
  }
};

const submitRubrica = () => {
  salvarRubrica();
};

// --- DIALOG EDITAR META ---

const dialogMeta = ref(false);
const indicadorMeta = ref(null);

const editarMeta = (ind) => {
  indicadorMeta.value = ind;
  dialogMeta.value = true;
};

// --- AÇÕES HEADER ---

const recalcularColaborador = async () => {
  try {
    await sRh.recalcular(
      route.params.codperiodo,
      colaborador.value.codperiodocolaborador
    );
    $q.notify({
      color: "green-5",
      textColor: "white",
      icon: "done",
      message: "Recalculado",
    });
    await recarregar();
  } catch (error) {
    $q.notify({
      color: "red-5",
      textColor: "white",
      icon: "error",
      message: extrairErro(error, "Erro ao recalcular"),
    });
  }
};

const encerrarColaborador = () => {
  $q.dialog({
    title: "Encerrar Colaborador",
    message: "Tem certeza? Um título será gerado.",
    cancel: true,
  }).onOk(async () => {
    try {
      await sRh.encerrar(
        route.params.codperiodo,
        colaborador.value.codperiodocolaborador
      );
      $q.notify({
        color: "green-5",
        textColor: "white",
        icon: "done",
        message: "Encerrado",
      });
      await recarregar();
    } catch (error) {
      $q.notify({
        color: "red-5",
        textColor: "white",
        icon: "error",
        message: extrairErro(error, "Erro ao encerrar"),
      });
    }
  });
};

const estornarColaborador = () => {
  $q.dialog({
    title: "Estornar Encerramento",
    message: "Tem certeza? O título será cancelado.",
    cancel: true,
  }).onOk(async () => {
    try {
      await sRh.estornar(
        route.params.codperiodo,
        colaborador.value.codperiodocolaborador
      );
      $q.notify({
        color: "green-5",
        textColor: "white",
        icon: "done",
        message: "Estornado",
      });
      await recarregar();
    } catch (error) {
      $q.notify({
        color: "red-5",
        textColor: "white",
        icon: "error",
        message: extrairErro(error, "Erro ao estornar"),
      });
    }
  });
};

// --- SELECT OPTIONS ---

const setorOptions = computed(() =>
  setores.value.map((pcs) => ({
    label:
      (pcs.setor?.unidade_negocio?.descricao || "") +
      " — " +
      (pcs.setor?.setor || ""),
    value: pcs.codperiodocolaboradorsetor,
  }))
);

const todosIndicadores = computed(() => colaborador.value?.indicadores || []);

const indicadorOptions = computed(() =>
  todosIndicadores.value.map((ind) => {
    let label = tipoIndicadorLabel(ind.tipo);
    const un = ind.unidade_negocio?.descricao;
    const setor = ind.setor?.setor;
    if (un || setor) {
      label += " — " + [un, setor].filter(Boolean).join(" / ");
    }
    if (ind.valoracumulado) {
      label += " (" + formataMoeda(ind.valoracumulado) + ")";
    }
    return { label, value: ind.codindicador };
  })
);

// --- LIFECYCLE ---

const recarregar = async () => {
  await sRh.getColaboradores(route.params.codperiodo);
  colaborador.value =
    sRh.colaboradores.find(
      (c) =>
        String(c.codperiodocolaborador) ===
        String(route.params.codperiodocolaborador)
    ) || null;
};

const carregar = async () => {
  loading.value = true;
  try {
    if (sRh.colaboradores.length === 0) {
      await sRh.getColaboradores(route.params.codperiodo);
    }
    colaborador.value =
      sRh.colaboradores.find(
        (c) =>
          String(c.codperiodocolaborador) ===
          String(route.params.codperiodocolaborador)
      ) || null;
  } catch (error) {
    $q.notify({
      color: "red-5",
      textColor: "white",
      icon: "error",
      message: extrairErro(error, "Erro ao carregar colaborador"),
    });
  } finally {
    loading.value = false;
  }
};

onMounted(() => {
  carregar();
});

watch(
  () => route.params.codperiodocolaborador,
  () => {
    if (route.name === "rhColaboradorDetalhe") carregar();
  }
);
</script>

<template>
  <!-- DIALOG SETOR -->
  <q-dialog v-model="dialogSetor">
    <q-card bordered flat style="width: 400px; max-width: 90vw">
      <q-form @submit="submitSetor()">
        <q-card-section class="text-grey-9 text-overline row items-center">
          <template v-if="isNovoSetor">VINCULAR SETOR</template>
          <template v-else>EDITAR SETOR</template>
        </q-card-section>

        <q-separator inset />

        <q-card-section>
          <div class="row q-col-gutter-md">
            <div class="col-12" v-if="isNovoSetor">
              <SelectSetor
                outlined
                v-model="modelSetor.codsetor"
                label="Setor"
                :rules="[(val) => !!val || 'Obrigatório']"
                autofocus
              />
            </div>
            <div class="col-6">
              <q-input
                outlined
                v-model.number="modelSetor.percentualrateio"
                label="% Rateio"
                type="number"
                step="0.01"
              />
            </div>
            <div class="col-6">
              <q-input
                outlined
                v-model.number="modelSetor.diastrabalhados"
                label="Dias Trabalhados"
                type="number"
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

  <!-- DIALOG RUBRICA -->
  <q-dialog v-model="dialogRubrica">
    <q-card bordered flat style="width: 600px; max-width: 90vw">
      <q-form @submit="submitRubrica()">
        <q-card-section class="text-grey-9 text-overline row items-center">
          <template v-if="isNovaRubrica">NOVA RUBRICA</template>
          <template v-else>EDITAR RUBRICA</template>
        </q-card-section>

        <q-separator inset />

        <q-card-section>
          <div class="row q-col-gutter-md">
            <!-- DESCRIÇÃO -->
            <div class="col-12">
              <q-input
                outlined
                v-model="modelRubrica.descricao"
                label="Descrição"
                :rules="[(val) => (val && val.length > 0) || 'Obrigatório']"
                autofocus
              />
            </div>

            <!-- SETOR -->
            <div class="col-12" v-if="setorOptions.length > 0">
              <q-select
                outlined
                v-model="modelRubrica.codperiodocolaboradorsetor"
                label="Setor"
                :options="setorOptions"
                map-options
                emit-value
                clearable
              />
            </div>

            <!-- TIPO VALOR -->
            <div class="col-12">
              <small class="text-grey">Tipo de Valor:</small>
              <q-radio
                v-model="modelRubrica.tipovalor"
                checked-icon="task_alt"
                unchecked-icon="panorama_fish_eye"
                val="P"
                label="Percentual"
              />
              <q-radio
                v-model="modelRubrica.tipovalor"
                checked-icon="task_alt"
                unchecked-icon="panorama_fish_eye"
                val="F"
                label="Fixo"
              />
            </div>

            <!-- PERCENTUAL + INDICADOR -->
            <template v-if="modelRubrica.tipovalor === 'P'">
              <div class="col-4">
                <q-input
                  outlined
                  v-model.number="modelRubrica.percentual"
                  label="Percentual %"
                  type="number"
                  step="0.01"
                  :rules="[(val) => val > 0 || 'Obrigatório']"
                />
              </div>
              <div class="col-8">
                <q-select
                  outlined
                  v-model="modelRubrica.codindicador"
                  label="Indicador"
                  :options="indicadorOptions"
                  map-options
                  emit-value
                  clearable
                />
              </div>
            </template>

            <!-- VALOR FIXO -->
            <div class="col-12" v-if="modelRubrica.tipovalor === 'F'">
              <q-input
                outlined
                v-model.number="modelRubrica.valorfixo"
                label="Valor Fixo (R$)"
                type="number"
                step="0.01"
                :rules="[(val) => val != null || 'Obrigatório']"
              />
            </div>

            <!-- CONDIÇÃO -->
            <div class="col-12">
              <small class="text-grey">Condição:</small>
              <q-radio
                v-model="modelRubrica.tipocondicao"
                checked-icon="task_alt"
                unchecked-icon="panorama_fish_eye"
                :val="null"
                label="Nenhuma"
              />
              <q-radio
                v-model="modelRubrica.tipocondicao"
                checked-icon="task_alt"
                unchecked-icon="panorama_fish_eye"
                val="M"
                label="Meta Atingida"
              />
              <q-radio
                v-model="modelRubrica.tipocondicao"
                checked-icon="task_alt"
                unchecked-icon="panorama_fish_eye"
                val="R"
                label="Ranking (1o lugar)"
              />
            </div>

            <!-- INDICADOR CONDIÇÃO -->
            <div class="col-12" v-if="modelRubrica.tipocondicao">
              <q-select
                outlined
                v-model="modelRubrica.codindicadorcondicao"
                label="Indicador da Condição"
                :options="indicadorOptions"
                map-options
                emit-value
                clearable
              />
            </div>

            <!-- TOGGLES -->
            <div class="col-12">
              <q-toggle v-model="modelRubrica.concedido" label="Concedido" />
              <q-toggle v-model="modelRubrica.recorrente" label="Recorrente" />
              <q-toggle
                v-model="modelRubrica.descontaabsenteismo"
                label="Desconta Absenteísmo"
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

  <!-- DIALOG EDITAR META -->
  <DialogEditarMeta
    v-model="dialogMeta"
    :indicador="indicadorMeta"
    @salvo="recarregar()"
  />

  <!-- CONTEÚDO PRINCIPAL -->
  <div style="max-width: 1280px; margin: auto">
    <q-inner-loading :showing="loading" />

    <template v-if="!loading && colaborador">
      <!-- HEADER -->
      <q-item class="q-pt-lg q-pb-sm">
        <q-item-section avatar>
          <q-avatar
            color="grey-8"
            text-color="grey-4"
            size="80px"
            v-if="nome !== '—'"
          >
            {{ nome.slice(0, 1) }}
          </q-avatar>
        </q-item-section>
        <q-item-section>
          <div class="text-h4 text-grey-9">
            <router-link
              v-if="colaborador?.colaborador?.codpessoa"
              :to="{
                name: 'pessoaView',
                params: { id: colaborador.colaborador.codpessoa },
              }"
              class="text-primary"
            >
              {{ nome }}
            </router-link>
            <template v-else>{{ nome }}</template>
          </div>
          <div class="text-h5 text-grey-7">
            <span v-if="cargo">{{ cargo }}</span>
          </div>
          <div
            v-if="colaborador?.colaborador?.contratacao"
            class="text-caption text-grey"
          >
            Contratação:
            {{ formataDataSemHora(colaborador.colaborador.contratacao) }}
            ({{ formataFromNow(colaborador.colaborador.contratacao) }})
          </div>
        </q-item-section>
        <q-item-section side>
          <q-btn
            flat
            dense
            round
            icon="arrow_back"
            color="grey-7"
            :to="{
              name: 'rhDashboard',
              params: { codperiodo: route.params.codperiodo },
              query: { tab: 'colaboradores' },
            }"
          >
            <q-tooltip>Voltar</q-tooltip>
          </q-btn>
        </q-item-section>
      </q-item>

      <div class="q-pa-md">
      <div class="row q-col-gutter-md">
        <!-- COLUNA ESQUERDA -->
        <div class="col-xs-12 col-md-8">
          <!-- RUBRICAS -->
          <q-card bordered flat class="q-mb-md">
            <q-card-section class="text-grey-9 text-overline row items-center">
              RUBRICAS
              <q-space />
              <q-btn
                flat
                dense
                round
                icon="refresh"
                size="sm"
                color="grey-7"
                @click="recalcularColaborador()"
                v-if="podeEditar && colaborador.status === 'A'"
              >
                <q-tooltip>Recalcular</q-tooltip>
              </q-btn>
              <q-btn
                flat
                dense
                round
                icon="check_circle"
                size="sm"
                color="green-7"
                @click="encerrarColaborador()"
                v-if="podeEditar && colaborador.status === 'A'"
              >
                <q-tooltip>Encerrar</q-tooltip>
              </q-btn>
              <q-btn
                flat
                dense
                round
                icon="undo"
                size="sm"
                color="grey-7"
                @click="estornarColaborador()"
                v-if="podeEditar && colaborador.status === 'E'"
              >
                <q-tooltip>Estornar</q-tooltip>
              </q-btn>
              <q-btn
                flat
                round
                dense
                icon="add"
                size="sm"
                color="primary"
                v-if="podeEditar && colaborador.status === 'A'"
                @click="abrirNovaRubrica()"
              >
                <q-tooltip>Nova Rubrica</q-tooltip>
              </q-btn>
            </q-card-section>

            <q-markup-table
              flat
              separator="horizontal"
              v-if="rubricas.length > 0"
              class="rh-tabela"
            >
              <thead>
                <tr class="text-left">
                  <th>Descrição</th>
                  <th class="text-center">Tipo</th>
                  <th class="text-right">%/Valor</th>
                  <th>Indicador</th>
                  <th>Condição</th>
                  <th class="text-right">Calculado</th>
                  <th class="text-center">Conc.</th>
                  <th class="text-right" v-if="podeEditar">Ações</th>
                </tr>
              </thead>
              <tbody>
                <tr
                  v-for="r in rubricas"
                  :key="r.codcolaboradorrubrica"
                  :class="!r.concedido ? 'text-grey-5' : ''"
                >
                  <td>{{ r.descricao }}</td>
                  <td class="text-center">
                    <q-badge
                      :color="r.tipovalor === 'P' ? 'blue' : 'purple'"
                      :label="tipoValorLabel(r.tipovalor)"
                    />
                  </td>
                  <td class="text-right">
                    <template v-if="r.tipovalor === 'P'">
                      {{ r.percentual }}%
                    </template>
                    <template v-else>
                      {{ formataMoeda(r.valorfixo) }}
                    </template>
                  </td>
                  <td>
                    <template v-if="r.indicador">
                      {{ tipoIndicadorLabel(r.indicador.tipo) }}
                    </template>
                    <template v-else>—</template>
                  </td>
                  <td>{{ condicaoLabel(r) }}</td>
                  <td class="text-right text-weight-bold">
                    {{ formataMoeda(r.valorcalculado) }}
                  </td>
                  <td class="text-center">
                    <q-toggle
                      v-if="podeEditar && colaborador.status === 'A'"
                      :model-value="r.concedido"
                      @update:model-value="toggleConcedido(r)"
                      dense
                    />
                    <q-icon
                      v-else
                      :name="r.concedido ? 'check_circle' : 'cancel'"
                      :color="r.concedido ? 'green' : 'red'"
                      size="sm"
                    />
                  </td>
                  <td class="text-right" v-if="podeEditar">
                    <q-btn
                      flat
                      dense
                      round
                      icon="edit"
                      size="sm"
                      color="grey-7"
                      @click="editarRubrica(r)"
                      v-if="colaborador.status === 'A'"
                    >
                      <q-tooltip>Editar</q-tooltip>
                    </q-btn>
                    <q-btn
                      flat
                      dense
                      round
                      icon="delete"
                      size="sm"
                      color="grey-7"
                      @click="excluirRubrica(r)"
                      v-if="colaborador.status === 'A'"
                    >
                      <q-tooltip>Excluir</q-tooltip>
                    </q-btn>
                  </td>
                </tr>
                <!-- TOTAL -->
                <tr class="text-weight-bold bg-grey-2">
                  <td colspan="5" class="text-right">TOTAL</td>
                  <td class="text-right">
                    {{ formataMoeda(colaborador.valortotal) }}
                  </td>
                  <td class="text-center">
                    <q-badge
                      :color="colaborador.status === 'A' ? 'green' : 'blue'"
                      :label="
                        colaborador.status === 'A' ? 'Aberto' : 'Encerrado'
                      "
                    />
                  </td>
                  <td v-if="podeEditar" class="text-right">
                    <a
                      v-if="colaborador.codtitulo"
                      :href="linkTitulo"
                      target="_blank"
                      class="text-primary text-caption"
                    >
                      #{{ String(colaborador.codtitulo).padStart(8, "0") }}
                      <q-icon name="open_in_new" size="xs" />
                    </a>
                  </td>
                </tr>
              </tbody>
            </q-markup-table>
            <div v-else class="q-pa-md text-center text-grey">
              Nenhuma rubrica cadastrada
            </div>
          </q-card>
        </div>

        <!-- COLUNA DIREITA -->
        <div class="col-xs-12 col-md-4">
          <!-- SETORES -->
          <q-card bordered flat class="q-mb-md q-pb-md">
            <q-card-section class="text-grey-9 text-overline row items-center">
              SETORES
              <q-space />
              <q-btn
                flat
                round
                dense
                icon="add"
                size="sm"
                color="primary"
                v-if="podeEditar && colaborador.status === 'A'"
                @click="abrirNovoSetor()"
              >
                <q-tooltip>Adicionar Setor</q-tooltip>
              </q-btn>
            </q-card-section>

            <q-list v-if="setores.length > 0">
              <template
                v-for="pcs in setores"
                :key="pcs.codperiodocolaboradorsetor"
              >
                <q-separator inset />
                <q-item>
                  <q-item-section>
                    <q-item-label>
                      {{ pcs.setor?.setor || "—" }}
                    </q-item-label>
                    <q-item-label caption>
                      {{ pcs.setor?.unidade_negocio?.descricao || "" }}
                    </q-item-label>
                    <q-item-label caption>
                      Rateio: {{ pcs.percentualrateio }}% —
                      <span
                        :class="pcs.diastrabalhados !== diasUteisPeriodo ? 'text-red text-weight-bold' : ''"
                        :style="pcs.diastrabalhados !== diasUteisPeriodo ? 'cursor: help' : ''"
                      >
                        Dias: {{ pcs.diastrabalhados }}
                        <q-tooltip v-if="pcs.diastrabalhados !== diasUteisPeriodo">
                          Dias úteis do período: {{ diasUteisPeriodo }}
                        </q-tooltip>
                      </span>
                    </q-item-label>
                  </q-item-section>
                  <q-item-section
                    side
                    v-if="podeEditar && colaborador.status === 'A'"
                  >
                    <q-item-label caption>
                      <q-btn
                        flat
                        dense
                        round
                        icon="edit"
                        size="sm"
                        color="grey-7"
                        @click="editarSetor(pcs)"
                      >
                        <q-tooltip>Editar</q-tooltip>
                      </q-btn>
                      <q-btn
                        flat
                        dense
                        round
                        icon="delete"
                        size="sm"
                        color="grey-7"
                        @click="excluirSetor(pcs)"
                      >
                        <q-tooltip>Excluir</q-tooltip>
                      </q-btn>
                    </q-item-label>
                  </q-item-section>
                </q-item>
              </template>
            </q-list>
            <div v-else class="q-pa-md text-center text-grey">
              Nenhum setor vinculado
            </div>
          </q-card>

          <!-- INDICADORES -->
          <q-card bordered flat class="q-mb-md q-pb-md">
            <q-card-section class="text-grey-9 text-overline row items-center">
              INDICADORES
            </q-card-section>

            <template v-if="indicadores.length > 0">
              <template v-for="ind in indicadores" :key="ind.codindicador">
                <q-separator inset />
                <q-card-section class="q-py-sm">
                  <div class="row items-center">
                    <div class="col">
                      <q-badge
                        :color="
                          ind.tipo === 'V'
                            ? 'blue'
                            : ind.tipo === 'C'
                            ? 'purple'
                            : ind.tipo === 'U'
                            ? 'orange'
                            : 'teal'
                        "
                        :label="tipoIndicadorLabel(ind.tipo)"
                        class="q-mr-sm"
                      />
                    </div>
                    <div class="col-auto">
                      <q-btn
                        flat
                        dense
                        round
                        icon="receipt_long"
                        size="xs"
                        color="grey-7"
                        :to="{
                          name: 'rhIndicadorExtrato',
                          params: {
                            codperiodo: route.params.codperiodo,
                            codindicador: ind.codindicador,
                          },
                        }"
                      >
                        <q-tooltip>Ver Extrato</q-tooltip>
                      </q-btn>
                      <q-btn
                        flat
                        dense
                        round
                        icon="edit"
                        size="xs"
                        color="grey-7"
                        @click="editarMeta(ind)"
                        v-if="podeEditar && colaborador.status === 'A'"
                      >
                        <q-tooltip>Editar Meta</q-tooltip>
                      </q-btn>
                    </div>
                  </div>
                  <div class="text-caption q-mt-xs">
                    <div class="text-grey-6" style="font-size: 10px">
                      #{{ ind.codindicador }}
                      <template v-if="ind.unidade_negocio">
                        — {{ ind.unidade_negocio.descricao }}
                      </template>
                      <template v-if="ind.setor">
                        — {{ ind.setor.setor }}
                      </template>
                    </div>
                    <div>
                      Vendas:
                      <span class="text-weight-bold">
                        {{ formataMoeda(ind.valoracumulado) }}
                      </span>
                    </div>
                    <div>
                      Meta:
                      <span class="text-weight-bold">
                        {{ ind.meta ? formataMoeda(ind.meta) : "—" }}
                      </span>
                    </div>
                    <div v-if="ind.meta">
                      Ating:
                      <span
                        class="text-weight-bold"
                        :class="
                          'text-' +
                          corProgresso(
                            (parseFloat(ind.valoracumulado) /
                              parseFloat(ind.meta)) *
                              100
                          )
                        "
                      >
                        {{
                          formataPercentual(
                            (parseFloat(ind.valoracumulado) /
                              parseFloat(ind.meta)) *
                              100
                          )
                        }}
                      </span>
                    </div>
                  </div>
                  <q-linear-progress
                    v-if="ind.meta"
                    :value="
                      Math.min(
                        parseFloat(ind.valoracumulado) / parseFloat(ind.meta) || 0,
                        1
                      )
                    "
                    size="6px"
                    stripe
                    rounded
                    class="q-mt-xs"
                    :color="
                      corProgresso(
                        (parseFloat(ind.valoracumulado) /
                          parseFloat(ind.meta)) *
                          100
                      )
                    "
                  />
                </q-card-section>
              </template>
            </template>
            <div v-else class="q-pa-md text-center text-grey">
              Nenhum indicador
            </div>
          </q-card>
        </div>
      </div>
      </div>
    </template>

    <!-- COLABORADOR NÃO ENCONTRADO -->
    <div
      v-else-if="!loading && !colaborador"
      class="q-pa-xl text-center text-grey"
    >
      Colaborador não encontrado
    </div>
  </div>
</template>
