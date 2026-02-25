<script setup>
import { ref, computed } from "vue";
import { useQuasar } from "quasar";
import { useRoute } from "vue-router";
import { rhStore } from "src/stores/rh";
import DialogEditarMeta from "./DialogEditarMeta.vue";

const $q = useQuasar();
const route = useRoute();
const sRh = rhStore();

// --- AGRUPAMENTO POR UNIDADE → SETOR ---

const agrupado = computed(() => {
  const unidadeMap = new Map();

  sRh.indicadores.forEach((ind) => {
    const codun = ind.codunidadenegocio || 0;
    const descun = ind.unidade_negocio_nome || "Sem Unidade";

    if (!unidadeMap.has(codun)) {
      unidadeMap.set(codun, {
        codunidadenegocio: codun,
        descricao: descun,
        setorMap: new Map(),
      });
    }

    const grupo = unidadeMap.get(codun);
    const codsetor = ind.codsetor || 0;
    const nomeSetor = ind.setor_nome || "Sem Setor";

    if (!grupo.setorMap.has(codsetor)) {
      grupo.setorMap.set(codsetor, {
        codsetor,
        setor: nomeSetor,
        indicadores: [],
      });
    }
    grupo.setorMap.get(codsetor).indicadores.push(ind);
  });

  return Array.from(unidadeMap.values())
    .sort((a, b) => a.descricao.localeCompare(b.descricao))
    .map((u) => ({
      ...u,
      setores: Array.from(u.setorMap.values()).sort((a, b) =>
        a.setor.localeCompare(b.setor)
      ),
    }));
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

const tipoIndicadorLabel = (tipo) => {
  const map = { U: "Unidade", S: "Setor", V: "Vendedor", C: "Caixa" };
  return map[tipo] || tipo;
};

const tipoIndicadorColor = (tipo) => {
  const map = { V: "blue", C: "purple", S: "teal", U: "orange" };
  return map[tipo] || "grey";
};

const atingimento = (ind) => {
  const vendas = parseFloat(ind.valoracumulado) || 0;
  const meta = parseFloat(ind.meta) || 0;
  if (!vendas || !meta) return null;
  return (vendas / meta) * 100;
};

const extrairErro = (error, fallback) => {
  const data = error.response?.data;
  if (!data) return fallback;
  if (data.errors) {
    const primeiro = Object.values(data.errors).flat()[0];
    if (primeiro) return primeiro;
  }
  return data.mensagem || data.message || data.erro || fallback;
};

// --- OPTIONS PARA SELECTS ---

const tipoOptions = [
  { label: "Vendedor", value: "V" },
  { label: "Caixa", value: "C" },
  { label: "Setor", value: "S" },
  { label: "Unidade", value: "U" },
];

const colaboradoresOptions = computed(() => {
  return sRh.colaboradores.map((pc) => ({
    label: pc.colaborador?.pessoa?.fantasia || "—",
    value: pc.codcolaborador,
  }));
});

const unidadesOptions = computed(() => {
  const map = new Map();
  sRh.colaboradores.forEach((pc) => {
    (pc.periodo_colaborador_setor_s || []).forEach((pcs) => {
      const un = pcs.setor?.unidade_negocio;
      if (un && !map.has(un.codunidadenegocio)) {
        map.set(un.codunidadenegocio, {
          label: un.descricao,
          value: un.codunidadenegocio,
        });
      }
    });
  });
  return Array.from(map.values()).sort((a, b) =>
    a.label.localeCompare(b.label)
  );
});

const setoresOptions = computed(() => {
  const map = new Map();
  sRh.colaboradores.forEach((pc) => {
    (pc.periodo_colaborador_setor_s || []).forEach((pcs) => {
      if (
        pcs.setor &&
        !map.has(pcs.codsetor) &&
        (!modelCriar.value.codunidadenegocio ||
          pcs.setor.unidade_negocio?.codunidadenegocio ===
            modelCriar.value.codunidadenegocio)
      ) {
        map.set(pcs.codsetor, {
          label: pcs.setor.setor,
          value: pcs.codsetor,
        });
      }
    });
  });
  return Array.from(map.values()).sort((a, b) =>
    a.label.localeCompare(b.label)
  );
});

// --- DIALOG CRIAR INDICADOR ---

const dialogCriar = ref(false);
const modelCriar = ref({});

const abrirDialogCriar = () => {
  modelCriar.value = {
    tipo: null,
    codcolaborador: null,
    codunidadenegocio: null,
    codsetor: null,
    meta: null,
  };
  dialogCriar.value = true;
};

const submitCriar = async () => {
  dialogCriar.value = false;
  try {
    await sRh.criarIndicador(route.params.codperiodo, modelCriar.value);
    $q.notify({
      color: "green-5",
      textColor: "white",
      icon: "done",
      message: "Indicador criado",
    });
    await sRh.getIndicadores(route.params.codperiodo);
  } catch (error) {
    $q.notify({
      color: "red-5",
      textColor: "white",
      icon: "error",
      message: extrairErro(error, "Erro ao criar indicador"),
    });
  }
};

// --- DIALOG EDITAR META ---

const dialogMeta = ref(false);
const indicadorMeta = ref(null);

const editarMeta = (ind) => {
  indicadorMeta.value = ind;
  dialogMeta.value = true;
};

const recarregarIndicadores = () => sRh.getIndicadores(route.params.codperiodo);

// --- EXCLUIR INDICADOR ---

const excluirIndicador = (ind) => {
  $q.dialog({
    title: "Excluir Indicador",
    message: "Tem certeza que deseja excluir este indicador?",
    cancel: true,
    persistent: true,
  }).onOk(async () => {
    try {
      await sRh.excluirIndicador(ind.codindicador);
      $q.notify({
        color: "green-5",
        textColor: "white",
        icon: "done",
        message: "Indicador excluído",
      });
      await recarregarIndicadores();
    } catch (error) {
      $q.notify({
        color: "red-5",
        textColor: "white",
        icon: "error",
        message: extrairErro(error, "Erro ao excluir indicador"),
      });
    }
  });
};
</script>

<template>
  <!-- DIALOG CRIAR INDICADOR -->
  <q-dialog v-model="dialogCriar">
    <q-card bordered flat style="width: 400px; max-width: 90vw">
      <q-form @submit="submitCriar()">
        <q-card-section class="text-grey-9 text-overline">
          NOVO INDICADOR
        </q-card-section>

        <q-separator inset />

        <q-card-section class="q-gutter-md">
          <q-select
            outlined
            v-model="modelCriar.tipo"
            :options="tipoOptions"
            label="Tipo"
            emit-value
            map-options
            :rules="[(val) => !!val || 'Obrigatório']"
          />

          <q-select
            v-if="modelCriar.tipo === 'V' || modelCriar.tipo === 'C'"
            outlined
            v-model="modelCriar.codcolaborador"
            :options="colaboradoresOptions"
            label="Colaborador"
            emit-value
            map-options
            :rules="[(val) => !!val || 'Obrigatório']"
          />

          <q-select
            outlined
            v-model="modelCriar.codunidadenegocio"
            :options="unidadesOptions"
            label="Unidade de Negócio"
            emit-value
            map-options
            clearable
          />

          <q-select
            outlined
            v-model="modelCriar.codsetor"
            :options="setoresOptions"
            label="Setor"
            emit-value
            map-options
            clearable
          />

          <q-input
            outlined
            v-model.number="modelCriar.meta"
            label="Meta"
            type="number"
            step="0.01"
          />
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
    @salvo="recarregarIndicadores()"
  />

  <!-- BOTÃO ADICIONAR -->
  <div class="row justify-end q-mb-sm">
    <q-btn
      flat
      dense
      icon="add"
      label="Novo Indicador"
      color="primary"
      @click="abrirDialogCriar()"
    />
  </div>

  <!-- CONTEÚDO -->
  <template v-for="unidade in agrupado" :key="unidade.codunidadenegocio">
    <q-card bordered flat class="q-mb-md">
      <q-card-section class="text-grey-9 text-overline">
        {{ unidade.descricao }}
      </q-card-section>

      <q-markup-table flat separator="horizontal">
        <thead>
          <tr>
            <th class="text-center" style="width: 100px">Tipo</th>
            <th class="text-left">Colaborador</th>
            <th class="text-right">Vendas</th>
            <th class="text-right">Meta</th>
            <th class="text-right" style="width: 80px">Ating.</th>
            <th style="width: 120px"></th>
            <th class="text-center" style="width: 60px">Lanç.</th>
            <th class="text-center" style="width: 80px"></th>
          </tr>
        </thead>
        <tbody>
          <template v-for="setor in unidade.setores" :key="setor.codsetor">
            <!-- SETOR HEADER -->
            <tr v-if="setor.codsetor">
              <td
                colspan="8"
                class="text-caption text-grey-7 text-weight-medium"
                style="border-bottom: none; padding-top: 12px"
              >
                {{ setor.setor }}
              </td>
            </tr>

            <!-- INDICADORES DO SETOR -->
            <tr v-for="ind in setor.indicadores" :key="ind.codindicador">
              <td class="text-center">
                <q-badge
                  :color="tipoIndicadorColor(ind.tipo)"
                  :label="tipoIndicadorLabel(ind.tipo)"
                />
              </td>
              <td>
                {{ ind.colaborador_nome || "—" }}
              </td>
              <td class="text-right">
                {{ formataMoeda(ind.valoracumulado) }}
              </td>
              <td class="text-right">
                {{ ind.meta ? formataMoeda(ind.meta) : "—" }}
                <q-btn
                  flat
                  dense
                  round
                  icon="edit"
                  size="sm"
                  color="grey-7"
                  @click="editarMeta(ind)"
                >
                  <q-tooltip>Editar Meta</q-tooltip>
                </q-btn>
              </td>
              <td class="text-right">
                <span
                  v-if="atingimento(ind) != null"
                  class="text-weight-bold"
                  :class="'text-' + corProgresso(atingimento(ind))"
                >
                  {{ formataPercentual(atingimento(ind)) }}
                </span>
                <span v-else class="text-grey">—</span>
              </td>
              <td>
                <q-linear-progress
                  v-if="ind.meta"
                  :value="
                    Math.min(
                      parseFloat(ind.valoracumulado) / parseFloat(ind.meta) ||
                        0,
                      1
                    )
                  "
                  size="8px"
                  stripe
                  rounded
                  :color="corProgresso(atingimento(ind))"
                />
              </td>
              <td class="text-center text-grey-7">
                {{ ind.lancamentos_count ?? 0 }}
              </td>
              <td class="text-right">
                <q-btn
                  v-if="(ind.lancamentos_count ?? 0) === 0"
                  flat
                  dense
                  round
                  icon="delete"
                  size="sm"
                  color="grey-7"
                  @click="excluirIndicador(ind)"
                >
                  <q-tooltip>Excluir Indicador</q-tooltip>
                </q-btn>
                <q-btn
                  flat
                  dense
                  round
                  icon="receipt_long"
                  size="sm"
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
              </td>
            </tr>
          </template>
        </tbody>
      </q-markup-table>
    </q-card>
  </template>

  <div v-if="agrupado.length === 0" class="q-pa-md text-center text-grey">
    Nenhum indicador encontrado
  </div>
</template>
