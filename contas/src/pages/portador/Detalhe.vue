<script setup>
// O portador e o período (doc-4): o cabeçalho do portador (cadastro, extrato do banco) e o razão
// em abas Ano → Mês → Período, só com o que existe; cada período mostra situação, resumo por
// origem e os lançamentos com o saldo corrente. O FAB cria movimento: transferir, avulso e, na
// gaveta, item do caixa. A URL leva direto ao período.
import { computed, watch, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useQuasar } from 'quasar'
import { storeToRefs } from 'pinia'
import MgEmptyState from '@components/MgEmptyState.vue'
import MgInfoCriacao from '@components/MgInfoCriacao.vue'
import TransferirCaixaDialog from '@components/caixa/TransferirCaixaDialog.vue'
import AvulsoCaixaDialog from '@components/caixa/AvulsoCaixaDialog.vue'
import ItemCaixaDialog from '@components/caixa/ItemCaixaDialog.vue'
import { formataNumero, formataData, formataTimestamp } from '@components/formatters'
import { periodoStore } from '@components/stores/periodoStore'
import PortadorDialog from 'components/portador/PortadorDialog.vue'
import PeriodoCabecalho from 'components/portador/PeriodoCabecalho.vue'
import PeriodoResumo from 'components/portador/PeriodoResumo.vue'
import PeriodoLancamentos from 'components/portador/PeriodoLancamentos.vue'
import { usePortadorStore } from 'src/stores/portadorStore'
import { portadorTipoLabel, portadorTipoColor } from 'src/constants/portadorTipo'

const MESES = ['jan', 'fev', 'mar', 'abr', 'mai', 'jun', 'jul', 'ago', 'set', 'out', 'nov', 'dez']

const route = useRoute()
const router = useRouter()
const $q = useQuasar()
const store = periodoStore()
const sPortador = usePortadorStore()
const { portador, pode, periodos, periodo, carregando } = storeToRefs(store)

const codportador = computed(() => Number(route.params.codportador))
const codperiodo = computed(() =>
  route.params.codportadorperiodo ? Number(route.params.codportadorperiodo) : null,
)

// ---- abas: o período fica no mês do início ----
const ano = (p) => new Date(p.inicio).getFullYear()
const mes = (p) => new Date(p.inicio).getMonth()
const para = (p) => ({
  name: 'portador-detalhe',
  params: { codportador: codportador.value, codportadorperiodo: p.codportadorperiodo },
})
// a aba de ano ou mês leva ao período da tela, se estiver nela; senão, ao último dela
const destino = (lista) =>
  para(
    lista.find((p) => p.codportadorperiodo === periodo.value?.codportadorperiodo) ??
      lista[lista.length - 1],
  )

const anos = computed(() =>
  [...new Set(periodos.value.map(ano))].map((a) => ({
    ano: a,
    to: destino(periodos.value.filter((p) => ano(p) === a)),
  })),
)
const doAno = computed(() =>
  periodo.value ? periodos.value.filter((p) => ano(p) === ano(periodo.value)) : [],
)
const meses = computed(() =>
  [...new Set(doAno.value.map(mes))].map((m) => ({
    mes: m,
    to: destino(doAno.value.filter((p) => mes(p) === m)),
  })),
)
const doMes = computed(() =>
  periodo.value ? doAno.value.filter((p) => mes(p) === mes(periodo.value)) : [],
)

const rotulo = (p) => {
  if (portador.value?.ehGaveta) return formataTimestamp(p.inicio, 0)
  return formataData(p.inicio, 0) + (p.fim ? ` a ${formataData(p.fim, 0)}` : ' →')
}
const situacaoCurta = (p) =>
  p.aberto ? (p.fim && !portador.value?.ehGaveta ? 'Reaberto' : 'Aberto') : 'fechado'
// gaveta com a sessão aberta (badge no cabeçalho)
const caixaAberto = computed(
  () => !!portador.value?.ehGaveta && periodos.value.some((p) => p.aberto),
)

