<script setup>
import { computed } from 'vue'
import {
  tipoIndicadorLabel,
  corProgresso,
  calcAtingimento,
  statusColaboradorLabel,
  statusColaboradorColor,
} from 'src/utils/rhFormatters'
import { formataNumero, formataCodigo } from '@components/formatters'
import MgTabelaValores from '@components/MgTabelaValores.vue'
import BtnExtratoIndicador from 'src/components/rh/BtnExtratoIndicador.vue'

const props = defineProps({
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

// Atingimento do indicador que serve de base (ou de condição) da rubrica.
const atingimentoRubrica = (r) => {
  const ind = rubricaIndicador(r)
  return ind ? calcAtingimento(ind.valoracumulado ?? ind.vendas, ind.meta, ind.atingimento) : null
}

// A coluna Ações só existe quando dá para editar — declarar aqui evita o v-if
// duplicado em <th> e <td> que desalinhava a tabela.
const colunas = computed(() => {
  const cols = [
    { nome: 'descricao', label: 'Descrição', tipo: 'slot', largura: '24%' },
    { nome: 'tipovalor', label: 'Tipo', tipo: 'slot', align: 'center', largura: '8%' },
    { nome: 'valor', label: '%/Valor', tipo: 'slot', align: 'right', largura: '11%' },
    { nome: 'indicador', label: 'Indicador / Condição', tipo: 'slot', largura: '18%' },
    {
      nome: 'atingimento',
      label: 'Ating.',
      tipo: 'percentual',
      largura: '7%',
      classe: 'text-weight-bold',
      valor: atingimentoRubrica,
      cor: corProgresso,
    },
    {
      nome: 'progresso',
      label: 'Progresso',
      tipo: 'progresso',
      largura: '10%',
      valor: atingimentoRubrica,
      cor: corProgresso,
    },
    {
      nome: 'valorcalculado',
      label: 'Calculado',
      tipo: 'numero',
      largura: '10%',
      classe: 'text-weight-bold',
    },
  ]
  if (props.podeEditar) {
    cols.push({ nome: 'acoes', label: 'Ações', tipo: 'slot', align: 'right', largura: '6%' })
  }
  return cols
})

const rodape = computed(() => [
  {
    classe: 'text-weight-bold bg-grey-2',
    valores: { descricao: 'TOTAL', valorcalculado: props.valortotal },
  },
])

// Rubrica não concedida entra apagada — o valor calculado dela é zero.
const linhaClasse = (r) => (!r.concedido ? 'text-grey-5' : '')
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

    <MgTabelaValores
      :colunas="colunas"
      :linhas="rubricas"
      :rodape="rubricas.length ? rodape : []"
      chave="codcolaboradorrubrica"
      :linha-classe="linhaClasse"
      vazio="Nenhuma rubrica cadastrada."
    >
      <template #celula-descricao="{ linha }">
        {{ rubricaDescricao(linha) }}
        <q-icon
          v-if="linha.observacao"
          name="sticky_note_2"
          size="xs"
          color="grey-6"
          class="q-ml-xs"
        >
          <q-tooltip>{{ linha.observacao }}</q-tooltip>
        </q-icon>
      </template>

      <template #celula-tipovalor="{ linha }">
        <q-badge
          :color="tipoValorColor(linha.tipovalor)"
          :label="tipoValorLabel(linha.tipovalor)"
        />
      </template>

      <template #celula-valor="{ linha }">
        <template v-if="linha.tipovalor === 'P'">{{ linha.percentual }}%</template>
        <template v-else-if="linha.tipovalor === 'Q'">
          {{ formataNumero(linha.valorunitario) }} × {{ linha.quantidade }}
        </template>
        <template v-else>{{ formataNumero(linha.valorfixo) }}</template>
      </template>

      <template #celula-indicador="{ linha }">
        <div class="row items-center no-wrap">
          <span>{{ indicadorCondicaoLabel(linha) }}</span>
          <template v-if="rubricaIndicador(linha)">
            <BtnExtratoIndicador
              v-if="codperiodo"
              :codperiodo="codperiodo"
              :codindicador="rubricaIndicador(linha).codindicador"
              :nome-rota="nomeRotaExtrato"
            />
            <q-btn
              flat
              round
              dense
              icon="edit"
              size="sm"
              color="grey-7"
              @click="emit('editar-meta', rubricaIndicador(linha))"
              v-if="podeEditar && status === 'A'"
            >
              <q-tooltip>Editar meta</q-tooltip>
            </q-btn>
          </template>
        </div>
      </template>

      <template #celula-concedido="{ linha }">
        <q-toggle
          v-if="podeEditar && status === 'A'"
          :model-value="linha.concedido"
          @update:model-value="emit('toggle-concedido', linha)"
        />
        <q-icon
          v-else
          :name="linha.concedido ? 'check_circle' : 'cancel'"
          :color="linha.concedido ? 'green' : 'red'"
          size="sm"
        />
      </template>

      <template #celula-acoes="{ linha }">
        <template v-if="status === 'A'">
          <q-btn
            flat
            round
            dense
            icon="edit"
            size="sm"
            color="grey-7"
            @click="emit('editar', linha)"
          >
            <q-tooltip>Editar</q-tooltip>
          </q-btn>
          <q-btn
            flat
            round
            dense
            icon="delete"
            size="sm"
            color="grey-7"
            @click="emit('excluir', linha)"
          >
            <q-tooltip>Excluir</q-tooltip>
          </q-btn>
        </template>
      </template>

      <!-- Status do colaborador e link do título fecham a linha de TOTAL -->
      <template #rodape-concedido>
        <q-badge :color="statusColaboradorColor(status)" :label="statusColaboradorLabel(status)" />
      </template>

      <template #rodape-acoes>
        <a v-if="codtitulo" :href="linkTitulo" target="_blank" class="text-primary text-caption">
          {{ formataCodigo(codtitulo) }}
          <q-icon name="open_in_new" size="xs" />
        </a>
      </template>
    </MgTabelaValores>
  </q-card>
</template>
