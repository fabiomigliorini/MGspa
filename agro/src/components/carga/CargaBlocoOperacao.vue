<script setup>
// Bloco "Operação": o tipo de romaneio como manchete (é o que a carga É) e a
// ficha do caminhão logo abaixo. Dois controles com atrito diferente de
// propósito — o toggle troca a operação (com confirmação depois da 1ª pesagem,
// quem decide isso é o CargaForm em `trocarOperacao`); o lápis abre o dialog
// dos campos do caminhão.
import { ref, computed, inject } from 'vue'
import { storeToRefs } from 'pinia'
import { useCargaStore } from 'src/stores/carga'
import { useSincronizacaoStore } from 'src/stores/sincronizacao'
import { agoraLocal, cargaFinalizada } from 'src/utils/carga'
import { formataTimestamp } from '@components/formatters'
import MgInputData from '@components/MgInputData.vue'
import MgSelectPessoa from '@components/MgSelectPessoa.vue'
import CaminhaoDialog from 'components/CaminhaoDialog.vue'
import SelectSentido from './SelectSentido.vue'

const props = defineProps({
  carga: { type: Object, required: true },
  novo: { type: Boolean, default: false },
})

const store = useCargaStore()
const { veiculosAtivos } = storeToRefs(store)
const { online } = storeToRefs(useSincronizacaoStore())

// Provido pelo CargaForm.vue — reaproveita o MESMO caminho de persistência que
// "salvar sem avançar" já usava (troca de safra + erro tratado na página); este
// bloco não importa a store pra persistir, só pra dado de apoio (placa/veículo).
const persistirBloco = inject('persistirBloco')
// Troca do tipo de romaneio — a confirmação mora LÁ (CargaForm), não aqui: a
// guarda não pode depender de quem chama.
const trocarOperacao = inject('trocarOperacao')

// Indireção (padrão ContratoForm/SafraForm): muta o objeto reativo compartilhado
// sem disparar vue/no-mutating-props — `carga` é a MESMA referência que o
// CargaForm.vue conhece como `local`, a mutação já é o mecanismo de sincronismo.
const carga = computed(() => props.carga)

// Máximo do campo de chegada = agora (não deixa lançar no futuro).
const dataMax = agoraLocal()

// Finalizada: o romaneio fechado não muda mais de operação (TASK-141 abriu a
// troca ATÉ finalizar, não depois).
const finalizada = computed(() => cargaFinalizada(carga.value))

const dialogAberto = ref(false)
const edicao = ref({})
const placaOptions = ref([])
const placaBusca = ref('')
const cadastroCaminhao = ref(false)

function abrir() {
  edicao.value = {
    placa: carga.value.placa,
    codveiculo: carga.value.codveiculo,
    placacarreta: carga.value.placacarreta,
    codpessoamotorista: carga.value.codpessoamotorista,
    motorista: carga.value.motorista,
    data: carga.value.data,
  }
  placaBusca.value = ''
  dialogAberto.value = true
}

function filtrarPlaca(val, update) {
  placaBusca.value = (val || '').toUpperCase()
  update(() => {
    const termo = placaBusca.value
    placaOptions.value = veiculosAtivos.value
      .filter((v) => (v.placa || '').toUpperCase().includes(termo))
      .slice(0, 50)
      .map((v) => ({ label: v.placa, value: v.placa }))
  })
}
function resolverPlaca(placa) {
  const p = (placa || '').toUpperCase() || null
  edicao.value.placa = p
  edicao.value.codveiculo = p
    ? veiculosAtivos.value.find((v) => (v.placa || '').toUpperCase() === p)?.codveiculo || null
    : null
}
function onPlacaBlur() {
  if (placaBusca.value && placaBusca.value !== edicao.value.placa) resolverPlaca(placaBusca.value)
}
async function onCaminhaoCriado(veiculo) {
  await store.adicionarVeiculo(veiculo)
  edicao.value.codveiculo = veiculo.codveiculo
  edicao.value.placa = veiculo.placa
}

// Online usa o select padrão de pessoa; offline (ou reabrindo um nome digitado
// offline, sem id) cai num texto livre — mesma regra do formulário original.
const motoristaTextoLivre = computed(
  () => !online.value || (!!edicao.value.motorista && !edicao.value.codpessoamotorista),
)
function onMotoristaSelect(opt) {
  edicao.value.motorista = opt?.label || null
}
function onMotoristaClear() {
  edicao.value.motorista = null
}

async function salvar() {
  Object.assign(carga.value, {
    placa: edicao.value.placa,
    codveiculo: edicao.value.codveiculo,
    placacarreta: edicao.value.placacarreta,
    codpessoamotorista: edicao.value.codpessoamotorista,
    motorista: edicao.value.motorista,
    data: edicao.value.data,
  })
  try {
    const ok = await persistirBloco()
    if (ok) dialogAberto.value = false
  } catch {
    // erro já notificado por quem persiste (CargaPage) — mantém o dialog aberto
  }
}
</script>

