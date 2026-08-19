<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import { useQuasar } from 'quasar'
import { rhStore } from 'src/stores/rh'
import {
  corProgresso,
  calcAtingimento,
  statusColaboradorLabel,
  statusColaboradorColor,
  extrairErro,
} from 'src/utils/rhFormatters'
import { formataNumero, formataPercentual } from '@components/formatters'
import { api } from 'boot/axios'
import { abrirPdf } from '@components/abrirPdf'
import MgTabelaValores from '@components/MgTabelaValores.vue'
import MgEmptyState from '@components/MgEmptyState.vue'
import MgInfoCriacao from '@components/MgInfoCriacao.vue'
import BtnExtratoIndicador from 'src/components/rh/BtnExtratoIndicador.vue'
import BadgeTipoIndicador from 'src/components/rh/BadgeTipoIndicador.vue'
import AcoesUnidade from 'src/components/rh/AcoesUnidade.vue'
import AcoesSetor from 'src/components/rh/AcoesSetor.vue'
import DialogUnidade from 'src/components/rh/DialogUnidade.vue'
import DialogSetor from 'src/components/rh/DialogSetor.vue'
import DialogAdicionarColaboradores from 'src/components/rh/DialogAdicionarColaboradores.vue'
import DialogEditarMeta from 'src/components/rh/DialogEditarMeta.vue'

const props = defineProps({
  codperiodo: { type: [Number, String], required: true },
  codunidade: { type: [Number, String], required: true },
  descricao: { type: String, default: '' },
  unidade: { type: Object, default: null },
  podeEditar: { type: Boolean, default: false },
  periodoStatus: { type: String, default: 'A' },
})

const emit = defineEmits(['atualizado'])

const $q = useQuasar()
const sRh = rhStore()

const loading = ref(false)
const dados = ref(null)

const setores = computed(() => dados.value?.setores || [])

const atingimentoInd = (ind) =>
  ind ? calcAtingimento(ind.valoracumulado ?? ind.vendas, ind.meta, ind.atingimento) : null

// Colunas do colaborador (custo) usam rowspan; as do indicador repetem por
// sub-linha. Mesmo conjunto de colunas serve as tabelas de setor e o total
// da filial — é o que garante que as duas fiquem alinhadas.
const colunas = [
  { nome: 'nome', label: 'Nome', tipo: 'texto', largura: '18%', agrupada: true },
  { nome: 'tipo', label: 'Tipo', tipo: 'slot', align: 'center', largura: '6%' },
  { nome: 'indicador', label: 'Indicador', tipo: 'slot', align: 'right', largura: '10%' },
  { nome: 'meta', label: 'Meta', tipo: 'slot', align: 'right', largura: '10%' },
  {
    nome: 'atingimento',
    label: 'Ating.',
    tipo: 'percentual',
    largura: '5%',
    classe: 'text-weight-bold',
    valor: (linha, sub) => atingimentoInd(sub),
    cor: corProgresso,
  },
  {
    nome: 'progresso',
    label: 'Progresso',
    tipo: 'progresso',
    largura: '7%',
    valor: (linha, sub) => atingimentoInd(sub),
    cor: corProgresso,
  },
  { nome: 'salario', label: 'Salário', tipo: 'numero', largura: '8%', agrupada: true },
  { nome: 'adicional', label: 'Adicional', tipo: 'numero', largura: '7%', agrupada: true },
  { nome: 'encargos', label: 'Encargos', tipo: 'numero', largura: '7%', agrupada: true },
  { nome: 'variaveis', label: 'Variáveis', tipo: 'numero', largura: '8%', agrupada: true },
  {
    nome: 'total',
    label: 'Total',
    tipo: 'numero',
    largura: '8%',
    agrupada: true,
    classe: 'text-weight-medium',
  },
  { nome: 'status', label: 'Status', tipo: 'slot', align: 'center', largura: '6%', agrupada: true },
]

// Subtotal do setor: só dinheiro. Somar os indicadores individuais (V/C) daria
// double-count contra o coletivo (S) e contra o da unidade (U).
const rodapeSetor = (setor) => {
  const t = setor.totais
  if (!t) return []
  return [
    {
      valores: {
        nome: 'Subtotal',
        salario: t.salario,
        adicional: t.adicional,
        encargos: t.encargos,
        variaveis: t.variaveis,
        total: t.total,
      },
    },
  ]
}

