<script setup>
// Cabeçalho do período (doc-4, R8): a situação (aberto, fechado por quem e quando, reaberto) e as
// ações de estado. Gaveta: abrir e fechar com contagem (M13), reabrir, borderô. Demais portadores:
// fechar com corte e reabrir (M12).
import { ref, computed } from 'vue'
import { useRouter } from 'vue-router'
import { useQuasar } from 'quasar'
import { storeToRefs } from 'pinia'
import MgInput from '@components/MgInput.vue'
import MgInputData from '@components/MgInputData.vue'
import ContagemCaixa from '@components/caixa/ContagemCaixa.vue'
import { formataNumero, formataTimestamp, formataData } from '@components/formatters'
import { periodoStore } from '@components/stores/periodoStore'

const router = useRouter()
const $q = useQuasar()
const store = periodoStore()
const { portador, periodo, periodos, pode, salvando } = storeToRefs(store)

const gaveta = computed(() => !!portador.value?.ehGaveta)
const ultimo = computed(
  () =>
    !periodo.value ||
    periodos.value[periodos.value.length - 1]?.codportadorperiodo ===
      periodo.value.codportadorperiodo,
)

const situacao = computed(() => {
  const p = periodo.value
  if (!p) return gaveta.value ? 'O caixa nunca foi aberto.' : 'Nenhum movimento ainda.'
  if (gaveta.value) {
    const aberto = `Aberto em ${formataTimestamp(p.inicio, 0)} por ${p.usuarioabertura}`
    return p.aberto
      ? aberto
      : `${aberto} · fechado em ${formataTimestamp(p.fim, 0)} por ${p.usuariofechamento}`
  }
  if (!p.aberto) {
    return `Fechado em ${formataTimestamp(p.fechamento, 0)} por ${p.usuariofechamento}`
  }
  return p.corrente
    ? `Corrente, aberto desde ${formataData(p.inicio)}`
    : `Reaberto (de ${formataData(p.inicio)} a ${formataData(p.fim)})`
})

// ---- gaveta: contagem para abrir ou fechar ----
const dialogContagem = ref(false)
const abrindo = ref(false)
const itensAbrir = ref([])
const envelope = ref(0)
const contagem = ref({ contagem: {}, itens: {} })
const observacoes = ref('')
const refContagem = ref(null)

const itensC = computed(() =>
  (abrindo.value ? itensAbrir.value : periodo.value?.itens || []).filter((i) => i.modo === 'C'),
)

async function prepararAbrir() {
  const g = await store.gaveta()
  if (!g) return
  itensAbrir.value = g.itens
  envelope.value = g.envelope
  abrindo.value = true
  contagem.value = { contagem: {}, itens: {} }
  observacoes.value = ''
  dialogContagem.value = true
}

function prepararFechar() {
  abrindo.value = false
  contagem.value = { contagem: {}, itens: {} }
  observacoes.value = ''
  dialogContagem.value = true
}

const payload = () => ({
  contagem: Object.fromEntries(
    Object.entries(contagem.value.contagem).filter(([, q]) => Number(q) > 0),
  ),
  itens: Object.fromEntries(
    Object.entries(contagem.value.itens).map(([cod, v]) => [cod, Number(v) || 0]),
  ),
  observacoes: observacoes.value || null,
})

async function confirmarContagem() {
  if (abrindo.value) {
    const cod = await store.abrirCaixa(payload())
    if (!cod) return
    dialogContagem.value = false
    router.push({
      name: 'portador-detalhe',
      params: { codportador: portador.value.codportador, codportadorperiodo: cod },
    })
    return
  }
  $q.dialog({
    title: 'Fechar o caixa',
    message: `Fechar com R$ ${formataNumero(refContagem.value?.total ?? 0)} contados na gaveta? A diferença para o sistema vira ajuste.`,
    cancel: { label: 'Cancelar', color: 'grey-8', flat: true },
    ok: { label: 'Fechar', color: 'primary', flat: true },
  }).onOk(async () => {
    if (await store.fecharCaixa(payload())) dialogContagem.value = false
  })
}

