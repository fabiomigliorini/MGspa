<script setup>
// Caixas (M11 doc-3): os portadores em espécie da filial — saldo, sessão da gaveta, transferências
// chegando e saindo — e as transferências entre portadores (a confirmar e histórico), com a Nova
// transferência de → para. Saldo de gaveta só depois da conferência do gerente (às cegas, M9).
import { ref, onMounted } from 'vue'
import { useQuasar } from 'quasar'
import { useCaixaStore } from 'src/stores/caixaStore'
import { formataNumero, formataTimestamp, formataCodigo } from '@components/formatters'
import MgEmptyState from '@components/MgEmptyState.vue'
import MgInput from '@components/MgInput.vue'
import MgInputValor from '@components/MgInputValor.vue'
import MgSelectPortador from '@components/MgSelectPortador.vue'

const $q = useQuasar()
const store = useCaixaStore()

const COR_ESTADO = { P: 'amber-8', E: 'green-7', C: 'red-7' }
const ESTADO = { P: 'A confirmar', E: 'Efetivada', C: 'Cancelada' }

const estadoSessao = (c) => {
  if (!c.ehGaveta) return null
  if (!c.sessao) return { label: 'Nunca aberto', cor: 'grey-6' }
  if (c.sessao.aberta) return { label: 'Aberto', cor: 'green-7' }
  if (!c.sessao.conferencia) return { label: 'Fechado, a conferir', cor: 'amber-8' }
  return { label: 'Fechado e conferido', cor: 'grey-7' }
}

// ---- nova transferência ----
const vazio = () => ({
  codportadororigem: null,
  codportadordestino: null,
  valor: null,
  observacoes: '',
})
const form = ref(vazio())
const bloqueios = ref({})

const novaTransferencia = async () => {
  form.value = vazio()
  await store.buscarCaixas()
  bloqueios.value = Object.fromEntries(
    store.caixas.filter((c) => c.bloqueio).map((c) => [c.codportador, c.bloqueio]),
  )
  store.dialogTransferir = true
}

const salvar = async () => {
  const pag = await store.transferir({
    ...form.value,
    observacoes: form.value.observacoes || null,
  })
  if (pag) store.dialogTransferir = false
}

const cancelar = (t) => {
  $q.dialog({
    title: 'Cancelar transferência',
    message: 'Desfaz os dois lançamentos. Valor diferente? Cancele e registre outra. Motivo:',
    prompt: {
      model: '',
      type: 'text',
      outlined: true,
      isValid: (v) => (v || '').trim().length >= 5,
    },
    ok: { label: 'Cancelar transferência', color: 'negative', flat: true },
    cancel: { label: 'Voltar', color: 'grey-8', flat: true },
  }).onOk((justificativa) => store.cancelar(t.codpagamento, justificativa))
}

onMounted(store.atualizar)
</script>