// Total da filial: aqui Indicador/Meta/Ating./Progresso SÃO preenchidos, com o
// indicador de unidade — a linha vira o clone da linha desta filial no Resumão.
const rodapeFilial = computed(() => {
  const d = dados.value
  if (!d?.totais) return []
  return [
    {
      valores: {
        nome: 'Total da Filial',
        indicador: d.vendas,
        meta: d.meta,
        atingimento: calcAtingimento(d.vendas, d.meta, d.atingimento),
        progresso: calcAtingimento(d.vendas, d.meta, d.atingimento),
        salario: d.totais.salario,
        adicional: d.totais.adicional,
        encargos: d.totais.encargos,
        variaveis: d.totais.variaveis,
        total: d.totais.total,
      },
    },
  ]
})

// Cabeçalho do card mostra só o indicador COLETIVO do setor (tipo S).
// Os individuais (Vendedor/Caixa) aparecem na linha de cada colaborador.
const indicadoresColetivos = (setor) => (setor.indicadores || []).filter((i) => i.tipo === 'S')

// Destino do colaborador — serve o link do nome E a linha inteira da tabela
// (`linha-to`), que leva pro detalhe (tela de rubricas). As ações de
// encerrar/estornar/recalcular ficam só lá.
// O "voltar" no detalhe deriva a aba da unidade do próprio colaborador (resource).
const colaboradorTo = (c) => ({
  name: 'rhColaboradorDetalhe',
  params: {
    codperiodo: props.codperiodo,
    codperiodocolaborador: c.codperiodocolaborador,
  },
})

// Recibos de acerto só desta filial (unidade) — PDF menor e mais rápido.
const imprimirRecibos = () => {
  abrirPdf(
    api,
    `v1/rh/periodo/${props.codperiodo}/acertos/recibos`,
    { codunidadenegocio: props.codunidade },
    { title: 'Recibos — ' + (props.descricao || 'Filial') },
  )
}

const carregar = async () => {
  loading.value = true
  try {
    dados.value = await sRh.getUnidade(props.codperiodo, props.codunidade)
  } catch (error) {
    $q.notify({
      color: 'red-5',
      textColor: 'white',
      icon: 'error',
      message: extrairErro(error, 'Erro ao carregar unidade'),
    })
  } finally {
    loading.value = false
  }
}

const notificar = (color, icon, message) => $q.notify({ color, textColor: 'white', icon, message })

// --- AÇÕES DA UNIDADE ---

const dialogUnidade = ref(false)

const editarUnidade = () => {
  dialogUnidade.value = true
}

const onSalvoUnidade = async () => {
  await carregar()
  emit('atualizado')
}

const toggleUnidade = async () => {
  try {
    if (props.unidade?.inativo) {
      await sRh.ativarUnidade(props.codunidade)
      notificar('green-5', 'done', 'Unidade ativada')
    } else {
      await sRh.inativarUnidade(props.codunidade)
      notificar('green-5', 'done', 'Unidade inativada')
    }
    emit('atualizado')
    await carregar()
  } catch (error) {
    notificar('red-5', 'error', extrairErro(error, 'Erro ao alterar unidade'))
  }
}

const excluirUnidade = () => {
  $q.dialog({
    title: 'Excluir Unidade',
    message: `Tem certeza que deseja excluir "${props.descricao}"?`,
    cancel: { label: 'Cancelar', color: 'grey-8', flat: true },
    ok: { label: 'Excluir', color: 'red-5', flat: true },
  }).onOk(async () => {
    try {
      await sRh.excluirUnidade(props.codunidade)
      notificar('green-5', 'done', 'Unidade excluída')
      emit('atualizado')
    } catch (error) {
      notificar('red-5', 'error', extrairErro(error, 'Erro ao excluir unidade'))
    }
  })
}

// --- AÇÕES DO SETOR ---

const dialogSetor = ref(false)
const setorEdit = ref(null)

const abrirNovoSetor = () => {
  setorEdit.value = null
  dialogSetor.value = true
}

const editarSetor = (setor) => {
  setorEdit.value = { ...setor }
  dialogSetor.value = true
}

// Remover/mover colaborador muda o custo do período, então além do refetch da
// unidade é preciso bubblar pro PeriodoDashboard recarregar os KPIs do topo.
const onSalvoSetor = async () => {
  await carregar()
  emit('atualizado')
}