function reabrirCaixa() {
  $q.dialog({
    title: 'Reabrir o caixa',
    message:
      'O caixa volta a ficar aberto: o ajuste do fechamento é desfeito e os títulos de repasse dos itens são estornados. Continuar?',
    cancel: { label: 'Cancelar', color: 'grey-8', flat: true },
    ok: { label: 'Reabrir', color: 'primary', flat: true },
  }).onOk(() => store.reabrirCaixa())
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
  <q-card flat bordered class="q-mb-md">
    <q-card-section class="row items-center q-col-gutter-sm">
      <div class="col-12 col-sm">
        <div class="text-subtitle1 text-weight-medium">
          {{ periodo?.descricao ?? 'Sem período' }}
          <q-badge
            v-if="periodo"
            class="q-ml-sm"
            :color="periodo.aberto ? 'green-7' : 'grey-7'"
            :label="periodo.aberto ? 'Aberto' : 'Fechado'"
          />
        </div>
        <div class="text-caption text-grey-7">{{ situacao }}</div>
      </div>
      <div class="col-12 col-sm-auto row justify-end q-gutter-sm">
        <template v-if="gaveta">
          <q-btn
            v-if="pode.caixa && ultimo && !periodo?.aberto"
            unelevated
            color="primary"
            icon="lock_open"
            label="Abrir caixa"
            @click="prepararAbrir"
          />
          <q-btn
            v-if="pode.caixa && periodo?.aberto"
            unelevated
            color="deep-orange-7"
            icon="lock"
            label="Fechar caixa"
            @click="prepararFechar"
          />
          <q-btn
            v-if="pode.reabrirCaixa && ultimo && periodo && !periodo.aberto"
            flat
            color="primary"
            icon="lock_open"
            label="Reabrir"
            @click="reabrirCaixa"
          />
          <q-btn
            v-if="periodo && !periodo.aberto"
            flat
            color="primary"
            icon="picture_as_pdf"
            label="Borderô"
            @click="store.abrirBordero()"
          />
        </template>
        <template v-else-if="pode.periodo && periodo">
          <q-btn
            v-if="periodo.aberto"
            unelevated
            color="deep-orange-7"
            icon="lock"
            :label="periodo.corrente ? 'Fechar com corte' : 'Fechar'"
            @click="periodo.corrente ? prepararCorte() : fecharPeriodo()"
          />
          <q-btn
            v-else
            flat
            color="primary"
            icon="lock_open"
            label="Reabrir"
            @click="reabrirPeriodo"
          />
        </template>
      </div>
    </q-card-section>
  </q-card>

  <!-- gaveta: contagem para abrir ou fechar -->
  <q-dialog v-model="dialogContagem">
    <q-card flat style="width: 600px; max-width: 95vw">
      <q-form @submit.prevent="confirmarContagem">
        <q-card-section class="text-h6">
          {{ abrindo ? 'Abrir caixa' : 'Fechar caixa' }}
          <div class="text-caption text-grey-7">
            <template v-if="abrindo">Envelope R$ {{ formataNumero(envelope) }}</template>
            <template v-else>Conte o que fica na gaveta, depois da sangria</template>
          </div>
        </q-card-section>
        <q-card-section>
          <ContagemCaixa ref="refContagem" v-model="contagem" :itens="itensC" autofocus />
          <MgInput
            v-model="observacoes"
            label="Observação"
            type="textarea"
            autogrow
            maxlength="250"
            class="q-mt-md"
          />
        </q-card-section>
        <q-card-actions align="right">
          <q-btn flat label="Cancelar" color="grey-8" v-close-popup tabindex="-1" />
          <q-btn
            flat
            color="primary"
            type="submit"
            :label="abrindo ? 'Abrir' : 'Fechar'"
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
