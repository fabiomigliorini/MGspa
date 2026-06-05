<script setup>
import { ref, onMounted } from 'vue'
import { api } from 'src/services/api'
import { useCadastro } from 'src/composables/useCadastro'
import MgSelectPessoa from '@components/MgSelectPessoa.vue'
import MgInputValor from '@components/MgInputValor.vue'
import MgInputData from '@components/MgInputData.vue'

const cad = useCadastro('contrato', 'codcontrato', 'Contrato')
const culturas = ref([])
const safras = ref([])
const naturezas = ref([])

const tipos = [
  { label: 'Fixo', value: 'FIXO' },
  { label: 'A fixar', value: 'FIXAR' },
  { label: 'Barter', value: 'BARTER' },
]
const moedas = [
  { label: 'R$', value: 'BRL' },
  { label: 'US$', value: 'USD' },
]
const corTipo = { FIXO: 'green-7', FIXAR: 'orange-8', BARTER: 'deep-purple-6' }

function fmt(v, dec = 0) {
  if (v === null || v === undefined || v === '') return '0'
  return Number(v).toLocaleString('pt-BR', { minimumFractionDigits: dec, maximumFractionDigits: dec })
}
function progresso(c) {
  const q = Number(c.quantidade) || 0
  return q > 0 ? Math.min(1, (Number(c.carregado) || 0) / q) : 0
}

function novo() {
  cad.abrirNovo({ tipo: 'FIXO', moeda: 'BRL' })
}

onMounted(async () => {
  const [{ data: cu }, { data: sa }, { data: nat }] = await Promise.all([
    api.get('v1/cultura'),
    api.get('v1/safra'),
    api.get('v1/natureza-operacao'),
  ])
  culturas.value = cu.data ?? cu
  safras.value = sa.data ?? sa
  naturezas.value = nat.data ?? nat
  await cad.carregar()
})
</script>