const toggleSetor = async (setor) => {
  try {
    if (setor.inativo) {
      await sRh.ativarSetor(setor.codsetor)
      notificar('green-5', 'done', 'Setor ativado')
    } else {
      await sRh.inativarSetor(setor.codsetor)
      notificar('green-5', 'done', 'Setor inativado')
    }
    await carregar()
  } catch (error) {
    notificar('red-5', 'error', extrairErro(error, 'Erro ao alterar setor'))
  }
}

const excluirSetor = (setor) => {
  $q.dialog({
    title: 'Excluir Setor',
    message: `Tem certeza que deseja excluir "${setor.descricao}"?`,
    cancel: { label: 'Cancelar', color: 'grey-8', flat: true },
    ok: { label: 'Excluir', color: 'red-5', flat: true },
  }).onOk(async () => {
    try {
      await sRh.excluirSetor(setor.codsetor)
      notificar('green-5', 'done', 'Setor excluído')
      await carregar()
    } catch (error) {
      notificar('red-5', 'error', extrairErro(error, 'Erro ao excluir setor'))
    }
  })
}

// --- ADICIONAR COLABORADORES (no setor) ---

const dialogAddColab = ref(false)
const addColabSetor = ref(null)

const abrirAddColab = (setor) => {
  addColabSetor.value = setor
  dialogAddColab.value = true
}

// --- EDITAR META DO INDICADOR ---

const dialogMeta = ref(false)
const indicadorMeta = ref(null)

const editarMeta = (ind) => {
  indicadorMeta.value = ind
  dialogMeta.value = true
}

onMounted(() => carregar())

// Também observa o período: ficando na mesma aba com o período trocado, a
// unidade não muda e sem isso a tabela continuaria com os dados antigos.
watch(
  () => [props.codperiodo, props.codunidade],
  () => carregar(),
)
</script>

