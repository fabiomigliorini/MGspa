<script setup>
// Filtros da listagem única de pagamentos (M6.1 doc-3), no drawer de cada app.
// No PDV a listagem é travada nele: sem filial nem PDV.
import { ref } from 'vue'
import { pagamentoListaStore } from '@components/stores/pagamentoListaStore'
import { MEIOS } from '@components/cobranca/pagamento.js'
import MgInput from '@components/MgInput.vue'
import MgSelect from '@components/MgSelect.vue'
import MgInputData from '@components/MgInputData.vue'
import MgInputValor from '@components/MgInputValor.vue'
import MgSelectFilial from '@components/MgSelectFilial.vue'
import MgSelectPdv from '@components/MgSelectPdv.vue'
import MgSelectPortador from '@components/MgSelectPortador.vue'
import MgSelectPessoa from '@components/MgSelectPessoa.vue'
import MgSelectMaquineta from '@components/MgSelectMaquineta.vue'
import MgSelectUsuario from '@components/MgSelectUsuario.vue'

const store = pagamentoListaStore()

const ESTADOS = [
  { value: 'P', label: 'Pendente' },
  { value: 'E', label: 'Efetivado' },
  { value: 'C', label: 'Cancelado' },
]

const ORIGENS = [
  { value: 'V', label: 'Venda' },
  { value: 'T', label: 'Títulos' },
  { value: 'A', label: 'Taxa, tarifa, rendimento' },
]

const OPCOES_MEIO = Object.entries(MEIOS).map(([value, label]) => ({ value: Number(value), label }))

// Meio de pagamento só lista depois de 2 caracteres, como os outros campos de busca
const semAcento = (t) =>
  (t || '')
    .normalize('NFD')
    .replace(/[\u0300-\u036f]/g, '')
    .toLowerCase()
const opcoesMeio = ref([])
const buscaMeio = ref('')
// com a lista vazia (filtro guardado, página recarregada) o chip ainda mostra o nome
const rotuloMeio = (opt) =>
  OPCOES_MEIO.find((o) => o.value === Number(opt?.value ?? opt))?.label ?? String(opt)
function filtrarMeio(val, update) {
  update(() => {
    const busca = semAcento(val)
    buscaMeio.value = busca
    opcoesMeio.value =
      busca.length < 2 ? [] : OPCOES_MEIO.filter((o) => semAcento(o.label).includes(busca))
  })
}
</script>

<template>
  <div class="row q-col-gutter-md">
    <div class="col-6">
      <MgInputData v-model="store.filtros.transacao_de" label="De" :bottom-slots="false" />
    </div>
    <div class="col-6">
      <MgInputData v-model="store.filtros.transacao_ate" label="Até" :bottom-slots="false" />
    </div>
    <div class="col-6">
      <MgInputValor
        v-model="store.filtros.valor_de"
        label="Valor de"
        prefix="R$"
        :min="0"
        clearable
        :bottom-slots="false"
      />
    </div>
    <div class="col-6">
      <MgInputValor
        v-model="store.filtros.valor_ate"
        label="Valor até"
        prefix="R$"
        :min="0"
        clearable
        :bottom-slots="false"
      />
    </div>
    <div class="col-12">
      <MgSelect
        v-model="store.filtros.origem"
        :options="ORIGENS"
        label="Origem"
        multiple
        emit-value
        map-options
        outlined
        clearable
        :bottom-slots="false"
      />
    </div>
    <div class="col-12">
      <MgSelect
        v-model="store.filtros.estado"
        :options="ESTADOS"
        label="Status"
        multiple
        emit-value
        map-options
        outlined
        clearable
        :bottom-slots="false"
      />
    </div>
    <div class="col-12">
      <MgInput
        v-model="store.filtros.documento"
        label="Documento (venda ou título)"
        clearable
        :bottom-slots="false"
      />
    </div>
    <div class="col-12">
      <MgSelectPessoa
        v-model="store.filtros.codpessoa"
        label="Pessoa"
        clearable
        :bottom-slots="false"
      />
    </div>
    <div class="col-12" v-if="!store.travadoPdv">
      <MgSelectFilial
        v-model="store.filtros.codfilial"
        label="Filial"
        clearable
        :bottom-slots="false"
      />
    </div>
    <div class="col-12" v-if="!store.travadoPdv">
      <MgSelectPdv
        v-model="store.filtros.codpdv"
        :codfilial="store.filtros.codfilial"
        label="PDV"
        clearable
        :bottom-slots="false"
      />
    </div>
    <div class="col-12">
      <div class="col-12 q-mb-md">
        <MgSelectPortador
          v-model="store.filtros.codportador"
          label="Portador"
          clearable
          inativos
          remoto
          :bottom-slots="false"
        />
      </div>
      <MgSelect
        v-model="store.filtros.meio"
        :options="opcoesMeio"
        label="Meio de pagamento"
        multiple
        use-input
        input-debounce="100"
        emit-value
        map-options
        outlined
        clearable
        :bottom-slots="false"
        @filter="filtrarMeio"
      >
        <template #selected-item="scope">
          <q-chip removable dense :tabindex="-1" @remove="scope.removeAtIndex(scope.index)">
            {{ rotuloMeio(scope.opt) }}
          </q-chip>
        </template>
        <template #no-option>
          <q-item>
            <q-item-section class="text-grey">
              {{ buscaMeio.length >= 2 ? 'Nenhum resultado' : 'Digite ao menos 2 caracteres' }}
            </q-item-section>
          </q-item>
        </template>
      </MgSelect>
    </div>
    <div class="col-12">
      <MgSelectMaquineta
        v-model="store.filtros.codmaquineta"
        label="Maquineta"
        clearable
        remoto
        :bottom-slots="false"
      />
    </div>
    <div class="col-12">
      <MgSelectUsuario
        v-model="store.filtros.codusuariocriacao"
        label="Usuário"
        clearable
        :bottom-slots="false"
      />
    </div>
    <div class="col-12">
      <MgInput
        v-model="store.filtros.codpagamento"
        label="Código do pagamento"
        inputmode="numeric"
        clearable
        :bottom-slots="false"
      />
    </div>
  </div>
</template>
