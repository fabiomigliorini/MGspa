<script setup>
import { ref, computed, onMounted, watch } from "vue";
import { useQuasar } from "quasar";
import { useRoute } from "vue-router";
import { rhStore } from "src/stores/rh";
import { api } from "boot/axios";
import AcertoModal from "./AcertoModal.vue";

const $q = useQuasar();
const route = useRoute();
const sRh = rhStore();

const loading = ref(false);
const dias = ref(5);
const acertos = ref([]);
const modalAberto = ref(false);
const colaboradorAtivo = ref(null);
const dialogPdf = ref(false);
const pdfUrl = ref(null);
const pdfTitulo = ref("");

const formataMoeda = (valor) => {
  return new Intl.NumberFormat("pt-BR", {
    style: "currency",
    currency: "BRL",
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
  }).format(parseFloat(valor) || 0);
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

// --- AGRUPAMENTO ---

const acertosPorUnidade = computed(() => {
  const map = new Map();
  acertos.value.forEach((a) => {
    const cod = a.codunidadenegocio;
    if (!map.has(cod)) {
      map.set(cod, { codunidadenegocio: cod, unidade: a.unidade, items: [] });
    }
    map.get(cod).items.push(a);
  });
  return Array.from(map.values()).sort((a, b) =>
    (a.unidade || "").localeCompare(b.unidade || "", "pt-BR")
  );
});

const temEfetivados = computed(() =>
  acertos.value.some((a) => a.status_acerto === "efetivado")
);

const formataRemanescente = (item) => {
  if (!item.remanescente_qtd) return "—";
  return (
    formataMoeda(item.remanescente_valor) + " (" + item.remanescente_qtd + ")"
  );
};

// --- API ---

const carregarAcertos = async () => {
  loading.value = true;
  try {
    const ret = await sRh.getAcertos(route.params.codperiodo, dias.value);
    acertos.value = ret.data.data;
  } catch (error) {
    $q.notify({
      color: "red-5",
      textColor: "white",
      icon: "error",
      message: extrairErro(error, "Erro ao carregar acertos"),
    });
  } finally {
    loading.value = false;
  }
};

const abrirModal = (colaborador) => {
  colaboradorAtivo.value = colaborador;
  modalAberto.value = true;
};

const estornar = (item) => {
  $q.dialog({
    title: "Estornar Acerto",
    message: `Tem certeza que deseja estornar o acerto de ${item.nome}?`,
    cancel: true,
  }).onOk(async () => {
    try {
      await sRh.estornarAcerto(
        route.params.codperiodo,
        item.codperiodocolaborador
      );
      $q.notify({
        color: "green-5",
        textColor: "white",
        icon: "done",
        message: "Acerto estornado",
      });
      await carregarAcertos();
    } catch (error) {
      $q.notify({
        color: "red-5",
        textColor: "white",
        icon: "error",
        message: extrairErro(error, "Erro ao estornar acerto"),
      });
    }
  });
};

// --- PDF ---

const abrirPdf = async (endpoint, titulo) => {
  try {
    const ret = await api.get(endpoint, { responseType: "blob" });
    const blob = new Blob([ret.data], { type: "application/pdf" });
    pdfTitulo.value = titulo;
    pdfUrl.value = URL.createObjectURL(blob);
    dialogPdf.value = true;
  } catch {
    $q.notify({
      color: "red-5",
      textColor: "white",
      icon: "error",
      message: "Erro ao gerar PDF",
    });
  }
};

const fecharPdf = () => {
  if (pdfUrl.value) {
    URL.revokeObjectURL(pdfUrl.value);
    pdfUrl.value = null;
  }
  dialogPdf.value = false;
};

const imprimirTodosRecibos = () => {
  abrirPdf(
    `v1/rh/periodo/${route.params.codperiodo}/acertos/recibos`,
    "Recibos de Acertos"
  );
};

const relatorioFolha = () => {
  abrirPdf(
    `v1/rh/periodo/${route.params.codperiodo}/acertos/relatorio-folha`,
    "Relatório Folha"
  );
};

const imprimirReciboColaborador = (item) => {
  abrirPdf(
    `v1/rh/periodo/${route.params.codperiodo}/acertos/${item.codperiodocolaborador}/recibos`,
    `Recibo - ${item.nome}`
  );
};

// --- LIFECYCLE ---

let diasTimer = null;
watch(dias, () => {
  clearTimeout(diasTimer);
  diasTimer = setTimeout(carregarAcertos, 400);
});

watch(
  () => route.params.codperiodo,
  (novoId) => {
    if (novoId) carregarAcertos();
  }
);

onMounted(carregarAcertos);
</script>

<template>
  <AcertoModal
    v-if="colaboradorAtivo"
    v-model="modalAberto"
    :colaborador="colaboradorAtivo"
    :codperiodo="route.params.codperiodo"
    :dias="dias"
    @efetivado="carregarAcertos()"
  />

  <!-- Dialog PDF -->
  <q-dialog v-model="dialogPdf" @hide="fecharPdf">
    <q-card bordered flat style="width: 90vw; height: 90vh; max-width: 90vw">
      <q-card-section class="text-grey-9 text-overline row items-center">
        {{ pdfTitulo }}
        <q-space />
        <q-btn
          flat
          round
          dense
          icon="close"
          size="sm"
          color="grey-7"
          v-close-popup
        />
      </q-card-section>
      <q-card-section class="q-pt-none" style="height: calc(90vh - 80px)">
        <iframe
          v-if="pdfUrl"
          :src="pdfUrl"
          style="width: 100%; height: 100%; border: none"
        ></iframe>
      </q-card-section>
    </q-card>
  </q-dialog>

  <!-- TOPO -->
  <div class="row items-center q-mb-md q-gutter-sm">
    <q-input
      v-model.number="dias"
      type="number"
      label="Dias"
      outlined
      style="width: 80px"
      min="1"
    />
    <div class="text-caption text-grey-7">dias para simulação</div>
    <q-space />
    <q-btn
      v-if="temEfetivados"
      flat
      icon="print"
      label="Imprimir Todos os Recibos"
      color="primary"
      @click="imprimirTodosRecibos()"
    />
    <q-btn
      flat
      icon="description"
      label="Relatório Folha"
      color="grey-7"
      @click="relatorioFolha()"
    />
  </div>

  <q-inner-loading :showing="loading" style="min-height: 80px" />

  <!-- VAZIO -->
  <div
    v-if="!loading && acertos.length === 0"
    class="q-pa-md text-center text-grey"
  >
    Nenhum colaborador encontrado
  </div>

  <!-- CARDS POR UNIDADE -->
  <template v-for="grupo in acertosPorUnidade" :key="grupo.codunidadenegocio">
    <q-card bordered flat class="q-mb-md">
      <q-card-section class="text-grey-9 text-overline">
        {{ grupo.unidade }}
      </q-card-section>

      <q-markup-table flat separator="horizontal">
        <colgroup>
          <col style="width: 20%" />
          <col style="width: 11%" />
          <col style="width: 11%" />
          <col style="width: 10%" />
          <col style="width: 10%" />
          <col style="width: 16%" />
          <col style="width: 10%" />
          <col style="width: 12%" />
        </colgroup>
        <thead>
          <tr>
            <th class="text-left">Nome</th>
            <th class="text-right">Créditos</th>
            <th class="text-right">Débitos</th>
            <th class="text-right">Financeiro</th>
            <th class="text-right">Folha</th>
            <th class="text-right">Remanescente</th>
            <th class="text-center">Status</th>
            <th class="text-right">Ações</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="item in grupo.items" :key="item.codperiodocolaborador">
            <td :class="item.status_periodo === 'A' ? 'text-grey-5' : ''">
              {{ item.nome }}
              <q-icon
                v-if="item.status_periodo === 'A'"
                name="schedule"
                size="14px"
                class="q-ml-xs"
              >
                <q-tooltip>Colaborador não encerrado</q-tooltip>
              </q-icon>
            </td>
            <td class="text-right">{{ formataMoeda(item.creditos) }}</td>
            <td class="text-right">{{ formataMoeda(item.debitos) }}</td>
            <td class="text-right">{{ formataMoeda(item.financeiro) }}</td>
            <td class="text-right">{{ formataMoeda(item.folha) }}</td>
            <td class="text-right">{{ formataRemanescente(item) }}</td>
            <td class="text-center">
              <q-badge
                :color="
                  item.status_acerto === 'efetivado' ? 'green-7' : 'grey-6'
                "
                :label="
                  item.status_acerto === 'efetivado' ? 'Efetivado' : 'Pendente'
                "
              />
            </td>
            <td class="text-right text-no-wrap">
              <!-- A: sem ações -->
              <template v-if="item.status_periodo === 'A'" />

              <!-- E + pendente: botão de acerto -->
              <template v-else-if="item.status_acerto === 'pendente'">
                <q-btn
                  flat
                  round
                  icon="sync_alt"
                  size="sm"
                  color="primary"
                  @click="abrirModal(item)"
                >
                  <q-tooltip>Realizar Acerto</q-tooltip>
                </q-btn>
              </template>

              <!-- E + efetivado: imprimir + estornar -->
              <template v-else>
                <q-btn
                  flat
                  round
                  icon="print"
                  size="sm"
                  color="grey-7"
                  @click="imprimirReciboColaborador(item)"
                >
                  <q-tooltip>Imprimir Recibo</q-tooltip>
                </q-btn>
                <q-btn
                  flat
                  round
                  icon="undo"
                  size="sm"
                  color="grey-7"
                  @click="estornar(item)"
                >
                  <q-tooltip>Estornar Acerto</q-tooltip>
                </q-btn>
              </template>
            </td>
          </tr>
        </tbody>
      </q-markup-table>
    </q-card>
  </template>
</template>
