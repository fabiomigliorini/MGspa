<template>
  <MgInput
    outlined
    v-model="model"
    label="Inscrição Estadual"
    :mask="mascara"
    class="q-mb-md"
    unmasked-value
  />
</template>

<script setup>
import MgInput from '@components/MgInput.vue'
import { ref, computed } from 'vue'
import { pessoaStore } from 'src/stores/pessoa'
import { mascaraIe } from '@components/formatters'

const sPessoa = pessoaStore()
const model = ref(null)

const ufNfe = computed(() => {
  const enderecos = sPessoa.item?.PessoaEnderecoS || []
  const nfe = enderecos.find((e) => e.nfe === true)
  return nfe?.uf || null
})

const mascara = computed(() => mascaraIe(ufNfe.value) || '##.###.###-#')
</script>
