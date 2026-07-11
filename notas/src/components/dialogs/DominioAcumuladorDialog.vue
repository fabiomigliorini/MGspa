<script setup>
import { ref, computed, watch } from 'vue'
import dominioService from '../../services/dominioService'
import { notificarSucesso, notificarErro } from '../../utils/notify'

const props = defineProps({
  modelValue: { type: Boolean, required: true },
  // Objeto base: { coddominioacumulador, codfilial, ... }. Novo = coddominioacumulador null.
  acumulador: { type: Object, default: null },
})
const emit = defineEmits(['update:modelValue', 'saved'])

const loading = ref(false)
const isEdicao = computed(() => !!form.value.coddominioacumulador)

const form = ref({})

watch(
  () => props.modelValue,
  (aberto) => {
    if (aberto && props.acumulador) {
      form.value = {
        coddominioacumulador: props.acumulador.coddominioacumulador ?? null,
        codfilial: props.acumulador.codfilial,
        codcfop: props.acumulador.codcfop ?? null,
        icmscst: props.acumulador.icmscst ?? null,
        acumuladoravista: props.acumulador.acumuladoravista ?? null,
        acumuladorprazo: props.acumulador.acumuladorprazo ?? null,
        historico: props.acumulador.historico ?? null,
        movimentacaofisica: props.acumulador.movimentacaofisica ?? true,
        movimentacaocontabil: props.acumulador.movimentacaocontabil ?? true,
      }
    }
  },
)

const close = () => emit('update:modelValue', false)

const submit = async () => {
  loading.value = true
  try {
    const salvo = await dominioService.salvarAcumulador(form.value)
    notificarSucesso('Acumulador salvo!')
    emit('saved', salvo)
    close()
  } catch (error) {
    notificarErro(error, 'Falha ao salvar acumulador')
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <q-dialog :model-value="modelValue" @update:model-value="emit('update:modelValue', $event)">
    <q-card style="width: 450px; max-width: 95vw">
      <q-card-section class="bg-primary text-white">
        <div class="text-h6">{{ isEdicao ? 'Editar' : 'Novo' }} Acumulador</div>
      </q-card-section>

      <q-separator />

      <q-form @submit.prevent="submit">
        <q-card-section class="row q-col-gutter-md">
          <q-input
            class="col-6"
            v-model="form.codcfop"
            label="CFOP *"
            outlined
            mask="####"
            autofocus
            :disable="isEdicao"
            :rules="[
              (v) => !!v || 'Obrigatório',
              (v) => String(v).length === 4 || 'CFOP deve ter 4 dígitos',
            ]"
          />
          <q-input
            class="col-6"
            v-model="form.icmscst"
            label="CST *"
            outlined
            mask="##"
            :disable="isEdicao"
            :rules="[(v) => v >= 0 || 'Obrigatório']"
          />
          <q-input
            class="col-6"
            v-model="form.acumuladoravista"
            label="Acumulador à Vista *"
            outlined
            mask="#######"
            :rules="[(v) => v > 0 || 'Obrigatório']"
          />
          <q-input
            class="col-6"
            v-model="form.acumuladorprazo"
            label="Acumulador à Prazo *"
            outlined
            mask="#######"
            :rules="[(v) => v > 0 || 'Obrigatório']"
          />
          <q-input
            class="col-12"
            v-model="form.historico"
            label="Histórico"
            outlined
            counter
            maxlength="100"
          />
          <q-checkbox
            class="col-6"
            v-model="form.movimentacaofisica"
            label="Movimentação Física"
            color="primary"
          />
          <q-checkbox
            class="col-6"
            v-model="form.movimentacaocontabil"
            label="Movimentação Contábil"
            color="primary"
          />
        </q-card-section>

        <q-separator />

        <q-card-actions align="right" class="q-pa-md">
          <q-btn flat label="Cancelar" color="grey-8" @click="close" tabindex="-1" />
          <q-btn flat label="Salvar" color="primary" icon="save" type="submit" :loading="loading" />
        </q-card-actions>
      </q-form>
    </q-card>
  </q-dialog>
</template>
