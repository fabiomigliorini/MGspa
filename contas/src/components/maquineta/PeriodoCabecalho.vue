<script setup>
// Cabeçalho do período da maquineta (TASK-188 M9.8), no padrão do cabeçalho do período do
// portador: a situação (aberto, pendente, conferido) e as ações, uma por botão. Não conferido:
// início e fim, dividir e unificar com o anterior; conferido: reabrir (volta a pendente). A
// conferência (quantidade e total do borderô) fica no resumo; a foto, na coluna ao lado.
import { ref, computed, watch } from 'vue'
import { useRouter } from 'vue-router'
import { useQuasar } from 'quasar'
import { storeToRefs } from 'pinia'
import MgInput from '@components/MgInput.vue'
import MgInputData from '@components/MgInputData.vue'
import {
  formataNumero,
  formataData,
  formataTimestamp,
  formataTimestampIso,
} from '@components/formatters'
import { useMaquinetaPeriodoStore } from 'src/stores/maquinetaPeriodoStore'
import { linhasDoPeriodo } from 'components/maquineta/linhas'

const router = useRouter()
const $q = useQuasar()
const store = useMaquinetaPeriodoStore()
const { maquineta, periodo, periodos, salvando } = storeToRefs(store)

const situacao = computed(() => periodo.value?.situacao)
const naoConferido = computed(() => !!periodo.value && situacao.value !== 'conferido')
const irPara = (cod) =>
  router.push({
    name: 'maquineta-detalhe',
    params: { codmaquineta: maquineta.value.codmaquineta, codmaquinetalote: cod },
  })

const BADGE = {
  aberto: { cor: 'green-7', label: 'Aberto' },
  pendente: { cor: 'amber-8', label: 'Pendente' },
  conferido: { cor: 'grey-7', label: 'Conferido' },
}

const linhas = computed(() => {
  const p = periodo.value
  const ret = [
    `De ${formataTimestamp(p.abertura)} até ${p.fim ? formataTimestamp(p.fim) : 'agora'}`,
  ]
  if (p.situacao === 'conferido' && p.usuariofechamento) {
    ret.push(`Conferido por ${p.usuariofechamento} em ${formataTimestamp(p.fechamento)}`)
  }
  return ret
})

// o relatório da maquininha é de um dia: período não conferido com mais de um dia não bate
const ms = (d) => new Date(String(d).replace(' ', 'T')).getTime()
const dias = computed(() => {
  const p = periodo.value
  const ini = new Date(ms(p.abertura))
  const fim = p.fim ? new Date(ms(p.fim)) : new Date()
  ini.setHours(0, 0, 0, 0)
  fim.setHours(0, 0, 0, 0)
  return Math.round((fim - ini) / 86400000) + 1
})
const avisoDias = computed(() =>
  naoConferido.value && dias.value > 1
    ? `Este período tem ${dias.value} dias (${formataData(periodo.value.abertura)} a ${formataData(periodo.value.fim ?? new Date())}); o relatório da maquininha é de um dia só. Divida na meia-noite.`
    : null,
)
const avisoPendente = computed(() =>
  situacao.value === 'pendente'
    ? 'O borderô não bate com o sistema: corrija os lançamentos (ou divida e unifique os períodos) ou o digitado, e confira de novo.'
    : null,
)

function reabrir() {
  $q.dialog({
    title: 'Reabrir',
    message: 'O período volta a pendente e aceita correções. Continuar?',
    cancel: { label: 'Cancelar', color: 'grey-8', flat: true },
    ok: { label: 'Reabrir', color: 'primary', flat: true },
  }).onOk(() => store.reabrir())
}

// ---- início e fim ----
const iso = (d) => formataTimestampIso(d ? new Date(d) : new Date())
const dialogDatas = ref(false)
const datas = ref({ inicio: null, fim: null, observacoes: null })

function prepararDatas() {
  const p = periodo.value
  datas.value = {
    inicio: iso(p.abertura),
    fim: p.fim ? iso(p.fim) : null,
    observacoes: p.observacoes,
  }
  dialogDatas.value = true
}

async function salvarDatas() {
  if (await store.editarDatas({ ...datas.value })) dialogDatas.value = false
}

