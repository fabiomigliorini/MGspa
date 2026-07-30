<script setup>
import { ref, computed, watch } from 'vue'
import { useQuasar } from 'quasar'
import { rhStore } from 'src/stores/rh'
import { extrairErro } from 'src/utils/rhFormatters'
import MgSelectUnidadeNegocio from '@components/MgSelectUnidadeNegocio.vue'
import MgSelectTipoSetor from '@components/MgSelectTipoSetor.vue'
import MgInfoCriacao from '@components/MgInfoCriacao.vue'

const props = defineProps({
  modelValue: { type: Boolean, default: false },
  setor: { type: Object, default: null },
  codunidadenegocio: { type: [Number, String], default: null },
})

const emit = defineEmits(['update:modelValue', 'salvo'])

const $q = useQuasar()
const sRh = rhStore()

const dialog = ref(false)
const cad = ref({})
const salvando = ref(false)

const isNovo = computed(() => !cad.value.codsetor)

watch(
  () => props.modelValue,
  (v) => {
    dialog.value = v
    if (v) {
      cad.value = props.setor
        ? { ...props.setor }
        : {
            setor: '',
            codunidadenegocio: props.codunidadenegocio ?? null,
            codtiposetor: null,
            indicadorvendedor: false,
            indicadorcaixa: false,
            indicadorcoletivo: false,
          }
    }
  },
)
watch(dialog, (v) => emit('update:modelValue', v))

const submit = async () => {
  if (salvando.value) return
  salvando.value = true
  try {
    const payload = {
      setor: cad.value.setor,
      codunidadenegocio: cad.value.codunidadenegocio,
      codtiposetor: cad.value.codtiposetor,
      indicadorvendedor: !!cad.value.indicadorvendedor,
      indicadorcaixa: !!cad.value.indicadorcaixa,
      indicadorcoletivo: !!cad.value.indicadorcoletivo,
    }
    if (isNovo.value) {
      await sRh.criarSetor(payload)
    } else {
      await sRh.atualizarSetor(cad.value.codsetor, payload)
    }
    $q.notify({
      color: 'green-5',
      textColor: 'white',
      icon: 'done',
      message: isNovo.value ? 'Setor criado' : 'Setor atualizado',
    })
    dialog.value = false
    emit('salvo')
  } catch (error) {
    $q.notify({
      color: 'red-5',
      textColor: 'white',
      icon: 'error',
      message: extrairErro(error, 'Erro ao salvar setor'),
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
        <template v-if="isNovo">NOVO SETOR</template>
        <template v-else>EDITAR SETOR</template>
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
                v-model="cad.setor"
                label="Descrição"
                autofocus
                :rules="[(val) => (val && val.length > 0) || 'Obrigatório']"
              />
            </div>
            <div class="col-12 col-sm-6">
              <MgSelectUnidadeNegocio
                v-model="cad.codunidadenegocio"
                :rules="[(val) => !!val || 'Obrigatório']"
              />
            </div>
            <div class="col-12 col-sm-6">
              <MgSelectTipoSetor
                v-model="cad.codtiposetor"
                :rules="[(val) => !!val || 'Obrigatório']"
              />
            </div>
            <div class="col-12">
              <small class="text-grey">Indicadores gerados:</small>
              <div class="row items-center">
                <q-toggle v-model="cad.indicadorvendedor" label="Vendedor" />
                <q-toggle v-model="cad.indicadorcaixa" label="Caixa" />
                <q-toggle v-model="cad.indicadorcoletivo" label="Coletivo" />
              </div>
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
