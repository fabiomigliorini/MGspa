<script setup>
// Caixa do PDV (M9 doc-3): abre e fecha o dinheiro da gaveta contando moedas e cédulas. Fechar
// imprime o borderô do caixa, que sobe ao escritório com o dinheiro; o gerente confere no contas.
import { ref, computed, onMounted } from 'vue'
import { useQuasar } from 'quasar'
import { formataNumero, formataTimestamp } from '@components/formatters'
import MgInput from '@components/MgInput.vue'
import MgInputValor from '@components/MgInputValor.vue'
import MgEmptyState from '@components/MgEmptyState.vue'
import { caixaStore } from 'stores/caixa'
import { negocioStore } from 'stores/negocio'

const $q = useQuasar()
const sCaixa = caixaStore()
const sNegocio = negocioStore()

const form = ref({ moedas: null, cedulas: null, observacoes: '' })
const total = computed(() => (form.value.moedas || 0) + (form.value.cedulas || 0))

const limpar = () => {
  form.value = { moedas: null, cedulas: null, observacoes: '' }
}

const contagem = () => ({
  moedas: form.value.moedas || 0,
  cedulas: form.value.cedulas || 0,
  observacoes: form.value.observacoes || null,
})

async function abrir() {
  if (await sCaixa.abrir(contagem())) limpar()
}

function fechar() {
  $q.dialog({
    title: 'Fechar o caixa',
    message: `Fechar com R$ ${formataNumero(total.value)} contados? Depois de fechado o caixa não recebe mais dinheiro.`,
    cancel: { label: 'Cancelar', color: 'grey-8', flat: true },
    ok: { label: 'Fechar', color: 'primary', flat: true },
  }).onOk(async () => {
    if (await sCaixa.fechar(contagem(), sNegocio.padrao.impressora)) limpar()
  })
}

onMounted(sCaixa.status)
</script>

<template>
  <q-page class="bg-grey-2">
    <div class="q-pa-md" style="max-width: 600px; margin: auto">
      <MgEmptyState v-if="!sCaixa.carregando && !sCaixa.gaveta" icon="point_of_sale">
        Este PDV não tem gaveta. Peça ao administrador para vincular a gaveta em Configurações →
        PDV.
      </MgEmptyState>

      <template v-else-if="sCaixa.gaveta">
        <q-card flat bordered class="q-mb-md">
          <q-card-section>
            <div class="row items-center">
              <div class="col">
                <div class="text-h6">{{ sCaixa.gaveta.portador }}</div>
                <div v-if="sCaixa.sessao" class="text-caption text-grey-7">
                  Aberto por {{ sCaixa.sessao.usuarioabertura }} em
                  {{ formataTimestamp(sCaixa.sessao.inicio, 2) }}
                </div>
                <div v-else-if="sCaixa.ultima" class="text-caption text-grey-7">
                  Último fechamento: {{ sCaixa.ultima.usuariofechamento }} em
                  {{ formataTimestamp(sCaixa.ultima.fim, 2) }}
                </div>
              </div>
              <q-badge
                :color="sCaixa.sessao ? 'green-7' : 'grey-7'"
                :label="sCaixa.sessao ? 'Aberto' : 'Fechado'"
              />
            </div>
          </q-card-section>

          <q-form @submit.prevent="sCaixa.sessao ? fechar() : abrir()">
            <q-card-section>
              <div class="text-subtitle2 q-mb-sm">
                {{ sCaixa.sessao ? 'Contagem para fechar' : 'Contagem para abrir (troco)' }}
              </div>
              <div class="row q-col-gutter-md">
                <div class="col-6">
                  <MgInputValor v-model="form.moedas" label="Moedas" autofocus />
                </div>
                <div class="col-6">
                  <MgInputValor v-model="form.cedulas" label="Cédulas" />
                </div>
                <div class="col-12 text-right text-h6">R$ {{ formataNumero(total) }}</div>
                <div class="col-12">
                  <MgInput
                    v-model="form.observacoes"
                    label="Observação"
                    type="textarea"
                    autogrow
                    maxlength="250"
                  />
                </div>
              </div>
            </q-card-section>
            <q-card-actions align="right">
              <q-btn
                unelevated
                :color="sCaixa.sessao ? 'deep-orange-7' : 'primary'"
                :icon="sCaixa.sessao ? 'lock' : 'lock_open'"
                :label="sCaixa.sessao ? 'Fechar caixa' : 'Abrir caixa'"
                type="submit"
                :loading="sCaixa.salvando"
              />
            </q-card-actions>
          </q-form>
        </q-card>

        <q-card v-if="sCaixa.ultima && !sCaixa.sessao" flat bordered>
          <q-card-section class="row items-center">
            <div class="col">
              <div class="text-subtitle2">Borderô do último fechamento</div>
              <div class="text-caption text-grey-7">
                Contado R$ {{ formataNumero(sCaixa.ultima.saldofinal) }}
                <template v-if="sCaixa.ultima.conferencia">
                  · conferido por {{ sCaixa.ultima.usuarioconferencia }}
                </template>
                <template v-else> · aguardando a conferência do gerente</template>
              </div>
            </div>
            <q-btn
              flat
              round
              size="sm"
              color="grey-7"
              icon="print"
              @click="
                sCaixa.imprimirBordero(sCaixa.ultima.codportadorperiodo, sNegocio.padrao.impressora)
              "
            >
              <q-tooltip>Imprimir o borderô</q-tooltip>
            </q-btn>
            <q-btn
              flat
              round
              size="sm"
              color="grey-7"
              icon="picture_as_pdf"
              @click="sCaixa.abrirBordero(sCaixa.ultima.codportadorperiodo)"
            >
              <q-tooltip>Ver o borderô</q-tooltip>
            </q-btn>
          </q-card-section>
        </q-card>
      </template>
    </div>
  </q-page>
</template>
