<script setup>
import {
  formataMoeda,
  tipoIndicadorLabel,
} from "src/utils/rhFormatters";

const props = defineProps({
  rubricas: { type: Array, default: () => [] },
  valortotal: { type: Number, default: 0 },
  status: { type: String, default: "A" },
  codtitulo: { type: Number, default: null },
  linkTitulo: { type: String, default: "" },
  podeEditar: { type: Boolean, default: false },
});

const emit = defineEmits([
  "editar",
  "excluir",
  "toggle-concedido",
  "recalcular",
  "encerrar",
  "estornar",
  "nova-rubrica",
]);

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
</script>

<template>
  <q-card bordered flat class="q-mb-md">
    <q-card-section class="text-grey-9 text-overline row items-center">
      RUBRICAS
      <q-space />
      <q-btn
        flat dense round icon="refresh" size="sm" color="grey-7"
        @click="emit('recalcular')"
        v-if="podeEditar && status === 'A'"
      >
        <q-tooltip>Recalcular</q-tooltip>
      </q-btn>
      <q-btn
        flat dense round icon="check_circle" size="sm" color="green-7"
        @click="emit('encerrar')"
        v-if="podeEditar && status === 'A'"
      >
        <q-tooltip>Encerrar</q-tooltip>
      </q-btn>
      <q-btn
        flat dense round icon="undo" size="sm" color="grey-7"
        @click="emit('estornar')"
        v-if="podeEditar && status === 'E'"
      >
        <q-tooltip>Estornar</q-tooltip>
      </q-btn>
      <q-btn
        flat round dense icon="add" size="sm" color="primary"
        v-if="podeEditar && status === 'A'"
        @click="emit('nova-rubrica')"
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
              v-if="podeEditar && status === 'A'"
              :model-value="r.concedido"
              @update:model-value="emit('toggle-concedido', r)"
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
              flat dense round icon="edit" size="sm" color="grey-7"
              @click="emit('editar', r)"
              v-if="status === 'A'"
            >
              <q-tooltip>Editar</q-tooltip>
            </q-btn>
            <q-btn
              flat dense round icon="delete" size="sm" color="grey-7"
              @click="emit('excluir', r)"
              v-if="status === 'A'"
            >
              <q-tooltip>Excluir</q-tooltip>
            </q-btn>
          </td>
        </tr>
        <!-- TOTAL -->
        <tr class="text-weight-bold bg-grey-2">
          <td colspan="5" class="text-right">TOTAL</td>
          <td class="text-right">
            {{ formataMoeda(valortotal) }}
          </td>
          <td class="text-center">
            <q-badge
              :color="status === 'A' ? 'green' : 'blue'"
              :label="status === 'A' ? 'Aberto' : 'Encerrado'"
            />
          </td>
          <td v-if="podeEditar" class="text-right">
            <a
              v-if="codtitulo"
              :href="linkTitulo"
              target="_blank"
              class="text-primary text-caption"
            >
              #{{ String(codtitulo).padStart(8, "0") }}
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
</template>
