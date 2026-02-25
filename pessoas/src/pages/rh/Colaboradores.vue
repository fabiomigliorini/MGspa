<script setup>
import { ref, computed } from "vue";
import { useQuasar } from "quasar";
import { useRoute } from "vue-router";
import { rhStore } from "src/stores/rh";
import { guardaToken } from "src/stores";

const $q = useQuasar();
const route = useRoute();
const sRh = rhStore();
const user = guardaToken();

const podeEditar = computed(
  () => user.verificaPermissaoUsuario("Recursos Humanos")
);

const diasUteisPeriodo = computed(() => sRh.dashboard?.periodo?.diasuteis || 0);
const alertas = computed(() => sRh.dashboard?.alertas || []);

// --- AGRUPAMENTO POR UNIDADE → SETOR ---

const agrupado = computed(() => {
  const semSetor = [];
  const unidadeMap = new Map();

  sRh.colaboradores.forEach((pc) => {
    const setores = pc.periodo_colaborador_setor_s || [];
    if (setores.length === 0) {
      semSetor.push({ pc, pcs: null });
      return;
    }
    setores.forEach((pcs) => {
      const un = pcs.setor?.unidade_negocio;
      const codun = un?.codunidadenegocio || 0;
      const descun = un?.descricao || "Sem Unidade";

      if (!unidadeMap.has(codun)) {
        unidadeMap.set(codun, {
          codunidadenegocio: codun,
          descricao: descun,
          setorMap: new Map(),
        });
      }

      const grupo = unidadeMap.get(codun);
      const codsetor = pcs.codsetor;
      const nomeSetor = pcs.setor?.setor || "—";

      if (!grupo.setorMap.has(codsetor)) {
        grupo.setorMap.set(codsetor, {
          codsetor,
          setor: nomeSetor,
          colaboradores: [],
        });
      }
      grupo.setorMap.get(codsetor).colaboradores.push({ pc, pcs });
    });
  });

  const resultado = [];

  if (semSetor.length > 0) {
    resultado.push({
      codunidadenegocio: null,
      descricao: "SEM SETOR",
      alerta: true,
      setores: [{ codsetor: null, setor: null, colaboradores: semSetor }],
    });
  }

  const unidades = Array.from(unidadeMap.values())
    .sort((a, b) => a.descricao.localeCompare(b.descricao))
    .map((u) => ({
      codunidadenegocio: u.codunidadenegocio,
      descricao: u.descricao,
      alerta: false,
      setores: Array.from(u.setorMap.values()).sort((a, b) =>
        a.setor.localeCompare(b.setor)
      ),
    }));

  return resultado.concat(unidades);
});

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

const nomeColaborador = (pc) => {
  return pc.colaborador?.pessoa?.fantasia || "—";
};

const tipoIndicadorLabel = (tipo) => {
  const map = { U: "Unidade", S: "Setor", V: "Vendedor", C: "Caixa" };
  return map[tipo] || tipo;
};

const indicadoresLinha = (pcs) => {
  const indicadores = pcs?.indicadores || [];
  return indicadores.length > 0 ? indicadores : [null];
};

const vendasInd = (ind) =>
  ind ? parseFloat(ind.valoracumulado) || 0 : null;

const metaInd = (ind) =>
  ind ? parseFloat(ind.meta) || null : null;

const atingimentoInd = (ind) => {
  const vendas = vendasInd(ind);
  const meta = metaInd(ind);
  if (!vendas || !meta) return null;
  return (vendas / meta) * 100;
};

