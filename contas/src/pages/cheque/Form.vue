<script setup>
import { ref, computed, watch, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { api } from 'src/services/api'
import { notifySuccess, notifyError } from 'src/utils/notify'
import { formataDataIso } from '@components/formatters'
import SelectPessoa from '@components/MgSelectPessoa.vue'
import MgInputValor from '@components/MgInputValor.vue'
import MgInputData from '@components/MgInputData.vue'
import MgInputFormatado from '@components/MgInputFormatado.vue'

const route = useRoute()
const router = useRouter()

const codcheque = computed(() => (route.params.codcheque ? Number(route.params.codcheque) : null))
const isNovo = computed(() => !codcheque.value)

const hoje = formataDataIso(new Date())
const model = ref({
  cmc7: '',
  valor: null,
  emissao: hoje,
  vencimento: hoje,
  codpessoa: null,
  observacao: '',
})
const emitentes = ref([])
const derivado = ref({ banco: '', agencia: '', contacorrente: '', numero: '' })
const cmc7Info = ref(null)
const saving = ref(false)
const carregando = ref(false)
let ultimoCmc7Consultado = null

const somenteDigitos = (v) => (v || '').toString().replace(/\D/g, '')

const novaLinha = (cnpj = '', emitente = '', codchequeemitente = null) => ({
  codchequeemitente,
  cnpj,
  emitente,
})
const adicionarEmitente = () => emitentes.value.push(novaLinha())
const removerEmitente = (i) => {
  if (emitentes.value.length > 1) emitentes.value.splice(i, 1)
}

async function consultarCmc7() {
  const digitos = somenteDigitos(model.value.cmc7)
  if (digitos.length < 30) {
    cmc7Info.value = null
    return
  }
  if (digitos === ultimoCmc7Consultado) return
  ultimoCmc7Consultado = digitos
  try {
    const { data } = await api.get(`v1/cheque/consulta-cmc7/${digitos}`)
    if (data.error) {
      cmc7Info.value = { valido: false, error: data.error }
      notifyError({ response: { data: { message: data.error } } }, data.error)
      return
    }
    cmc7Info.value = { valido: data.valido, error: data.valido ? null : 'CMC7 inválido' }
    derivado.value = {
      banco: data.banco,
      agencia: data.agencia,
      contacorrente: data.contacorrente,
      numero: data.numero,
    }
    if (data.ultimo?.codpessoa && !model.value.codpessoa) {
      model.value.codpessoa = data.ultimo.codpessoa
    }
    const ultimosEmit = data.ultimo?.emitentes || []
    const todosVazios = emitentes.value.every((e) => !e.cnpj && !e.emitente)
    if (ultimosEmit.length && todosVazios) {
      emitentes.value = ultimosEmit.map((e) => novaLinha(somenteDigitos(e.cnpj), e.emitente))
    } else if (!emitentes.value.length) {
      adicionarEmitente()
    }
  } catch {
    cmc7Info.value = { valido: false, error: 'Erro ao consultar CMC7' }
  }
}

watch(
  () => model.value.cmc7,
  () => {
    if (somenteDigitos(model.value.cmc7).length >= 30) consultarCmc7()
  },
)

async function consultarEmitenteCnpj(linha) {
  const cnpj = somenteDigitos(linha.cnpj)
  if (!cnpj) return
  try {
    const { data } = await api.get(`v1/cheque/consulta-emitente/${cnpj}`)
    if (data.codpessoa) {
      if (!model.value.codpessoa) model.value.codpessoa = data.codpessoa
      if (!linha.emitente) linha.emitente = data.pessoa
    }
  } catch {
    /* consulta best-effort, silenciosa */
  }
}

function onEmissaoChange() {
  if (!model.value.vencimento || model.value.vencimento < model.value.emissao) {
    model.value.vencimento = model.value.emissao
  }
}

async function carregar() {
  if (isNovo.value) {
    emitentes.value = [novaLinha()]
    return
  }
  carregando.value = true
  try {
    const { data } = await api.get(`v1/cheque/${codcheque.value}`)
    model.value = {
      cmc7: data.cmc7 || '',
      valor: data.valor,
      emissao: (data.emissao || '').substring(0, 10),
      vencimento: (data.vencimento || '').substring(0, 10),
      codpessoa: data.codpessoa,
      observacao: data.observacao || '',
    }
    derivado.value = {
      banco: data.banco?.banco ?? data.codbanco,
      agencia: data.agencia,
      contacorrente: data.contacorrente,
      numero: data.numero,
    }
    cmc7Info.value = { valido: true, error: null }
    ultimoCmc7Consultado = somenteDigitos(data.cmc7)
    emitentes.value = (data.cheque_emitente_s || []).map((e) =>
      novaLinha(somenteDigitos(e.cnpj), e.emitente, e.codchequeemitente),
    )
    if (!emitentes.value.length) emitentes.value = [novaLinha()]
  } catch (e) {
    notifyError(e, 'Erro ao carregar cheque')
  } finally {
    carregando.value = false
  }
}

async function submit() {
  saving.value = true
  try {
    const payload = {
      cmc7: somenteDigitos(model.value.cmc7),
      valor: model.value.valor,
      emissao: model.value.emissao,
      vencimento: model.value.vencimento,
      codpessoa: model.value.codpessoa,
      observacao: model.value.observacao,
      emitentes: emitentes.value
        .filter((e) => somenteDigitos(e.cnpj) || e.emitente)
        .map((e) => ({
          codchequeemitente: e.codchequeemitente,
          cnpj: somenteDigitos(e.cnpj),
          emitente: e.emitente,
        })),
    }
    if (isNovo.value) {
      await api.post('v1/cheque', payload)
      notifySuccess('Cheque criado')
    } else {
      await api.put(`v1/cheque/${codcheque.value}`, payload)
      notifySuccess('Cheque atualizado')
    }
    router.push({ name: 'cheque' })
  } catch (e) {
    notifyError(e, 'Erro ao salvar cheque')
  } finally {
    saving.value = false
  }
}

onMounted(carregar)
</script>

<template>
  <q-page class="q-pa-md">
    <div style="max-width: 900px; margin: auto">
      <div class="row items-center q-mb-md">
        <q-btn flat dense round icon="arrow_back" :to="{ name: 'cheque' }" aria-label="Voltar" />
        <div class="text-h6 q-ml-sm">{{ isNovo ? 'Novo Cheque' : 'Editar Cheque' }}</div>
      </div>

      <q-card bordered flat>
        <q-inner-loading :showing="carregando">
          <q-spinner-dots color="primary" size="40px" />
        </q-inner-loading>

        <q-form @submit.prevent="submit">
          <q-card-section>
            <div class="row q-col-gutter-md">
              <!-- CMC7 -->
              <div class="col-12">
                <q-input
                  v-model="model.cmc7"
                  outlined
                  label="CMC7"
                  autofocus
                  hint="Linha magnética do cheque (30 dígitos)"
                  @blur="consultarCmc7"
                  :rules="[(v) => !!v || 'Informe o CMC7']"
                >
                  <template #prepend><q-icon name="qr_code" /></template>
                </q-input>
                <div v-if="cmc7Info" class="q-mt-xs">
                  <q-chip
                    v-if="cmc7Info.valido"
                    dense
                    color="green-6"
                    text-color="white"
                    icon="check"
                  >
                    CMC7 Válido
                  </q-chip>
                  <q-chip v-else dense color="red-6" text-color="white" icon="error">
                    {{ cmc7Info.error }}
                  </q-chip>
                </div>
              </div>

              <!-- Dados derivados do CMC7 -->
              <div class="col-6 col-sm-3">
                <q-input :model-value="derivado.banco" outlined readonly label="Banco" />
              </div>
              <div class="col-6 col-sm-3">
                <q-input :model-value="derivado.agencia" outlined readonly label="Agência" />
              </div>
              <div class="col-6 col-sm-3">
                <q-input :model-value="derivado.contacorrente" outlined readonly label="Conta" />
              </div>
              <div class="col-6 col-sm-3">
                <q-input :model-value="derivado.numero" outlined readonly label="Número" />
              </div>

              <!-- Valor / datas -->
              <div class="col-12 col-sm-4">
                <MgInputValor
                  v-model="model.valor"
                  prefix="R$"
                  label="Valor"
                  :rules="[(v) => (!!v && v > 0) || 'Informe o valor']"
                />
              </div>
              <div class="col-6 col-sm-4">
                <MgInputData
                  v-model="model.emissao"
                  label="Emissão"
                  @update:model-value="onEmissaoChange"
                  :rules="[(v) => !!v || 'Informe a emissão']"
                />
              </div>
              <div class="col-6 col-sm-4">
                <MgInputData
                  v-model="model.vencimento"
                  label="Vencimento"
                  :min="model.emissao"
                  :rules="[(v) => !!v || 'Informe o vencimento']"
                />
              </div>

              <!-- Cliente -->
              <div class="col-12">
                <SelectPessoa
                  v-model="model.codpessoa"
                  outlined
                  clearable
                  label="Cliente"
                  :rules="[(v) => !!v || 'Selecione o cliente']"
                />
              </div>
            </div>
          </q-card-section>

          <q-separator inset />

          <!-- Emitentes -->
          <q-card-section>
            <div class="text-overline text-grey-8 q-mb-sm">EMITENTES</div>
            <div
              v-for="(linha, i) in emitentes"
              :key="i"
              class="row q-col-gutter-sm items-start q-mb-xs"
            >
              <div class="col-12 col-sm-4">
                <q-input
                  v-model="linha.cnpj"
                  outlined
                  label="CNPJ / CPF"
                  mask="##############"
                  unmasked-value
                  @blur="consultarEmitenteCnpj(linha)"
                >
                  <template #prepend><q-icon name="badge" /></template>
                </q-input>
              </div>
              <div class="col-10 col-sm-7">
                <MgInputFormatado v-model="linha.emitente" outlined label="Emitente" />
              </div>
              <div class="col-2 col-sm-1 flex items-center">
                <q-btn
                  flat
                  dense
                  round
                  size="sm"
                  color="grey-7"
                  icon="delete"
                  :disable="emitentes.length === 1"
                  @click="removerEmitente(i)"
                >
                  <q-tooltip>Remover</q-tooltip>
                </q-btn>
              </div>
            </div>
            <q-btn
              flat
              dense
              no-caps
              color="primary"
              icon="add"
              label="Adicionar emitente"
              @click="adicionarEmitente"
            />
          </q-card-section>

          <q-separator inset />

          <!-- Observação -->
          <q-card-section>
            <q-input
              v-model="model.observacao"
              outlined
              type="textarea"
              label="Observação"
              maxlength="200"
              autogrow
            />
          </q-card-section>

          <q-separator inset />

          <q-card-actions align="right" class="text-primary">
            <q-btn flat label="Cancelar" color="grey-8" :to="{ name: 'cheque' }" tabindex="-1" />
            <q-btn flat label="Salvar" type="submit" :loading="saving" />
          </q-card-actions>
        </q-form>
      </q-card>
    </div>
  </q-page>
</template>
