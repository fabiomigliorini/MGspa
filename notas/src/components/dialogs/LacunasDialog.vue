<script setup>
import { ref, watch } from 'vue'
import { useQuasar } from 'quasar'
import { useInutilizacaoStore } from '../../stores/inutilizacaoStore'

const $q = useQuasar()
const inutilizacaoStore = useInutilizacaoStore()

const model = defineModel({ type: Boolean })

const grupos = ref([])
const loading = ref(false)
const justificativa = ref('Falha de sistema, saltou numeracao')
const inutilizando = ref(new Set())
const inutilizados = ref(new Set())

// A chave identifica a FAIXA, nao um numero solto
const chave = (g, f) => `${g.codfilial}-${g.serie}-${g.modelo}-${f.numeroinicial}-${f.numerofinal}`

const rotuloFaixa = (f) =>
  f.numeroinicial === f.numerofinal
    ? `Número ${f.numeroinicial}`
    : `Números ${f.numeroinicial} a ${f.numerofinal} (${f.quantidade})`

const carregar = async () => {
  grupos.value = []
  inutilizados.value = new Set()
  loading.value = true
  try {
    grupos.value = await inutilizacaoStore.detectarLacunas()
  } catch (error) {
    $q.notify({
      color: 'red-5',
      icon: 'error',
      message: error.response?.data?.message || 'Erro ao detectar lacunas',
    })
  } finally {
    loading.value = false
  }
}

/**
 * Inutiliza a faixa inteira numa chamada so.
 *
 * Antes era um POST por numero, e cada um criava uma tblnotafiscal falsa. Agora vai uma
 * requisicao por faixa e nenhuma nota e fabricada.
 */
const inutilizarFaixa = async (grupo, faixa) => {
  const k = chave(grupo, faixa)
  inutilizando.value = new Set(inutilizando.value).add(k)

  try {
    const inut = await inutilizacaoStore.inutilizar({
      codfilial: grupo.codfilial,
      modelo: grupo.modelo,
      serie: grupo.serie,
      numeroinicial: faixa.numeroinicial,
      numerofinal: faixa.numerofinal,
      justificativa: justificativa.value,
    })

    inutilizados.value = new Set(inutilizados.value).add(k)

    $q.notify({
      color: inut.homologada ? 'green-5' : 'red-5',
      icon: inut.homologada ? 'done' : 'error',
      message: `${rotuloFaixa(faixa)} — ${inut.cstat ?? ''} ${inut.xmotivo ?? ''}`,
    })
  } catch (error) {
    $q.notify({
      color: 'red-5',
      icon: 'error',
      message: `${rotuloFaixa(faixa)}: ${error.response?.data?.message || error.message}`,
    })
  } finally {
    const novoSet = new Set(inutilizando.value)
    novoSet.delete(k)
    inutilizando.value = novoSet
  }
}

watch(model, (aberto) => {
  if (aberto) carregar()
})
</script>

<template>
  <q-dialog v-model="model">
    <q-card
      flat
      style="width: 600px; max-width: 90vw; max-height: 80vh; display: flex; flex-direction: column"
    >
      <q-card-section class="bg-red-7 text-white">
        <div class="text-h6">Inutilizar Lacunas</div>
        <div class="text-caption">Números saltados na numeração (últimos 90 dias)</div>
      </q-card-section>

      <q-card-section class="q-pb-none q-mb-none">
        <q-input
          v-model="justificativa"
          label="Justificativa"
          outlined
          :rules="[(v) => v.length >= 15 || 'Mínimo 15 caracteres']"
        />
      </q-card-section>

      <q-card-section class="q-pt-none q-mt-none" style="overflow-y: auto; flex: 1; min-height: 0">
        <!-- Loading -->
        <template v-if="loading">
          <div class="text-center q-pa-lg">
            <q-spinner color="primary" size="2em" />
            <div class="text-caption q-mt-sm">Buscando lacunas...</div>
          </div>
        </template>

        <!-- Sem lacunas -->
        <template v-else-if="grupos.length === 0">
          <div class="text-center q-pa-lg text-grey-6">Nenhuma lacuna encontrada...</div>
        </template>

        <!-- Agrupado por filial/serie/modelo -->
        <template v-else>
          <template
            v-for="grupo in grupos"
            :key="`${grupo.codfilial}-${grupo.serie}-${grupo.modelo}`"
          >
            <q-item-label header class="text-weight-medium">
              {{ grupo.filial }} — Série {{ grupo.serie }} — Modelo {{ grupo.modelo }}
            </q-item-label>
            <q-list separator>
              <!-- Uma linha por FAIXA de numeros consecutivos, nao por numero solto -->
              <q-item
                v-for="faixa in grupo.faixas"
                :key="`${faixa.numeroinicial}-${faixa.numerofinal}`"
              >
                <q-item-section>
                  <q-item-label>{{ rotuloFaixa(faixa) }}</q-item-label>
                </q-item-section>
                <q-item-section side>
                  <q-icon v-if="inutilizados.has(chave(grupo, faixa))" name="done" color="green" />
                  <q-btn
                    v-else
                    flat
                    round
                    size="sm"
                    color="red-7"
                    icon="block"
                    :loading="inutilizando.has(chave(grupo, faixa))"
                    :disable="justificativa.length < 15"
                    @click="inutilizarFaixa(grupo, faixa)"
                  >
                    <q-tooltip>Inutilizar a faixa inteira</q-tooltip>
                  </q-btn>
                </q-item-section>
              </q-item>
            </q-list>
          </template>
        </template>
      </q-card-section>

      <q-card-actions align="right">
        <q-btn flat label="Fechar" color="grey-8" v-close-popup tabindex="-1" />
      </q-card-actions>
    </q-card>
  </q-dialog>
</template>
