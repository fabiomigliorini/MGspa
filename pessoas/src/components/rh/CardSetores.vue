<script setup>
const props = defineProps({
  setores: { type: Array, default: () => [] },
  diasUteisPeriodo: { type: Number, default: 0 },
  podeEditar: { type: Boolean, default: false },
  status: { type: String, default: "A" },
});

const emit = defineEmits(["editar", "excluir", "adicionar"]);
</script>

<template>
  <q-card bordered flat class="q-mb-md q-pb-md">
    <q-card-section class="text-grey-9 text-overline row items-center">
      SETORES
      <q-space />
      <q-btn
        flat round dense icon="add" size="sm" color="primary"
        v-if="podeEditar && status === 'A'"
        @click="emit('adicionar')"
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
            v-if="podeEditar && status === 'A'"
          >
            <q-item-label caption>
              <q-btn
                flat dense round icon="edit" size="sm" color="grey-7"
                @click="emit('editar', pcs)"
              >
                <q-tooltip>Editar</q-tooltip>
              </q-btn>
              <q-btn
                flat dense round icon="delete" size="sm" color="grey-7"
                @click="emit('excluir', pcs)"
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
</template>
