<script setup>
import { ref, computed } from 'vue'
import { useQuasar } from 'quasar'
import { storeToRefs } from 'pinia'
import { useContratoDetalheStore } from 'src/stores/contratoDetalhe'
import { notifySuccess, notifyError } from 'src/utils/notify'
import MgEmptyState from '@components/MgEmptyState.vue'
import MgInfoCriacao from '@components/MgInfoCriacao.vue'
import MgInputValor from '@components/MgInputValor.vue'
import MgSelectPessoa from '@components/MgSelectPessoa.vue'
import MgSelectNaturezaOperacao from '@components/MgSelectNaturezaOperacao.vue'

// Card "Plano de NF". Especialista na operação triangular: sequência de notas a
// emitir por carga, cada uma podendo referenciar a chave de outra (refNFe).
// Lê do store da tela e persiste pelas actions salvarNota/excluirNota.
const $q = useQuasar()
const store = useContratoDetalheStore()
const { notasPlano } = storeToRefs(store)

const dialogNota = ref(false)
const isNovoNota = ref(true)
const salvandoNota = ref(false)
const formNota = ref({})

// Opções de nota-pai (a que a atual referencia via refNFe) — exclui a própria.
const notaPaiOptions = computed(() =>
  notasPlano.value
    .filter((nt) => nt.codcontratonota !== formNota.value.codcontratonota)
    .map((nt) => ({
      label: `#${nt.ordem} ${nt.NaturezaOperacao?.naturezaoperacao || ''}`.trim(),
      value: nt.codcontratonota,
    })),
)
function novaNota() {
  formNota.value = { ordem: notasPlano.value.length + 1 }
  isNovoNota.value = true
  dialogNota.value = true
}
function editarNota(nt) {
  formNota.value = { ...nt }
  isNovoNota.value = false
  dialogNota.value = true
}
async function salvarNota() {
  if (salvandoNota.value) return
  salvandoNota.value = true
  try {
    await store.salvarNota(formNota.value)
    notifySuccess('Nota salva!')
    dialogNota.value = false
  } catch (e) {
    notifyError(e)
  } finally {
    salvandoNota.value = false
  }
}
function excluirNota(nt) {
  $q.dialog({
    title: 'Excluir',
    message: 'Excluir esta nota do plano?',
    cancel: { label: 'Cancelar', color: 'grey-8', flat: true },
    ok: { label: 'Excluir', color: 'red-5', flat: true },
  }).onOk(async () => {
    try {
      await store.excluirNota(nt)
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
        <q-item-label class="text-subtitle1">Plano de NF</q-item-label>
        <q-item-label caption>
          Notas a emitir por carga (operação triangular = sequência com referência de chave)
        </q-item-label>
      </q-item-section>
      <q-item-section side>
        <q-btn flat round size="sm" color="primary" icon="add" @click="novaNota">
          <q-tooltip>Nova nota no plano</q-tooltip>
        </q-btn>
      </q-item-section>
    </q-item>
    <q-separator />
    <q-list separator>
      <q-item v-for="nt in notasPlano" :key="nt.codcontratonota">
        <q-item-section avatar>
          <q-avatar color="blue-grey-1" text-color="blue-grey-8" :label="String(nt.ordem)" />
        </q-item-section>
        <q-item-section>
          <q-item-label>
            {{ nt.NaturezaOperacao?.naturezaoperacao || '—' }}
            <q-badge
              v-if="nt.codcontratonotapai"
              color="indigo-4"
              :label="`ref #${notasPlano.find((x) => x.codcontratonota === nt.codcontratonotapai)?.ordem || '?'}`"
              class="q-ml-xs"
            />
          </q-item-label>
          <q-item-label caption>
            {{ nt.PessoaNf?.fantasia || nt.PessoaNf?.pessoa || 'Destinatário do contrato' }}
            <span v-if="nt.observacaonf"> · {{ nt.observacaonf }}</span>
          </q-item-label>
        </q-item-section>
        <q-item-section side>
          <div class="row items-center no-wrap">
            <MgInfoCriacao :registro="nt" />
            <q-btn flat dense round size="sm" color="grey-7" icon="edit" @click="editarNota(nt)" />
            <q-btn
              flat
              dense
              round
              size="sm"
              color="grey-7"
              icon="delete"
              @click="excluirNota(nt)"
            />
          </div>
        </q-item-section>
      </q-item>
      <MgEmptyState v-if="!notasPlano.length" plain icon="receipt_long">
        Sem plano de NF. Venda direta emite uma nota só (não precisa de plano).
      </MgEmptyState>
    </q-list>

    <!-- Dialog Nota do plano (operação triangular) -->
    <q-dialog v-model="dialogNota">
      <q-card flat style="width: 480px; max-width: 95vw">
        <q-form @submit.prevent="salvarNota">
          <q-card-section class="bg-primary text-white">
            <div class="text-h6">{{ isNovoNota ? 'Nova nota' : 'Editar nota' }}</div>
          </q-card-section>
          <q-card-section class="q-pt-md">
            <div class="row q-col-gutter-md">
              <div class="col-12 col-sm-4">
                <MgInputValor v-model="formNota.ordem" :decimals="0" label="Ordem" autofocus />
              </div>
              <div class="col-12 col-sm-8">
                <MgSelectNaturezaOperacao
                  v-model="formNota.codnaturezaoperacao"
                  label="Natureza da operação"
                  lazy-rules
                  :rules="[(v) => !!v]"
                />
              </div>
              <div class="col-12">
                <MgSelectPessoa
                  v-model="formNota.codpessoanf"
                  label="Pessoa da NF (destinatário)"
                  clearable
                />
              </div>
              <div class="col-12">
                <q-select
                  v-model="formNota.codcontratonotapai"
                  :options="notaPaiOptions"
                  emit-value
                  map-options
                  outlined
                  clearable
                  label="Referencia a chave de (refNFe)"
                />
              </div>
              <div class="col-12">
                <q-input
                  v-model="formNota.observacaonf"
                  label="Observação da NF"
                  type="textarea"
                  autogrow
                  outlined
                />
              </div>
            </div>
          </q-card-section>
          <q-card-actions align="right">
            <q-btn flat label="Cancelar" color="grey-8" v-close-popup tabindex="-1" />
            <q-btn type="submit" flat label="Salvar" color="primary" :loading="salvandoNota" />
          </q-card-actions>
        </q-form>
      </q-card>
    </q-dialog>
  </q-card>
</template>
