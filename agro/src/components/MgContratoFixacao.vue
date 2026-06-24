<script setup>
import { ref } from 'vue'
import { useQuasar } from 'quasar'
import { storeToRefs } from 'pinia'
import { useContratoDetalheStore } from 'src/stores/contratoDetalhe'
import { notifySuccess, notifyError } from 'src/utils/notify'
import MgEmptyState from '@components/MgEmptyState.vue'
import MgInfoCriacao from '@components/MgInfoCriacao.vue'
import MgFixacaoImpostosDialog from 'components/MgFixacaoImpostosDialog.vue'

// Card "Fixação de preço". Especialista em fixar o preço das sacas do contrato:
// lista as fixações ativas, abre o MgFixacaoImpostosDialog (preço + impostos →
// líquido snapshot) e exclui. Lê tudo do store da tela (fixado/a fixar/preço
// médio são getters do store) e usa as actions p/ persistir.
const $q = useQuasar()
const store = useContratoDetalheStore()
const { contrato, cod, fixacoes, fixado, afixar, precoMedio } = storeToRefs(store)

function fmt(v, dec = 0) {
  if (v === null || v === undefined || v === '') return '—'
  return Number(v).toLocaleString('pt-BR', {
    minimumFractionDigits: dec,
    maximumFractionDigits: dec,
  })
}
function rs(v) {
  return 'R$ ' + fmt(v, 2)
}
function fmtData(d) {
  if (!d) return ''
  const [a, m, dia] = d.slice(0, 10).split('-')
  return `${dia}/${m}/${a}`
}

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
</script>

<template>
  <q-card flat bordered class="q-mb-md">
    <q-item>
      <q-item-section>
        <q-item-label class="text-subtitle1">Fixação de preço</q-item-label>
        <q-item-label caption>
          Fixado {{ fmt(fixado) }} sc · A fixar {{ fmt(afixar) }} sc · Preço médio
          {{ rs(precoMedio) }}/sc
        </q-item-label>
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
            {{ fmt(f.quantidade) }} sc · {{ rs(f.precoreal) }}/sc
            <span v-if="f.precoliquido != null" class="text-green-8">
              · líq {{ rs(f.precoliquido) }}</span
            >
            <q-badge v-if="f.isentofethab" color="teal-5" label="isento FETHAB" class="q-ml-xs" />
          </q-item-label>
          <q-item-label caption>
            {{ fmtData(f.data) }}
            <span v-if="f.moeda && f.moeda !== 'BRL'"
              >· {{ f.moeda }} {{ fmt(f.preco, 2) }} × {{ fmt(f.dolar, 4) }}</span
            >
          </q-item-label>
        </q-item-section>
        <q-item-section side>
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

    <!-- Modal Fixação (valores + impostos). afixar = saldo a fixar (sc) p/ o
         dialog avisar antes; o backend é quem garante (ContratoFixacaoRequest). -->
    <MgFixacaoImpostosDialog
      v-model="impostosDialog"
      :cod="cod"
      :contrato="contrato"
      :fixacao="impostosFixacao"
      :afixar="afixar"
      @saved="store.carregar()"
    />
  </q-card>
</template>
