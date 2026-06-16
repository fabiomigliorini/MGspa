<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import { useRoute } from 'vue-router'
import { api } from 'src/services/api'
import { formataNumero, formataData, formataCodigo, formataCnpjCpf } from '@components/formatters'
import { notifyError } from 'src/utils/notify'
import { chequeStatusLabel, chequeStatusColor } from 'src/constants/chequeStatus'
import MgInfoCriacao from '@components/MgInfoCriacao.vue'

const route = useRoute()

const cheque = ref(null)
const loading = ref(false)

const codcheque = computed(() => (route.params.codcheque ? Number(route.params.codcheque) : null))

const emitentes = computed(() => cheque.value?.cheque_emitente_s || [])
const repasses = computed(() =>
  (cheque.value?.cheque_repasse_cheque_s || [])
    .map((pivot) => ({
      compensacao: pivot.compensacao,
      repasse: pivot.cheque_repasse,
    }))
    .filter((r) => r.repasse)
    .sort((a, b) => (b.repasse.codchequerepasse ?? 0) - (a.repasse.codchequerepasse ?? 0)),
)

const urlPessoa = (codpessoa) =>
  codpessoa ? `${process.env.PESSOAS_URL}/pessoa/${codpessoa}` : null

async function carregar() {
  if (!codcheque.value) return
  loading.value = true
  try {
    const { data } = await api.get(`v1/cheque/${codcheque.value}`)
    cheque.value = data
  } catch (e) {
    notifyError(e, 'Erro ao carregar cheque')
    cheque.value = null
  } finally {
    loading.value = false
  }
}

onMounted(carregar)
watch(() => route.fullPath, carregar)
</script>