// ---- dividir: o corte na régua do período (do início ao fim; aberto, até agora), com os
// lançamentos marcados; começa no meio do tempo. O campo de data e a régua andam juntos ----
const dialogDividir = ref(false)
const reguaInicio = ref(0)
const reguaFim = ref(0)
const corteMs = ref(0)
const corteDividir = ref(null)
const marcas = computed(() =>
  linhasDoPeriodo(periodo.value).map((x) => ({ value: ms(x.momento), label: '', x })),
)
const naRegua = (v) => {
  const t = ms(corteDividir.value || v)
  return (
    (t > reguaInicio.value && t < reguaFim.value) ||
    `Entre ${formataTimestamp(new Date(reguaInicio.value))} e ${formataTimestamp(new Date(reguaFim.value))}`
  )
}

watch(corteMs, (t) => {
  if (dialogDividir.value) corteDividir.value = formataTimestampIso(new Date(t))
})
watch(corteDividir, (v) => {
  const t = ms(v)
  if (!isNaN(t) && t > reguaInicio.value && t < reguaFim.value && t !== corteMs.value) {
    corteMs.value = t
  }
})

function prepararDividir() {
  const p = periodo.value
  reguaInicio.value = ms(p.abertura)
  reguaFim.value =
    p.fim && ms(p.fim) < Date.now() ? ms(p.fim) : Math.floor(Date.now() / 1000) * 1000
  corteMs.value = Math.round((reguaInicio.value + reguaFim.value) / 2000) * 1000
  corteDividir.value = formataTimestampIso(new Date(corteMs.value))
  dialogDividir.value = true
}

async function salvarDividir() {
  const cod = await store.dividir(corteDividir.value)
  if (!cod) return
  dialogDividir.value = false
  irPara(cod)
}

// ---- unificar com o anterior (os dois não conferidos) ----
const anterior = computed(() =>
  periodos.value.find((p) => p.codmaquinetalote === periodo.value?.anterior?.codmaquinetalote),
)
const podeUnificar = computed(
  () => naoConferido.value && !!anterior.value && anterior.value.situacao !== 'conferido',
)

function unificar() {
  $q.dialog({
    title: 'Unificar',
    message: `Juntar este período ao de ${formataTimestamp(anterior.value.abertura)}? Fica um só, com o fim deste e o borderô digitado deste (sem ele, o do anterior).`,
    cancel: { label: 'Cancelar', color: 'grey-8', flat: true },
    ok: { label: 'Unificar', color: 'primary', flat: true },
  }).onOk(async () => {
    const cod = await store.unificar()
    if (cod) irPara(cod)
  })
}
</script>

