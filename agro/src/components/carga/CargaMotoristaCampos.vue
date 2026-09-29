<script setup>
// Dados do motorista NOVO no modal de Operação — o mesmo formulário serve pros
// dois caminhos: "sem cadastro" (grava tudo só na carga) e "cadastrar" (cria a
// pessoa com celular e endereço; a chave fica no rodapé do modal). Transportar
// grão é mais sério que comprar na loja: CPF, nome completo, celular e endereço
// são obrigatórios nos dois.
//
// Só campos, sem q-form: fica DENTRO do form do modal (o Salvar valida tudo
// junto) e renderiza as colunas direto na grade do pai (fragmento), 3 por linha:
// CPF | Nome — Telefone | Endereço — Bairro | CEP | Cidade. O endereço é um
// campo só (rua, número e complemento), sem campos separados.
import { ref, computed, nextTick } from 'vue'
import { useQuasar } from 'quasar'
import { MASCARA_CPF, MASCARA_CEP, MASCARA_TELEFONE_CELULAR } from '@components/formatters'
import { isCpfValido, isTelefoneValido } from '@components/validador'
import MgInput from '@components/MgInput.vue'
import MgSelectCidade from '@components/MgSelectCidade.vue'
import { nomeCompleto, pessoaPorCpf, enderecoPeloCep } from 'src/utils/motorista'

const props = defineProps({
  // O `edicao` do modal (campos *motorista).
  dados: { type: Object, required: true },
  online: { type: Boolean, default: true },
})
// `existente`: o CPF já é de uma pessoa cadastrada — o modal usa ela.
const emit = defineEmits(['existente'])

const $q = useQuasar()

// Indireção (padrão dos blocos): muta o objeto do pai sem vue/no-mutating-props.
const d = computed(() => props.dados)

const cpfRef = ref(null)
const nomeRef = ref(null)
const telefoneRef = ref(null)
const buscandoCep = ref(false)

// Foco no primeiro campo ainda vazio (o CPF ou o nome podem vir da busca).
function focar() {
  nextTick(() => {
    if (!d.value.cpfmotorista) cpfRef.value?.focus()
    else if (!d.value.motorista) nomeRef.value?.focus()
    else telefoneRef.value?.focus()
  })
}

// Pessoa do cadastro com o CPF digitado, ou null. Uma consulta por CPF: o blur
// e o Salvar (que não espera o blur) reaproveitam a mesma promessa.
let verificacao = { cpf: null, promessa: Promise.resolve(null) }
function verificarCpf() {
  const cpf = d.value.cpfmotorista
  if (!props.online || !isCpfValido(cpf)) return Promise.resolve(null)
  if (verificacao.cpf !== cpf) {
    // sem conexão: segue como está (o backend barra CPF duplicado no cadastro)
    verificacao = { cpf, promessa: pessoaPorCpf(cpf).catch(() => null) }
  }
  return verificacao.promessa
}
defineExpose({ focar, verificarCpf })

async function onCpfBlur() {
  const p = await verificarCpf()
  if (p) emit('existente', p)
}

// O CEP vem DEPOIS do endereço e do bairro: completa só o que ainda está em
// branco (principalmente a cidade), nunca por cima do que já foi digitado —
// "Rua X, 120" não pode virar "Rua X".
async function onCep(cep) {
  if ((cep || '').length !== 8 || !props.online) return
  buscandoCep.value = true
  try {
    const end = await enderecoPeloCep(cep)
    if (!end) {
      $q.notify({ type: 'warning', message: 'CEP não encontrado — confira a cidade.' })
      return
    }
    if (!d.value.enderecomotorista && end.endereco) d.value.enderecomotorista = end.endereco
    if (!d.value.bairromotorista && end.bairro) d.value.bairromotorista = end.bairro
    if (!d.value.codcidademotorista && end.codcidade) d.value.codcidademotorista = end.codcidade
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

  <!-- Sempre celular: o pátio liga/manda mensagem pro motorista na estrada. -->
  <MgInput
    ref="telefoneRef"
    v-model="d.telefonemotorista"
    label="Telefone"
    :mask="MASCARA_TELEFONE_CELULAR"
    unmasked-value
    inputmode="tel"
    class="col-12 col-sm-4"
    lazy-rules
    :rules="[(v) => isTelefoneValido(v, 2) || 'Informe o celular com DDD.']"
  />
  <MgInput
    v-model="d.enderecomotorista"
    label="Endereço"
    placeholder="Rua, número, complemento"
    maxlength="100"
    class="col-12 col-sm-8"
    lazy-rules
    :rules="[(v) => !!v || 'Informe o endereço.']"
  />

  <MgInput
    v-model="d.bairromotorista"
    label="Bairro"
    maxlength="50"
    class="col-12 col-sm-4"
    lazy-rules
    :rules="[(v) => !!v || 'Informe o bairro.']"
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
  <!-- Offline não há como buscar cidade: aí ela fica pra depois, pra não
       travar o pátio sem internet. -->
  <MgSelectCidade
    v-model="d.codcidademotorista"
    class="col-12 col-sm-4"
    lazy-rules
    :rules="[(v) => !online || !!v || 'Informe a cidade.']"
  />
</template>