<template>
  <q-page class="q-pa-md">
    <div style="max-width: 1086px; margin: auto">
      <q-card bordered flat class="q-mb-md">
        <q-card-section class="row items-center no-wrap">
          <q-btn flat round size="sm" color="grey-7" icon="arrow_back" :to="{ name: 'home' }" />
          <q-avatar color="indigo-1" text-color="indigo-8" icon="description" class="q-ml-sm" />
          <div class="col q-ml-md">
            <div class="text-h6">Contratos de Venda</div>
            <div class="text-caption text-grey-7">Fixo · A fixar · Barter</div>
          </div>
          <q-btn flat round size="sm" color="primary" icon="add" @click="novo">
            <q-tooltip>Novo contrato</q-tooltip>
          </q-btn>
        </q-card-section>
      </q-card>

      <div v-if="cad.items.length" class="row q-col-gutter-md">
        <div v-for="c in cad.items" :key="c.codcontrato" class="col-12 col-sm-6">
          <q-card flat bordered :class="{ 'bg-grey-2': c.inativo }">
            <q-item
              clickable
              v-ripple
              :to="{ name: 'contrato-detalhe', params: { codcontrato: c.codcontrato } }"
            >
              <q-item-section avatar>
                <q-avatar :color="corTipo[c.tipo] || 'grey-7'" text-color="white" icon="description" />
              </q-item-section>
              <q-item-section>
                <q-item-label class="text-subtitle1">
                  {{ c.contrato }}
                  <q-chip
                    dense
                    square
                    :color="corTipo[c.tipo] || 'grey-7'"
                    text-color="white"
                    :label="c.tipo"
                    class="q-ml-xs"
                  />
                </q-item-label>
                <q-item-label caption>
                  {{ c.Pessoa?.fantasia || c.Pessoa?.pessoa || '—' }} · {{ c.Cultura?.cultura }}
                </q-item-label>
                <q-item-label class="q-mt-xs">
                  <q-linear-progress
                    :value="progresso(c)"
                    color="green-6"
                    track-color="grey-3"
                    size="8px"
                    rounded
                  />
                  <div class="text-caption text-grey-7 q-mt-xs">
                    {{ fmt(c.carregado) }} / {{ fmt(c.quantidade) }} sc carregadas
                  </div>
                </q-item-label>
              </q-item-section>
              <q-item-section side>
                <q-btn flat round size="sm" color="grey-7" icon="more_vert" @click.prevent.stop>
                  <q-menu>
                    <q-list style="min-width: 150px">
                      <q-item clickable v-close-popup @click="cad.editar(c)">
                        <q-item-section avatar><q-icon name="edit" /></q-item-section>
                        <q-item-section>Editar</q-item-section>
                      </q-item>
                      <q-item clickable v-close-popup @click="cad.alternarInativo(c)">
                        <q-item-section avatar>
                          <q-icon :name="c.inativo ? 'play_arrow' : 'pause'" />
                        </q-item-section>
                        <q-item-section>{{ c.inativo ? 'Ativar' : 'Inativar' }}</q-item-section>
                      </q-item>
                      <q-item clickable v-close-popup @click="cad.excluir(c)">
                        <q-item-section avatar><q-icon name="delete" /></q-item-section>
                        <q-item-section>Excluir</q-item-section>
                      </q-item>
                    </q-list>
                  </q-menu>
                </q-btn>
              </q-item-section>
            </q-item>
          </q-card>
        </div>
      </div>

      <q-banner v-else rounded class="bg-grey-2 text-grey-7">
        Nenhum contrato. Crie o primeiro com o botão <q-icon name="add" />.
      </q-banner>

      <!-- Dialog Contrato -->
      <q-dialog v-model="cad.dialog">
        <q-card bordered flat style="width: 560px; max-width: 95vw">
          <q-form @submit="cad.salvar()">
            <q-card-section class="bg-primary text-white">
              <div class="text-h6">{{ cad.isNovo ? 'Novo Contrato' : 'Editar Contrato' }}</div>
            </q-card-section>
            <q-card-section class="q-gutter-md scroll" style="max-height: 70vh">
              <div class="row q-col-gutter-md">
                <q-input v-model="cad.form.contrato" label="Nº / identificação" outlined autofocus class="col-12 col-sm-6" />
                <q-btn-toggle
                  v-model="cad.form.tipo"
                  :options="tipos"
                  no-caps
                  unelevated
                  toggle-color="primary"
                  color="grey-3"
                  text-color="grey-9"
                  class="col-12 col-sm-6 self-center"
                />
              </div>

              <MgSelectPessoa v-model="cad.form.codpessoa" label="Comprador" />

              <div class="row q-col-gutter-md">
                <q-select
                  v-model="cad.form.codcultura"
                  :options="culturas"
                  option-value="codcultura"
                  option-label="cultura"
                  emit-value
                  map-options
                  outlined
                  label="Cultura"
                  class="col-6"
                />
                <q-select
                  v-model="cad.form.codsafra"
                  :options="safras"
                  option-value="codsafra"
                  option-label="safra"
                  emit-value
                  map-options
                  outlined
                  clearable
                  label="Safra (opcional)"
                  class="col-6"
                />
              </div>

              <div class="row q-col-gutter-md">
                <MgInputValor v-model="cad.form.quantidade" :decimals="0" suffix="sc" label="Quantidade" class="col-4" />
                <MgInputValor v-model="cad.form.preco" :decimals="2" label="Preço / saca" class="col-4" />
                <q-btn-toggle
                  v-model="cad.form.moeda"
                  :options="moedas"
                  no-caps
                  unelevated
                  toggle-color="primary"
                  color="grey-3"
                  text-color="grey-9"
                  class="col-4 self-center"
                />
              </div>

              <div class="row q-col-gutter-md">
                <MgInputData v-model="cad.form.dataembarque" label="Embarque até" type="date" class="col-6" />
                <q-input v-model="cad.form.localentrega" label="Local / FOB-CIF" outlined class="col-6" />
              </div>

              <q-expansion-item icon="receipt_long" label="Dados fiscais (NF)" dense-toggle>
                <div class="q-gutter-md q-pt-sm">
                  <q-select
                    v-model="cad.form.codnaturezaoperacao"
                    :options="naturezas"
                    option-value="codnaturezaoperacao"
                    option-label="naturezaoperacao"
                    emit-value
                    map-options
                    outlined
                    clearable
                    label="Natureza da operação"
                  />
                  <MgSelectPessoa v-model="cad.form.codpessoanf" label="Emitir NF para (destinatário)" />
                  <q-input v-model="cad.form.observacaonf" label="Observações da NF" type="textarea" autogrow outlined />
                </div>
              </q-expansion-item>

              <q-input v-model="cad.form.observacao" label="Observações" type="textarea" autogrow outlined />
            </q-card-section>
            <q-card-actions align="right">
              <q-btn flat label="Cancelar" color="grey-8" v-close-popup tabindex="-1" />
              <q-btn type="submit" unelevated label="Salvar" color="primary" :loading="cad.salvando" />
            </q-card-actions>
          </q-form>
        </q-card>
      </q-dialog>
    </div>
  </q-page>
</template>