<template>
  <q-card flat bordered>
    <q-card-section>
      <div class="row items-center q-mb-sm">
        <div class="text-subtitle2 text-grey-8">Operação</div>
        <q-space />
        <q-btn flat round dense icon="edit" size="sm" color="grey-7" @click="abrir" />
      </div>

      <!-- O toggle é o controle E o indicador: mostra o que a carga é. -->
      <SelectSentido
        :model-value="carga.sentido"
        :disable="finalizada"
        @update:model-value="trocarOperacao"
      />
      <div v-if="finalizada" class="text-caption text-grey-6 q-mt-xs">
        Romaneio finalizado — a operação não muda mais.
      </div>

      <q-separator class="q-my-md" />

      <div class="row q-col-gutter-md">
        <div class="col-6 col-sm-3">
          <div class="text-caption text-grey-6">Placa</div>
          <div class="row items-center no-wrap">
            <q-icon name="local_shipping" color="blue-grey-6" size="20px" class="q-mr-sm" />
            <span class="text-body1 text-weight-medium">{{ carga.placa || '—' }}</span>
          </div>
        </div>
        <div class="col-6 col-sm-3">
          <div class="text-caption text-grey-6">Carreta</div>
          <div class="row items-center no-wrap">
            <q-icon name="link" color="blue-grey-6" size="20px" class="q-mr-sm" />
            <span class="text-body1 text-weight-medium">{{ carga.placacarreta || '—' }}</span>
          </div>
        </div>
        <div class="col-12 col-sm-3">
          <div class="text-caption text-grey-6">Motorista</div>
          <div class="row items-center no-wrap">
            <q-icon name="person" color="blue-grey-6" size="20px" class="q-mr-sm" />
            <span class="text-body1 text-weight-medium ellipsis">{{ carga.motorista || '—' }}</span>
          </div>
        </div>
        <div class="col-12 col-sm-3">
          <div class="text-caption text-grey-6">Chegada</div>
          <div class="row items-center no-wrap">
            <q-icon name="schedule" color="blue-grey-6" size="20px" class="q-mr-sm" />
            <span class="text-body1 text-weight-medium">
              {{ carga.data ? formataTimestamp(carga.data) : '—' }}
            </span>
          </div>
        </div>
      </div>
    </q-card-section>
  </q-card>

  <q-dialog v-model="dialogAberto">
    <q-card style="width: 600px; max-width: 90vw">
      <q-form @submit="salvar">
        <q-card-section>
          <div class="text-subtitle1 q-mb-md">Caminhão</div>
          <div class="row q-col-gutter-x-md">
            <q-select
              :model-value="edicao.placa"
              :options="placaOptions"
              label="Placa"
              outlined
              use-input
              fill-input
              hide-selected
              clearable
              input-debounce="200"
              new-value-mode="add-unique"
              option-label="label"
              option-value="value"
              emit-value
              map-options
              class="col-6 col-sm-3"
              autofocus
              lazy-rules
              :rules="[() => !!edicao.placa || 'Informe a placa.']"
              @filter="filtrarPlaca"
              @update:model-value="resolverPlaca"
              @blur="onPlacaBlur"
            >
              <template #no-option>
                <q-item v-if="placaBusca" clickable @click="cadastroCaminhao = true">
                  <q-item-section avatar><q-icon name="add" color="primary" /></q-item-section>
                  <q-item-section class="text-primary">Cadastrar “{{ placaBusca }}”</q-item-section>
                </q-item>
                <q-item v-else>
                  <q-item-section class="text-grey-6">Digite a placa…</q-item-section>
                </q-item>
              </template>
            </q-select>

            <q-input
              v-model="edicao.placacarreta"
              label="Carreta"
              outlined
              class="col-6 col-sm-3"
              @update:model-value="edicao.placacarreta = ($event || '').toUpperCase()"
            />

            <MgSelectPessoa
              v-if="!motoristaTextoLivre"
              v-model="edicao.codpessoamotorista"
              label="Motorista"
              clearable
              :bottom-slots="false"
              class="col-12 col-sm-3"
              @select="onMotoristaSelect"
              @clear="onMotoristaClear"
            />
            <q-input
              v-else
              v-model="edicao.motorista"
              label="Motorista"
              hint="Offline — texto livre"
              outlined
              clearable
              class="col-12 col-sm-3"
              @update:model-value="edicao.codpessoamotorista = null"
            />

            <MgInputData
              v-model="edicao.data"
              type="timestamp"
              label="Chegada"
              :max="dataMax"
              class="col-12 col-sm-3"
            />
          </div>
        </q-card-section>
        <q-card-actions align="right">
          <q-btn label="Cancelar" flat color="grey-8" v-close-popup tabindex="-1" />
          <q-btn label="Salvar" type="submit" flat color="primary" />
        </q-card-actions>
      </q-form>
    </q-card>
  </q-dialog>

  <CaminhaoDialog v-model="cadastroCaminhao" :placa="placaBusca" @criado="onCaminhaoCriado" />
</template>
