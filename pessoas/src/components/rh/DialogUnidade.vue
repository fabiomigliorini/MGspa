<script setup>
import { ref, computed, watch } from 'vue'
import { useQuasar } from 'quasar'
import { rhStore } from 'src/stores/rh'
import { extrairErro } from 'src/utils/rhFormatters'
import MgSelectFilial from '@components/MgSelectFilial.vue'
import MgInfoCriacao from '@components/MgInfoCriacao.vue'

const props = defineProps({
  modelValue: { type: Boolean, default: false },
  unidade: { type: Object, default: null },
})

const emit = defineEmits(['update:modelValue', 'salvo'])

const $q = useQuasar()
const sRh = rhStore()

const dialog = ref(false)
const cad = ref({})
const salvando = ref(false)

const isNovo = computed(() => !cad.value.codunidadenegocio)

watch(
  () => props.modelValue,
  (v) => {
    dialog.value = v
    if (v) {
      cad.value = props.unidade ? { ...props.unidade } : { descricao: '', codfilial: null }
    }
  },
)
watch(dialog, (v) => emit('update:modelValue', v))

const submit = async () => {
  if (salvando.value) return
  salvando.value = true
  try {
    const payload = { descricao: cad.value.descricao, codfilial: cad.value.codfilial }
    if (isNovo.value) {
      await sRh.criarUnidade(payload)
    } else {
      await sRh.atualizarUnidade(cad.value.codunidadenegocio, payload)
    }
    $q.notify({
      color: 'green-5',
      textColor: 'white',
      icon: 'done',
      message: isNovo.value ? 'Unidade criada' : 'Unidade atualizada',
    })
    dialog.value = false
    emit('salvo')
  } catch (error) {
    $q.notify({
      color: 'red-5',
      textColor: 'white',
      icon: 'error',
      message: extrairErro(error, 'Erro ao salvar unidade'),
    })
  } finally {
    salvando.value = false
  }
}
</script>

<template>
  <q-dialog v-model="dialog">
    <q-card flat style="width: 600px; max-width: 90vw">
      <q-card-section class="text-grey-9 text-overline row items-center">
        <template v-if="isNovo">NOVA UNIDADE</template>
        <template v-else>EDITAR UNIDADE</template>
        <q-space />
        <MgInfoCriacao v-if="!isNovo" :registro="cad" />
      </q-card-section>

      <q-form @submit.prevent="submit()">
        <q-separator inset />

        <q-card-section>
          <div class="row q-col-gutter-md">
            <div class="col-12">
              <q-input
                outlined
                v-model="cad.descricao"
                label="Descrição"
                autofocus
                :rules="[(val) => (val && val.length > 0) || 'Obrigatório']"
              />
            </div>
            <div class="col-12">
              <MgSelectFilial v-model="cad.codfilial" clearable />
            </div>
          </div>
        </q-card-section>

        <q-separator inset />

        <q-card-actions align="right" class="text-primary">
          <q-btn flat label="Cancelar" v-close-popup tabindex="-1" color="grey-8" />
          <q-btn flat label="Salvar" type="submit" :loading="salvando" />
        </q-card-actions>
      </q-form>
    </q-card>
  </q-dialog>
</template>
