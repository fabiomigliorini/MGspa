<script setup>
import { computed } from "vue";
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

const indicadorPessoal = (pc, pcs) => {
  const indicadores = pc.indicadores || [];
  const codsetor = pcs?.codsetor;

  // 1. Match direto por codsetor (qualquer tipo: V, C, S, U)
  if (codsetor) {
    const porSetor = indicadores.find((i) => i.codsetor === codsetor);
    if (porSetor) return porSetor;
  }

  // 2. Indicadores pessoais (V/C)
  const pessoais = indicadores.filter((i) => i.tipo === "V" || i.tipo === "C");
  if (pessoais.length === 0) return null;
  if (!codsetor) return pessoais[0];

  // 3. Match por unidade, excluindo indicadores que já pertencem a outro PCS
  const codun = pcs?.setor?.unidade_negocio?.codunidadenegocio;
  if (codun) {
    const outrosSetores = (pc.periodo_colaborador_setor_s || [])
      .filter((s) => s.codsetor !== codsetor)
      .map((s) => s.codsetor);
    const match = pessoais.find(
      (i) => i.codunidadenegocio === codun && !outrosSetores.includes(i.codsetor)
    );
    if (match) return match;
  }

  return null;
};

const vendasColaborador = (pc, pcs) => {
  const ind = indicadorPessoal(pc, pcs);
  return ind ? parseFloat(ind.valoracumulado) || 0 : null;
};

const metaColaborador = (pc, pcs) => {
  const ind = indicadorPessoal(pc, pcs);
  return ind ? parseFloat(ind.meta) || null : null;
};

const atingimentoColaborador = (pc, pcs) => {
  const vendas = vendasColaborador(pc, pcs);
  const meta = metaColaborador(pc, pcs);
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

// --- AÇÕES ---

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
          <col style="width: 20%" />
          <col style="width: 7%" />
          <col style="width: 6%" />
          <col style="width: 13%" />
          <col style="width: 13%" />
          <col style="width: 7%" />
          <col style="width: 12%" />
          <col style="width: 9%" />
          <col style="width: 13%" v-if="podeEditar" />
        </colgroup>
        <thead>
          <tr>
            <th class="text-left">Nome</th>
            <th class="text-right">Rateio</th>
            <th class="text-right">Dias</th>
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
                :colspan="podeEditar ? 9 : 8"
                class="text-caption text-grey-7 text-weight-medium"
                style="border-bottom: none; padding-top: 12px"
              >
                {{ setor.setor }}
              </td>
            </tr>

            <!-- COLABORADORES DO SETOR -->
            <tr
              v-for="item in setor.colaboradores"
              :key="
                item.pc.codperiodocolaborador +
                '-' +
                (item.pcs?.codperiodocolaboradorsetor || 0)
              "
              :class="corLinha(item.pc)"
            >
              <td>
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
              <td class="text-right">
                {{
                  item.pcs
                    ? item.pcs.percentualrateio + "%"
                    : "—"
                }}
              </td>
              <td
                class="text-right"
                :class="item.pcs && item.pcs.diastrabalhados !== diasUteisPeriodo ? 'text-red' : ''"
                :style="item.pcs && item.pcs.diastrabalhados !== diasUteisPeriodo ? 'cursor: help' : ''"
              >
                {{ item.pcs ? item.pcs.diastrabalhados : "—" }}
                <q-tooltip v-if="item.pcs && item.pcs.diastrabalhados !== diasUteisPeriodo">
                  Dias úteis do período: {{ diasUteisPeriodo }}
                </q-tooltip>
              </td>
              <td class="text-right">
                {{
                  vendasColaborador(item.pc, item.pcs) != null
                    ? formataMoeda(vendasColaborador(item.pc, item.pcs))
                    : "—"
                }}
              </td>
              <td class="text-right">
                {{
                  metaColaborador(item.pc, item.pcs) != null
                    ? formataMoeda(metaColaborador(item.pc, item.pcs))
                    : "—"
                }}
              </td>
              <td class="text-right">
                <span
                  v-if="atingimentoColaborador(item.pc, item.pcs) != null"
                  :class="
                    'text-weight-bold text-' +
                    corProgresso(atingimentoColaborador(item.pc, item.pcs))
                  "
                >
                  {{
                    formataPercentual(
                      atingimentoColaborador(item.pc, item.pcs)
                    )
                  }}
                </span>
                <span v-else>—</span>
              </td>
              <td class="text-right">
                {{ formataMoeda(item.pc.valortotal) }}
              </td>
              <td class="text-center">
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
              <td class="text-right text-no-wrap" v-if="podeEditar">
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
              </td>
            </tr>
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
