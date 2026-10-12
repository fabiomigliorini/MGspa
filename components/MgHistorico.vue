<script setup>
// Histórico de um registro (TASK-209): a auditoria e as ocorrências, buscadas no backend
// toda vez que abre, da mais nova para a mais velha. Para tblnegocio vem também a auditoria dos
// itens, pagamentos, parcelas e vales (AuditoriaService::FILHOS). A ocorrência abre a tela dela
// no contas, em outra aba.
import { ref } from 'vue'
import { Notify } from 'quasar'
import { api } from 'src/services/api'
import { extrairErro } from '@components/extrairErro'
import MgAuditoriaItem from '@components/MgAuditoriaItem.vue'
import MgEmptyState from '@components/MgEmptyState.vue'
import MgOcorrenciaItem from '@components/MgOcorrenciaItem.vue'

const props = defineProps({
  tabela: { type: String, required: true },
  codigo: { type: Number, default: null },
})

const urlOcorrencia = (codocorrencia) => `${process.env.CONTAS_URL}/ocorrencia/${codocorrencia}`

const dialog = ref(false)
const carregando = ref(false)
const auditorias = ref([])
const ocorrencias = ref([])

const abrir = async () => {
  auditorias.value = []
  ocorrencias.value = []
  dialog.value = true
  carregando.value = true
  try {
    const { data } = await api.get(`v1/auditoria/${props.tabela}/${props.codigo}`)
    auditorias.value = data.auditorias
    ocorrencias.value = data.ocorrencias
  } catch (error) {
    dialog.value = false
    Notify.create({
      type: 'negative',
      message: extrairErro(error, 'Erro ao buscar o histórico!'),
      timeout: 3000,
      actions: [{ icon: 'close', color: 'white' }],
    })
  } finally {
    carregando.value = false
  }
}
</script>

<template>
  <q-btn flat round size="sm" color="grey-7" icon="history" @click.stop="abrir">
    <q-tooltip>Histórico</q-tooltip>
  </q-btn>

  <q-dialog v-model="dialog">
    <q-card flat style="width: 600px; max-width: 95vw">
      <q-card-section class="text-h6">Histórico</q-card-section>

      <!-- rola a seção, não o card: a barra nativa do card cobre o canto arredondado -->
      <q-card-section class="q-pt-none scroll" style="max-height: 60vh">
        <template v-if="!carregando">
          <MgEmptyState v-if="!auditorias.length && !ocorrencias.length" plain icon="history">
            Nenhuma alteração registrada.
          </MgEmptyState>

          <!-- no histórico do próprio negócio, o link para ele não serve -->
          <template v-if="ocorrencias.length">
            <div class="text-subtitle2 text-grey-8 q-mb-sm">Ocorrências</div>
            <q-list bordered separator class="rounded-borders q-mb-md">
              <MgOcorrenciaItem
                v-for="oc in ocorrencias"
                :key="oc.codocorrencia"
                :ocorrencia="oc"
                :link-negocio="tabela != 'tblnegocio'"
                clickable
                :href="urlOcorrencia(oc.codocorrencia)"
                target="_blank"
              />
            </q-list>
          </template>

          <template v-if="auditorias.length">
            <div class="text-subtitle2 text-grey-8 q-mb-sm">Auditoria</div>
            <q-list bordered separator class="rounded-borders">
              <MgAuditoriaItem v-for="aud in auditorias" :key="aud.codauditoria" :auditoria="aud" />
            </q-list>
          </template>
        </template>
      </q-card-section>

      <q-inner-loading :showing="carregando" />

      <q-card-actions align="right">
        <q-btn flat label="Fechar" color="primary" v-close-popup />
      </q-card-actions>
    </q-card>
  </q-dialog>
</template>
