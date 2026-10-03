<script setup>
// Cabeçalho do período (doc-4, redefinição do dinheiro): a situação (aberto, pendente, fechado) e
// as ações de estado, uma por botão (cada botão faz uma coisa só). Espécie (abrir novo período fica
// ao lado das abas, na página): fechar (com a contagem final já informada pelo resumo; dentro da
// tolerância do portador fecha, acima dá erro e fica pendente), reabrir, início e fim, dividir,
// unificar e borderô. As contagens são os botões do resumo. Banco: fechar com corte e reabrir
// (M12). Abrir, contar e fechar: operador; o resto: gestor.
// Embaixo, no mesmo card, o resumo do período.
import { ref, computed, watch } from 'vue'
import { useRouter } from 'vue-router'
import { useQuasar } from 'quasar'
import { storeToRefs } from 'pinia'
import MgInput from '@components/MgInput.vue'
import MgInputData from '@components/MgInputData.vue'
import ContagemCaixa from '@components/caixa/ContagemCaixa.vue'
import PeriodoResumo from 'components/portador/PeriodoResumo.vue'
import { formataNumero, formataTimestamp, formataTimestampIso } from '@components/formatters'
import { periodoStore } from '@components/stores/periodoStore'

const router = useRouter()
const $q = useQuasar()
const store = periodoStore()
const { portador, periodo, periodos, salvando, gestor } = storeToRefs(store)

const caixa = computed(() => !!portador.value?.ehCaixa)
const situacao = computed(() => periodo.value?.situacao)
const naoFechado = computed(() => !!periodo.value && situacao.value !== 'fechado')
const reais = (v) => `R$ ${formataNumero(v ?? 0)}`

const BADGE = {
  aberto: { cor: 'green-7', label: 'Aberto' },
  pendente: { cor: 'amber-8', label: 'Pendente' },
  fechado: { cor: 'grey-7', label: 'Fechado' },
}

// o intervalo, quem fechou e, no pendente, o que falta
const linhas = computed(() => {
  const p = periodo.value
  if (!p) return [caixa.value ? 'Nenhum período aberto ainda.' : 'Nenhum movimento ainda.']
  const ret = [`De ${formataTimestamp(p.inicio)} até ${p.fim ? formataTimestamp(p.fim) : 'agora'}`]
  if (p.situacao === 'fechado' && p.usuariofechamento) {
    ret.push(`Fechado por ${p.usuariofechamento} em ${formataTimestamp(p.fechamento)}`)
  }
  return ret
})
// fecha-se do mais antigo para o mais novo: algum período anterior não fechado
const anteriorPendente = computed(() =>
  periodos.value.some((p) => p.inicio < periodo.value?.inicio && p.situacao !== 'fechado'),
)
const avisoPendente = computed(() => {
  const p = periodo.value
  if (p?.situacao !== 'pendente' || p.diferenca == null) return null
  if (anteriorPendente.value) {
    return 'Há período anterior pendente: este só fecha depois dele (feche do mais antigo para o mais novo).'
  }
  if (Math.abs(p.diferenca) <= p.tolerancia) {
    return `Diferença de ${reais(p.diferenca)} dentro da tolerância (${reais(p.tolerancia)}): feche para concluir.`
  }
  return (
    `Diferença de ${reais(p.diferenca)} acima da tolerância (${reais(p.tolerancia)}). ` +
    (p.diferenca < 0
      ? 'Faltou dinheiro: lance o vale do colaborador ou o movimento que faltou e feche de novo.'
      : 'Sobrou dinheiro: ache o que aconteceu, lance a correção e feche de novo.')
  )
})

const iso = (d) => formataTimestampIso(d ? new Date(d) : new Date())
const limpa = (contagem) =>
  Object.fromEntries(Object.entries(contagem || {}).filter(([, q]) => Number(q) > 0))

// ---- fechar: só fecha, com a contagem final já informada (sem ela, o servidor recusa) ----
const naoFecha = computed(() => {
  const p = periodo.value
  if (p?.pendentes) {
    return `Há ${p.pendentes} transferência(s) a confirmar: confirme ou cancele antes`
  }
  if (p?.contagem?.final?.contado == null && p?.saldofinal) {
    return 'Conte o dinheiro antes (botão ao lado do saldo final)'
  }
  return null
})

function fecharPeriodo() {
  store.fechar()
}

// ---- contagem inicial ou final pelo resumo (fechado: só para ver) ----
const dialogContagem = ref(false)
const momento = ref('inicial')
const contagem = ref({ contagem: {}, itens: {} })
const soConsulta = computed(() => !naoFechado.value)

function prepararContar(m) {
  momento.value = m
  contagem.value = { contagem: { ...(periodo.value.contagem?.[m]?.contagem || {}) }, itens: {} }
  dialogContagem.value = true
}

