<script setup>
// O portador e o período (doc-4): o cabeçalho do portador (cadastro, usuários, extrato do banco)
// e o movimento em abas Ano → Mês → Período, só com o que existe; cada período mostra situação
// (aberto, pendente, fechado), resumo por origem e os lançamentos com o saldo corrente (com os
// botões de ajuste e transferência no cabeçalho). A URL leva direto ao período.
// Só abre para operador ou gestor do portador.
import { computed, watch, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useQuasar } from 'quasar'
import { storeToRefs } from 'pinia'
import MgEmptyState from '@components/MgEmptyState.vue'
import MgInfoCriacao from '@components/MgInfoCriacao.vue'
import Periodo from '@components/portador/Periodo.vue'
import { formataNumero, formataDataAbreviada } from '@components/formatters'
import { periodoStore } from '@components/stores/periodoStore'
import PortadorDialog from 'components/portador/PortadorDialog.vue'
import PortadorUsuariosDialog from 'components/portador/PortadorUsuariosDialog.vue'
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

// anos, meses e períodos do mais novo para o mais antigo
const anos = computed(() =>
  [...new Set(periodos.value.map(ano))].reverse().map((a) => ({
    ano: a,
    to: destino(periodos.value.filter((p) => ano(p) === a)),
  })),
)
const doAno = computed(() =>
  periodo.value ? periodos.value.filter((p) => ano(p) === ano(periodo.value)) : [],
)
const meses = computed(() =>
  [...new Set(doAno.value.map(mes))].reverse().map((m) => ({
    mes: m,
    to: destino(doAno.value.filter((p) => mes(p) === m)),
  })),
)
const doMes = computed(() =>
  periodo.value ? doAno.value.filter((p) => mes(p) === mes(periodo.value)).reverse() : [],
)

// a aba mostra só a data final (15/jul/2026); sem fim, "aberto"
const rotulo = (p) => (p.fim ? formataDataAbreviada(p.fim, 4) : 'aberto')
const BADGE = { aberto: ['green-7', 'Aberto'], pendente: ['amber-8', 'Pendente'] }
// espécie com período aberto (badge no cabeçalho)
const caixaAberto = computed(
  () => !!portador.value?.ehCaixa && periodos.value.some((p) => p.situacao === 'aberto'),
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

// ---- abrir novo período (espécie; só um aberto por portador): só confirma; o início quem decide
// é o servidor ----
const podeAbrir = computed(
  () => !!portador.value?.ehCaixa && !periodos.value.some((p) => p.situacao === 'aberto'),
)

function abrirPeriodo() {
  $q.dialog({
    title: 'Abrir novo período',
    message: `Abrir um novo período em ${portador.value.portador}?`,
    cancel: { label: 'Não', color: 'grey-8', flat: true },
    ok: { label: 'Sim', color: 'primary', flat: true },
  }).onOk(async () => {
    const cod = await store.abrir()
    if (cod) router.push(para({ codportadorperiodo: cod }))
  })
}
</script>

<template>
  <q-page>
    <div class="q-pa-md" style="max-width: 1086px; margin: auto">
      <template v-if="portador">
        <!-- portador: voltar, nome e ações, como nas outras telas de detalhe; no celular o saldo e
             as ações descem para a linha de baixo -->
        <div class="row items-center q-col-gutter-x-sm q-mb-sm">
          <div class="col-auto">
            <q-btn flat round icon="arrow_back" color="grey-7" :to="{ name: 'portador' }" />
          </div>
          <div class="col" style="min-width: 0">
            <div
              class="text-h5 ellipsis"
              :class="portador.inativo ? 'text-strike text-grey-6' : 'text-grey-9'"
            >
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
          <div class="col-12 col-sm-auto">
            <div class="row items-center no-wrap">
              <div v-if="portador.tipo === 'E'" class="text-h6 text-grey-9 q-mr-sm">
                R$ {{ formataNumero(portador.saldo) }}
              </div>
              <div v-else class="text-caption text-grey-6 q-mr-sm">saldo após a conciliação</div>
              <q-space />
              <q-btn
                v-if="portador.tipo === 'B'"
                flat
                no-caps
                color="primary"
                icon="receipt_long"
                :label="$q.screen.gt.xs ? 'Extrato do banco' : undefined"
                :to="extrato"
              >
                <q-tooltip v-if="$q.screen.xs">Extrato do banco</q-tooltip>
              </q-btn>
              <q-btn
                v-if="pode.usuarios"
                flat
                round
                size="sm"
                color="grey-7"
                icon="admin_panel_settings"
                @click="store.dialogUsuarios = true"
              >
                <q-tooltip>Usuários e papéis</q-tooltip>
              </q-btn>
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
          </div>
        </div>

        <q-card v-if="(periodos.length && periodo) || podeAbrir" flat bordered class="q-mb-md">
          <!-- abas: ano → mês → período -->
          <template v-if="periodos.length && periodo">
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
              <!-- sem período aberto, o novo vem antes de todos, no formato de uma aba -->
              <div
                v-if="podeAbrir"
                v-ripple
                role="button"
                tabindex="0"
                class="q-tab q-tab--no-caps relative-position self-stretch flex flex-center text-center cursor-pointer text-primary"
                @click="abrirPeriodo"
                @keyup.enter="abrirPeriodo"
              >
                <div class="column items-center q-py-xs">
                  <q-icon name="add" size="20px" />
                  <div class="text-weight-medium">Novo período</div>
                  <div class="text-caption text-grey-6">abrir</div>
                </div>
              </div>
              <q-route-tab v-for="p in doMes" :key="p.codportadorperiodo" :to="para(p)" exact>
                <div class="column items-center q-py-xs">
                  <div class="text-weight-medium">{{ rotulo(p) }}</div>
                  <div class="text-caption">R$ {{ formataNumero(p.saldofinal) }}</div>
                  <q-badge
                    v-if="BADGE[p.situacao]"
                    :color="BADGE[p.situacao][0]"
                    :label="BADGE[p.situacao][1]"
                  />
                  <div v-else class="text-caption text-grey-6">fechado</div>
                </div>
              </q-route-tab>
            </q-tabs>
          </template>
          <!-- espécie ainda sem período -->
          <template v-else>
            <div class="row justify-end q-pa-sm">
              <q-btn
                flat
                no-caps
                color="primary"
                icon="add"
                label="Abrir novo período"
                @click="abrirPeriodo"
              />
            </div>
          </template>
        </q-card>

        <Periodo>
          <MgEmptyState v-if="!periodos.length && !carregando" icon="receipt_long">
            Nenhum movimento neste portador.
          </MgEmptyState>
        </Periodo>
      </template>
    </div>

    <q-inner-loading :showing="carregando" color="primary" />

    <PortadorUsuariosDialog />
    <PortadorDialog />
  </q-page>
</template>