<template>
  <q-card flat bordered>
    <q-card-section class="row no-wrap items-start">
      <div class="col">
        <div class="text-subtitle1 text-weight-medium">
          Período da maquineta
          <q-badge class="q-ml-sm" :color="BADGE[situacao].cor" :label="BADGE[situacao].label" />
          <q-badge v-if="periodo.semBordero" class="q-ml-xs" color="orange-8" label="sem borderô" />
        </div>
        <div v-for="l in linhas" :key="l" class="text-caption text-grey-7">{{ l }}</div>
        <div v-if="avisoPendente" class="text-caption text-amber-10 q-mt-xs">
          {{ avisoPendente }}
        </div>
        <div v-if="avisoDias" class="text-caption text-amber-10 q-mt-xs">
          {{ avisoDias }}
        </div>
        <div
          v-for="(l, i) in periodo.observacoes?.split('\n') ?? []"
          :key="i"
          class="text-caption text-grey-9"
        >
          {{ l }}
        </div>
      </div>
      <div class="col-auto row no-wrap items-center q-ml-sm">
        <template v-if="naoConferido">
          <q-btn flat round size="sm" color="grey-7" icon="edit_calendar" @click="prepararDatas">
            <q-tooltip>Início e fim</q-tooltip>
          </q-btn>
          <q-btn flat round size="sm" color="grey-7" icon="call_split" @click="prepararDividir">
            <q-tooltip>Dividir numa data</q-tooltip>
          </q-btn>
          <q-btn
            v-if="podeUnificar"
            flat
            round
            size="sm"
            color="grey-7"
            icon="merge"
            @click="unificar"
          >
            <q-tooltip>Unificar com o anterior</q-tooltip>
          </q-btn>
        </template>
        <q-btn v-else flat round size="sm" color="grey-7" icon="lock_reset" @click="reabrir">
          <q-tooltip>Reabrir</q-tooltip>
        </q-btn>
      </div>
    </q-card-section>
  </q-card>

  <!-- início e fim -->
  <q-dialog v-model="dialogDatas">
    <q-card flat style="width: 400px; max-width: 90vw">
      <q-form @submit.prevent="salvarDatas">
        <q-card-section class="text-grey-9 text-overline">INÍCIO E FIM</q-card-section>
        <q-separator inset />
        <q-card-section class="text-caption text-grey-7 q-pb-none">
          Sem invadir os períodos vizinhos. Os lançamentos ficam onde estão.
        </q-card-section>
        <q-card-section>
          <div class="row q-col-gutter-md">
            <div class="col-12">
              <MgInputData
                v-model="datas.inicio"
                type="timestamp"
                label="Início"
                autofocus
                :rules="[(v) => !!v]"
              />
            </div>
            <div v-if="datas.fim !== null" class="col-12">
              <MgInputData v-model="datas.fim" type="timestamp" label="Fim" :rules="[(v) => !!v]" />
            </div>
            <div class="col-12">
              <MgInput
                v-model="datas.observacoes"
                label="Observações"
                type="textarea"
                autogrow
                rows="2"
                maxlength="500"
              />
            </div>
          </div>
        </q-card-section>
        <q-separator inset />
        <q-card-actions align="right">
          <q-btn flat label="Cancelar" color="grey-8" v-close-popup tabindex="-1" />
          <q-btn flat color="primary" type="submit" label="Salvar" :loading="salvando" />
        </q-card-actions>
      </q-form>
    </q-card>
  </q-dialog>

  <!-- dividir: régua com os lançamentos e o corte -->
  <q-dialog v-model="dialogDividir">
    <q-card flat style="width: 600px; max-width: 95vw">
      <q-form @submit.prevent="salvarDividir">
        <q-card-section class="text-grey-9 text-overline">DIVIDIR</q-card-section>
        <q-separator inset />
        <q-card-section class="text-caption text-grey-7 q-pb-none">
          A primeira parte termina no corte e fica pendente, sem borderô digitado; a segunda fica
          com o fim, o borderô digitado e a foto de agora. O cartão (e o cancelamento) depois do
          corte vai para a segunda.
        </q-card-section>
        <q-card-section>
          <q-slider
            v-model="corteMs"
            :min="reguaInicio"
            :max="reguaFim"
            :step="1000"
            :marker-labels="marcas"
            label
            :label-value="formataTimestamp(new Date(corteMs))"
            color="primary"
          >
            <template #marker-label-group="{ markerList }">
              <div
                v-for="m in markerList"
                :key="m.index"
                :class="m.classes"
                :style="m.style"
                class="cursor-pointer"
                @click="corteMs = m.value"
              >
                <q-icon
                  name="circle"
                  size="10px"
                  :color="marcas[m.index].x.valor < 0 ? 'red-8' : 'green-8'"
                />
                <q-tooltip>
                  {{ formataTimestamp(marcas[m.index].x.momento) }} ·
                  {{ marcas[m.index].x.l.meiodescricao }} ·
                  {{ formataNumero(marcas[m.index].x.valor) }}
                </q-tooltip>
              </div>
            </template>
          </q-slider>
          <div class="row justify-between text-caption text-grey-7 q-mb-md">
            <div>{{ formataTimestamp(new Date(reguaInicio)) }}</div>
            <div>{{ formataTimestamp(new Date(reguaFim)) }}</div>
          </div>
          <MgInputData
            v-model="corteDividir"
            type="timestamp"
            label="Corte"
            :rules="[(v) => !!v, naRegua]"
          />
        </q-card-section>
        <q-separator inset />
        <q-card-actions align="right">
          <q-btn flat label="Cancelar" color="grey-8" v-close-popup tabindex="-1" />
          <q-btn flat color="primary" type="submit" label="Dividir" :loading="salvando" />
        </q-card-actions>
      </q-form>
    </q-card>
  </q-dialog>
</template>
