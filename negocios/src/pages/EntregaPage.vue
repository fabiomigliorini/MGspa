<script setup>
import { onMounted, watch } from 'vue'
import { storeToRefs } from 'pinia'
import MgEmptyState from '@components/MgEmptyState.vue'
import { formataCodigo, formataReal, tempoRelativo } from '@components/formatters'
import SelectEstoqueLocal from 'components/selects/SelectEstoqueLocal.vue'
import { entregaStore, RESULTADOS, MOTIVOS } from 'stores/entrega'
import { negocioStore } from 'stores/negocio'

// Entregas (TASK-55, doc-6): o que a loja tem para entregar (é a origem: retirada e entrega
// ao cliente) e o que tem para receber (é o destino: reposição e transferência).
const sEntrega = entregaStore()
const sNegocio = negocioStore()
const { lado, codestoquelocal } = storeToRefs(sEntrega)

const LADOS = [
  { value: 'a-fazer', label: 'Entregas a fazer' },
  { value: 'a-receber', label: 'A receber' },
]

const ultima = (n) => {
  if (!n.ultimoresultado) return null
  const r = RESULTADOS.find((x) => x.value === n.ultimoresultado)
  const m = (MOTIVOS[n.ultimoresultado] || []).find((x) => x.value === n.ultimomotivo)
  return `${r?.label}${m ? ' · ' + m.label : ''} ${tempoRelativo(n.ultimadata)}`
}

watch([lado, codestoquelocal], () => sEntrega.carregarPendentes())

onMounted(() => {
  if (!codestoquelocal.value) codestoquelocal.value = sNegocio.padrao.codestoquelocal
  sEntrega.carregarPendentes()
})
</script>

<template>
  <q-page class="q-pa-md">
    <div style="max-width: 1086px; margin: auto">
      <q-card flat bordered class="q-mb-md">
        <q-card-section>
          <div class="row q-col-gutter-md items-center">
            <div class="col-12 col-sm-6">
              <SelectEstoqueLocal
                v-model="codestoquelocal"
                label="Loja"
                outlined
                somente-controla-estoque
              />
            </div>
            <div class="col-12 col-sm-6">
              <q-option-group v-model="lado" :options="LADOS" type="radio" inline />
            </div>
          </div>
        </q-card-section>
      </q-card>

      <MgEmptyState
        v-if="!sEntrega.carregandoPendentes && !sEntrega.pendentes.length"
        icon="local_shipping"
      >
        Nada {{ lado === 'a-fazer' ? 'para entregar' : 'para receber' }} nesta loja.
      </MgEmptyState>

      <q-card v-else flat bordered>
        <q-list separator>
          <q-item
            v-for="n in sEntrega.pendentes"
            :key="n.codnegocio"
            clickable
            :to="`/negocio/${n.codnegocio}`"
          >
            <q-item-section>
              <q-item-label class="ellipsis">
                {{ formataCodigo(n.codnegocio) }} · {{ n.siglaorigem }}
                <template v-if="n.sigladestino"> para {{ n.sigladestino }}</template>
                · {{ n.fantasia }}
              </q-item-label>
              <q-item-label caption class="ellipsis">
                {{ n.naturezaoperacao }} · {{ formataReal(n.valortotal) }} ·
                {{ tempoRelativo(n.lancamento) }}
              </q-item-label>
              <q-item-label caption class="ellipsis text-deep-orange-7" v-if="ultima(n)">
                {{ ultima(n) }}
              </q-item-label>
            </q-item-section>
            <q-item-section side>
              <q-icon name="chevron_right" />
            </q-item-section>
          </q-item>
        </q-list>
      </q-card>
    </div>
  </q-page>
</template>