async function salvarContagem() {
  if (soConsulta.value) return
  if (await store.contar(momento.value, limpa(contagem.value.contagem))) {
    dialogContagem.value = false
  }
}

// ---- início e fim ----
const dialogDatas = ref(false)
const datas = ref({ inicio: null, fim: null, observacoes: null })

function prepararDatas() {
  const p = periodo.value
  datas.value = {
    inicio: iso(p.inicio),
    fim: p.fim ? iso(p.fim) : null,
    observacoes: p.observacoes,
  }
  dialogDatas.value = true
}

async function salvarDatas() {
  if (await store.editarDatas({ ...datas.value })) dialogDatas.value = false
}

// ---- dividir: o corte escolhido na régua do período (do início ao fim; aberto, até agora), com
// os lançamentos marcados; começa no meio do tempo. O campo de data e a régua andam juntos ----
const dialogDividir = ref(false)
const ms = (d) => new Date(String(d).replace(' ', 'T')).getTime()
const reguaInicio = ref(0)
const reguaFim = ref(0)
const corteMs = ref(0)
const corteDividir = ref(null)
const marcas = computed(() =>
  (periodo.value?.lancamentos ?? []).map((l) => ({ value: ms(l.transacao), label: '', l })),
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
  reguaInicio.value = ms(p.inicio)
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
  router.push({
    name: 'portador-detalhe',
    params: { codportador: portador.value.codportador, codportadorperiodo: cod },
  })
}

// ---- unificar com o anterior (os dois não fechados; o anterior sem diferença) ----
const anterior = computed(() => {
  const i = periodos.value.findIndex(
    (p) => p.codportadorperiodo === periodo.value?.codportadorperiodo,
  )
  return i > 0 ? periodos.value[i - 1] : null
})
const podeUnificar = computed(
  () =>
    naoFechado.value &&
    !!anterior.value &&
    anterior.value.situacao !== 'fechado' &&
    !anterior.value.diferenca,
)

function unificar() {
  $q.dialog({
    title: 'Unificar',
    message: `Juntar este período ao ${anterior.value.descricao}? Fica um só, com o fim e a contagem final deste.`,
    cancel: { label: 'Cancelar', color: 'grey-8', flat: true },
    ok: { label: 'Unificar', color: 'primary', flat: true },
  }).onOk(async () => {
    const cod = await store.unificar()
    if (cod) {
      router.push({
        name: 'portador-detalhe',
        params: { codportador: portador.value.codportador, codportadorperiodo: cod },
      })
    }
  })
}

function reabrir() {
  $q.dialog({
    title: 'Reabrir',
    message: caixa.value
      ? 'O período volta a aceitar correções e a contagem final pode mudar (o período seguinte começa com ela). Continuar?'
      : 'O período volta a aceitar lançamentos. Continuar?',
    cancel: { label: 'Cancelar', color: 'grey-8', flat: true },
    ok: { label: 'Reabrir', color: 'primary', flat: true },
  }).onOk(() => store.reabrir())
}

// ---- banco: fechar com corte (padrão: último dia do mês anterior) ----
const dialogCorte = ref(false)
const corte = ref(null)

function prepararCorte() {
  const hoje = new Date()
  const d = new Date(hoje.getFullYear(), hoje.getMonth(), 0)
  corte.value = [
    d.getFullYear(),
    String(d.getMonth() + 1).padStart(2, '0'),
    String(d.getDate()).padStart(2, '0'),
  ].join('-')
  dialogCorte.value = true
}

async function fecharCorte() {
  if (await store.fecharCorte(periodo.value.corrente ? corte.value : null)) {
    dialogCorte.value = false
  }
}
</script>