// ---- carregar: sem período na URL, o servidor manda o último (o watch abaixo leva a URL) ----
async function carregar() {
  await store.carregar(codportador.value, codperiodo.value)
}

watch([codportador, codperiodo], ([cod, per]) => {
  if (cod === portador.value?.codportador && per && per === periodo.value?.codportadorperiodo)
    return
  carregar()
})
onMounted(carregar)

// sem período na URL (ou o primeiro movimento de um portador sem período): a URL vai para o da tela
watch(
  () => periodo.value?.codportadorperiodo,
  (id) => {
    if (id && !codperiodo.value) router.replace(para(periodo.value))
  },
)

// ---- cadastro ----
const extrato = computed(() => {
  const hoje = new Date()
  return {
    name: 'extrato',
    params: {
      codportador: codportador.value,
      ano: String(hoje.getFullYear()),
      mes: String(hoje.getMonth() + 1).padStart(2, '0'),
    },
  }
})

function excluir() {
  $q.dialog({
    title: 'Excluir',
    message: `Excluir o portador "${portador.value.portador}"? Com movimento, só inativando.`,
    ok: { label: 'Excluir', color: 'red-5', flat: true },
    cancel: { label: 'Cancelar', color: 'grey-8', flat: true },
  }).onOk(async () => {
    if (await sPortador.excluir(portador.value)) router.push({ name: 'portador' })
  })
}

// ---- FAB: na gaveta só com o caixa aberto ----
const podeMovimentar = computed(() => !portador.value?.ehGaveta || !!periodo.value?.aberto)
</script>

