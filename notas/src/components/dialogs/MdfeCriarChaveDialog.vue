<script setup>
import { ref, watch } from 'vue'
import { useRouter } from 'vue-router'
import mdfeService from '../../services/mdfeService'
import { notificarSucesso, notificarErro } from '../../utils/notify'
import { validarChaveNFe } from 'src/utils/validators'

const props = defineProps({
  modelValue: { type: Boolean, required: true },
})
const emit = defineEmits(['update:modelValue'])

const router = useRouter()
const nfechave = ref('')
const loading = ref(false)

watch(
  () => props.modelValue,
  (aberto) => {
    if (aberto) nfechave.value = ''
  },
)

const close = () => emit('update:modelValue', false)

const handlePaste = (evt) => {
  const texto = evt.clipboardData?.getData('text')
  if (texto) {
    evt.preventDefault()
    nfechave.value = texto.replace(/\D/g, '').substring(0, 44)
  }
}

const submit = async () => {
  const chave = nfechave.value.replace(/\D/g, '')
  if (chave.length !== 44 || !validarChaveNFe(chave)) {
    notificarErro(null, 'Chave da NFe inválida')
    return
  }
  loading.value = true
  try {
    const mdfe = await mdfeService.criarDaNfeChave(chave)
    notificarSucesso('MDFe criado!')
    close()
    router.push({ name: 'mdfe-view', params: { codmdfe: mdfe.codmdfe } })
  } catch (error) {
    notificarErro(error, 'Falha ao criar MDFe pela chave')
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <q-dialog
    :model-value="modelValue"
    @update:model-value="emit('update:modelValue', $event)"
  >
    <q-card style="width: 600px; max-width: 95vw">
      <q-card-section class="bg-primary text-white">
        <div class="text-h6">Novo MDFe pela Chave da NFe</div>
      </q-card-section>

      <q-separator />

      <q-form @submit.prevent="submit">
        <q-card-section>
          <q-input
            v-model="nfechave"
            label="Chave da NFe *"
            outlined
            autofocus
            mask="#### #### #### #### #### #### #### #### #### #### ####"
            unmasked-value
            placeholder="Digite os 44 dígitos da chave de acesso"
            :rules="[
              (v) => !!v || 'Campo obrigatório',
              (v) => validarChaveNFe(v) || 'Chave de acesso inválida',
            ]"
            lazy-rules
            @paste="handlePaste"
          />
        </q-card-section>

        <q-separator />

        <q-card-actions align="right" class="q-pa-md">
          <q-btn flat label="Cancelar" color="grey-8" @click="close" tabindex="-1" />
          <q-btn unelevated label="Criar" color="primary" icon="add" type="submit" :loading="loading" />
        </q-card-actions>
      </q-form>
    </q-card>
  </q-dialog>
</template>