<template>
  <q-page class="q-pa-md">
    <div style="max-width: 1000px; margin: auto">
      <template v-if="cheque">
        <!-- Cabeçalho -->
        <q-item class="q-pb-md q-px-none">
          <q-item-section avatar>
            <q-btn
              flat
              dense
              round
              icon="arrow_back"
              :to="{ name: 'cheque' }"
              aria-label="Voltar"
            />
          </q-item-section>
          <q-item-section>
            <div class="text-h4 text-grey-9 ellipsis">
              Cheque {{ formataCodigo(cheque.codcheque) }}
            </div>
            <div class="text-subtitle2 text-grey-7 ellipsis">
              {{ cheque.pessoa?.pessoa }}
            </div>
          </q-item-section>
          <q-item-section side>
            <q-btn
              flat
              round
              icon="edit"
              color="grey-7"
              :to="{ name: 'cheque-editar', params: { codcheque: cheque.codcheque } }"
            >
              <q-tooltip>Editar</q-tooltip>
            </q-btn>
          </q-item-section>
        </q-item>

        <!-- Cards resumo -->
        <div class="row q-col-gutter-md q-mb-md">
          <div class="col-xs-6 col-sm-3">
            <q-card bordered flat class="q-py-sm text-center">
              <div class="text-caption text-grey-7">Banco</div>
              <div class="text-h6 text-grey-8 ellipsis">{{ cheque.banco?.banco }}</div>
              <div class="text-caption text-grey-7">Ag. {{ cheque.agencia }}</div>
            </q-card>
          </div>
          <div class="col-xs-6 col-sm-3">
            <q-card bordered flat class="q-py-sm text-center">
              <div class="text-caption text-grey-7">Valor</div>
              <div class="text-h6 text-weight-bold">R$ {{ formataNumero(cheque.valor) }}</div>
            </q-card>
          </div>
          <div class="col-xs-6 col-sm-3">
            <q-card bordered flat class="q-py-sm text-center">
              <div class="text-caption text-grey-7">Vencimento</div>
              <div class="text-h6 text-grey-8">{{ formataData(cheque.vencimento) }}</div>
              <div class="text-caption text-grey-7">Emissão {{ formataData(cheque.emissao) }}</div>
            </q-card>
          </div>
          <div class="col-xs-6 col-sm-3">
            <q-card bordered flat class="q-py-sm text-center">
              <div class="text-caption text-grey-7">Status</div>
              <div class="q-mt-xs">
                <q-badge :color="chequeStatusColor(cheque.indstatus)">
                  {{ chequeStatusLabel(cheque.indstatus) }}
                </q-badge>
              </div>
            </q-card>
          </div>
        </div>

        <!-- Detalhes -->
        <q-card bordered flat>
          <q-card-section class="text-grey-9 text-overline row items-center">
            DETALHES DO CHEQUE
            <q-space />
            <q-btn
              flat
              round
              dense
              icon="edit"
              size="sm"
              color="grey-7"
              :to="{ name: 'cheque-editar', params: { codcheque: cheque.codcheque } }"
            >
              <q-tooltip>Editar</q-tooltip>
            </q-btn>
            <MgInfoCriacao :registro="cheque" />
          </q-card-section>

          <div class="row q-col-gutter-lg q-pa-md">
            <div class="col-xs-6 col-sm-4 col-md-2">
              <div class="text-overline text-grey-7">#</div>
              <div class="text-body2">{{ formataCodigo(cheque.codcheque) }}</div>
            </div>
            <div class="col-xs-6 col-sm-4 col-md-3">
              <div class="text-overline text-grey-7">Banco</div>
              <div class="text-body2">{{ cheque.banco?.banco }} ({{ cheque.codbanco }})</div>
            </div>
            <div class="col-xs-6 col-sm-4 col-md-2">
              <div class="text-overline text-grey-7">Agência</div>
              <div class="text-body2">{{ cheque.agencia }}</div>
            </div>
            <div class="col-xs-6 col-sm-4 col-md-3">
              <div class="text-overline text-grey-7">Conta Corrente</div>
              <div class="text-body2">{{ cheque.contacorrente }}</div>
            </div>
            <div class="col-xs-6 col-sm-4 col-md-2">
              <div class="text-overline text-grey-7">Número</div>
              <div class="text-body2">{{ cheque.numero }}</div>
            </div>
            <div class="col-12 col-md-7">
              <div class="text-overline text-grey-7">CMC7</div>
              <div class="text-body2" style="word-break: break-all">{{ cheque.cmc7 }}</div>
            </div>
            <div class="col-xs-6 col-sm-4 col-md-5">
              <div class="text-overline text-grey-7">Cliente</div>
              <div class="text-body2">
                <q-btn
                  v-if="cheque.codpessoa"
                  flat
                  dense
                  no-caps
                  padding="0"
                  color="primary"
                  :label="cheque.pessoa?.pessoa"
                  :href="urlPessoa(cheque.codpessoa)"
                  target="_blank"
                />
              </div>
            </div>
            <div class="col-xs-6 col-sm-4 col-md-3">
              <div class="text-overline text-grey-7">Emissão</div>
              <div class="text-body2">{{ formataData(cheque.emissao) }}</div>
            </div>
            <div class="col-xs-6 col-sm-4 col-md-3">
              <div class="text-overline text-grey-7">Vencimento</div>
              <div class="text-body2">{{ formataData(cheque.vencimento) }}</div>
            </div>
            <div class="col-xs-6 col-sm-4 col-md-3" v-if="cheque.repasse">
              <div class="text-overline text-grey-7">Data Repasse</div>
              <div class="text-body2">{{ formataData(cheque.repasse) }}</div>
            </div>
            <div class="col-xs-6 col-sm-4 col-md-3" v-if="cheque.destino">
              <div class="text-overline text-grey-7">Destino</div>
              <div class="text-body2">{{ cheque.destino }}</div>
            </div>
          </div>
        </q-card>

        <!-- Observação -->
        <q-card bordered flat class="q-mt-md" v-if="cheque.observacao">
          <div class="row q-col-gutter-sm q-pa-md">
            <div class="col-12">
              <div class="text-overline text-grey-7">Observações</div>
              <div
                class="text-body2 bg-grey-2 rounded-borders q-pa-sm"
                style="white-space: pre-line"
              >
                {{ cheque.observacao }}
              </div>
            </div>
          </div>
        </q-card>

        <div class="row q-col-gutter-md">
          <!-- Emitentes -->
          <div class="col-xs-12 col-sm-6">
            <q-card bordered flat class="q-mt-md">
              <q-card-section class="text-grey-9 text-overline">
                EMITENTES ({{ emitentes.length }})
              </q-card-section>
              <q-list separator v-if="emitentes.length">
                <q-item v-for="emit in emitentes" :key="emit.codchequeemitente">
                  <q-item-section>
                    <q-item-label class="text-weight-medium">{{ emit.emitente }}</q-item-label>
                    <q-item-label caption v-if="emit.cnpj">
                      CNPJ/CPF {{ formataCnpjCpf(emit.cnpj) }}
                    </q-item-label>
                  </q-item-section>
                </q-item>
              </q-list>
              <q-card-section v-else class="text-center text-grey-6 q-pa-md">
                Nenhum emitente
              </q-card-section>
            </q-card>
          </div>

          <!-- Repasse -->
          <div class="col-xs-12 col-sm-6">
            <q-card bordered flat class="q-mt-md">
              <q-card-section class="text-grey-9 text-overline">
                REPASSE ({{ repasses.length }})
              </q-card-section>
              <q-list separator v-if="repasses.length">
                <q-item
                  v-for="r in repasses"
                  :key="r.repasse.codchequerepasse"
                  :to="{
                    name: 'cheque-repasse-detalhe',
                    params: { codchequerepasse: r.repasse.codchequerepasse },
                  }"
                  :class="{ 'bg-grey-2': !!r.repasse.inativo }"
                >
                  <q-item-section>
                    <q-item-label class="text-primary text-weight-bold">
                      Repasse {{ formataCodigo(r.repasse.codchequerepasse) }}
                      <q-badge v-if="r.repasse.inativo" color="orange-7" class="q-ml-xs">
                        Inativo
                      </q-badge>
                    </q-item-label>
                    <q-item-label caption>{{ r.repasse.portador?.portador }}</q-item-label>
                    <q-item-label caption v-if="r.repasse.observacoes">
                      {{ r.repasse.observacoes }}
                    </q-item-label>
                  </q-item-section>
                  <q-item-section side>
                    <q-item-label class="text-weight-medium">
                      {{ formataData(r.repasse.data) }}
                    </q-item-label>
                    <q-item-label caption v-if="r.compensacao">
                      Comp. {{ formataData(r.compensacao) }}
                    </q-item-label>
                  </q-item-section>
                </q-item>
              </q-list>
              <q-card-section v-else class="text-center text-grey-6 q-pa-md">
                Cheque ainda não repassado
              </q-card-section>
            </q-card>
          </div>
        </div>

        <!-- Título vinculado (cobrança / liquidação) -->
        <q-card bordered flat class="q-mt-md" v-if="cheque.codtitulo">
          <q-card-section class="text-grey-9 text-overline">TÍTULO VINCULADO</q-card-section>
          <q-list>
            <q-item :to="{ name: 'titulo-detalhe', params: { codtitulo: cheque.codtitulo } }">
              <q-item-section>
                <q-item-label class="text-primary text-weight-bold">
                  Título {{ formataCodigo(cheque.codtitulo) }}
                  <span v-if="cheque.titulo?.numero">— {{ cheque.titulo.numero }}</span>
                </q-item-label>
                <q-item-label caption v-if="cheque.titulo?.vencimento">
                  Vencimento {{ formataData(cheque.titulo.vencimento) }}
                </q-item-label>
              </q-item-section>
              <q-item-section side v-if="cheque.titulo?.valor != null">
                <q-item-label class="text-weight-medium">
                  R$ {{ formataNumero(Math.abs(cheque.titulo.valor)) }}
                </q-item-label>
              </q-item-section>
            </q-item>
          </q-list>
        </q-card>
      </template>
    </div>

    <q-inner-loading :showing="loading" color="primary" />
  </q-page>
</template>