<template>
  <q-page>
    <div class="q-pa-md" style="max-width: 1086px; margin: auto">
      <q-btn
        flat
        round
        icon="arrow_back"
        :to="{ name: 'portador' }"
        aria-label="Voltar"
        class="q-mb-sm"
      />

      <template v-if="portador">
        <!-- portador -->
        <q-card flat bordered class="q-mb-md">
          <q-card-section class="row items-center q-col-gutter-sm">
            <div class="col-12 col-sm">
              <div class="text-h6" :class="portador.inativo ? 'text-strike text-grey-6' : ''">
                {{ portador.portador }}
                <q-badge
                  v-if="caixaAberto"
                  color="green-7"
                  class="q-ml-sm text-body2"
                  label="Aberto"
                />
              </div>
              <div class="text-caption text-grey-7">
                <q-badge
                  :color="portadorTipoColor(portador.tipo)"
                  :label="portador.ehGaveta ? 'Gaveta' : portadorTipoLabel(portador.tipo)"
                  class="q-mr-xs"
                />
                {{ portador.filial ?? 'Sem filial' }}
                <template v-if="portador.banco"> · {{ portador.banco }}</template>
                <template v-if="portador.conta">
                  · ag {{ portador.agencia }} cc {{ portador.conta }}-{{ portador.contadigito }}
                </template>
                <template v-if="portador.inativo"> · inativo</template>
              </div>
            </div>
            <div class="col-12 col-sm-auto text-right">
              <div v-if="portador.tipo === 'E'" class="text-h6">
                R$ {{ formataNumero(portador.saldo) }}
              </div>
              <div v-else class="text-caption text-grey-6">saldo após a conciliação</div>
            </div>
            <div class="col-12 col-sm-auto row items-center no-wrap justify-end">
              <q-btn
                v-if="portador.tipo === 'B'"
                flat
                no-caps
                color="primary"
                icon="receipt_long"
                label="Extrato do banco"
                :to="extrato"
              />
              <template v-if="pode.cadastro">
                <MgInfoCriacao :registro="portador" />
                <q-btn
                  flat
                  round
                  size="sm"
                  color="grey-7"
                  icon="edit"
                  @click="sPortador.editar(portador)"
                >
                  <q-tooltip>Editar</q-tooltip>
                </q-btn>
                <q-btn
                  flat
                  round
                  size="sm"
                  color="grey-7"
                  :icon="portador.inativo ? 'play_arrow' : 'pause'"
                  @click="sPortador.alternarInativo(portador)"
                >
                  <q-tooltip>{{ portador.inativo ? 'Reativar' : 'Inativar' }}</q-tooltip>
                </q-btn>
                <q-btn flat round size="sm" color="grey-7" icon="delete" @click="excluir">
                  <q-tooltip>Excluir (só sem movimento)</q-tooltip>
                </q-btn>
              </template>
            </div>
          </q-card-section>

          <!-- abas: ano → mês → período -->
          <template v-if="periodos.length && periodo">
            <q-separator />
            <q-tabs
              align="left"
              outside-arrows
              mobile-arrows
              no-caps
              active-color="primary"
              indicator-color="primary"
              class="text-grey-8"
            >
              <q-route-tab v-for="a in anos" :key="a.ano" :to="a.to" exact :label="String(a.ano)" />
            </q-tabs>
            <q-separator />
            <q-tabs
              align="left"
              outside-arrows
              mobile-arrows
              no-caps
              active-color="primary"
              indicator-color="primary"
              class="text-grey-8"
            >
              <q-route-tab v-for="m in meses" :key="m.mes" :to="m.to" exact :label="MESES[m.mes]" />
            </q-tabs>
            <q-separator />
            <q-tabs
              align="left"
              outside-arrows
              mobile-arrows
              no-caps
              active-color="primary"
              indicator-color="primary"
              class="text-grey-8"
            >
              <q-route-tab v-for="p in doMes" :key="p.codportadorperiodo" :to="para(p)" exact>
                <div class="column items-center q-py-xs">
                  <div class="text-weight-medium">{{ rotulo(p) }}</div>
                  <div class="text-caption">R$ {{ formataNumero(p.saldofinal) }}</div>
                  <q-badge v-if="p.aberto" color="green-7" :label="situacaoCurta(p)" />
                  <div v-else class="text-caption text-grey-6">{{ situacaoCurta(p) }}</div>
                </div>
              </q-route-tab>
            </q-tabs>
          </template>
        </q-card>

        <PeriodoCabecalho />
        <PeriodoResumo />
        <PeriodoLancamentos />

        <MgEmptyState v-if="!periodos.length && !carregando" icon="receipt_long">
          Nenhum movimento neste portador desde o go-live.
        </MgEmptyState>
      </template>
    </div>

    <q-inner-loading :showing="carregando" color="primary" />

    <q-page-sticky
      v-if="portador && podeMovimentar && (pode.transferir || pode.avulso)"
      position="bottom-right"
      :offset="[18, 18]"
    >
      <div class="row q-gutter-sm items-end">
        <q-btn
          v-if="portador.ehGaveta && pode.caixa && periodo?.itens?.length"
          fab-mini
          color="grey-8"
          icon="inventory_2"
          @click="store.abrirItem()"
        >
          <q-tooltip anchor="top middle" self="bottom middle">Item do caixa</q-tooltip>
        </q-btn>
        <q-btn
          v-if="pode.avulso"
          fab-mini
          color="grey-8"
          icon="edit_note"
          @click="store.dialogAvulso = true"
        >
          <q-tooltip anchor="top middle" self="bottom middle">Lançamento avulso</q-tooltip>
        </q-btn>
        <q-btn
          v-if="pode.transferir"
          fab
          color="primary"
          icon="swap_horiz"
          @click="store.dialogTransferir = true"
        >
          <q-tooltip anchor="top middle" self="bottom middle">Transferir</q-tooltip>
        </q-btn>
      </div>
    </q-page-sticky>

    <TransferirCaixaDialog />
    <AvulsoCaixaDialog />
    <ItemCaixaDialog />
    <PortadorDialog />
  </q-page>
</template>
