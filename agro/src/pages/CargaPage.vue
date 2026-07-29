<script setup>
import { ref, computed, onMounted } from 'vue'
import { useQuasar } from 'quasar'
import { storeToRefs } from 'pinia'
import { useCargaStore } from 'src/stores/carga'
import { useSincronizacaoStore } from 'src/stores/sincronizacao'
import { sacas } from 'src/utils/desconto'
import { agoraLocal } from 'src/utils/carga'
import CargaDialog from 'components/CargaDialog.vue'
import MgInputData from '@components/MgInputData.vue'

const $q = useQuasar()
const store = useCargaStore()
const sinc = useSincronizacaoStore()

const {
  safras,
  codsafraAtiva,
  sentidoAtivo,
  dataFiltro,
  etapasDoSentido,
  cargasPorEtapa,
  culturaAtiva,
} = storeToRefs(store)
const { online, sincronizando } = storeToRefs(sinc)

const hojeIso = agoraLocal().slice(0, 10)

// Metadados de cada etapa (todas as etapas possíveis; o board mostra as do sentido).
const ETAPA_META = {
  PBT: { label: 'Peso Bruto', icon: 'scale', color: 'orange-8' },
  TARA: { label: 'Tara', icon: 'monitor_weight', color: 'teal-7' },
  CLASSIFICACAO: { label: 'Classificação', icon: 'science', color: 'deep-purple-6' },
  FISCAL: { label: 'Nota Fiscal', icon: 'receipt_long', color: 'deep-orange-7' },
  FINALIZADO: { label: 'Finalizado', icon: 'task_alt', color: 'green-7' },
}

const colunas = computed(() => etapasDoSentido.value.map((e) => ({ etapa: e, ...ETAPA_META[e] })))

const finalizadosOcultos = ref(true)
function listaVisivel(etapa) {
  return !(etapa === 'FINALIZADO' && finalizadosOcultos.value)
}

const voltarSafra = computed(() =>
  codsafraAtiva.value ? { name: 'safra-detalhe', params: { codsafra: codsafraAtiva.value } } : null,
)

const dialog = ref(false)
const cargaSel = ref(null)
const novo = ref(false)

function abrir(carga) {
  cargaSel.value = JSON.parse(JSON.stringify(carga))
  novo.value = false
  dialog.value = true
}
function novaCarga() {
  // Sem safra ativa (ex.: cold start offline sem cache) a carga nasceria com
  // codsafra:null — invisível no board e rejeitada pra sempre no sync. Barra antes.
  if (!codsafraAtiva.value) {
    $q.notify({ type: 'warning', message: 'Sincronize ao menos uma safra antes de registrar cargas.' })
    return
  }
  cargaSel.value = store.nova()
  novo.value = true
  dialog.value = true
}
async function onSalvar(carga) {
  await store.salvar(carga)
  dialog.value = false
}
async function onAvancar(carga) {
  await store.salvar(carga)
}
async function onCancelar(carga) {
  // Já no servidor → inativa (estorna); pendente local → descarta do Dexie.
  if (carga.codcarga || carga.sincronizado) await store.inativar(carga)
  else await store.descartarPendente(carga)
  dialog.value = false
}

const pesosaca = computed(() => culturaAtiva.value?.pesosaca || 60)

// Totais do FINALIZADO — já do dia filtrado (cargasPorEtapa recorta a data).
const totaisFinalizado = computed(() => {
  let bruto = 0
  let desconto = 0
  let liquido = 0
  for (const c of cargasPorEtapa.value.FINALIZADO || []) {
    bruto += Number(c.bruto) || 0
    desconto += Number(c.desconto) || 0
    liquido += Number(c.liquido) || 0
  }
  return {
    bruto,
    desconto,
    liquido,
    sacas: sacas(liquido, pesosaca.value) || 0,
    pct: bruto > 0 ? (desconto / bruto) * 100 : 0,
  }
})

function indiceEtapa(etapa) {
  return etapasDoSentido.value.indexOf(etapa)
}
function corSegmento(carga, i) {
  return i <= indiceEtapa(carga.etapa) ? ETAPA_META[carga.etapa]?.color || 'grey-5' : 'grey-3'
}

function fmt(v, dec = 0) {
  if (v === null || v === undefined || v === '') return '—'
  return Number(v).toLocaleString('pt-BR', {
    minimumFractionDigits: dec,
    maximumFractionDigits: dec,
  })
}