const corLinha = (pc) => {
  if (pc.status === "E") return "bg-green-1";
  const rubricas = pc.colaborador_rubrica_s || [];
  if (rubricas.length === 0) return "bg-yellow-1";
  return "";
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

// --- DIALOG ADICIONAR COLABORADORES ---

const dialogAdicionar = ref(false);
const disponiveis = ref([]);
const selecionados = ref([]);
const loadingDisponiveis = ref(false);

const abrirDialogAdicionar = async () => {
  loadingDisponiveis.value = true;
  selecionados.value = [];
  dialogAdicionar.value = true;
  try {
    disponiveis.value = await sRh.getColaboradoresDisponiveis(route.params.codperiodo);
  } catch (error) {
    $q.notify({
      color: "red-5",
      textColor: "white",
      icon: "error",
      message: extrairErro(error, "Erro ao carregar colaboradores"),
    });
  } finally {
    loadingDisponiveis.value = false;
  }
};

const selecionarTodos = () => {
  if (selecionados.value.length === disponiveis.value.length) {
    selecionados.value = [];
  } else {
    selecionados.value = disponiveis.value.map((c) => c.codcolaborador);
  }
};

const submitAdicionar = async () => {
  if (selecionados.value.length === 0) return;
  dialogAdicionar.value = false;
  try {
    await sRh.adicionarColaboradores(route.params.codperiodo, selecionados.value);
    $q.notify({
      color: "green-5",
      textColor: "white",
      icon: "done",
      message: selecionados.value.length + " colaborador(es) adicionado(s)",
    });
    await sRh.getColaboradores(route.params.codperiodo);
  } catch (error) {
    $q.notify({
      color: "red-5",
      textColor: "white",
      icon: "error",
      message: extrairErro(error, "Erro ao adicionar colaboradores"),
    });
  }
};

// --- AÇÕES ---

const excluirColaborador = (pc) => {
  $q.dialog({
    title: "Remover Colaborador",
    message:
      "Tem certeza que deseja remover " +
      nomeColaborador(pc) +
      " deste período? Setores e rubricas serão removidos.",
    cancel: true,
  }).onOk(async () => {
    try {
      await sRh.excluirColaborador(route.params.codperiodo, pc.codperiodocolaborador);
      $q.notify({
        color: "green-5",
        textColor: "white",
        icon: "done",
        message: "Colaborador removido",
      });
      await sRh.getColaboradores(route.params.codperiodo);
    } catch (error) {
      $q.notify({
        color: "red-5",
        textColor: "white",
        icon: "error",
        message: extrairErro(error, "Erro ao remover colaborador"),
      });
    }
  });
};

const recalcular = async (pc) => {
  try {
    await sRh.recalcular(route.params.codperiodo, pc.codperiodocolaborador);
    $q.notify({
      color: "green-5",
      textColor: "white",
      icon: "done",
      message: "Recalculado",
    });
    await sRh.getColaboradores(route.params.codperiodo);
  } catch (error) {
    $q.notify({
      color: "red-5",
      textColor: "white",
      icon: "error",
      message: extrairErro(error, "Erro ao recalcular"),
    });
  }
};

const encerrar = (pc) => {
  $q.dialog({
    title: "Encerrar Colaborador",
    message:
      "Tem certeza que deseja encerrar " +
      nomeColaborador(pc) +
      "? Um título será gerado.",
    cancel: true,
  }).onOk(async () => {
    try {
      await sRh.encerrar(route.params.codperiodo, pc.codperiodocolaborador);
      $q.notify({
        color: "green-5",
        textColor: "white",
        icon: "done",
        message: "Colaborador encerrado",
      });
      await sRh.getColaboradores(route.params.codperiodo);
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

const estornar = (pc) => {
  $q.dialog({
    title: "Estornar Encerramento",
    message:
      "Tem certeza que deseja estornar o encerramento de " +
      nomeColaborador(pc) +
      "? O título será cancelado.",
    cancel: true,
  }).onOk(async () => {
    try {
      await sRh.estornar(route.params.codperiodo, pc.codperiodocolaborador);
      $q.notify({
        color: "green-5",
        textColor: "white",
        icon: "done",
        message: "Encerramento estornado",
      });
      await sRh.getColaboradores(route.params.codperiodo);
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
</script>

<template>
  <!-- DIALOG ADICIONAR COLABORADORES -->
  <q-dialog v-model="dialogAdicionar">
    <q-card bordered flat style="width: 600px; max-width: 90vw">
      <q-card-section class="text-grey-9 text-overline row items-center">
        ADICIONAR COLABORADORES
        <q-space />
        <q-btn
          v-if="disponiveis.length > 0"
          flat
          dense
          size="sm"
          :label="selecionados.length === disponiveis.length ? 'Desmarcar todos' : 'Selecionar todos'"
          color="primary"
          @click="selecionarTodos()"
        />
      </q-card-section>

      <q-separator inset />

      <q-card-section style="max-height: 400px; overflow-y: auto" class="q-pa-none">
        <q-inner-loading :showing="loadingDisponiveis" />
        <q-list separator v-if="!loadingDisponiveis && disponiveis.length > 0">
          <q-item
            v-for="c in disponiveis"
            :key="c.codcolaborador"
            tag="label"
            clickable
            v-ripple
          >
            <q-item-section side>
              <q-checkbox v-model="selecionados" :val="c.codcolaborador" />
            </q-item-section>
            <q-item-section>
              <q-item-label>{{ c.fantasia || "—" }}</q-item-label>
              <q-item-label caption v-if="c.cargo">{{ c.cargo }}</q-item-label>
            </q-item-section>
          </q-item>
        </q-list>
        <div
          v-if="!loadingDisponiveis && disponiveis.length === 0"
          class="q-pa-lg text-center text-grey"
        >
          Todos os colaboradores ativos ja estao vinculados a este periodo.
        </div>
      </q-card-section>

      <q-separator inset />

      <q-card-actions align="right" class="text-primary">
        <q-btn flat label="Cancelar" v-close-popup tabindex="-1" color="grey-8" />
        <q-btn
          flat
          :label="'Adicionar (' + selecionados.length + ')'"
          @click="submitAdicionar()"
          :disable="selecionados.length === 0"
        />
      </q-card-actions>
    </q-card>
  </q-dialog>

  <!-- BOTÃO ADICIONAR -->
  <div v-if="podeEditar" class="row justify-end q-mb-sm">
    <q-btn
      flat
      dense
      icon="person_add"
      label="Adicionar Colaboradores"
      color="primary"
      @click="abrirDialogAdicionar()"
    />
  </div>

  <!-- ALERTAS -->
  <template v-if="alertas.length > 0">
    <q-card
      v-for="(alerta, i) in alertas"
      :key="'alerta-' + i"
      bordered
      flat
      class="q-mb-sm"
      :class="alerta.tipo === 'sem_meta' ? 'bg-orange-1' : 'bg-yellow-1'"
    >
      <q-card-section class="row items-center q-py-sm">
        <q-icon
          :name="alerta.tipo === 'sem_meta' ? 'warning' : 'info'"
          :color="'orange'"
          size="sm"
          class="q-mr-sm"
        />
        <span class="text-body2">
          <router-link
            v-if="alerta.codperiodocolaborador"
            :to="{
              name: 'rhColaboradorDetalhe',
              params: {
                codperiodo: route.params.codperiodo,
                codperiodocolaborador: alerta.codperiodocolaborador,
              },
            }"
            class="text-primary"
          >
            {{ alerta.mensagem }}
          </router-link>
          <template v-else>{{ alerta.mensagem }}</template>
        </span>
      </q-card-section>
    </q-card>
  </template>

  <!-- CARDS POR UNIDADE -->
  <template v-for="unidade in agrupado" :key="unidade.codunidadenegocio">
    <q-card
      bordered
      flat
      class="q-mb-md"
      :class="unidade.alerta ? 'bg-red-1' : ''"
    >
      <q-card-section class="text-grey-9 text-overline">
        {{ unidade.descricao }}
      </q-card-section>

      <q-markup-table flat separator="horizontal">
        <colgroup>
          <col style="width: 18%" />
          <col style="width: 6%" />
          <col style="width: 5%" />
          <col style="width: 8%" />
          <col style="width: 12%" />
          <col style="width: 12%" />
          <col style="width: 7%" />
          <col style="width: 11%" />
          <col style="width: 8%" />
          <col style="width: 13%" v-if="podeEditar" />
        </colgroup>
        <thead>
          <tr>
            <th class="text-left">Nome</th>
            <th class="text-right">Rateio</th>
            <th class="text-right">Dias</th>
            <th class="text-center">Tipo</th>
            <th class="text-right">Vendas</th>
            <th class="text-right">Meta</th>
            <th class="text-right">Ating.</th>
            <th class="text-right">Total Var.</th>
            <th class="text-center">Status</th>
            <th class="text-right" v-if="podeEditar">Ações</th>
          </tr>
        </thead>
        <tbody>
          <template
            v-for="setor in unidade.setores"
            :key="setor.codsetor"
          >
            <!-- SETOR HEADER -->
            <tr v-if="setor.setor">
              <td
                :colspan="podeEditar ? 10 : 9"
                class="text-caption text-grey-7 text-weight-medium"
                style="border-bottom: none; padding-top: 12px"
              >
                {{ setor.setor }}
              </td>
            </tr>

            <!-- COLABORADORES DO SETOR -->
            <template
              v-for="item in setor.colaboradores"
              :key="
                item.pc.codperiodocolaborador +
                '-' +
                (item.pcs?.codperiodocolaboradorsetor || 0)
              "
            >
              <tr
                v-for="(ind, idx) in indicadoresLinha(item.pcs)"
                :key="(ind?.codindicador || 'none') + '-' + idx"
                :class="corLinha(item.pc)"
              >
                <!-- COLUNAS AGRUPADAS (só primeira linha) -->
                <td
                  v-if="idx === 0"
                  :rowspan="indicadoresLinha(item.pcs).length"
                >
                  <router-link
                    :to="{
                      name: 'rhColaboradorDetalhe',
                      params: {
                        codperiodo: route.params.codperiodo,
                        codperiodocolaborador:
                          item.pc.codperiodocolaborador,
                      },
                    }"
                    class="text-primary"
                  >
                    {{ nomeColaborador(item.pc) }}
                  </router-link>
                </td>
                <td
                  v-if="idx === 0"
                  :rowspan="indicadoresLinha(item.pcs).length"
                  class="text-right"
                >
                  {{
                    item.pcs
                      ? item.pcs.percentualrateio + "%"
                      : "—"
                  }}
                </td>
                <td
                  v-if="idx === 0"
                  :rowspan="indicadoresLinha(item.pcs).length"
                  class="text-right"
                  :class="item.pcs && item.pcs.diastrabalhados !== diasUteisPeriodo ? 'text-red' : ''"
                  :style="item.pcs && item.pcs.diastrabalhados !== diasUteisPeriodo ? 'cursor: help' : ''"
                >
                  {{ item.pcs ? item.pcs.diastrabalhados : "—" }}
                  <q-tooltip v-if="item.pcs && item.pcs.diastrabalhados !== diasUteisPeriodo">
                    Dias úteis do período: {{ diasUteisPeriodo }}
                  </q-tooltip>
                </td>

                <!-- COLUNAS POR INDICADOR -->
                <td class="text-center">
                  <router-link
                    v-if="ind"
                    :to="{
                      name: 'rhIndicadorExtrato',
                      params: {
                        codperiodo: route.params.codperiodo,
                        codindicador: ind.codindicador,
                      },
                    }"
                    style="text-decoration: none"
                  >
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
                      style="cursor: pointer"
                    />
                  </router-link>
                  <span v-else>—</span>
                </td>
                <td class="text-right">
                  {{
                    vendasInd(ind) != null
                      ? formataMoeda(vendasInd(ind))
                      : "—"
                  }}
                </td>
                <td class="text-right">
                  {{
                    metaInd(ind) != null
                      ? formataMoeda(metaInd(ind))
                      : "—"
                  }}
                </td>
                <td class="text-right">
                  <span
                    v-if="atingimentoInd(ind) != null"
                    :class="
                      'text-weight-bold text-' +
                      corProgresso(atingimentoInd(ind))
                    "
                  >
                    {{ formataPercentual(atingimentoInd(ind)) }}
                  </span>
                  <span v-else>—</span>
                </td>

                <!-- COLUNAS AGRUPADAS (só primeira linha) -->
                <td
                  v-if="idx === 0"
                  :rowspan="indicadoresLinha(item.pcs).length"
                  class="text-right"
                >
                  {{ formataMoeda(item.pc.valortotal) }}
                </td>
                <td
                  v-if="idx === 0"
                  :rowspan="indicadoresLinha(item.pcs).length"
                  class="text-center"
                >
                  <q-badge
                    :color="
                      item.pc.status === 'A' ? 'green' : 'blue'
                    "
                    :label="
                      item.pc.status === 'A'
                        ? 'Aberto'
                        : 'Encerrado'
                    "
                  />
                </td>
                <td
                  v-if="idx === 0 && podeEditar"
                  :rowspan="indicadoresLinha(item.pcs).length"
                  class="text-right text-no-wrap"
                >
                  <q-btn
                    flat
                    dense
                    round
                    icon="visibility"
                    size="sm"
                    color="grey-7"
                    :to="{
                      name: 'rhColaboradorDetalhe',
                      params: {
                        codperiodo: route.params.codperiodo,
                        codperiodocolaborador:
                          item.pc.codperiodocolaborador,
                      },
                    }"
                  >
                    <q-tooltip>Ver Detalhe</q-tooltip>
                  </q-btn>
                  <q-btn
                    flat
                    dense
                    round
                    icon="refresh"
                    size="sm"
                    color="grey-7"
                    @click="recalcular(item.pc)"
                    v-if="item.pc.status === 'A'"
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
                    @click="encerrar(item.pc)"
                    v-if="item.pc.status === 'A'"
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
                    @click="estornar(item.pc)"
                    v-if="item.pc.status === 'E'"
                  >
                    <q-tooltip>Estornar</q-tooltip>
                  </q-btn>
                  <q-btn
                    flat
                    dense
                    round
                    icon="person_remove"
                    size="sm"
                    color="red-7"
                    @click="excluirColaborador(item.pc)"
                    v-if="item.pc.status === 'A'"
                  >
                    <q-tooltip>Remover do Periodo</q-tooltip>
                  </q-btn>
                </td>
              </tr>
            </template>
          </template>
        </tbody>
      </q-markup-table>
    </q-card>
  </template>

  <div
    v-if="agrupado.length === 0"
    class="q-pa-md text-center text-grey"
  >
    Nenhum colaborador encontrado
  </div>
</template>
