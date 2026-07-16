<script setup>
import { ref, onMounted, computed, watch } from 'vue'
import { pessoaStore } from 'src/stores/pessoa'

const sPessoa = pessoaStore()

onMounted(async () => {
  // pessoas()
})

const props = defineProps({
  modelcodPessoa: {},
})

const codigoUsuarioPessoa = computed({
  get() {
    return props.modelcodPessoa
  },

  set(value) {
    emit('update:modelcodPessoa', value)
  },
})

watch(codigoUsuarioPessoa, async (value) => {
  if (value) {
    // NAO escrever em sPessoa.filtroPesquisa aqui: e o filtro da tela /pessoas e e persistido.
    // buscaPessoasSelectUsuario recebe o codpessoa por parametro, entao era escrita morta que
    // so vazava estado de /usuarios pra /pessoas.
    const ret = await sPessoa.buscaPessoasSelectUsuario({ codpessoa: value })
    opcoes.value = ret.data.data
  }
})

const emit = defineEmits(['update:modelcodPessoa'])

const buscaPessoas = (val, update) => {
  if (val === '') {
    update(() => {
      opcoes.value = []
    })
    return
  }
  update(async () => {
    const needle = val.toLowerCase()
    try {
      if (needle.length > 3) {
        const ret = await sPessoa.buscaPessoasSelectUsuario({ pessoa: needle })
        opcoes.value = ret.data.data
        return
      }
    } catch (error) {
      console.log(error)
    }
  })
}

const opcoes = ref([])
</script>

<template>
  <q-select
    use-input
    outlined
    :model-value="modelcodPessoa"
    :options="opcoes"
    map-options
    emit-value
    option-label="pessoa"
    option-value="codpessoa"
    @filter="buscaPessoas"
    clearable
  />
</template>
