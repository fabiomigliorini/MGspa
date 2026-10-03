<script setup>
// Importação do OFX (o que o banco mandou, para a Extrato do banco), aberta pelo cabeçalho do
// painel /portador (doc-4, R6; era o FAB da tela Saldos). Mesma rota v1/portador/importar-ofx.
import { ref, computed } from 'vue'
import { useQuasar } from 'quasar'
import { storeToRefs } from 'pinia'
import { useAuthStore } from 'stores/auth'
import { usePortadorStore } from 'src/stores/portadorStore'

const $q = useQuasar()
const authStore = useAuthStore()
const store = usePortadorStore()
const { dialogOfx } = storeToRefs(store)

const enviando = ref(false)
const falhou = ref(false)
const url = computed(() => process.env.API_URL + 'v1/portador/importar-ofx')

const aoTerminar = () => {
  enviando.value = false
  if (!falhou.value) dialogOfx.value = false
  falhou.value = false
}

const aoImportar = (info) => {
  const resp = JSON.parse(info.xhr.response)
  Object.values(resp).forEach((r) => {
    $q.notify({
      message: `Importados ${r.registros} registros no portador "${r.portador}" com ${r.falhas} falhas!`,
      color: 'green-5',
    })
  })
}

const aoFalhar = (response) => {
  falhou.value = true
  response.files.forEach((arquivo) => {
    $q.notify({ message: `Falha ao importar o arquivo "${arquivo.name}"!`, color: 'red-5' })
  })
}
</script>

<template>
  <q-dialog v-model="dialogOfx">
    <q-card flat style="width: 600px; max-width: 90vw">
      <q-card-section class="text-h6">Importar OFX</q-card-section>
      <q-card-section>
        <q-uploader
          :url="url"
          field-name="arquivos[]"
          accept=".ofx"
          label="Arquivos OFX do banco"
          :headers="[{ name: 'Authorization', value: `Bearer ${authStore.token}` }]"
          multiple
          flat
          bordered
          style="width: 100%"
          @uploading="enviando = true"
          @uploaded="aoImportar"
          @failed="aoFalhar"
          @finish="aoTerminar"
        />
      </q-card-section>
      <q-card-actions align="right">
        <q-btn flat label="Fechar" color="grey-8" v-close-popup :disable="enviando" />
      </q-card-actions>
    </q-card>
  </q-dialog>
</template>