<template>
  <q-page>
    <div class="q-pa-md" style="max-width: 1086px; margin: auto">
      <q-tabs
        v-model="store.aba"
        align="left"
        no-caps
        class="text-grey-8 q-mb-md"
        active-color="primary"
      >
        <q-tab name="portadores" label="Portadores" icon="savings" />
        <q-tab name="transferencias" label="Transferências" icon="sync_alt">
          <q-badge v-if="store.pendentes.length" color="amber-8" floating>
            {{ store.pendentes.length }}
          </q-badge>
        </q-tab>
      </q-tabs>

      <q-tab-panels v-model="store.aba" animated keep-alive>
        <!-- portadores em espécie -->
        <q-tab-panel name="portadores" class="q-pa-none">
          <q-card bordered flat v-if="store.caixas.length">
            <q-list separator>
              <q-item v-for="c in store.caixas" :key="c.codportador">
                <q-item-section avatar>
                  <q-icon
                    :name="c.ehGaveta ? 'point_of_sale' : 'savings'"
                    :color="c.ehGaveta ? 'green-7' : 'amber-8'"
                  />
                </q-item-section>
                <q-item-section>
                  <q-item-label>{{ c.portador }}</q-item-label>
                  <q-item-label caption>
                    {{ c.filial }}
                    <template v-if="c.ehGaveta">· gaveta</template>
                    <template v-else-if="c.financeiro">· financeiro</template>
                    <template v-else>· cofre / troco</template>
                  </q-item-label>
                  <q-item-label v-if="c.ehGaveta && c.sessao" caption>
                    {{ c.sessao.aberta ? 'aberto em' : 'fechado em' }}
                    {{ formataTimestamp(c.sessao.aberta ? c.sessao.inicio : c.sessao.fim, 2) }}
                  </q-item-label>
                  <q-item-label v-if="c.chegando.quantidade" caption class="text-amber-9">
                    {{ c.chegando.quantidade }} chegando a confirmar · R$
                    {{ formataNumero(c.chegando.valor) }}
                  </q-item-label>
                  <q-item-label v-if="c.saindo.quantidade" caption class="text-amber-9">
                    {{ c.saindo.quantidade }} saindo a confirmar · R$
                    {{ formataNumero(c.saindo.valor) }}
                  </q-item-label>
                </q-item-section>
                <q-item-section side>
                  <q-item-label v-if="c.saldo !== null" class="text-weight-bold text-grey-9">
                    R$ {{ formataNumero(c.saldo) }}
                  </q-item-label>
                  <q-item-label v-else caption>
                    {{ c.ehGaveta ? 'saldo após a conferência' : '—' }}
                  </q-item-label>
                  <q-badge
                    v-if="estadoSessao(c)"
                    :color="estadoSessao(c).cor"
                    :label="estadoSessao(c).label"
                  />
                </q-item-section>
              </q-item>
            </q-list>
          </q-card>
          <MgEmptyState v-else-if="!store.carregandoCaixas" icon="savings">
            Nenhum portador em espécie nesta filial.
          </MgEmptyState>
        </q-tab-panel>

        <!-- transferências -->
        <q-tab-panel name="transferencias" class="q-pa-none">
          <template
            v-for="grupo in [
              { titulo: 'A confirmar', itens: store.pendentes },
              { titulo: 'Histórico do período', itens: store.historico },
            ]"
            :key="grupo.titulo"
          >
            <div class="text-subtitle1 q-mb-sm">{{ grupo.titulo }}</div>
            <q-card bordered flat class="q-mb-md">
              <q-list v-if="grupo.itens.length" separator>
                <q-item
                  v-for="t in grupo.itens"
                  :key="t.codpagamento"
                  :to="{ name: 'pagamento-detalhe', params: { id: t.codpagamento } }"
                >
                  <q-item-section>
                    <q-item-label :class="t.estado === 'C' ? 'text-strike text-grey-6' : ''">
                      {{ t.portadororigem }} → {{ t.portadordestino }}
                    </q-item-label>
                    <q-item-label caption>
                      {{ formataCodigo(t.codpagamento) }} · {{ formataTimestamp(t.transacao, 2) }} ·
                      {{ t.meiodescricao }} · {{ t.usuariocriacao }}
                    </q-item-label>
                    <q-item-label v-if="t.observacoes" caption>{{ t.observacoes }}</q-item-label>
                    <q-item-label v-if="t.estado === 'C'" caption class="text-negative">
                      {{ t.justificativa }}
                    </q-item-label>
                  </q-item-section>
                  <q-item-section side>
                    <q-item-label
                      class="text-weight-bold"
                      :class="t.estado === 'C' ? 'text-strike text-grey-6' : 'text-grey-9'"
                    >
                      R$ {{ formataNumero(t.total) }}
                    </q-item-label>
                    <q-badge :color="COR_ESTADO[t.estado]" :label="ESTADO[t.estado]" />
                  </q-item-section>
                  <q-item-section side v-if="t.podeConfirmar || t.podeCancelar">
                    <div class="row no-wrap">
                      <q-btn
                        v-if="t.podeConfirmar"
                        flat
                        round
                        size="sm"
                        color="grey-7"
                        icon="done"
                        :loading="store.salvando"
                        @click.prevent.stop="store.confirmar(t.codpagamento)"
                      >
                        <q-tooltip>Confirmar o recebimento</q-tooltip>
                      </q-btn>
                      <q-btn
                        v-if="t.podeCancelar"
                        flat
                        round
                        size="sm"
                        color="grey-7"
                        icon="block"
                        @click.prevent.stop="cancelar(t)"
                      >
                        <q-tooltip>Cancelar</q-tooltip>
                      </q-btn>
                    </div>
                  </q-item-section>
                </q-item>
              </q-list>
              <MgEmptyState v-else plain icon="sync_alt">Nenhuma transferência.</MgEmptyState>
            </q-card>
          </template>
        </q-tab-panel>
      </q-tab-panels>
    </div>

    <q-page-sticky position="bottom-right" :offset="[18, 18]">
      <q-btn fab icon="sync_alt" color="primary" @click="novaTransferencia">
        <q-tooltip anchor="top middle" self="bottom middle">Nova transferência</q-tooltip>
      </q-btn>
    </q-page-sticky>

    <q-dialog v-model="store.dialogTransferir">
      <q-card flat style="width: 500px; max-width: 90vw">
        <q-form @submit.prevent="salvar">
          <q-card-section class="text-h6">Nova transferência</q-card-section>
          <q-card-section>
            <div class="row q-col-gutter-md">
              <div class="col-12">
                <MgSelectPortador
                  v-model="form.codportadororigem"
                  label="De"
                  :tipos="['E', 'B']"
                  agrupar
                  :codfilial="store.filtros.codfilial"
                  :bloqueios="bloqueios"
                  autofocus
                  :rules="[(v) => !!v]"
                  lazy-rules
                />
              </div>
              <div class="col-12">
                <MgSelectPortador
                  v-model="form.codportadordestino"
                  label="Para"
                  :tipos="['E', 'B']"
                  agrupar
                  :codfilial="store.filtros.codfilial"
                  :excluir="form.codportadororigem ? [form.codportadororigem] : null"
                  :bloqueios="bloqueios"
                  :rules="[(v) => !!v]"
                  lazy-rules
                />
              </div>
              <div class="col-12">
                <MgInputValor
                  v-model="form.valor"
                  label="Valor"
                  :rules="[(v) => v > 0]"
                  lazy-rules
                />
              </div>
              <div class="col-12">
                <MgInput
                  v-model="form.observacoes"
                  label="Observação"
                  type="textarea"
                  autogrow
                  maxlength="300"
                />
              </div>
            </div>
          </q-card-section>
          <q-card-actions align="right">
            <q-btn flat label="Cancelar" color="grey-8" v-close-popup />
            <q-btn
              flat
              label="Transferir"
              color="primary"
              type="submit"
              :loading="store.salvando"
            />
          </q-card-actions>
        </q-form>
      </q-card>
    </q-dialog>
  </q-page>
</template>
