<script setup>
import { ref, computed } from 'vue'
import { useRoute } from 'vue-router'
import { rhStore } from 'src/stores/rh'
import { useAuthStore } from 'src/stores'
import { corProgresso, calcAtingimento, calcPctVendas, somaCampos } from 'src/utils/rhFormatters'
import { formataNumero } from '@components/formatters'
import MgTabelaValores from '@components/MgTabelaValores.vue'
import MgEmptyState from '@components/MgEmptyState.vue'
import BtnExtratoIndicador from 'src/components/rh/BtnExtratoIndicador.vue'
import DialogEditarMeta from 'src/components/rh/DialogEditarMeta.vue'

const route = useRoute()
const sRh = rhStore()
const user = useAuthStore()

const podeEditar = computed(() => user.temPermissao('Recursos Humanos'))

const resumo = computed(() => sRh.resumo || {})
const periodoStatus = computed(() => resumo.value.periodo?.status)
const unidades = computed(() => resumo.value.unidades || [])

// Campos de dinheiro somados em todo subtotal — a mesma lista serve as duas telas.
const CAMPOS_CUSTO = {
  totalsalario: 'totalsalario',
  totaladicional: 'totaladicional',
  totalencargos: 'totalencargos',
  totalvariaveis: 'totalvariaveis',
  total: 'total',
}

const colunas = [
  {
    nome: 'descricao',
    label: 'Unidade',
    tipo: 'texto',
    largura: '14%',
    classe: 'text-weight-medium',
  },
  { nome: 'vendas', label: 'Vendas', tipo: 'numero', largura: '10%' },
  { nome: 'meta', label: 'Meta', tipo: 'numero', largura: '10%' },
  {
    nome: 'atingimento',
    label: 'Ating.',
    tipo: 'percentual',
    largura: '6%',
    valor: (u) => calcAtingimento(u.vendas, u.meta, u.atingimento),
    cor: corProgresso,
  },
  {
    nome: 'progresso',
    label: 'Progresso',
    tipo: 'progresso',
    largura: '10%',
    valor: (u) => calcAtingimento(u.vendas, u.meta, u.atingimento),
    cor: corProgresso,
  },
  { nome: 'totalsalario', label: 'Salário', tipo: 'numero', largura: '9%' },
  { nome: 'totaladicional', label: 'Adicional', tipo: 'numero', largura: '9%' },
  { nome: 'totalencargos', label: 'Encargos', tipo: 'numero', largura: '9%' },
  { nome: 'totalvariaveis', label: 'Variáveis', tipo: 'numero', largura: '9%' },
  { nome: 'total', label: 'Total', tipo: 'numero', largura: '9%', classe: 'text-weight-medium' },
  // {
  //   nome: 'pctvendas',
  //   label: '% Vendas',
  //   tipo: 'percentual',
  //   largura: '5%',
  //   classe: 'text-weight-medium',
  //   valor: (u) =>
  //     u.pctvendas != null ? parseFloat(u.pctvendas) : calcPctVendas(u.total, u.vendas),
  // },
]

const grupos = computed(() => {
  const mapa = {}
  for (const u of unidades.value) {
    const key = u.codempresa || 0
    if (!mapa[key]) {
      mapa[key] = { codempresa: u.codempresa, empresa: u.empresa || 'Sem empresa', unidades: [] }
    }
    mapa[key].unidades.push(u)
  }
  const result = Object.values(mapa)
  for (const g of result) {
    g.unidades.sort((a, b) => {
      const fa = a.codfilial || 0
      const fb = b.codfilial || 0
      if (fa !== fb) return fa - fb
      return (a.descricao || '').localeCompare(b.descricao || '')
    })
    // Meta/Ating./Progresso ficam em branco no subtotal: somar metas de unidades
    // diferentes não produz um número com significado.
    const soma = somaCampos(g.unidades, { ...CAMPOS_CUSTO, vendas: 'vendas' })
    g.rodape = [
      {
        valores: {
          descricao: 'Subtotal',
          vendas: soma.vendas,
          totalsalario: soma.totalsalario,
          totaladicional: soma.totaladicional,
          totalencargos: soma.totalencargos,
          totalvariaveis: soma.totalvariaveis,
          total: soma.total,
          pctvendas: calcPctVendas(soma.total, soma.vendas),
        },
      },
    ]
  }
  return result
})

const totalVendas = computed(() => somaCampos(unidades.value, { vendas: 'vendas' }).vendas)

const rodapeGeral = computed(() => [
  {
    valores: {
      descricao: 'Total Geral',
      vendas: totalVendas.value,
      totalsalario: resumo.value.totalsalario,
      totaladicional: resumo.value.totaladicional,
      totalencargos: resumo.value.totalencargos,
      totalvariaveis: resumo.value.totalvariaveis,
      total: resumo.value.total,
      pctvendas: calcPctVendas(resumo.value.total, totalVendas.value),
    },
  },
])

// --- EDITAR META ---

const dialogMeta = ref(false)
const indicadorMeta = ref(null)

const editarMeta = (u) => {
  if (!u.codindicador) return
  indicadorMeta.value = { codindicador: u.codindicador, meta: u.meta }
  dialogMeta.value = true
}

const recarregar = () => sRh.getResumo(route.params.codperiodo)
</script>

<template>
  <!-- TABELAS POR EMPRESA -->
  <q-card bordered flat class="q-mb-md" v-for="grupo in grupos" :key="grupo.codempresa">
    <q-card-section class="text-grey-9 text-overline row items-center">
      {{ grupo.empresa }}
    </q-card-section>

    <MgTabelaValores
      :colunas="colunas"
      :linhas="grupo.unidades"
      :rodape="grupo.unidades.length > 1 ? grupo.rodape : []"
      chave="codunidadenegocio"
      vazio="Nenhuma unidade nesta empresa."
    >
      <!-- VENDAS: valor + extrato do indicador da unidade -->
      <template #celula-vendas="{ linha, valor }">
        <div class="row items-center justify-end no-wrap">
          <span>{{ valor != null ? formataNumero(valor) : '—' }}</span>
          <BtnExtratoIndicador
            v-if="linha.codindicador"
            :codperiodo="route.params.codperiodo"
            :codindicador="linha.codindicador"
            class="q-ml-xs"
          />
        </div>
      </template>

      <!-- META: valor + editar -->
      <template #celula-meta="{ linha, valor }">
        <div class="row items-center justify-end no-wrap">
          <span>{{ valor ? formataNumero(valor) : '—' }}</span>
          <q-btn
            v-if="podeEditar && linha.codindicador && periodoStatus === 'A'"
            flat
            round
            dense
            icon="edit"
            size="sm"
            color="grey-7"
            class="q-ml-xs"
            @click="editarMeta(linha)"
          >
            <q-tooltip>Editar Meta</q-tooltip>
          </q-btn>
        </div>
      </template>
    </MgTabelaValores>
  </q-card>

  <!-- TOTAL GERAL -->
  <q-card bordered flat class="q-mb-md" v-if="grupos.length > 1">
    <MgTabelaValores :colunas="colunas" :linhas="[]" :rodape="rodapeGeral" :cabecalho="false" />
  </q-card>

  <MgEmptyState v-if="unidades.length === 0">Nenhuma unidade encontrada.</MgEmptyState>

  <DialogEditarMeta v-model="dialogMeta" :indicador="indicadorMeta" @salvo="recarregar()" />
</template>
