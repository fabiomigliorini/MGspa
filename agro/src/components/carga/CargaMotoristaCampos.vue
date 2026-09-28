<script setup>
// Dados do motorista NOVO no modal de Operação — o mesmo formulário serve pros
// dois caminhos: "sem cadastro" (grava tudo só na carga) e "cadastrar" (cria a
// pessoa com telefone e endereço). Transportar grão é mais sério que comprar na
// loja: CPF, nome completo, telefone e endereço são obrigatórios nos dois.
//
// Só campos, sem q-form: fica DENTRO do form do modal (o Salvar valida tudo
// junto) e renderiza as colunas direto na grade do pai (fragmento), 3 por linha.
import { ref, computed, nextTick } from 'vue'
import { useQuasar } from 'quasar'
import { MASCARA_CPF, MASCARA_CEP, mascaraTelefone } from '@components/formatters'
import { isCpfValido, isTelefoneValido } from '@components/validador'
import MgInput from '@components/MgInput.vue'
import MgSelectCidade from '@components/MgSelectCidade.vue'
import { nomeCompleto, pessoaPorCpf, enderecoPeloCep } from 'src/utils/motorista'

const props = defineProps({
  // O `edicao` do modal (campos *motorista + `cadastrarMotorista`).
  dados: { type: Object, required: true },
  online: { type: Boolean, default: true },
})
// `existente`: o CPF já é de uma pessoa cadastrada — o modal usa ela.
const emit = defineEmits(['existente'])

const $q = useQuasar()

// Indireção (padrão dos blocos): muta o objeto do pai sem vue/no-mutating-props.
const d = computed(() => props.dados)

const TIPOS_TELEFONE = [
  { label: 'Celular', value: 2 },
  { label: 'Fixo', value: 1 },
]
// O tipo só escolhe a máscara; o que se grava é DDD + número (10 fixo, 11 celular).
const tipoTelefone = ref(String(props.dados.telefonemotorista || '').length === 10 ? 1 : 2)

const cpfRef = ref(null)
const nomeRef = ref(null)
const telefoneRef = ref(null)
const numeroRef = ref(null)
const buscandoCep = ref(false)

// Foco no primeiro campo ainda vazio (o CPF ou o nome podem vir da busca).
function focar() {
  nextTick(() => {
    if (!d.value.cpfmotorista) cpfRef.value?.focus()
    else if (!d.value.motorista) nomeRef.value?.focus()
    else telefoneRef.value?.focus()
  })
}
defineExpose({ focar })

async function onCpfBlur() {
  if (!props.online || !isCpfValido(d.value.cpfmotorista)) return
  try {
    const p = await pessoaPorCpf(d.value.cpfmotorista)
    if (p) emit('existente', p)
  } catch {
    // sem conexão: segue como está (o backend barra CPF duplicado no cadastro)
  }
}

async function onCep(cep) {
  if ((cep || '').length !== 8 || !props.online) return
  buscandoCep.value = true
  try {
    const end = await enderecoPeloCep(cep)
    if (!end) {
      $q.notify({ type: 'warning', message: 'CEP não encontrado — preencha o endereço.' })
      return
    }
    Object.assign(d.value, {
      enderecomotorista: end.endereco,
      bairromotorista: end.bairro,
      codcidademotorista: end.codcidade,
    })
    numeroRef.value?.focus()
  } catch {
    // viacep fora do ar: segue o preenchimento manual
  } finally {
    buscandoCep.value = false
  }
}
</script>

<template>
  <MgInput
    ref="cpfRef"
    v-model="d.cpfmotorista"
    label="CPF do motorista"
    :mask="MASCARA_CPF"
    unmasked-value
    inputmode="numeric"
    class="col-12 col-sm-4"
    lazy-rules
    :rules="[(v) => !!v || 'Informe o CPF.', (v) => isCpfValido(v) || 'CPF inválido.']"
    @blur="onCpfBlur"
  />
  <MgInput
    ref="nomeRef"
    v-model="d.motorista"
    label="Nome completo"
    maxlength="60"
    class="col-12 col-sm-8"
    lazy-rules
    :rules="[(v) => nomeCompleto(v) || 'Informe nome e sobrenome.']"
  />

  <q-select
    v-model="tipoTelefone"
    :options="TIPOS_TELEFONE"
    label="Tipo"
    emit-value
    map-options
    outlined
    bottom-slots
    class="col-12 col-sm-4"
    @update:model-value="d.telefonemotorista = null"
  />
  <MgInput
    ref="telefoneRef"
    v-model="d.telefonemotorista"
    label="Telefone"
    :mask="mascaraTelefone(tipoTelefone)"
    unmasked-value
    inputmode="tel"
    class="col-12 col-sm-4"
    lazy-rules
    :rules="[(v) => isTelefoneValido(v, tipoTelefone) || 'Informe o telefone com DDD.']"
  />
  <MgInput
    v-model="d.cepmotorista"
    label="CEP"
    :mask="MASCARA_CEP"
    unmasked-value
    inputmode="numeric"
    :loading="buscandoCep"
    class="col-12 col-sm-4"
    lazy-rules
    :rules="[(v) => (v || '').length === 8 || 'Informe o CEP.']"
    @update:model-value="onCep"
  />

  <MgInput
    v-model="d.enderecomotorista"
    label="Endereço"
    maxlength="100"
    class="col-12 col-sm-8"
    lazy-rules
    :rules="[(v) => !!v || 'Informe o endereço.']"
  />
  <MgInput
    ref="numeroRef"
    v-model="d.numeromotorista"
    label="Número"
    maxlength="10"
    class="col-12 col-sm-4"
    lazy-rules
    :rules="[(v) => !!v || 'Informe o número (S/N se não houver).']"
  />

  <MgInput
    v-model="d.complementomotorista"
    label="Complemento"
    maxlength="50"
    bottom-slots
    class="col-12 col-sm-4"
  />
  <MgInput
    v-model="d.bairromotorista"
    label="Bairro"
    maxlength="50"
    class="col-12 col-sm-4"
    lazy-rules
    :rules="[(v) => !!v || 'Informe o bairro.']"
  />
  <!-- Offline não há como buscar cidade: aí ela fica pra depois, pra não
       travar o pátio sem internet. -->
  <MgSelectCidade
    v-model="d.codcidademotorista"
    class="col-12 col-sm-4"
    lazy-rules
    :rules="[(v) => !online || !!v || 'Informe a cidade.']"
  />

  <div class="col-12">
    <q-toggle
      v-model="d.cadastrarMotorista"
      :disable="!online"
      label="Cadastrar este motorista no sistema (fica disponível nas próximas cargas)"
    />
    <div v-if="!online" class="text-caption text-grey-6 q-pl-sm">
      Sem conexão: o motorista fica só nesta carga.
    </div>
  </div>
</template>
