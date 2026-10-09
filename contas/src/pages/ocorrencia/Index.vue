<script setup>
// Livro de ocorrências (TASK-205): o que o caixa cancelou, removeu, diminuiu, descontou ou
// esqueceu aberto nos PDVs monitorados. O gerente lê, conversa com o caixa e marca conferida.
import { ref, onMounted } from 'vue'
import { useOcorrenciaStore } from 'src/stores/ocorrenciaStore'
import { formataNumero, formataTimestamp } from '@components/formatters'
import { TIPO } from '@components/ocorrencia.js'
import MgEmptyState from '@components/MgEmptyState.vue'
import MgInput from '@components/MgInput.vue'

const store = useOcorrenciaStore()

const VISUAL = {
  [TIPO.ITEM_EXCLUIDO]: { icone: 'remove_shopping_cart', cor: 'orange-8' },
  [TIPO.QUANTIDADE_DIMINUIDA]: { icone: 'exposure_neg_1', cor: 'orange-8' },
  [TIPO.PRECO_DIMINUIDO]: { icone: 'trending_down', cor: 'orange-8' },
  [TIPO.PAGAMENTO_EXCLUIDO]: { icone: 'money_off', cor: 'deep-orange-7' },
  [TIPO.NEGOCIO_CANCELADO]: { icone: 'cancel', cor: 'red-6' },
  [TIPO.PAGAMENTO_ESTORNADO]: { icone: 'undo', cor: 'red-6' },
  [TIPO.VALE_ESTORNADO]: { icone: 'card_giftcard', cor: 'red-6' },
  [TIPO.DESCONTO_ACIMA]: { icone: 'percent', cor: 'purple-6' },
  [TIPO.NEGOCIO_ESQUECIDO]: { icone: 'hourglass_empty', cor: 'blue-grey-6' },
}
const visual = (oc) => VISUAL[oc.tipo] ?? { icone: 'help_outline', cor: 'grey-6' }

const urlNegocio = (codnegocio) => `${process.env.NEGOCIOS_URL}/negocio/${codnegocio}`

const carregarMais = async (index, done) => {
  await store.fetchItems(false)
  done(!store.hasMore)
}

// conferir: observação opcional (o que o caixa explicou)
const dialogConferir = ref(false)
const ocorrencia = ref(null)
const observacao = ref(null)

const abrirConferir = (oc) => {
  ocorrencia.value = oc
  observacao.value = null
  dialogConferir.value = true
}

const conferir = async () => {
  if (await store.conferir(ocorrencia.value.codocorrencia, observacao.value)) {
    dialogConferir.value = false
  }
}

onMounted(() => store.fetchItems(true))
</script>

<template>
  <q-page>
    <q-infinite-scroll @load="carregarMais" :offset="250">
      <div class="q-pa-md" style="max-width: 1086px; margin: auto">
        <div class="row items-center q-mb-sm">
          <div class="col text-subtitle1 text-grey-8">
            {{ store.total }}
            {{ store.total == 1 ? 'ocorrência' : 'ocorrências' }}
            {{ store.filters.situacao == 'pendente' ? 'a conferir' : '' }}
          </div>
          <q-btn
            flat
            round
            size="sm"
            color="primary"
            icon="refresh"
            :loading="store.loading"
            @click="store.fetchItems(true)"
          >
            <q-tooltip>Atualizar</q-tooltip>
          </q-btn>
        </div>

        <q-card v-if="store.items.length" bordered flat class="q-mb-md">
          <q-list separator>
            <q-item v-for="oc in store.items" :key="oc.codocorrencia">
              <q-item-section avatar top>
                <q-avatar :icon="visual(oc).icone" :color="visual(oc).cor" text-color="white" />
              </q-item-section>

              <q-item-section>
                <q-item-label class="text-weight-medium">{{ oc.descricao }}</q-item-label>
                <q-item-label caption>
                  {{ oc.tipodescricao }}
                  <template v-if="oc.motivodescricao"> · {{ oc.motivodescricao }}</template>
                  <template v-if="oc.justificativa"> · “{{ oc.justificativa }}”</template>
                </q-item-label>
                <q-item-label caption>
                  {{ oc.usuario ?? 'sem usuário' }}
                  <template v-if="oc.pdv"> · {{ oc.pdv }}</template>
                  · {{ oc.filial }} · {{ formataTimestamp(oc.criacao) }}
                </q-item-label>
                <q-item-label v-if="oc.conferencia" caption class="text-green-8">
                  <q-icon name="task_alt" />
                  Conferida por {{ oc.usuarioconferencia }} em
                  {{ formataTimestamp(oc.conferencia) }}
                  <template v-if="oc.observacao"> · “{{ oc.observacao }}”</template>
                </q-item-label>
              </q-item-section>

              <q-item-section side top>
                <q-item-label class="text-weight-bold text-grey-9">
                  R$ {{ formataNumero(oc.valor) }}
                </q-item-label>
                <div class="q-mt-xs">
                  <q-btn
                    v-if="oc.codnegocio"
                    flat
                    round
                    size="sm"
                    color="grey-7"
                    icon="open_in_new"
                    :href="urlNegocio(oc.codnegocio)"
                    target="_blank"
                  >
                    <q-tooltip>Abrir o negócio #{{ oc.codnegocio }}</q-tooltip>
                  </q-btn>
                  <q-btn
                    v-if="oc.conferencia"
                    flat
                    round
                    size="sm"
                    color="grey-7"
                    icon="undo"
                    :loading="store.salvando"
                    @click="store.reabrir(oc.codocorrencia)"
                  >
                    <q-tooltip>Reabrir</q-tooltip>
                  </q-btn>
                  <q-btn
                    v-else
                    flat
                    round
                    size="sm"
                    color="green-7"
                    icon="done"
                    @click="abrirConferir(oc)"
                  >
                    <q-tooltip>Conferir</q-tooltip>
                  </q-btn>
                </div>
              </q-item-section>
            </q-item>
          </q-list>
        </q-card>

        <MgEmptyState v-if="!store.loading && !store.items.length" icon="task_alt">
          {{
            store.filters.situacao == 'pendente'
              ? 'Nenhuma ocorrência a conferir.'
              : 'Nenhuma ocorrência.'
          }}
        </MgEmptyState>
      </div>
    </q-infinite-scroll>

    <q-dialog v-model="dialogConferir">
      <q-card flat style="width: 400px; max-width: 95vw">
        <q-form @submit.prevent="conferir">
          <q-card-section class="text-h6">Conferir ocorrência</q-card-section>
          <q-card-section class="q-pt-none">
            <div class="q-mb-md">{{ ocorrencia?.descricao }}</div>
            <MgInput
              v-model="observacao"
              label="Observação (o que o caixa explicou)"
              maxlength="500"
              autofocus
            />
          </q-card-section>
          <q-card-actions align="right">
            <q-btn flat label="Cancelar" color="grey-8" tabindex="-1" v-close-popup />
            <q-btn flat label="Conferir" color="primary" type="submit" :loading="store.salvando" />
          </q-card-actions>
        </q-form>
      </q-card>
    </q-dialog>
  </q-page>
</template>
