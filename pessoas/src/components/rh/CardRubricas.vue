<script setup>
import { tipoIndicadorLabel } from 'src/utils/rhFormatters'
import { formataNumero, formataCodigo } from '@components/formatters'

defineProps({
  rubricas: { type: Array, default: () => [] },
  valortotal: { type: Number, default: 0 },
  status: { type: String, default: 'A' },
  codtitulo: { type: Number, default: null },
  linkTitulo: { type: String, default: '' },
  podeEditar: { type: Boolean, default: false },
  codperiodo: { type: [Number, String], default: null },
  nomeRotaExtrato: { type: String, default: 'rhIndicadorExtrato' },
})

const emit = defineEmits(['editar', 'excluir', 'toggle-concedido', 'nova-rubrica', 'editar-meta'])

const tipoValorLabel = (tipo) => {
  if (tipo === 'P') return '%'
  if (tipo === 'Q') return 'Un×Qt'
  return 'Fixo'
}

const tipoValorColor = (tipo) => {
  if (tipo === 'P') return 'blue'
  if (tipo === 'Q') return 'teal'
  return 'purple'
}

const rubricaDescricao = (r) => r.descricao || r.rubrica?.descricao || '—'

const condicaoLabel = (rubrica) => {
  if (!rubrica.tipocondicao) return '—'
  const tipo = rubrica.tipocondicao === 'M' ? 'Meta' : 'Rank'
  const ind = rubrica.indicador_condicao
  const indicadorLabel = ind ? tipoIndicadorLabel(ind.tipo) : ''
  return tipo + ' ' + indicadorLabel
}

// Indicador da rubrica: o base (percentual) ou, na falta, o da condição.
const rubricaIndicador = (r) => r.indicador || r.indicador_condicao || null

// Rótulo consolidado da coluna Indicador / Condição.
const indicadorCondicaoLabel = (r) =>
  r.indicador ? tipoIndicadorLabel(r.indicador.tipo) : condicaoLabel(r)
</script>

<template>
  <q-card bordered flat class="q-mb-md">
    <q-card-section class="text-grey-9 text-overline row items-center">
      RUBRICAS
      <q-space />
      <q-btn
        flat
        round
        icon="add"
        size="sm"
        color="primary"
        v-if="podeEditar && status === 'A'"
        @click="emit('nova-rubrica')"
      >
        <q-tooltip>Nova Rubrica</q-tooltip>
      </q-btn>
    </q-card-section>

    <q-markup-table flat separator="horizontal" v-if="rubricas.length > 0" class="rh-tabela">
      <thead>
        <tr class="text-left">
          <th>Descrição</th>
          <th class="text-center">Tipo</th>
          <th class="text-right">%/Valor</th>
          <th>Indicador / Condição</th>
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
          <td>
            {{ rubricaDescricao(r) }}
            <q-icon
              v-if="r.observacao"
              name="sticky_note_2"
              size="xs"
              color="grey-6"
              class="q-ml-xs"
            >
              <q-tooltip>{{ r.observacao }}</q-tooltip>
            </q-icon>
          </td>
          <td class="text-center">
            <q-badge :color="tipoValorColor(r.tipovalor)" :label="tipoValorLabel(r.tipovalor)" />
          </td>
          <td class="text-right">
            <template v-if="r.tipovalor === 'P'">{{ r.percentual }}%</template>
            <template v-else-if="r.tipovalor === 'Q'">
              {{ formataNumero(r.valorunitario) }} × {{ r.quantidade }}
            </template>
            <template v-else>{{ formataNumero(r.valorfixo) }}</template>
          </td>
          <td>
            <div class="row items-center no-wrap">
              <span>{{ indicadorCondicaoLabel(r) }}</span>
              <template v-if="rubricaIndicador(r)">
                <q-btn
                  flat
                  round
                  icon="receipt_long"
                  size="sm"
                  color="grey-7"
                  v-if="codperiodo"
                  :to="{
                    name: nomeRotaExtrato,
                    params: { codperiodo, codindicador: rubricaIndicador(r).codindicador },
                  }"
                >
                  <q-tooltip>Extrato do indicador</q-tooltip>
                </q-btn>
                <q-btn
                  flat
                  round
                  icon="edit"
                  size="sm"
                  color="grey-7"
                  @click="emit('editar-meta', rubricaIndicador(r))"
                  v-if="podeEditar && status === 'A'"
                >
                  <q-tooltip>Editar meta</q-tooltip>
                </q-btn>
              </template>
            </div>
          </td>
          <td class="text-right text-weight-bold">
            {{ formataNumero(r.valorcalculado) }}
          </td>
          <td class="text-center">
            <q-toggle
              v-if="podeEditar && status === 'A'"
              :model-value="r.concedido"
              @update:model-value="emit('toggle-concedido', r)"
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
              round
              icon="edit"
              size="sm"
              color="grey-7"
              @click="emit('editar', r)"
              v-if="status === 'A'"
            >
              <q-tooltip>Editar</q-tooltip>
            </q-btn>
            <q-btn
              flat
              round
              icon="delete"
              size="sm"
              color="grey-7"
              @click="emit('excluir', r)"
              v-if="status === 'A'"
            >
              <q-tooltip>Excluir</q-tooltip>
            </q-btn>
          </td>
        </tr>
        <!-- TOTAL -->
        <tr class="text-weight-bold bg-grey-2">
          <td colspan="4" class="text-right">TOTAL</td>
          <td class="text-right">
            {{ formataNumero(valortotal) }}
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
              {{ formataCodigo(codtitulo) }}
              <q-icon name="open_in_new" size="xs" />
            </a>
          </td>
        </tr>
      </tbody>
    </q-markup-table>
    <div v-else class="q-pa-md text-center text-grey">Nenhuma rubrica cadastrada</div>
  </q-card>
</template>
