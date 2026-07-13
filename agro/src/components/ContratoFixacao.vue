<script setup>
import { ref } from 'vue'
import { useQuasar } from 'quasar'
import { storeToRefs } from 'pinia'
import { useContratoDetalheStore } from 'src/stores/contratoDetalhe'
import { notifySuccess, notifyError } from 'src/utils/notify'
import { formataNumero, formataData } from '@components/formatters'
import MgEmptyState from '@components/MgEmptyState.vue'
import MgInfoCriacao from '@components/MgInfoCriacao.vue'
import FixacaoImpostosDialog from 'components/FixacaoImpostosDialog.vue'
import CambioDialog from 'components/CambioDialog.vue'
import ResumoImposto from 'components/ResumoImposto.vue'

// Card "Fixação de preço": GRID de fichas compactas, uma por fixação. Cada ficha
// traz preço, câmbio (US$: progresso + travas) e o cálculo fiscal (ResumoImposto)
// sobre a parte travada (ou o bruto BRL). Os 4 totais vêm do backend.
const $q = useQuasar()
const store = useContratoDetalheStore()
const { contrato, cod, fixacoes, afixar, pesosaca } = storeToRefs(store)

const ehUsd = store.ehUsd
const simbolo = store.simboloMoeda

function n(v) {
  return Number(v) || 0
}
function fmt(v, dec = 0) {
  return formataNumero(v, dec)
}
const fmtData = formataData

// Sacas cujo câmbio já está travado (BRL: todas). Base do bruto do ResumoImposto.
function sacasTravadas(f) {
  return n(f.preco) > 0 ? (n(f.totalmoeda) - n(f.saldomoeda)) / n(f.preco) : 0
}
// Fração do valor com câmbio travado (barra de progresso).
function progressoCambio(f) {
  return n(f.totalmoeda) > 0 ? (n(f.totalmoeda) - n(f.saldomoeda)) / n(f.totalmoeda) : 0
}
// Travas de câmbio ativas da fixação.
function travas(f) {
  return (f.ContratoFixacaoCambioS || []).filter((c) => !c.inativo)
}

// ---- Fixação (preço + impostos) ----
const impostosDialog = ref(false)
const impostosFixacao = ref(null)
function novaFixacao() {
  impostosFixacao.value = null
  impostosDialog.value = true
}
function editarFixacao(f) {
  impostosFixacao.value = f
  impostosDialog.value = true
}
function excluirFixacao(f) {
  $q.dialog({
    title: 'Excluir',
    message: 'Excluir esta fixação?',
    cancel: { label: 'Cancelar', color: 'grey-8', flat: true },
    ok: { label: 'Excluir', color: 'red-5', flat: true },
  }).onOk(async () => {
    try {
      await store.excluirFixacao(f)
      notifySuccess('Excluído!')
    } catch (e) {
      notifyError(e)
    }
  })
}

// ---- Trava de câmbio ----
const cambioDialog = ref(false)
const cambioFixacao = ref(null)
const cambioEdit = ref(null)
function travarCambio(f) {
  cambioFixacao.value = f
  cambioEdit.value = null
  cambioDialog.value = true
}
function editarCambio(f, c) {
  cambioFixacao.value = f
  cambioEdit.value = c
  cambioDialog.value = true
}
function excluirCambio(f, c) {
  $q.dialog({
    title: 'Excluir',
    message: 'Excluir esta trava de câmbio?',
    cancel: { label: 'Cancelar', color: 'grey-8', flat: true },
    ok: { label: 'Excluir', color: 'red-5', flat: true },
  }).onOk(async () => {
    try {
      await store.excluirCambio(f.codcontratofixacao, c)
      notifySuccess('Excluído!')
    } catch (e) {
      notifyError(e)
    }
  })
}
</script>

