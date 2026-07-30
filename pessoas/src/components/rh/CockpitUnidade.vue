<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import { useQuasar } from 'quasar'
import { useRouter } from 'vue-router'
import { rhStore } from 'src/stores/rh'
import {
  corProgresso,
  tipoIndicadorLabel,
  tipoIndicadorColor,
  extrairErro,
} from 'src/utils/rhFormatters'
import { formataNumero, formataPercentual } from '@components/formatters'
import { api } from 'boot/axios'
import { abrirPdf } from '@components/abrirPdf'
import MgInfoCriacao from '@components/MgInfoCriacao.vue'
import AcoesUnidade from 'src/components/rh/AcoesUnidade.vue'
import AcoesSetor from 'src/components/rh/AcoesSetor.vue'
import DialogUnidade from 'src/components/rh/DialogUnidade.vue'
import DialogSetor from 'src/components/rh/DialogSetor.vue'
import DialogAdicionarColaboradores from 'src/components/rh/DialogAdicionarColaboradores.vue'
import DialogEditarMeta from 'src/pages/rh/DialogEditarMeta.vue'

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
const router = useRouter()
const sRh = rhStore()

const loading = ref(false)
const dados = ref(null)

const setores = computed(() => dados.value?.setores || [])

const atingimentoInd = (ind) => {
  if (ind.atingimento != null) return parseFloat(ind.atingimento)
  const vendas = parseFloat(ind.valoracumulado ?? ind.vendas) || 0
  const meta = parseFloat(ind.meta) || 0
  if (!vendas || !meta) return null
  return (vendas / meta) * 100
}

// Cabeçalho do card mostra só o indicador COLETIVO do setor (tipo S).
// Os individuais (Vendedor/Caixa) aparecem na linha de cada colaborador.
const indicadoresColetivos = (setor) => (setor.indicadores || []).filter((i) => i.tipo === 'S')

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

// Linha inteira leva pro detalhe (tela de rubricas). As ações de
// encerrar/estornar/recalcular ficam só lá.
const irParaDetalhe = (c) => router.push(colaboradorTo(c))

onMounted(() => carregar())

watch(
  () => props.codunidade,
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
                <q-badge
                  :color="tipoIndicadorColor(ind.tipo)"
                  :label="tipoIndicadorLabel(ind.tipo)"
                  class="q-mr-xs"
                />
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
        <q-markup-table
          flat
          separator="horizontal"
          style="table-layout: fixed"
          v-if="(setor.colaboradores || []).length > 0"
        >
          <colgroup>
            <col style="width: 30%" />
            <col style="width: 10%" />
            <col style="width: 19%" />
            <col style="width: 19%" />
            <col style="width: 11%" />
            <col style="width: 11%" />
          </colgroup>
          <thead>
            <tr>
              <th class="text-left">Nome</th>
              <th class="text-left">Tipo</th>
              <th class="text-right">Indicador</th>
              <th class="text-right">Meta</th>
              <th class="text-right">Variáveis</th>
              <th class="text-center">Status</th>
            </tr>
          </thead>
          <tbody>
            <tr
              v-for="c in setor.colaboradores"
              :key="c.codperiodocolaborador"
              class="cursor-pointer"
              @click="irParaDetalhe(c)"
            >
              <td>
                <div class="text-primary">{{ c.nome }}</div>
                <div class="text-caption text-grey" v-if="c.cargo">{{ c.cargo }}</div>
              </td>
              <!-- TIPO (chip por indicador) -->
              <td>
                <div
                  v-for="ind in c.indicadores || []"
                  :key="ind.codindicador"
                  class="row items-center q-mb-xs"
                  style="min-height: 32px"
                >
                  <q-badge
                    :color="tipoIndicadorColor(ind.tipo)"
                    :label="tipoIndicadorLabel(ind.tipo)"
                  />
                </div>
                <span v-if="!(c.indicadores || []).length" class="text-grey">—</span>
              </td>
              <!-- INDICADOR (valor + extrato) -->
              <td class="text-right">
                <div
                  v-for="ind in c.indicadores || []"
                  :key="ind.codindicador"
                  class="row items-center justify-end no-wrap q-mb-xs"
                  style="min-height: 32px"
                >
                  <span class="text-weight-medium">{{ formataNumero(ind.vendas) }}</span>
                  <q-btn
                    flat
                    round
                    size="sm"
                    color="primary"
                    icon="receipt_long"
                    class="q-ml-xs"
                    @click.stop
                    :to="{
                      name: 'rhIndicadorExtrato',
                      params: { codperiodo: codperiodo, codindicador: ind.codindicador },
                    }"
                  >
                    <q-tooltip>Ver Extrato</q-tooltip>
                  </q-btn>
                </div>
                <span v-if="!(c.indicadores || []).length" class="text-grey">—</span>
              </td>
              <!-- META (valor + % + editar meta) -->
              <td class="text-right">
                <div
                  v-for="ind in c.indicadores || []"
                  :key="ind.codindicador"
                  class="row items-center justify-end no-wrap q-mb-xs"
                  style="min-height: 32px"
                >
                  <template v-if="ind.meta">
                    {{ formataNumero(ind.meta) }}
                    <span
                      v-if="atingimentoInd(ind) != null"
                      class="text-weight-bold q-ml-xs"
                      :class="'text-' + corProgresso(atingimentoInd(ind))"
                    >
                      {{ formataPercentual(atingimentoInd(ind)) }}
                    </span>
                  </template>
                  <span v-else class="text-grey">—</span>
                  <q-btn
                    v-if="podeEditar && periodoStatus === 'A'"
                    flat
                    round
                    size="sm"
                    color="grey-7"
                    icon="edit"
                    class="q-ml-xs"
                    @click.stop="editarMeta(ind)"
                  >
                    <q-tooltip>Editar Meta</q-tooltip>
                  </q-btn>
                </div>
                <span v-if="!(c.indicadores || []).length" class="text-grey">—</span>
              </td>
              <td class="text-right text-weight-medium">{{ formataNumero(c.variaveis) }}</td>
              <td class="text-center">
                <q-badge
                  :color="c.status === 'A' ? 'green' : 'blue'"
                  :label="c.status === 'A' ? 'Aberto' : 'Encerrado'"
                />
              </td>
            </tr>
          </tbody>
        </q-markup-table>
        <div v-else class="q-pa-md text-center text-grey">Nenhum colaborador neste setor</div>
      </q-card>

      <div v-if="setores.length === 0" class="q-pa-md text-center text-grey">
        Nenhum setor cadastrado nesta unidade
      </div>
    </template>

    <!-- DIALOGS -->
    <DialogUnidade v-model="dialogUnidade" :unidade="unidade" @salvo="onSalvoUnidade" />
    <DialogSetor
      v-model="dialogSetor"
      :setor="setorEdit"
      :codunidadenegocio="codunidade"
      @salvo="carregar()"
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
