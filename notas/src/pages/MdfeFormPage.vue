<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import {
  useMdfeStore,
  TIPO_EMITENTE_OPTIONS,
  TIPO_TRANSPORTADOR_OPTIONS,
} from '../stores/mdfeStore'
import mdfeService from '../services/mdfeService'
import veiculoService from '../services/veiculoService'
import { notificarSucesso, notificarErro } from '../utils/notify'
import { formataCodigo } from '@components/formatters'
import MgInputData from '@components/MgInputData.vue'
import MgSelectFilial from '@components/MgSelectFilial.vue'
import MgSelectCidade from '@components/MgSelectCidade.vue'
import MgSelectEstado from '@components/MgSelectEstado.vue'
import SelectPessoa from '@components/MgSelectPessoa.vue'

const router = useRouter()
const route = useRoute()
const store = useMdfeStore()

const codmdfe = computed(() => route.params.codmdfe)
const loading = ref(false)
const veiculoOptions = ref([])

const form = ref({
  codfilial: null,
  tipoemitente: null,
  tipotransportador: null,
  emissao: null,
  inicioviagem: null,
  codcidadecarregamento: null,
  codestadofim: null,
  informacoesadicionais: null,
  informacoescomplementares: null,
  MdfeVeiculoS: [],
})

const carregar = async () => {
  loading.value = true
  try {
    const [mdfe, veiculos] = await Promise.all([
      store.fetchMdfe(codmdfe.value),
      veiculoService.selectVeiculos(),
    ])
    veiculoOptions.value = veiculos
    form.value = {
      codfilial: mdfe.codfilial,
      tipoemitente: mdfe.tipoemitente,
      tipotransportador: mdfe.tipotransportador,
      emissao: mdfe.emissao,
      inicioviagem: mdfe.inicioviagem,
      codcidadecarregamento: mdfe.codcidadecarregamento,
      codestadofim: mdfe.codestadofim,
      informacoesadicionais: mdfe.informacoesadicionais,
      informacoescomplementares: mdfe.informacoescomplementares,
      MdfeVeiculoS: (mdfe.MdfeVeiculoS || []).map((v) => ({
        codveiculo: v.codveiculo,
        codpessoacondutor: v.codpessoacondutor,
      })),
    }
  } catch (error) {
    notificarErro(error, 'Falha ao carregar MDFe')
    router.push({ name: 'mdfe' })
  } finally {
    loading.value = false
  }
}

onMounted(carregar)

const addVeiculo = () =>
  form.value.MdfeVeiculoS.push({ codveiculo: null, codpessoacondutor: null })
const delVeiculo = (index) => form.value.MdfeVeiculoS.splice(index, 1)

const submit = async () => {
  loading.value = true
  try {
    const salvo = await mdfeService.update(codmdfe.value, form.value)
    store.upsertMdfe(salvo)
    notificarSucesso('MDFe atualizado!')
    router.push({ name: 'mdfe-view', params: { codmdfe: codmdfe.value } })
  } catch (error) {
    notificarErro(error, 'Falha ao salvar MDFe')
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <q-page class="q-pa-md">
    <div style="max-width: 800px; margin: auto">
      <div class="row items-center q-mb-md">
        <q-btn
          flat
          round
          icon="arrow_back"
          :to="{ name: 'mdfe-view', params: { codmdfe } }"
          :disable="loading"
        />
        <div class="text-h5 q-ml-sm">Editar MDFe {{ formataCodigo(codmdfe) }}</div>
      </div>

      <q-form @submit.prevent="submit">
        <q-card flat bordered class="q-mb-md">
          <q-card-section class="bg-primary text-white text-body2 text-weight-bold">
            Emitente e Tipo
          </q-card-section>
          <q-card-section class="q-gutter-md">
            <MgSelectFilial
              v-model="form.codfilial"
              label="Filial *"
              clearable
              :bottom-slots="false"
            />
            <q-select
              v-model="form.tipoemitente"
              label="Tipo de Emitente *"
              outlined
              :options="TIPO_EMITENTE_OPTIONS"
              option-value="value"
              option-label="label"
              emit-value
              map-options
            />
            <q-select
              v-model="form.tipotransportador"
              label="Tipo de Transportador *"
              outlined
              :options="TIPO_TRANSPORTADOR_OPTIONS"
              option-value="value"
              option-label="label"
              emit-value
              map-options
            />
          </q-card-section>
        </q-card>

        <q-card flat bordered class="q-mb-md">
          <q-card-section class="bg-primary text-white text-body2 text-weight-bold">
            Viagem
          </q-card-section>
          <q-card-section class="q-gutter-md">
            <div class="row q-col-gutter-md">
              <div class="col-12 col-sm-6">
                <MgInputData v-model="form.emissao" type="timestamp" label="Emissão *" />
              </div>
              <div class="col-12 col-sm-6">
                <MgInputData
                  v-model="form.inicioviagem"
                  type="timestamp"
                  label="Início da Viagem *"
                />
              </div>
            </div>
            <MgSelectCidade
              v-model="form.codcidadecarregamento"
              label="Cidade de Carregamento *"
              clearable
              :bottom-slots="false"
            />
            <MgSelectEstado
              v-model="form.codestadofim"
              label="Estado Fim da Viagem *"
              clearable
              :bottom-slots="false"
            />
          </q-card-section>
        </q-card>

        <q-card flat bordered class="q-mb-md">
          <q-card-section class="bg-primary text-white text-body2 text-weight-bold">
            Veículos
          </q-card-section>
          <q-card-section class="q-gutter-md">
            <div
              v-for="(veiculo, index) in form.MdfeVeiculoS"
              :key="index"
              class="row q-col-gutter-sm items-start"
            >
              <div class="col-12 col-sm-5">
                <q-select
                  v-model="veiculo.codveiculo"
                  label="Veículo"
                  outlined
                  :options="veiculoOptions"
                  option-value="value"
                  option-label="label"
                  emit-value
                  map-options
                />
              </div>
              <div class="col">
                <SelectPessoa
                  v-model="veiculo.codpessoacondutor"
                  label="Condutor"
                  :bottom-slots="false"
                />
              </div>
              <div class="col-auto">
                <q-btn flat round size="sm" color="grey-7" icon="delete" @click="delVeiculo(index)" />
              </div>
            </div>
            <q-btn flat color="primary" icon="add" label="Adicionar Veículo" @click="addVeiculo" />
          </q-card-section>
        </q-card>

        <q-card flat bordered class="q-mb-md">
          <q-card-section class="bg-primary text-white text-body2 text-weight-bold">
            Informações
          </q-card-section>
          <q-card-section class="q-gutter-md">
            <q-input
              v-model="form.informacoesadicionais"
              label="Informações Adicionais"
              type="textarea"
              outlined
              autogrow
            />
            <q-input
              v-model="form.informacoescomplementares"
              label="Informações Complementares"
              type="textarea"
              outlined
              autogrow
            />
          </q-card-section>
        </q-card>

        <q-page-sticky position="bottom-right" :offset="[18, 18]">
          <q-btn fab icon="save" color="primary" type="submit" :loading="loading">
            <q-tooltip>Salvar</q-tooltip>
          </q-btn>
        </q-page-sticky>
      </q-form>
    </div>
  </q-page>
</template>