<template>
  <div>
    <q-inner-loading :showing="loading" style="min-height: 80px" />

    <template v-if="!loading">
      <!-- AÇÕES DA FILIAL -->
      <div class="row items-center q-mb-md" v-if="podeEditar">
        <q-space />
        <q-btn
          flat
          icon="print"
          label="Recibos"
          color="grey-7"
          v-if="setores.length > 0"
          @click="imprimirRecibos()"
        >
          <q-tooltip>Recibos de acerto desta filial</q-tooltip>
        </q-btn>
        <MgInfoCriacao v-if="unidade" :registro="unidade" class="q-ml-sm" />
        <AcoesUnidade
          v-if="unidade"
          :unidade="unidade"
          class="q-ml-sm"
          @editar="editarUnidade()"
          @toggle-inativo="toggleUnidade()"
          @excluir="excluirUnidade()"
          @adicionar-setor="abrirNovoSetor()"
        />
      </div>

      <q-card
        bordered
        flat
        class="q-mb-md"
        v-for="setor in setores"
        :key="setor.codsetor || 'sem-setor'"
      >
        <!-- CABEÇALHO DO SETOR -->
        <q-card-section class="text-grey-9 text-overline row items-center">
          {{ setor.descricao || 'Sem Setor' }}
          <q-badge v-if="setor.inativo" color="grey" label="Inativo" class="q-ml-sm" />
          <q-space />
          <div class="row items-center q-gutter-sm">
            <template v-for="ind in indicadoresColetivos(setor)" :key="ind.codindicador">
              <div class="text-caption text-grey-7">
                <BadgeTipoIndicador :tipo="ind.tipo" class="q-mr-xs" />
                {{ formataNumero(ind.valoracumulado ?? ind.vendas) }}
                <template v-if="ind.meta"> / {{ formataNumero(ind.meta) }} </template>
                <span
                  v-if="atingimentoInd(ind) != null"
                  class="text-weight-bold q-ml-xs"
                  :class="'text-' + corProgresso(atingimentoInd(ind))"
                >
                  {{ formataPercentual(atingimentoInd(ind)) }}
                </span>
              </div>
            </template>
          </div>
          <MgInfoCriacao v-if="podeEditar && setor.codsetor" :registro="setor" class="q-ml-sm" />
          <AcoesSetor
            v-if="podeEditar"
            :setor="setor"
            class="q-ml-sm"
            @editar="editarSetor(setor)"
            @toggle-inativo="toggleSetor(setor)"
            @excluir="excluirSetor(setor)"
            @adicionar-colaboradores="abrirAddColab(setor)"
          />
        </q-card-section>

        <!-- COLABORADORES DO SETOR -->
        <MgTabelaValores
          :colunas="colunas"
          :linhas="setor.colaboradores || []"
          :rodape="(setor.colaboradores || []).length ? rodapeSetor(setor) : []"
          chave="codperiodocolaborador"
          sub-linhas="indicadores"
          :linha-to="colaboradorTo"
          vazio="Nenhum colaborador neste setor."
        >
          <!-- router-link renderiza <a href> de verdade: Ctrl/⌘+clique, botão do
               meio e "abrir em nova aba" passam direto pro navegador, e o clique
               simples continua navegando pelo router (sem recarregar a página). -->
          <template #celula-nome="{ linha }">
            <router-link :to="colaboradorTo(linha)" class="text-primary">
              {{ linha.nome }}
            </router-link>
            <div class="text-caption text-grey" v-if="linha.cargo">{{ linha.cargo }}</div>
          </template>

          <template #celula-tipo="{ sub }">
            <BadgeTipoIndicador v-if="sub" :tipo="sub.tipo" />
            <span v-else class="text-grey">—</span>
          </template>

          <!-- INDICADOR: valor + extrato -->
          <template #celula-indicador="{ sub }">
            <div v-if="sub" class="row items-center justify-end no-wrap">
              <span class="text-weight-medium">{{ formataNumero(sub.vendas) }}</span>
              <BtnExtratoIndicador
                :codperiodo="codperiodo"
                :codindicador="sub.codindicador"
                class="q-ml-xs"
              />
            </div>
            <span v-else class="text-grey">—</span>
          </template>

          <!-- META: valor + editar -->
          <template #celula-meta="{ sub }">
            <div v-if="sub" class="row items-center justify-end no-wrap">
              <span>{{ sub.meta ? formataNumero(sub.meta) : '—' }}</span>
              <q-btn
                v-if="podeEditar && periodoStatus === 'A'"
                flat
                round
                dense
                size="sm"
                color="grey-7"
                icon="edit"
                class="q-ml-xs"
                @click.stop="editarMeta(sub)"
              >
                <q-tooltip>Editar Meta</q-tooltip>
              </q-btn>
            </div>
            <span v-else class="text-grey">—</span>
          </template>

          <template #celula-status="{ linha }">
            <q-badge
              :color="statusColaboradorColor(linha.status)"
              :label="statusColaboradorLabel(linha.status)"
            />
          </template>
        </MgTabelaValores>
      </q-card>

      <!-- TOTAL DA FILIAL — espelha a linha desta filial no Resumão -->
      <q-card bordered flat class="q-mb-md" v-if="rodapeFilial.length && setores.length">
        <MgTabelaValores
          :colunas="colunas"
          :linhas="[]"
          :rodape="rodapeFilial"
          :cabecalho="false"
        >
          <template #rodape-indicador="{ valor }">
            <div class="row items-center justify-end no-wrap">
              <span>{{ valor != null ? formataNumero(valor) : '—' }}</span>
              <BtnExtratoIndicador
                v-if="dados?.codindicador"
                :codperiodo="codperiodo"
                :codindicador="dados.codindicador"
                class="q-ml-xs"
              />
            </div>
          </template>
        </MgTabelaValores>
      </q-card>

      <MgEmptyState v-if="setores.length === 0">
        Nenhum setor cadastrado nesta unidade.
      </MgEmptyState>
    </template>

    <!-- DIALOGS -->
    <DialogUnidade v-model="dialogUnidade" :unidade="unidade" @salvo="onSalvoUnidade" />
    <DialogSetor
      v-model="dialogSetor"
      :setor="setorEdit"
      :codunidadenegocio="codunidade"
      :codperiodo="codperiodo"
      @salvo="onSalvoSetor()"
    />
    <DialogAdicionarColaboradores
      v-model="dialogAddColab"
      :codperiodo="codperiodo"
      :codsetor="addColabSetor?.codsetor"
      :setorNome="addColabSetor?.descricao"
      @salvo="carregar()"
    />
    <DialogEditarMeta v-model="dialogMeta" :indicador="indicadorMeta" @salvo="carregar()" />
  </div>
</template>
