<script setup>
// Cabeçalho do período (doc-4, R8): a situação (aberto, fechado por quem e quando, reaberto) e as
// ações de estado. Caixa (toda espécie: gaveta, cofre, troco, Caixa Financeiro): abrir, fechar
// (só com a contagem do fechamento batendo com o saldo final), reabrir e borderô; a contagem é o
// botão ao lado do saldo inicial e do final, no resumo, e abre o dialog daqui. Demais portadores:
// fechar com corte e reabrir (M12). Embaixo, no mesmo card, o resumo do período.
import { ref, computed } from 'vue'
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
const { portador, periodo, periodos, pode, salvando } = storeToRefs(store)

const caixa = computed(() => !!portador.value?.ehCaixa)
// a sessão que recebe movimento (aberta, sem fim); reaberta para correção não conta
const temAberta = computed(() => periodos.value.some((p) => p.aberto && !p.fim))

// o intervalo (de … até …) e, se fechado, quem fechou e quando
const situacao = computed(() => {
  const p = periodo.value
  if (!p) return [caixa.value ? 'O caixa nunca foi aberto.' : 'Nenhum movimento ainda.']
  const linhas = [
    `De ${formataTimestamp(p.inicio)} até ${p.fim ? formataTimestamp(p.fim) : 'agora'}`,
  ]
  if (!p.aberto) {
    linhas.push(`Fechado por ${p.usuariofechamento} em ${formataTimestamp(p.fechamento)}`)
  }
  return linhas
})

// ---- caixa: abrir, fechar e corrigir início e fim (um dialog de datas) ----
// 'abrir': início · 'fechar': fim (a reaberta já tem) · 'editar': início e, se tiver, fim
const dialogDatas = ref(false)
const modoDatas = ref('abrir')
const datas = ref({ inicio: null, fim: null, observacoes: null })
const iso = (d) => formataTimestampIso(d ? new Date(d) : new Date())
const TITULO_DATAS = { abrir: 'Abrir caixa', fechar: 'Fechar caixa', editar: 'Início e fim' }
const BOTAO_DATAS = { abrir: 'Abrir', fechar: 'Confirmar', editar: 'Salvar' }
const comInicio = computed(() => modoDatas.value !== 'fechar')
// por que não fecha (o botão fica desabilitado): saldo negativo ou contagem final que não bate
const naoFecha = computed(() => {
  const p = periodo.value
  if (!p) return null
  if (p.saldofinal < 0) return 'Saldo final negativo: lance o ajuste antes de fechar'
  const c = p.contagem?.final
  if (!c) return null
  if (c.contado == null) return p.saldofinal ? 'Conte o dinheiro (ao lado do saldo final)' : null
  return c.diferenca ? 'A contagem final não bate com o saldo final: lance o ajuste' : null
})
const comFim = computed(() =>
  modoDatas.value === 'fechar'
    ? !periodo.value?.fim
    : modoDatas.value === 'editar' && !!periodo.value?.fim,
)

function prepararDatas(modo) {
  modoDatas.value = modo
  datas.value = {
    inicio: modo === 'abrir' ? iso() : iso(periodo.value.inicio),
    fim: periodo.value?.fim ? iso(periodo.value.fim) : iso(),
    observacoes: modo === 'abrir' ? null : periodo.value.observacoes,
  }
  dialogDatas.value = true
}

async function salvarDatas() {
  const { inicio, fim, observacoes } = datas.value
  if (modoDatas.value === 'abrir') {
    const cod = await store.abrirCaixa({ inicio })
    if (!cod) return
    dialogDatas.value = false
    router.push({
      name: 'portador-detalhe',
      params: { codportador: portador.value.codportador, codportadorperiodo: cod },
    })
    return
  }
  const ok =
    modoDatas.value === 'fechar'
      ? await store.fecharCaixa(comFim.value ? { fim } : {})
      : await store.editarDatas({ inicio, fim: comFim.value ? fim : null, observacoes })
  if (ok) dialogDatas.value = false
}

