<script setup>
import { ref, onMounted, onBeforeUnmount } from 'vue'
import { api } from 'boot/axios'
import { blobUrlFromApi } from '@components/blobUrlFromApi'
import { negocioStore } from 'stores/negocio'

const props = defineProps({
  pasta: { type: String, required: true },
  anexo: { type: String, required: true },
  icon: { type: String, default: 'image' },
  label: { type: String, default: 'Anexo' },
  caption: { type: String, default: '' },
})

const emit = defineEmits(['excluir'])

const sNegocio = negocioStore()
const blobUrl = ref(null)

onMounted(async () => {
  try {
    blobUrl.value = await blobUrlFromApi(
      api,
      `/v1/pdv/negocio/${sNegocio.negocio.codnegocio}/anexo/${props.pasta}/${props.anexo}`,
      'image/*',
    )
  } catch (e) {
    console.log(e)
  }
})

onBeforeUnmount(() => {
  if (blobUrl.value) URL.revokeObjectURL(blobUrl.value)
})

const abrirNovaAba = () => {
  if (blobUrl.value) window.open(blobUrl.value, '_blank')
}
</script>

<template>
  <div class="col-xs-12 col-sm-6 col-md-6 col-lg-4 col-xl-3">
    <q-card flat bordered>
      <q-item clickable v-ripple @click="abrirNovaAba">
        <q-item-section avatar>
          <q-avatar :icon="icon" color="secondary" text-color="white" />
        </q-item-section>
        <q-item-section>
          <q-item-label class="ellipsis">{{ label }}</q-item-label>
          <q-item-label v-if="caption" caption class="ellipsis">{{ caption }}</q-item-label>
        </q-item-section>
      </q-item>
      <q-separator inset />
      <q-item clickable v-ripple @click="abrirNovaAba" style="height: 250px" class="q-pa-none">
        <q-img v-if="blobUrl" :src="blobUrl" fit="cover" spinner-color="primary" />
        <div v-else class="full-width row items-center justify-center">
          <q-spinner color="primary" size="2em" />
        </div>
      </q-item>
      <q-card-actions>
        <q-btn-group flat>
          <q-btn dense flat round icon="delete" color="negative" @click="emit('excluir')">
            <q-tooltip class="bg-accent">Excluir</q-tooltip>
          </q-btn>
        </q-btn-group>
      </q-card-actions>
    </q-card>
  </div>
</template>
