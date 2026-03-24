<script setup>
import {
  formataMoeda,
  formataPercentual,
  corProgresso,
  tipoIndicadorLabel,
  tipoIndicadorColor,
} from "src/utils/rhFormatters";

import { computed } from "vue";

const props = defineProps({
  indicadores: { type: Array, default: () => [] },
  rubricas: { type: Array, default: () => [] },
  codperiodo: { type: [Number, String], required: true },
  nomeRotaExtrato: { type: String, default: "rhIndicadorExtrato" },
  podeEditar: { type: Boolean, default: false },
  status: { type: String, default: "A" },
  somenteComRubrica: { type: Boolean, default: false },
});

const emit = defineEmits(["editar-meta"]);

const indicadoresFiltrados = computed(() => {
  if (!props.somenteComRubrica || props.rubricas.length === 0) {
    return props.indicadores;
  }
  const codsComRubrica = new Set();
  props.rubricas.forEach((r) => {
    if (r.codindicador) codsComRubrica.add(r.codindicador);
    if (r.codindicadorcondicao) codsComRubrica.add(r.codindicadorcondicao);
  });
  return props.indicadores.filter((ind) => codsComRubrica.has(ind.codindicador));
});

const atingimento = (ind) => {
  if (!ind.meta) return null;
  return (parseFloat(ind.valoracumulado) / parseFloat(ind.meta)) * 100;
};
</script>

<template>
  <q-card bordered flat class="q-mb-md q-pb-md">
    <q-card-section class="text-grey-9 text-overline row items-center">
      INDICADORES
    </q-card-section>

    <template v-if="indicadoresFiltrados.length > 0">
      <template v-for="ind in indicadoresFiltrados" :key="ind.codindicador">
        <q-separator inset />
        <q-card-section class="q-py-sm">
          <div class="row items-center">
            <div class="col">
              <q-badge
                :color="tipoIndicadorColor(ind.tipo)"
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
                  name: nomeRotaExtrato,
                  params: {
                    codperiodo: codperiodo,
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
                @click="emit('editar-meta', ind)"
                v-if="podeEditar && status === 'A'"
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
                :class="'text-' + corProgresso(atingimento(ind))"
              >
                {{ formataPercentual(atingimento(ind)) }}
              </span>
            </div>
          </div>
          <q-linear-progress
            v-if="ind.meta"
            :value="Math.min(atingimento(ind) / 100 || 0, 1)"
            size="6px"
            stripe
            rounded
            class="q-mt-xs"
            :color="corProgresso(atingimento(ind))"
          />
        </q-card-section>
      </template>
    </template>
    <div v-else class="q-pa-md text-center text-grey">
      Nenhum indicador
    </div>
  </q-card>
</template>