function reabrirCaixa() {
  $q.dialog({
    title: 'Reabrir o caixa',
    message:
      'O caixa volta a aceitar correções (a última sessão volta a ficar aberta) e os títulos de repasse dos itens são estornados. Continuar?',
    cancel: { label: 'Cancelar', color: 'grey-8', flat: true },
    ok: { label: 'Reabrir', color: 'primary', flat: true },
  }).onOk(() => store.reabrirCaixa())
}

// ---- contagem inicial ou final (o botão fica ao lado do saldo, no resumo) ----
const dialogContagem = ref(false)
const momento = ref('inicial')
// o estoque dos itens de contagem da gaveta vem do lançamento do item
const COLUNA_ITEM = { inicial: 'valorabertura', final: 'valorfechamento' }
const contagem = ref({ contagem: {}, itens: {} })
const itensC = computed(() => (periodo.value?.itens || []).filter((i) => i.modo === 'C'))
// fechado ou sem permissão: o dialog só mostra a contagem
const soConsulta = computed(() => !periodo.value?.aberto || !pode.value.caixa)

function prepararContar(m) {
  momento.value = m
  contagem.value = {
    contagem: { ...(periodo.value.contagem[m].contagem || {}) },
    itens: Object.fromEntries(itensC.value.map((i) => [i.codcaixaitem, i[COLUNA_ITEM[m]]])),
  }
  dialogContagem.value = true
}

async function salvarContagem() {
  if (soConsulta.value) return
  const ok = await store.contar(momento.value, {
    contagem: Object.fromEntries(
      Object.entries(contagem.value.contagem).filter(([, q]) => Number(q) > 0),
    ),
    itens: Object.fromEntries(
      Object.entries(contagem.value.itens).map(([cod, v]) => [cod, Number(v) || 0]),
    ),
  })
  if (ok) dialogContagem.value = false
}

// ---- demais: fechar com corte (padrão: último dia do mês anterior) e reabrir ----
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

async function fecharPeriodo() {
  const corrente = periodo.value.corrente
  if (await store.fecharPeriodo(corrente ? corte.value : null)) dialogCorte.value = false
}