function pontosResumo(carga) {
  const origem = (carga.pontos || []).filter((p) => p.papel === 'ORIGEM')
  const destino = (carga.pontos || []).filter((p) => p.papel === 'DESTINO')
  const lista = (carga.sentido === 'SAIDA' ? destino : origem).map((p) => p.rotulo).filter(Boolean)
  return lista.length ? lista.join(' · ') : 'Sem origem/destino'
}

// Siglas dos parâmetros conhecidos (o catálogo não tem coluna de sigla); demais
// caem no fallback dos 4 primeiros caracteres.
const ABREV = {
  Impureza: 'Imp',
  Umidade: 'Umid',
  Avariados: 'Avar',
  Esverdeados: 'Esv',
  Quebrados: 'Queb',
}
function abreviar(nome) {
  return !nome ? '?' : ABREV[nome] || nome.slice(0, 4)
}

// Nome do parâmetro offline-safe: item da tabela resolvida → nested do server
// (só cargas puxadas) → catálogo em cache → código. Cargas locais só têm o cod.
function nomeParametro(row, porCod) {
  return (
    porCod.get(row.codparametroclassificacao)?.parametroclassificacao ||
    row.ParametroClassificacao?.parametroclassificacao ||
    store.parametros.find((p) => p.codparametroclassificacao === row.codparametroclassificacao)
      ?.parametroclassificacao ||
    `#${row.codparametroclassificacao}`
  )
}

// Chips de classificação: só leituras preenchidas; `fora` = leitura acima da
// tolerância (gera desconto). Vale p/ FATOR e NORMALIZADO — mesma condição de
// percentualItem em utils/desconto.js.
function chipsClassificacao(carga) {
  const itens = store.itensResolvidos(store.resolverCodTabela(carga))
  const porCod = new Map(itens.map((i) => [i.codparametroclassificacao, i]))
  return (carga.classificacao || [])
    .filter((c) => c.leitura !== null && c.leitura !== undefined && c.leitura !== '')
    .map((c) => {
      const item = porCod.get(c.codparametroclassificacao)
      return {
        key: c.codparametroclassificacao,
        label: abreviar(nomeParametro(c, porCod)),
        leitura: c.leitura,
        // Só marca "fora" quando há item resolvido (senão tolerância viraria 0 e
        // todo parâmetro inativo/sem catálogo apareceria falsamente vermelho).
        fora: item ? Number(c.leitura) > (Number(item.tolerancia) || 0) : false,
      }
    })
}

// Aviso (só ENTRADA, onde a classificação vale): sem tabela resolvida (desconto
// ficaria 0 em silêncio) ou tabela divergente da do contrato.
function avisoTabela(carga) {
  if (carga.sentido !== 'ENTRADA') return null
  const resolvida = store.resolverCodTabela(carga)
  if (!resolvida) return 'Sem tabela de classificação — desconto não aplicado'
  const doContrato = store.codTabelaContrato(carga)
  if (doContrato && doContrato !== resolvida) return 'Tabela diferente da do contrato'
  return null
}

function hora(iso) {
  return !iso ? '' : new Date(iso).toLocaleTimeString('pt-BR', { hour: '2-digit', minute: '2-digit' })
}

function pageStyleFn(offset) {
  return { minHeight: `calc(100vh - ${offset || 80}px)` }
}

onMounted(async () => {
  await store.carregarReferencias()
  await store.carregarCargas()
  store.sincronizar().catch(() => {})
})
</script>