<template>
  <q-card flat bordered class="q-mb-md">
    <q-item>
      <q-item-section>
        <q-item-label class="text-subtitle1">Fixação de preço</q-item-label>
      </q-item-section>
      <q-item-section side>
        <q-btn flat round size="sm" color="primary" icon="add" @click="novaFixacao">
          <q-tooltip>Nova fixação</q-tooltip>
        </q-btn>
      </q-item-section>
    </q-item>
    <q-separator />

    <q-card-section>
      <MgEmptyState v-if="!fixacoes.length" plain icon="sell">
        Nenhuma fixação lançada.
      </MgEmptyState>

      <div v-else class="row q-col-gutter-md">
        <div v-for="f in fixacoes" :key="f.codcontratofixacao" class="col-12 col-sm-6 col-md-4">
          <q-card
            flat
            bordered
            class="full-height"
            :style="`border-left: 3px solid ${ehUsd(f) ? '#0d9488' : '#16a34a'}`"
          >
            <!-- Cabeçalho: quantidade + preço + ações -->
            <q-card-section class="q-pb-none">
              <div class="row items-start justify-between no-wrap">
                <div class="col">
                  <div class="text-h6 text-weight-bold">
                    {{ fmt(f.quantidade) }}
                    <span class="text-caption text-grey-6 text-weight-regular">sc</span>
                  </div>
                  <div class="text-caption text-grey-7">
                    <b class="text-grey-9">{{ simbolo(f.moeda) }} {{ fmt(f.preco, 2) }}</b> / saca
                    <span v-if="f.datavencimento">· vence {{ fmtData(f.datavencimento) }}</span>
                  </div>
                </div>
                <div class="row items-center no-wrap q-ml-sm">
                  <MgInfoCriacao :registro="f" />
                  <q-btn
                    flat
                    round
                    size="sm"
                    color="grey-7"
                    icon="edit"
                    @click="editarFixacao(f)"
                  />
                  <q-btn
                    flat
                    round
                    size="sm"
                    color="grey-7"
                    icon="delete"
                    @click="excluirFixacao(f)"
                  />
                </div>
              </div>
            </q-card-section>

            <q-card-section class="q-pt-sm">
              <!-- Câmbio (US$): fixado + progresso + saldo a travar -->
              <template v-if="ehUsd(f)">
                <div class="row items-baseline justify-between text-caption q-mb-xs">
                  <span class="text-grey-7"
                    >Fixado
                    <b class="text-grey-9"
                      >{{ simbolo(f.moeda) }} {{ fmt(f.totalmoeda, 2) }}</b
                    ></span
                  >
                  <span
                    class="text-weight-bold"
                    :class="progressoCambio(f) >= 1 ? 'text-green-7' : 'text-teal-7'"
                    >{{ fmt(progressoCambio(f) * 100, 0) }}%</span
                  >
                </div>
                <q-linear-progress
                  :value="progressoCambio(f)"
                  :color="progressoCambio(f) >= 1 ? 'green-6' : 'teal-6'"
                  track-color="grey-3"
                  size="6px"
                  rounded
                  class="q-mb-sm"
                />
                <div v-if="f.saldomoeda > 0.005" class="text-caption text-orange-8 q-mb-sm">
                  Falta travar {{ simbolo(f.moeda) }} {{ fmt(f.saldomoeda, 2) }}
                </div>
                <div v-else class="text-caption text-green-8 q-mb-sm">Câmbio 100% travado</div>
              </template>

              <!-- Cálculo fiscal (recibo) -->
              <ResumoImposto
                :bruto="n(f.totalbrl)"
                :sacas="sacasTravadas(f)"
                :tributos="f.tributos || []"
                :pesosaca="pesosaca"
              />

              <!-- Travas de câmbio + botão travar (US$) -->
              <template v-if="ehUsd(f)">
                <div
                  v-for="c in travas(f)"
                  :key="c.codcontratofixacaocambio"
                  class="row items-center no-wrap bg-grey-2 rounded-borders q-px-sm q-py-xs q-mt-sm"
                >
                  <q-icon name="lock" size="12px" class="text-teal-7 q-mr-xs" />
                  <span class="col text-caption text-grey-8">
                    {{ fmtData(c.data) }} ·
                    <b>{{ simbolo(f.moeda) }} {{ fmt(c.valor, 2) }} @ {{ fmt(c.cotacao, 4) }}</b>
                  </span>
                  <q-btn
                    flat
                    round
                    size="xs"
                    color="grey-6"
                    icon="edit"
                    @click="editarCambio(f, c)"
                  />
                  <q-btn
                    flat
                    round
                    size="xs"
                    color="grey-6"
                    icon="delete"
                    @click="excluirCambio(f, c)"
                  />
                </div>
                <q-btn
                  v-if="f.saldomoeda > 0.005"
                  flat
                  no-caps
                  size="sm"
                  color="primary"
                  icon="lock"
                  label="Travar câmbio"
                  class="q-mt-sm"
                  @click="travarCambio(f)"
                />
              </template>
            </q-card-section>
          </q-card>
        </div>
      </div>
    </q-card-section>

    <!-- Modal Fixação (valores + impostos). afixar = saldo a fixar (sc). -->
    <FixacaoImpostosDialog
      v-model="impostosDialog"
      :cod="cod"
      :contrato="contrato"
      :fixacao="impostosFixacao"
      :afixar="afixar"
      @saved="store.carregar()"
    />

    <!-- Modal Travar câmbio (fatia do valor em moeda estrangeira @ cotação). -->
    <CambioDialog
      v-model="cambioDialog"
      :fixacao="cambioFixacao || {}"
      :cambio="cambioEdit"
      @saved="store.carregar()"
    />
  </q-card>
</template>