function reabrirPeriodo() {
  $q.dialog({
    title: 'Reabrir o período',
    message: 'O período volta a aceitar lançamentos. Continuar?',
    cancel: { label: 'Cancelar', color: 'grey-8', flat: true },
    ok: { label: 'Reabrir', color: 'primary', flat: true },
  }).onOk(() => store.reabrirPeriodo())
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
            :color="periodo.aberto ? 'green-7' : 'grey-7'"
            :label="periodo.aberto ? 'Aberto' : 'Fechado'"
          />
        </div>
        <div v-for="l in situacao" :key="l" class="text-caption text-grey-7">{{ l }}</div>
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
          <q-btn
            v-if="pode.caixa && !temAberta"
            flat
            round
            size="sm"
            color="grey-7"
            icon="lock_open"
            @click="prepararDatas('abrir')"
          >
            <q-tooltip>Abrir caixa</q-tooltip>
          </q-btn>
          <!-- desabilitado o botão não mostra a dica: ela fica no span -->
          <span v-if="pode.caixa && periodo?.aberto">
            <q-btn
              flat
              round
              size="sm"
              color="grey-7"
              icon="lock"
              :disable="!!naoFecha"
              @click="prepararDatas('fechar')"
            />
            <q-tooltip>{{ naoFecha || 'Fechar caixa' }}</q-tooltip>
          </span>
          <q-btn
            v-if="pode.caixa && periodo?.aberto"
            flat
            round
            size="sm"
            color="grey-7"
            icon="edit_calendar"
            @click="prepararDatas('editar')"
          >
            <q-tooltip>Início e fim</q-tooltip>
          </q-btn>
          <q-btn
            v-if="pode.reabrirCaixa && periodo && !periodo.aberto"
            flat
            round
            size="sm"
            color="grey-7"
            icon="lock_reset"
            @click="reabrirCaixa"
          >
            <q-tooltip>Reabrir</q-tooltip>
          </q-btn>
          <q-btn
            v-if="periodo && !periodo.aberto"
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
        <template v-else-if="pode.periodo && periodo">
          <q-btn
            v-if="periodo.aberto"
            flat
            round
            size="sm"
            color="grey-7"
            icon="lock"
            @click="periodo.corrente ? prepararCorte() : fecharPeriodo()"
          >
            <q-tooltip>{{ periodo.corrente ? 'Fechar com corte' : 'Fechar' }}</q-tooltip>
          </q-btn>
          <q-btn
            v-if="!periodo.aberto"
            flat
            round
            size="sm"
            color="grey-7"
            icon="lock_reset"
            @click="reabrirPeriodo"
          >
            <q-tooltip>Reabrir</q-tooltip>
          </q-btn>
        </template>
      </div>
    </q-card-section>
    <PeriodoResumo @contar="prepararContar" />
  </q-card>

  <!-- contagem do caixa: a inicial ou a final -->
  <q-dialog v-model="dialogContagem">
    <q-card flat style="width: 600px; max-width: 95vw">
      <q-form @submit.prevent="salvarContagem">
        <q-card-section class="text-h6">
          Contagem {{ momento }}
          <div class="text-caption text-grey-7">
            Saldo {{ momento }} no sistema R$
            {{ formataNumero(momento === 'inicial' ? periodo.saldoinicial : periodo.saldofinal) }}
          </div>
        </q-card-section>
        <q-card-section>
          <ContagemCaixa v-model="contagem" :itens="itensC" :disable="soConsulta" autofocus />
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

  <!-- caixa: abrir (início), fechar (fim) e corrigir início e fim -->
  <q-dialog v-model="dialogDatas">
    <q-card flat style="width: 220px; max-width: 90vw">
      <q-form @submit.prevent="salvarDatas">
        <q-card-section class="text-h6">
          {{ TITULO_DATAS[modoDatas] }}
          <div class="text-caption text-grey-7">
            <template v-if="modoDatas === 'abrir'">
              Saldo inicial R$ {{ formataNumero(portador.saldo ?? 0) }}; a contagem vai ao lado
              dele.
            </template>
            <template v-else-if="modoDatas === 'fechar'">
              Saldo final R$ {{ formataNumero(periodo.saldofinal) }}; a contagem final precisa bater
              com ele.
            </template>
            <template v-else
              >Sem invadir as sessões vizinhas nem deixar lançamento de fora.</template
            >
          </div>
        </q-card-section>
        <q-card-section>
          <div class="row q-col-gutter-md">
            <div v-if="comInicio" class="col-12">
              <MgInputData
                v-model="datas.inicio"
                type="timestamp"
                label="Início"
                :autofocus="modoDatas === 'abrir'"
                :rules="[(v) => !!v]"
              />
            </div>
            <div v-if="comFim" class="col-12">
              <MgInputData
                v-model="datas.fim"
                type="timestamp"
                label="Fim"
                :autofocus="modoDatas === 'fechar'"
                :rules="[(v) => !!v]"
              />
            </div>
            <div v-if="modoDatas === 'editar'" class="col-12">
              <MgInput
                v-model="datas.observacoes"
                label="Observações"
                type="textarea"
                autogrow
                rows="2"
                autofocus
                maxlength="500"
              />
            </div>
          </div>
        </q-card-section>
        <q-card-actions align="right">
          <q-btn flat label="Cancelar" color="grey-8" v-close-popup tabindex="-1" />
          <q-btn
            flat
            color="primary"
            type="submit"
            :label="BOTAO_DATAS[modoDatas]"
            :loading="salvando"
          />
        </q-card-actions>
      </q-form>
    </q-card>
  </q-dialog>

  <!-- demais: fechar com corte -->
  <q-dialog v-model="dialogCorte">
    <q-card flat style="width: 400px; max-width: 90vw">
      <q-form @submit.prevent="fecharPeriodo">
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