<template>
  <q-page class="column bg-white" :style-fn="pageStyleFn">
    <!-- Barra superior: safra + tipo de romaneio + status.
         Grid (col-6 col-sm) em vez de no-wrap: em xs os dois selects quebram
         pra segunda linha, senão "Transferência" trunca. -->
    <div class="q-px-md q-pt-md q-pb-sm">
      <div class="row items-center q-col-gutter-sm">
        <div class="col-auto">
          <q-btn
            flat
            round
            icon="arrow_back"
            color="grey-7"
            :to="voltarSafra"
            :disable="!voltarSafra"
          >
            <q-tooltip>Voltar para a safra</q-tooltip>
          </q-btn>
        </div>

        <div class="col-6 col-sm">
          <q-select
            :model-value="codsafraAtiva"
            :options="safras"
            option-value="codsafra"
            option-label="safra"
            emit-value
            map-options
            outlined
            label="Safra"
            @update:model-value="store.definirSafra"
          />
        </div>

        <div class="col-6 col-sm">
          <q-select
            :model-value="sentidoAtivo"
            :options="store.SENTIDOS"
            option-value="value"
            option-label="label"
            emit-value
            map-options
            outlined
            label="Tipo de Romaneio"
            @update:model-value="store.definirSentido"
          />
        </div>

        <div class="col-6 col-sm">
          <MgInputData
            :model-value="dataFiltro"
            type="date"
            label="Data"
            :max="hojeIso"
            @update:model-value="store.definirData"
          />
        </div>

        <div class="col-auto">
          <q-chip
            :color="online ? 'green-1' : 'orange-1'"
            :text-color="online ? 'green-9' : 'orange-9'"
            :icon="online ? 'cloud_done' : 'cloud_off'"
          >
            <span class="gt-xs q-ml-xs">{{ online ? 'Online' : 'Offline' }}</span>
          </q-chip>
        </div>

        <div class="col-auto">
          <q-btn
            flat
            round
            icon="sync"
            :loading="sincronizando"
            color="grey-7"
            @click="store.sincronizar({ force: true })"
          >
            <q-tooltip>Sincronizar</q-tooltip>
          </q-btn>
        </div>
      </div>
    </div>

    <!-- Kanban horizontal: uma coluna por etapa do sentido -->
    <div class="row no-wrap q-gutter-md q-pa-md col" style="overflow-x: auto">
      <q-card
        v-for="e in colunas"
        :key="e.etapa"
        flat
        bordered
        class="overflow-hidden col column"
        style="min-width: 260px"
      >
        <q-item
          :class="`bg-${e.color} text-white`"
          class="rounded-borders"
          :clickable="e.etapa === 'FINALIZADO'"
          @click="e.etapa === 'FINALIZADO' && (finalizadosOcultos = !finalizadosOcultos)"
        >
          <q-item-section avatar><q-icon :name="e.icon" /></q-item-section>
          <q-item-section>
            <q-item-label class="text-weight-medium">{{ e.label }}</q-item-label>
          </q-item-section>
          <q-item-section side>
            <div class="row items-center no-wrap q-gutter-x-sm">
              <q-badge
                color="white"
                :text-color="e.color"
                :label="(cargasPorEtapa[e.etapa] || []).length"
              />
              <q-icon
                v-if="e.etapa === 'FINALIZADO'"
                :name="finalizadosOcultos ? 'expand_more' : 'expand_less'"
                size="sm"
                color="white"
              />
            </div>
          </q-item-section>
        </q-item>

        <div class="q-px-sm q-pb-sm col scroll">
          <q-banner
            v-if="e.etapa === 'FINALIZADO' && (cargasPorEtapa.FINALIZADO || []).length"
            rounded
            class="bg-green-1 text-green-10 q-mt-sm"
          >
            <template #avatar><q-icon name="agriculture" color="green-8" /></template>
            <div class="text-weight-medium">Total líquido</div>
            {{ fmt(totaisFinalizado.liquido) }} kg · {{ fmt(totaisFinalizado.sacas, 1) }} sacas
            <div class="text-caption">
              Desconto {{ fmt(totaisFinalizado.desconto) }} kg ({{ fmt(totaisFinalizado.pct, 1) }}%)
            </div>
          </q-banner>

          <div v-show="listaVisivel(e.etapa)">
            <q-card
              v-for="carga in cargasPorEtapa[e.etapa] || []"
              :key="carga.uuid"
              flat
              bordered
              class="cursor-pointer q-mt-sm"
              @click="abrir(carga)"
            >
              <q-card-section class="q-pa-sm">
                <!-- placa (+carreta) / origem→destino | avisos + sync + nº/hora -->
                <div class="row items-start no-wrap q-gutter-x-xs">
                  <div class="col">
                    <div class="row items-baseline no-wrap q-gutter-x-xs">
                      <span class="text-subtitle1 text-weight-bold">
                        {{ carga.placa || 'Sem placa' }}
                      </span>
                      <span v-if="carga.placacarreta" class="text-caption text-grey-6">
                        {{ carga.placacarreta }}
                      </span>
                    </div>
                    <div class="text-caption text-grey-7 ellipsis">
                      <q-icon
                        v-if="carga.sentido === 'SAIDA'"
                        name="east"
                        size="14px"
                        class="q-mr-xs"
                      />
                      {{ pontosResumo(carga) }}
                    </div>
                  </div>
                  <div class="column items-end">
                    <div class="row items-center no-wrap q-gutter-x-xs">
                      <q-icon
                        v-if="avisoTabela(carga)"
                        name="warning"
                        color="orange-7"
                        size="18px"
                      >
                        <q-tooltip>{{ avisoTabela(carga) }}</q-tooltip>
                      </q-icon>
                      <q-icon v-if="carga.syncerro" name="sync_problem" color="red-6" size="18px">
                        <q-tooltip>{{ carga.syncerro }}</q-tooltip>
                      </q-icon>
                      <q-icon
                        :name="carga.sincronizado ? 'cloud_done' : 'cloud_off'"
                        :color="carga.sincronizado ? 'green-5' : 'orange-6'"
                        size="18px"
                      >
                        <q-tooltip>
                          {{ carga.sincronizado ? 'Sincronizado' : 'Pendente' }}
                        </q-tooltip>
                      </q-icon>
                    </div>
                    <div class="text-caption text-grey-6 no-wrap">
                      <span v-if="carga.codcarga">#{{ carga.codcarga }} · </span>{{ hora(carga.data) }}
                    </div>
                  </div>
                </div>

                <!-- barra de progresso (mantém o height inline já existente) -->
                <div class="row no-wrap q-gutter-xs q-mt-sm">
                  <div
                    v-for="(s, i) in colunas"
                    :key="s.etapa"
                    class="col"
                    :class="`bg-${corSegmento(carga, i)}`"
                    style="height: 6px; border-radius: 3px"
                  />
                </div>

                <!-- chips de classificação — só quando há leituras; quebram linha -->
                <div
                  v-if="chipsClassificacao(carga).length"
                  class="row items-center q-gutter-xs q-mt-sm"
                >
                  <q-chip
                    v-for="chip in chipsClassificacao(carga)"
                    :key="chip.key"
                    dense
                    square
                    size="sm"
                    :color="chip.fora ? 'orange-2' : 'blue-grey-1'"
                    :text-color="chip.fora ? 'orange-10' : 'blue-grey-8'"
                  >
                    {{ chip.label }} {{ fmt(chip.leitura, 1) }}%
                  </q-chip>
                </div>

                <!-- métricas adaptadas à etapa + desconto total -->
                <div class="row items-center justify-between q-mt-sm no-wrap">
                  <div class="text-body2 text-grey-9">
                    <template v-if="carga.liquido != null">
                      <span class="text-weight-medium text-green-9">
                        {{ fmt(carga.liquido) }} kg
                      </span>
                      <span class="text-grey-7">
                        · {{ fmt(sacas(carga.liquido, pesosaca), 1) }} sc</span
                      >
                    </template>
                    <template v-else-if="carga.bruto != null">Bruto {{ fmt(carga.bruto) }} kg</template>
                    <template v-else-if="carga.pbt != null">PBT {{ fmt(carga.pbt) }} kg</template>
                    <template v-else>{{ carga.motorista || 'Aguardando pesagem' }}</template>
                  </div>
                  <q-badge
                    v-if="carga.desconto"
                    color="orange-1"
                    text-color="orange-9"
                    :label="`− ${fmt(carga.desconto)} kg`"
                  />
                </div>
              </q-card-section>
            </q-card>

            <div
              v-if="!(cargasPorEtapa[e.etapa] || []).length"
              class="text-grey-5 text-center q-pa-md"
            >
              Nenhum caminhão nesta etapa
            </div>
          </div>
        </div>
      </q-card>
    </div>

    <q-page-sticky position="bottom-right" :offset="[18, 18]">
      <q-btn
        fab
        icon="add"
        color="primary"
        label="Carga"
        :disable="!codsafraAtiva"
        @click="novaCarga"
      >
        <q-tooltip v-if="!codsafraAtiva">Sincronize uma safra antes de registrar cargas</q-tooltip>
      </q-btn>
    </q-page-sticky>

    <CargaDialog
      v-model="dialog"
      :carga="cargaSel"
      :novo="novo"
      @salvar="onSalvar"
      @avancar="onAvancar"
      @cancelar="onCancelar"
    />
  </q-page>
</template>