<template>
  <q-card flat bordered>
    <q-card-section class="row no-wrap items-start">
      <div class="col">
        <div class="text-subtitle1 text-weight-medium">
          {{ periodo?.descricao ?? 'Sem período' }}
          <q-badge
            v-if="periodo"
            class="q-ml-sm"
            :color="BADGE[situacao].cor"
            :label="BADGE[situacao].label"
          />
        </div>
        <div v-for="l in linhas" :key="l" class="text-caption text-grey-7">{{ l }}</div>
        <div v-if="avisoPendente" class="text-caption text-amber-10 q-mt-xs">
          {{ avisoPendente }}
        </div>
        <!-- as observações, linha a linha -->
        <div
          v-for="(l, i) in periodo?.observacoes?.split('\n') ?? []"
          :key="i"
          class="text-caption text-grey-9"
        >
          {{ l }}
        </div>
      </div>
      <div class="col-auto row no-wrap items-center q-ml-sm">
        <template v-if="caixa">
          <!-- desabilitado o botão não mostra a dica: ela fica no span -->
          <span v-if="naoFechado">
            <q-btn
              flat
              round
              size="sm"
              color="grey-7"
              icon="lock"
              :disable="!!naoFecha"
              @click="fecharPeriodo"
            />
            <q-tooltip>{{ naoFecha || 'Fechar' }}</q-tooltip>
          </span>
          <template v-if="gestor && naoFechado">
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
          <q-btn
            v-if="gestor && situacao === 'fechado'"
            flat
            round
            size="sm"
            color="grey-7"
            icon="lock_reset"
            @click="reabrir"
          >
            <q-tooltip>Reabrir</q-tooltip>
          </q-btn>
          <q-btn
            v-if="periodo && situacao !== 'aberto'"
            flat
            round
            size="sm"
            color="grey-7"
            icon="picture_as_pdf"
            @click="store.abrirBordero()"
          >
            <q-tooltip>Borderô</q-tooltip>
          </q-btn>
        </template>
        <template v-else-if="gestor && periodo">
          <q-btn
            v-if="naoFechado"
            flat
            round
            size="sm"
            color="grey-7"
            icon="lock"
            @click="periodo.corrente ? prepararCorte() : fecharCorte()"
          >
            <q-tooltip>{{ periodo.corrente ? 'Fechar com corte' : 'Fechar' }}</q-tooltip>
          </q-btn>
          <q-btn v-else flat round size="sm" color="grey-7" icon="lock_reset" @click="reabrir">
            <q-tooltip>Reabrir</q-tooltip>
          </q-btn>
        </template>
      </div>
    </q-card-section>
    <PeriodoResumo @contar="prepararContar" />
  </q-card>

  <!-- contagem inicial ou final -->
  <q-dialog v-model="dialogContagem">
    <q-card flat style="width: 600px; max-width: 95vw">
      <q-form @submit.prevent="salvarContagem">
        <q-card-section class="text-h6">
          Contagem {{ momento }}
          <div class="text-caption text-grey-7">
            Saldo {{ momento }} no sistema
            {{ reais(momento === 'inicial' ? periodo.saldoinicial : periodo.saldofinal) }}
          </div>
        </q-card-section>
        <q-card-section>
          <ContagemCaixa v-model="contagem" :disable="soConsulta" autofocus />
        </q-card-section>
        <q-card-actions align="right">
          <q-btn flat label="Cancelar" color="grey-8" v-close-popup tabindex="-1" />
          <q-btn
            flat
            color="primary"
            type="submit"
            label="Salvar"
            :disable="soConsulta"
            :loading="salvando"
          />
        </q-card-actions>
      </q-form>
    </q-card>
  </q-dialog>

  <!-- início e fim -->
  <q-dialog v-model="dialogDatas">
    <q-card flat style="width: 400px; max-width: 90vw">
      <q-form @submit.prevent="salvarDatas">
        <q-card-section class="text-h6">
          Início e fim
          <div class="text-caption text-grey-7">
            Sem invadir os períodos vizinhos nem deixar lançamento de fora.
          </div>
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
        <q-card-section class="text-h6">
          Dividir
          <div class="text-caption text-grey-7">
            A primeira parte termina no corte e fica pendente, sem contagem final; a segunda fica
            com o fim e a contagem final de agora e começa com o saldo da primeira.
          </div>
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
                  :color="marcas[m.index].l.valor < 0 ? 'red-8' : 'green-8'"
                />
                <q-tooltip>
                  {{ formataTimestamp(marcas[m.index].l.transacao) }} ·
                  {{ marcas[m.index].l.texto }} ·
                  {{ formataNumero(marcas[m.index].l.valor) }}
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
        <q-card-actions align="right">
          <q-btn flat label="Cancelar" color="grey-8" v-close-popup tabindex="-1" />
          <q-btn flat color="primary" type="submit" label="Dividir" :loading="salvando" />
        </q-card-actions>
      </q-form>
    </q-card>
  </q-dialog>

  <!-- banco: fechar com corte -->
  <q-dialog v-model="dialogCorte">
    <q-card flat style="width: 400px; max-width: 90vw">
      <q-form @submit.prevent="fecharCorte">
        <q-card-section class="text-h6">Fechar com corte</q-card-section>
        <q-card-section>
          <div class="text-caption text-grey-7 q-mb-sm">
            O que caiu depois do corte vai para o período seguinte.
          </div>
          <MgInputData v-model="corte" label="Corte" autofocus :rules="[(v) => !!v]" />
        </q-card-section>
        <q-card-actions align="right">
          <q-btn flat label="Cancelar" color="grey-8" v-close-popup tabindex="-1" />
          <q-btn flat label="Fechar" color="primary" type="submit" :loading="salvando" />
        </q-card-actions>
      </q-form>
    </q-card>
  </q-dialog>
</template>
