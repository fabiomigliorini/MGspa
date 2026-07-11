<script setup>
import { ref } from 'vue'
import { useQuasar } from 'quasar'
import { storeToRefs } from 'pinia'
import { useContratoDetalheStore } from 'src/stores/contratoDetalhe'
import { notifySuccess, notifyError } from 'src/utils/notify'
import { formataNumero, formataReal, formataData } from '@components/formatters'
import MgEmptyState from '@components/MgEmptyState.vue'
import MgInfoCriacao from '@components/MgInfoCriacao.vue'
import FixacaoImpostosDialog from 'components/FixacaoImpostosDialog.vue'
import CambioDialog from 'components/CambioDialog.vue'

// Card "Fixação de preço". Lista as fixações ativas; cada fixação fixa o preço
// (na sua moeda) e, quando dolarizada, TRAVA o câmbio em fatias (tabela filha).
// Lê tudo do store da tela; os 4 totais (totalmoeda/saldomoeda/totalbrl/
// liquidobrl) vêm calculados do backend.
const $q = useQuasar()
const store = useContratoDetalheStore()
const { contrato, cod, fixacoes, afixar } = storeToRefs(store)

const ehUsd = store.ehUsd
const simbolo = store.simboloMoeda

function fmt(v, dec = 0) {
  return formataNumero(v, dec)
}
const rs = formataReal
const fmtData = formataData

// R$/saca líquido firme (para o rótulo do BRL).
function liquidoSc(f) {
  return f.quantidade > 0 ? f.liquidobrl / f.quantidade : 0
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
    <q-list separator>
      <q-item v-for="f in fixacoes" :key="f.codcontratofixacao">
        <q-item-section>
          <q-item-label>
            {{ fmt(f.quantidade) }} sc · {{ simbolo(f.moeda) }} {{ fmt(f.preco, 2) }}/sc
            <span v-if="!ehUsd(f)" class="text-green-8">· líq {{ rs(liquidoSc(f)) }}/sc</span>
          </q-item-label>
          <q-item-label caption>
            {{ fmtData(f.data) }}
            <span v-if="f.datavencimento">· vence {{ fmtData(f.datavencimento) }}</span>
          </q-item-label>

          <!-- Câmbio (só fixação dolarizada): status + travas + botão travar -->
          <template v-if="ehUsd(f)">
            <q-item-label caption class="text-blue-grey-8 q-mt-xs">
              Fixado {{ simbolo(f.moeda) }} {{ fmt(f.totalmoeda, 2) }} · travado
              {{ rs(f.totalbrl) }}
              <span v-if="f.saldomoeda > 0.005">
                · falta travar {{ simbolo(f.moeda) }} {{ fmt(f.saldomoeda, 2) }}</span
              >
              <span v-else class="text-green-8">· câmbio 100% travado</span>
            </q-item-label>

            <div v-for="c in travas(f)" :key="c.codcontratofixacaocambio" class="row items-center">
              <q-item-label caption class="col">
                <q-icon name="lock" size="12px" class="q-mr-xs" />
                {{ fmtData(c.data) }} · {{ simbolo(f.moeda) }} {{ fmt(c.valor, 2) }} @
                {{ fmt(c.cotacao, 4) }} → {{ rs(c.valorbrl) }}
              </q-item-label>
              <q-btn
                flat
                dense
                round
                size="xs"
                color="grey-6"
                icon="edit"
                @click="editarCambio(f, c)"
              />
              <q-btn
                flat
                dense
                round
                size="xs"
                color="grey-6"
                icon="delete"
                @click="excluirCambio(f, c)"
              />
            </div>

            <div v-if="f.saldomoeda > 0.005" class="q-mt-xs">
              <q-btn
                flat
                dense
                no-caps
                size="sm"
                color="primary"
                icon="lock"
                label="Travar câmbio"
                @click="travarCambio(f)"
              />
            </div>
          </template>
        </q-item-section>

        <q-item-section side top>
          <div class="row items-center no-wrap">
            <MgInfoCriacao :registro="f" />
            <q-btn
              flat
              dense
              round
              size="sm"
              color="grey-7"
              icon="edit"
              @click="editarFixacao(f)"
            />
            <q-btn
              flat
              dense
              round
              size="sm"
              color="grey-7"
              icon="delete"
              @click="excluirFixacao(f)"
            />
          </div>
        </q-item-section>
      </q-item>
      <MgEmptyState v-if="!fixacoes.length" plain icon="sell">
        Nenhuma fixação lançada.
      </MgEmptyState>
    </q-list>

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
