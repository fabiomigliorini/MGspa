<script setup>
import { ref, computed, onMounted } from "vue";
import { useQuasar } from "quasar";
import { useRoute } from "vue-router";
import { rhStore } from "src/stores/rh";
import moment from "moment";
import DialogEditarMeta from "./DialogEditarMeta.vue";

const $q = useQuasar();
const route = useRoute();
const sRh = rhStore();

const loading = ref(false);
const indicador = ref(null);
const dialogMeta = ref(false);
const lancamentos = ref([]);
const pagina = ref(1);
const loadingScroll = ref(false);
const totalRegistros = ref(0);

// --- DIALOG LANÇAMENTO MANUAL ---
const dialogLancamento = ref(false);
const modelLancamento = ref({});
const isNovoLancamento = computed(() => !modelLancamento.value.codindicadorlancamento);

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

// --- LIFECYCLE ---

const carregar = async () => {
  loading.value = true;
  pagina.value = 1;
  loadingScroll.value = false;
  try {
    const ret = await sRh.getExtrato(route.params.codindicador, 1);
    indicador.value = ret.data.indicador;
    lancamentos.value = ret.data.lancamentos;
    totalRegistros.value = ret.meta.total;
    if (ret.meta.current_page >= ret.meta.last_page) {
      loadingScroll.value = true;
    }
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

const scrollLancamentos = async (index, done) => {
  pagina.value++;
  try {
    const ret = await sRh.getExtrato(route.params.codindicador, pagina.value);
    lancamentos.value.push(...ret.data.lancamentos);
    if (pagina.value >= ret.meta.last_page) {
      loadingScroll.value = true;
    }
    done();
  } catch {
    done(true);
  }
};

// --- LANÇAMENTO MANUAL CRUD ---

const extrairErro = (error, fallback) => {
  const data = error.response?.data;
  if (!data) return fallback;
  if (data.errors) {
    const primeiro = Object.values(data.errors).flat()[0];
    if (primeiro) return primeiro;
  }
  return data.mensagem || data.message || data.erro || fallback;
};

const abrirDialogLancamento = (lancamento = null) => {
  if (lancamento) {
    modelLancamento.value = {
      codindicadorlancamento: lancamento.codindicadorlancamento,
      valor: lancamento.valor,
      descricao: lancamento.descricao || "",
    };
  } else {
    modelLancamento.value = { valor: null, descricao: "" };
  }
  dialogLancamento.value = true;
};

const submitLancamento = async () => {
  dialogLancamento.value = false;
  try {
    if (isNovoLancamento.value) {
      await sRh.lancamentoManual(route.params.codindicador, {
        valor: modelLancamento.value.valor,
        descricao: modelLancamento.value.descricao || null,
      });
      $q.notify({ color: "green-5", textColor: "white", icon: "done", message: "Lançamento criado" });
    } else {
      await sRh.atualizarLancamento(modelLancamento.value.codindicadorlancamento, {
        valor: modelLancamento.value.valor,
        descricao: modelLancamento.value.descricao || null,
      });
      $q.notify({ color: "green-5", textColor: "white", icon: "done", message: "Lançamento atualizado" });
    }
    await carregar();
  } catch (error) {
    $q.notify({
      color: "red-5",
      textColor: "white",
      icon: "error",
      message: extrairErro(error, "Erro ao salvar lançamento"),
    });
  }
};

const excluirLancamento = (lancamento) => {
  $q.dialog({
    title: "Excluir Lançamento",
    message: `Excluir lançamento manual de ${formataMoeda(lancamento.valor)}?`,
    cancel: true,
    persistent: true,
  }).onOk(async () => {
    try {
      await sRh.excluirLancamento(lancamento.codindicadorlancamento);
      $q.notify({ color: "green-5", textColor: "white", icon: "done", message: "Lançamento excluído" });
      await carregar();
    } catch (error) {
      $q.notify({
        color: "red-5",
        textColor: "white",
        icon: "error",
        message: extrairErro(error, "Erro ao excluir lançamento"),
      });
    }
  });
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
            :to="{ name: 'rhDashboard', params: { codperiodo: route.params.codperiodo }, query: { tab: 'indicadores' } }"
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
                  <q-btn
                    flat
                    dense
                    round
                    icon="edit"
                    size="xs"
                    color="grey-7"
                    @click="dialogMeta = true"
                  />
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
                  {{ totalRegistros }}
                </div>
              </q-card-section>
            </q-card>
          </div>
        </div>

        <!-- TABELA DE LANÇAMENTOS -->
        <q-infinite-scroll
          @load="scrollLancamentos"
          :disable="loadingScroll"
          :offset="200"
        >
          <q-card bordered flat>
            <q-card-section class="text-grey-9 text-overline row items-center">
              EXTRATO
              <q-space />
              <q-btn
                flat
                round
                dense
                icon="add"
                size="sm"
                color="primary"
                @click="abrirDialogLancamento()"
              >
                <q-tooltip>Lançamento Manual</q-tooltip>
              </q-btn>
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
                      class="q-mr-xs"
                    />
                    <template v-if="l.manual">
                      <q-btn
                        flat
                        dense
                        round
                        icon="edit"
                        size="sm"
                        color="grey-7"
                        @click="abrirDialogLancamento(l)"
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
                        @click="excluirLancamento(l)"
                      >
                        <q-tooltip>Excluir</q-tooltip>
                      </q-btn>
                    </template>
                  </td>
                </tr>
              </tbody>
            </q-markup-table>
            <div v-else class="q-pa-md text-center text-grey">
              Nenhum lançamento encontrado
            </div>
          </q-card>

          <template v-slot:loading>
            <div class="row justify-center q-my-md">
              <q-spinner color="primary" size="30px" />
            </div>
          </template>
        </q-infinite-scroll>
      </div>
    </template>

    <DialogEditarMeta
      v-model="dialogMeta"
      :indicador="indicador"
      @salvo="carregar()"
    />

    <!-- DIALOG LANÇAMENTO MANUAL -->
    <q-dialog v-model="dialogLancamento">
      <q-card bordered flat style="width: 400px; max-width: 90vw">
        <q-form @submit="submitLancamento()">
          <q-card-section class="text-grey-9 text-overline">
            {{ isNovoLancamento ? "NOVO LANÇAMENTO MANUAL" : "EDITAR LANÇAMENTO MANUAL" }}
          </q-card-section>

          <q-separator inset />

          <q-card-section class="q-gutter-md">
            <q-input
              outlined
              v-model.number="modelLancamento.valor"
              label="Valor (R$)"
              type="number"
              step="0.01"
              autofocus
              :rules="[(val) => val != null && val !== '' || 'Obrigatório']"
            />
            <q-input
              outlined
              v-model="modelLancamento.descricao"
              label="Descrição"
              maxlength="200"
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
  </div>
</template>
