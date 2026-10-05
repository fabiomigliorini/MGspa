<script setup>
// Sessão da gaveta (M9 doc-3): o caixa fechou no PDV e subiu com o dinheiro e o borderô. O gerente
// conta o dinheiro e digita às cegas; depois de conferido aparecem o sistema, a contagem do caixa
// e as diferenças.
import { ref, computed, onMounted, watch } from 'vue'
import { useRoute } from 'vue-router'
import { useQuasar } from 'quasar'
import { formataNumero, formataTimestamp } from '@components/formatters'
import MgInput from '@components/MgInput.vue'
import MgInputValor from '@components/MgInputValor.vue'
import MgInfoCriacao from '@components/MgInfoCriacao.vue'
import { useConferenciaStore } from 'src/stores/conferenciaStore'

const route = useRoute()
const $q = useQuasar()
const store = useConferenciaStore()

const id = computed(() => Number(route.params.id))
const sessao = computed(() => store.sessao)
const conferida = computed(() => !!sessao.value?.conferencia)

const DOCUMENTOS = { V: 'Vendas', T: 'Títulos (notinhas, vales, adiantamentos)', A: 'Avulsos' }

const form = ref({ valorconferido: null, observacoes: '' })

const dif = (a, b) => Math.round(((a ?? 0) - (b ?? 0)) * 100) / 100
const corDiferenca = (v) => (Math.abs(v) < 0.005 ? 'text-green-8' : 'text-red-8')

async function carregar() {
  await store.carregarSessao(id.value)
  form.value = { valorconferido: null, observacoes: '' }
}

async function conferir() {
  await store.conferirSessao(id.value, {
    valorconferido: form.value.valorconferido ?? 0,
    observacoes: form.value.observacoes || null,
  })
}

function reabrir(caixa) {
  $q.dialog({
    title: caixa ? 'Reabrir o caixa' : 'Reabrir a conferência',
    message: caixa
      ? 'O caixa volta a ficar aberto no PDV e aceita dinheiro de novo. Continuar?'
      : 'A conferência é desfeita para corrigir os lançamentos. Continuar?',
    cancel: { label: 'Cancelar', color: 'grey-8', flat: true },
    ok: { label: 'Reabrir', color: 'primary', flat: true },
  }).onOk(() => store.reabrirSessao(id.value, caixa))
}

onMounted(carregar)
watch(id, carregar)
</script>

<template>
  <q-page>
    <div class="q-pa-md" style="max-width: 1086px; margin: auto">
      <q-btn
        flat
        round
        icon="arrow_back"
        :to="{ name: 'fechamento' }"
        aria-label="Voltar"
        class="q-mb-sm"
      />

      <template v-if="sessao">
        <q-card bordered flat class="q-mb-md">
          <q-card-section>
            <div class="row items-center">
              <div class="col">
                <div class="text-h6">{{ sessao.portador }}</div>
                <div class="text-caption text-grey-7">
                  Aberto por {{ sessao.usuarioabertura }} em
                  {{ formataTimestamp(sessao.inicio, 2) }}
                  <template v-if="sessao.fim">
                    · fechado por {{ sessao.usuariofechamento }} em
                    {{ formataTimestamp(sessao.fim, 2) }}
                  </template>
                </div>
              </div>
              <q-badge
                :color="conferida ? 'green-7' : sessao.aberta ? 'grey-6' : 'amber-8'"
                :label="conferida ? 'Conferido' : sessao.aberta ? 'Em andamento' : 'A conferir'"
              />
            </div>
          </q-card-section>

          <q-card-section v-if="sessao.aberta" class="text-grey-8">
            O caixa ainda está aberto no PDV: a conferência é depois que o caixa fechar.
          </q-card-section>

          <!-- às cegas: conta o dinheiro antes de ver o sistema -->
          <q-form v-else-if="!conferida" @submit.prevent="conferir">
            <q-card-section>
              <div class="row q-col-gutter-md">
                <div class="col-12 col-sm-6">
                  <MgInputValor v-model="form.valorconferido" label="Dinheiro contado" autofocus />
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
              <q-btn
                unelevated
                color="primary"
                icon="done_all"
                label="Conferir"
                type="submit"
                :loading="store.salvando"
              />
            </q-card-actions>
          </q-form>

          <template v-else>
            <q-card-section>
              <q-markup-table flat bordered separator="horizontal">
                <tbody>
                  <tr>
                    <td>Saldo inicial (abertura)</td>
                    <td class="text-right">{{ formataNumero(sessao.saldoinicial) }}</td>
                  </tr>
                  <tr v-for="d in sessao.resumo.dinheiro.documentos" :key="d.documento">
                    <td>{{ DOCUMENTOS[d.documento] }} ({{ d.quantidade }})</td>
                    <td class="text-right">{{ formataNumero(d.entrada - d.saida) }}</td>
                  </tr>
                  <tr class="text-weight-bold">
                    <td>Sistema</td>
                    <td class="text-right">{{ formataNumero(sessao.resumo.dinheiro.sistema) }}</td>
                  </tr>
                  <tr>
                    <td>Contado pelo caixa</td>
                    <td class="text-right">
                      {{ formataNumero(sessao.saldofinal) }}
                      <span
                        :class="
                          corDiferenca(dif(sessao.saldofinal, sessao.resumo.dinheiro.sistema))
                        "
                      >
                        ({{
                          formataNumero(dif(sessao.saldofinal, sessao.resumo.dinheiro.sistema))
                        }})
                      </span>
                    </td>
                  </tr>
                  <tr class="text-weight-bold">
                    <td>Conferido por {{ sessao.usuarioconferencia }}</td>
                    <td class="text-right">
                      {{ formataNumero(sessao.valorconferido) }}
                      <span
                        :class="
                          corDiferenca(dif(sessao.valorconferido, sessao.resumo.dinheiro.sistema))
                        "
                      >
                        ({{
                          formataNumero(dif(sessao.valorconferido, sessao.resumo.dinheiro.sistema))
                        }})
                      </span>
                    </td>
                  </tr>
                </tbody>
              </q-markup-table>
              <div v-if="sessao.observacoes" class="text-caption text-grey-7 q-mt-sm">
                {{ sessao.observacoes }}
              </div>
            </q-card-section>
            <q-card-section v-if="sessao.resumo.informativo.meios.length" class="q-pt-none">
              <div class="text-caption text-grey-7">
                Outros meios nos PDVs deste caixa (informação):
                <span v-for="m in sessao.resumo.informativo.meios" :key="m.meio" class="q-mr-md">
                  {{ m.descricao }} {{ formataNumero(m.valor) }} ({{ m.quantidade }})
                </span>
              </div>
            </q-card-section>
            <q-card-actions align="right">
              <q-btn
                flat
                color="primary"
                icon="lock_open"
                label="Reabrir conferência"
                @click="reabrir(false)"
              />
              <q-btn
                flat
                color="primary"
                icon="point_of_sale"
                label="Reabrir caixa"
                @click="reabrir(true)"
              />
            </q-card-actions>
          </template>
        </q-card>

        <MgInfoCriacao :registro="sessao" />
      </template>
    </div>
  </q-page>
</template>
