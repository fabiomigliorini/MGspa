<script setup>
import { ref, computed, watch } from "vue";
import { useQuasar } from "quasar";
import { rhStore } from "src/stores/rh";

const props = defineProps({
  modelValue: Boolean,
  colaborador: Object,
  codperiodo: [String, Number],
  dias: { type: Number, default: 5 },
});

const emit = defineEmits(["update:modelValue", "efetivado"]);

const $q = useQuasar();
const sRh = rhStore();

const loading = ref(false);
const salvando = ref(false);
const dadosColaborador = ref(null);
const titulos = ref([]);
const observacao = ref("");

// --- HELPERS ---

const formataMoeda = (valor) => {
  return new Intl.NumberFormat("pt-BR", {
    style: "currency",
    currency: "BRL",
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
  }).format(parseFloat(valor) || 0);
};

const formataData = (data) => {
  if (!data) return "";
  const [y, m, d] = data.substring(0, 10).split("-");
  return `${d}/${m}/${y}`;
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

// --- COMPUTED TOTAIS ---

const totalPagando = computed(() =>
  titulos.value.reduce((sum, t) => sum + (parseFloat(t.pagando) || 0), 0)
);

const totalDescontando = computed(() =>
  titulos.value.reduce((sum, t) => sum + (parseFloat(t.descontando) || 0), 0)
);

const resultado = computed(() => totalPagando.value - totalDescontando.value);

const labelResultado = computed(() => {
  if (resultado.value > 0.001)
    return { label: "Financeiro", color: "positive" };
  if (resultado.value < -0.001)
    return { label: "Acerto Folha", color: "negative" };
  return { label: "Encontro Total", color: "grey-7" };
});

const pctDesconto = computed(() => {
  const salario = dadosColaborador.value?.salario;
  if (!salario || salario <= 0 || resultado.value >= 0) return 0;
  return (Math.abs(resultado.value) / salario) * 100;
});

const excedeLimite = computed(() => {
  if (resultado.value >= 0) return false;
  const salario = dadosColaborador.value?.salario;
  if (!salario || salario <= 0) return false;
  const limite = dadosColaborador.value?.percentual_max_desconto;
  if (!limite) return false;
  return pctDesconto.value > limite;
});

const podeSalvar = computed(
  () => totalPagando.value > 0 || totalDescontando.value > 0
);

// --- VALIDAÇÃO DE INPUTS ---

const atualizarPagando = (titulo, val) => {
  const num = parseFloat(val) || 0;
  titulo.pagando = Math.min(Math.max(num, 0), Math.abs(titulo.saldo));
};

const atualizarDescontando = (titulo, val) => {
  const num = parseFloat(val) || 0;
  titulo.descontando = Math.min(Math.max(num, 0), Math.abs(titulo.saldo));
};

const isPreenchido = (titulo) => {
  if (titulo.saldo < 0) return titulo.pagando !== null;
  if (titulo.saldo > 0) return titulo.descontando !== null;
  return false;
};

const toggleLinha = (titulo) => {
  if (isPreenchido(titulo)) {
    titulo.pagando = null;
    titulo.descontando = null;
  } else {
    if (titulo.saldo < 0) titulo.pagando = Math.abs(titulo.saldo);
    if (titulo.saldo > 0) titulo.descontando = Math.abs(titulo.saldo);
  }
};

// --- CARREGAMENTO ---

const carregar = async () => {
  if (!props.colaborador) return;
  loading.value = true;
  titulos.value = [];
  observacao.value = "";
  dadosColaborador.value = null;
  try {
    const ret = await sRh.getTitulosAcerto(
      props.codperiodo,
      props.colaborador.codperiodocolaborador,
      props.dias
    );
    dadosColaborador.value = ret.data.data.colaborador;
    titulos.value = ret.data.data.titulos.map((t) => ({
      ...t,
      pagando: t.saldo < 0 ? parseFloat(t.sugestao_pagando) || null : null,
      descontando:
        t.saldo > 0 ? parseFloat(t.sugestao_descontando) || null : null,
    }));
  } catch (error) {
    $q.notify({
      color: "red-5",
      textColor: "white",
      icon: "error",
      message: extrairErro(error, "Erro ao carregar títulos"),
    });
    emit("update:modelValue", false);
  } finally {
    loading.value = false;
  }
};

watch(
  () => props.modelValue,
  (val) => {
    if (val) carregar();
  },
  { immediate: true }
);

// --- SUBMIT ---

const confirmar = async () => {
  salvando.value = true;
  try {
    const payload = {
      observacao: observacao.value,
      titulos: titulos.value.map((t) => ({
        codtitulo: t.codtitulo,
        descontando: parseFloat(t.descontando) || 0,
        pagando: parseFloat(t.pagando) || 0,
      })),
    };
    await sRh.efetivarAcerto(
      props.codperiodo,
      props.colaborador.codperiodocolaborador,
      payload
    );
    $q.notify({
      color: "green-5",
      textColor: "white",
      icon: "done",
      message: "Acerto efetivado com sucesso",
    });
    emit("efetivado");
    emit("update:modelValue", false);
  } catch (error) {
    $q.notify({
      color: "red-5",
      textColor: "white",
      icon: "error",
      message: extrairErro(error, "Erro ao efetivar acerto"),
    });
  } finally {
    salvando.value = false;
  }
};
</script>

<template>
  <q-dialog
    :model-value="modelValue"
    @update:model-value="emit('update:modelValue', $event)"
    :maximized="$q.screen.lt.md"
  >
    <q-card
      bordered
      flat
      style="width: 780px; max-width: 95vw; min-height: 200px"
    >
      <q-inner-loading :showing="loading" />

      <template v-if="!loading && dadosColaborador">
        <!-- CABEÇALHO -->
        <q-card-section class="q-pb-sm">
          <div class="text-h6 text-grey-9">{{ dadosColaborador.nome }}</div>
          <div class="text-caption text-grey-7">
            {{ dadosColaborador.cargo }}
            <template v-if="dadosColaborador.tempo_casa">
              · {{ dadosColaborador.tempo_casa }}
            </template>
            · Salário: {{ formataMoeda(dadosColaborador.salario) }}
          </div>
        </q-card-section>

        <q-separator />

        <!-- TÍTULOS -->
        <q-card-section class="q-pa-none">
          <!-- Header -->
          <div
            class="row items-center q-px-md q-py-xs text-caption text-weight-medium text-grey-7 bg-grey-2"
          >
            <div class="col-3">Título</div>
            <div class="col-2">Vencimento</div>
            <div class="col-3 text-center">Saldo</div>
            <div class="col-2 text-center">Pagando</div>
            <div class="col-2 text-center">Descontando</div>
          </div>
          <q-separator />

          <!-- Linhas -->
          <q-scroll-area style="height: 300px">
            <template v-for="titulo in titulos" :key="titulo.codtitulo">
              <div class="row items-center q-col-gutter-md q-pa-md">
                <div class="col-3 text-body2">{{ titulo.numero }}</div>
                <div class="col-2 text-body2">
                  {{ formataData(titulo.vencimento) }}
                </div>
                <div
                  class="col-3 text-right text-weight-medium"
                  :class="titulo.saldo < 0 ? 'text-green-8' : 'text-red-7'"
                >
                  {{ formataMoeda(Math.abs(titulo.saldo)) }}
                  <q-icon
                    :name="titulo.saldo < 0 ? 'south' : 'north'"
                    size="12px"
                  />
                  <q-btn
                    flat
                    round
                    size="sm"
                    :icon="isPreenchido(titulo) ? 'close' : 'add'"
                    :color="isPreenchido(titulo) ? 'grey-6' : 'primary'"
                    tabindex="-1"
                    @click="toggleLinha(titulo)"
                  >
                    <q-tooltip>{{
                      isPreenchido(titulo) ? "Remover" : "Adicionar"
                    }}</q-tooltip>
                  </q-btn>
                </div>
                <!-- Pagando -->
                <div class="col-2">
                  <q-input
                    dense
                    :model-value="titulo.pagando"
                    @update:model-value="(val) => atualizarPagando(titulo, val)"
                    type="number"
                    outlined
                    v-if="titulo.saldo < 0"
                    class="full-width"
                    input-class="text-right"
                    min="0"
                    :max="Math.abs(titulo.saldo)"
                    step="0.01"
                  />
                </div>
                <!-- Descontando + toggle linha -->
                <div class="col-2">
                  <div class="row no-wrap items-center">
                    <q-input
                      dense
                      :model-value="titulo.descontando"
                      @update:model-value="
                        (val) => atualizarDescontando(titulo, val)
                      "
                      type="number"
                      outlined
                      v-if="titulo.saldo > 0"
                      class="col"
                      input-class="text-right"
                      min="0"
                      :max="Math.abs(titulo.saldo)"
                      step="0.01"
                    />
                  </div>
                </div>
              </div>
              <q-separator />
            </template>

            <!-- Vazio -->
            <div
              v-if="titulos.length === 0"
              class="q-pa-md text-center text-grey"
            >
              Nenhum título encontrado
            </div>
          </q-scroll-area>
          <q-separator />

          <!-- Totais -->
          <div
            class="row items-center q-px-md q-py-sm text-weight-medium bg-grey-2"
          >
            <div class="col-8"></div>
            <div class="col-2 text-center text-positive">
              {{ formataMoeda(totalPagando) }}
            </div>
            <div class="col-2 text-center text-negative">
              {{ formataMoeda(totalDescontando) }}
            </div>
          </div>
          <div
            class="row items-center q-px-md q-py-sm text-weight-medium bg-grey-2"
          >
            <div
              class="col-8 text-right text-weight-bold"
              :class="'text-' + labelResultado.color"
            >
              {{ labelResultado.label }}:
            </div>
            <div
              class="col-2 text-center"
              :class="'text-' + labelResultado.color"
            >
              <template v-if="resultado >= 0">
                {{ formataMoeda(Math.abs(resultado)) }}
              </template>
            </div>
            <div
              class="col-2 text-center"
              :class="'text-' + labelResultado.color"
            >
              <template v-if="resultado < 0">
                {{ formataMoeda(Math.abs(resultado)) }}
              </template>
            </div>
          </div>
        </q-card-section>

        <!-- ALERTA LIMITE FOLHA -->
        <q-banner
          v-if="excedeLimite"
          class="bg-orange-1 text-orange-9 q-mx-md q-mb-sm"
          rounded
        >
          <template v-slot:avatar>
            <q-icon name="warning" color="orange" />
          </template>
          Atenção: desconto de
          {{ formataMoeda(Math.abs(resultado)) }} representa
          {{ pctDesconto.toFixed(1) }}% do salário ({{
            formataMoeda(dadosColaborador.salario)
          }}). Limite configurado:
          {{ dadosColaborador.percentual_max_desconto }}%.
        </q-banner>

        <!-- OBSERVAÇÃO -->
        <q-card-section class="q-pt-sm">
          <q-input
            v-model="observacao"
            type="textarea"
            label="Observação (opcional)"
            outlined
            rows="2"
            autogrow
            maxlength="200"
          />
        </q-card-section>

        <q-separator inset />

        <!-- AÇÕES -->
        <q-card-actions align="right" class="text-primary">
          <q-btn
            flat
            label="Cancelar"
            v-close-popup
            tabindex="-1"
            color="grey-8"
          />
          <q-btn
            flat
            label="Confirmar"
            :disable="!podeSalvar"
            :loading="salvando"
            @click="confirmar()"
          />
        </q-card-actions>
      </template>
    </q-card>
  </q-dialog>
</template>
