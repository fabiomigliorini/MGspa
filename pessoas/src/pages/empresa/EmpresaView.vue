<script setup>
import { formataCodigo, formataTimestamp } from '@components/formatters'
import { ref, onMounted, defineAsyncComponent, computed } from 'vue'
import { empresaStore } from 'src/stores/empresa'
import { useQuasar } from 'quasar'
import { useRoute, useRouter } from 'vue-router'
import { useAuth } from 'src/composables/useAuth'

const MGLayout = defineAsyncComponent(() => import('layouts/MGLayout.vue'))

const sEmpresa = empresaStore()
const $q = useQuasar()
const route = useRoute()
const router = useRouter()
const loading = ref(false)
const filtroFilial = ref('')

const buscarFiliais = async () => {
  sEmpresa.filtroFilial.filial = filtroFilial.value
  await sEmpresa.buscarFiliais(route.params.codempresa)
}

const modoEmissaoLabel = computed(() => {
  const modos = {
    1: 'Normal',
    9: 'Offline',
  }
  return modos[sEmpresa.item.modoemissaonfce] || '-'
})

const contingenciaFormatada = computed(() => {
  if (!sEmpresa.item.contingenciadata) return '-'
  return formataTimestamp(sEmpresa.item.contingenciadata)
})

const criacaoFormatada = computed(() => {
  if (!sEmpresa.item.criacao) return '-'
  return formataTimestamp(sEmpresa.item.criacao)
})

const alteracaoFormatada = computed(() => {
  if (!sEmpresa.item.alteracao) return '-'
  return formataTimestamp(sEmpresa.item.alteracao)
})

const carregarEmpresa = async () => {
  loading.value = true
  try {
    await sEmpresa.get(route.params.codempresa)
  } catch (error) {
    console.log(error)
    $q.notify({
      color: 'red-5',
      textColor: 'white',
      icon: 'error',
      message: 'Erro ao carregar empresa',
    })
    router.push('/empresa')
  } finally {
    loading.value = false
  }
}

const confirmarExclusao = () => {
  if (sEmpresa.filiais.length > 0) {
    $q.notify({
      color: 'red-5',
      textColor: 'white',
      icon: 'error',
      message: 'Não é possível excluir uma empresa que possui filiais. Exclua as filiais primeiro.',
    })
    return
  }

  $q.dialog({
    title: 'Confirmar Exclusão',
    message: `Para excluir a empresa "${sEmpresa.item.empresa}", digite EXCLUIR abaixo:`,
    prompt: {
      model: '',
      type: 'text',
      isValid: (val) => val === 'EXCLUIR',
    },
    cancel: { label: 'Cancelar', color: 'grey-8', flat: true },
    ok: { label: 'Excluir', color: 'red-5', flat: true },
  }).onOk(async () => {
    try {
      await sEmpresa.removerEmpresa(sEmpresa.item.codempresa)
      $q.notify({
        color: 'green-5',
        textColor: 'white',
        icon: 'check',
        message: 'Empresa excluída com sucesso!',
      })
      router.push('/empresa')
    } catch (error) {
      $q.notify({
        color: 'red-5',
        textColor: 'white',
        icon: 'error',
        message: error.response?.data?.message || 'Erro ao excluir empresa',
      })
    }
  })
}

// ---------------------------------------------------------------- Contingencia NFC-e
const { temPermissao } = useAuth()
const podeContingencia = computed(() => temPermissao('Gerente'))
const salvandoContingencia = ref(false)

const emContingencia = computed(() => sEmpresa.item?.modoemissaonfce == 9)

const salvarConfContingencia = async () => {
  salvandoContingencia.value = true
  try {
    await sEmpresa.atualizarEmpresa(sEmpresa.item.codempresa, {
      contingenciaautomatica: sEmpresa.item.contingenciaautomatica,
      contingenciatolerancia: sEmpresa.item.contingenciatolerancia,
    })
    $q.notify({ color: 'green-5', icon: 'done', message: 'Configuração salva!' })
  } catch (error) {
    $q.notify({
      color: 'red-5',
      icon: 'error',
      message: error.response?.data?.message || 'Erro ao salvar configuração',
    })
  } finally {
    salvandoContingencia.value = false
  }
}

const entrarContingencia = () => {
  $q.dialog({
    title: 'Entrar em contingência',
    message:
      'Toda NFC-e passará a ser emitida off-line, sem passar pela SEFAZ, e transmitida ' +
      'depois pelo robô (prazo legal de 24h). Informe a justificativa:',
    prompt: {
      model: '',
      type: 'text',
      outlined: true,
      isValid: (val) => val && val.length >= 15,
    },
    cancel: { label: 'Cancelar', flat: true },
    ok: { label: 'Entrar em contingência', flat: true, color: 'orange' },
  }).onOk(async (justificativa) => {
    try {
      await sEmpresa.entrarContingencia(sEmpresa.item.codempresa, justificativa)
      $q.notify({ color: 'orange', icon: 'cloud_off', message: 'Empresa em contingência off-line' })
    } catch (error) {
      $q.notify({
        color: 'red-5',
        icon: 'error',
        message: error.response?.data?.message || 'Erro ao entrar em contingência',
      })
    }
  })
}

const sairContingencia = () => {
  $q.dialog({
    title: 'Voltar ao modo normal',
    message:
      'A NFC-e volta a ser transmitida direto para a SEFAZ. As notas já emitidas ' +
      'off-line continuam pendentes e serão transmitidas pelo robô.',
    cancel: { label: 'Cancelar', flat: true },
    ok: { label: 'Voltar ao normal', flat: true, color: 'primary' },
  }).onOk(async () => {
    try {
      await sEmpresa.sairContingencia(sEmpresa.item.codempresa)
      $q.notify({ color: 'green-5', icon: 'cloud_done', message: 'Emissão normal restabelecida' })
    } catch (error) {
      $q.notify({
        color: 'red-5',
        icon: 'error',
        message: error.response?.data?.message || 'Erro ao sair da contingência',
      })
    }
  })
}

onMounted(() => {
  carregarEmpresa()
  buscarFiliais()
})
</script>

<template>
  <MGLayout back-button>
    <template #tituloPagina>
      <span class="q-pl-sm">Detalhes da Empresa</span>
    </template>

    <template #botaoVoltar>
      <q-btn flat dense round to="/empresa" icon="arrow_back" aria-label="Voltar" />
    </template>

    <template #content>
      <q-page>
        <div v-if="!loading && sEmpresa.item.codempresa" class="container-detalhes">
          <!-- HEADER -->
          <q-item class="q-pt-lg q-pb-sm">
            <q-item-section avatar>
              <q-avatar color="grey-8" text-color="grey-4" size="80px" icon="business" />
            </q-item-section>
            <q-item-section>
              <div class="text-h4 text-grey-9">
                #{{ sEmpresa.item.codempresa }} {{ sEmpresa.item.empresa }}
              </div>
            </q-item-section>
          </q-item>

          <!-- CONTEÚDO -->
          <div class="row q-col-gutter-md q-pa-md">
            <!-- COLUNA PRINCIPAL -->
            <div class="col-xs-12 col-md-8">
              <div class="row q-col-gutter-md">
                <!-- CARD DETALHES -->
                <div class="col-12">
                  <q-card bordered flat class="q-pa-none">
                    <q-card-section class="text-grey-9 text-overline row items-center q-pa-md">
                      DETALHES DA EMPRESA
                      <q-space />
                      <q-btn
                        flat
                        round
                        dense
                        icon="edit"
                        size="sm"
                        color="grey-7"
                        :to="'/empresa/' + sEmpresa.item.codempresa + '/editar'"
                      >
                        <q-tooltip>Editar</q-tooltip>
                      </q-btn>
                      <q-btn
                        flat
                        round
                        dense
                        icon="delete"
                        size="sm"
                        color="grey-7"
                        @click="confirmarExclusao"
                      >
                        <q-tooltip>Excluir</q-tooltip>
                      </q-btn>
                      <q-btn flat round dense icon="info" size="sm" color="grey-7">
                        <q-tooltip>
                          <div>Criado em: {{ criacaoFormatada }}</div>
                          <div>Alterado em: {{ alteracaoFormatada }}</div>
                        </q-tooltip>
                      </q-btn>
                    </q-card-section>

                    <!-- Info Grid -->
                    <div class="row q-col-gutter-sm q-pa-md">
                      <div class="col-xs-12 col-sm-6">
                        <div class="text-overline text-grey-7">Codigo</div>
                        <div class="text-body2">
                          {{ formataCodigo(sEmpresa.item.codempresa) }}
                        </div>
                      </div>

                      <div class="col-xs-12 col-sm-6">
                        <div class="text-overline text-grey-7">Modo Emissão NFCe</div>
                        <div class="text-body2">
                          <q-badge
                            :color="sEmpresa.item.modoemissaonfce === 1 ? 'green' : 'orange'"
                            :label="modoEmissaoLabel"
                          />
                        </div>
                      </div>

                      <div class="col-xs-12 col-sm-6" v-if="sEmpresa.item.contingenciadata">
                        <div class="text-overline text-grey-7">Data de Contingência</div>
                        <div class="text-body2">
                          {{ contingenciaFormatada }}
                        </div>
                      </div>

                      <div
                        class="col-xs-12 col-sm-6"
                        v-if="sEmpresa.item.contingenciajustificativa"
                      >
                        <div class="text-overline text-grey-7">Justificativa de Contingência</div>
                        <div class="text-body2">
                          {{ sEmpresa.item.contingenciajustificativa }}
                        </div>
                      </div>

                      <div class="col-xs-12 col-sm-6" v-if="sEmpresa.item.criacao">
                        <div class="text-overline text-grey-7">Criação</div>
                        <div class="text-body2">
                          {{ criacaoFormatada }}
                        </div>
                      </div>

                      <div class="col-xs-12 col-sm-6">
                        <div class="text-overline text-grey-7">Última Alteração</div>
                        <div class="text-body2">
                          {{ alteracaoFormatada }}
                        </div>
                      </div>
                    </div>
                  </q-card>
                </div>
              </div>
            </div>

            <!-- COLUNA LATERAL -->
            <div class="col-xs-12 col-md-4">
              <div class="row q-col-gutter-md">
                <!-- CARD CONTINGENCIA NFC-e -->
                <div class="col-12">
                  <q-card bordered flat>
                    <q-card-section class="text-grey-9 text-overline row items-center">
                      CONTINGÊNCIA NFC-e
                      <q-space />
                      <q-badge :color="emContingencia ? 'orange' : 'secondary'">
                        <q-icon
                          :name="emContingencia ? 'cloud_off' : 'cloud_done'"
                          size="xs"
                          class="q-mr-xs"
                        />
                        {{ emContingencia ? 'Off-line' : 'Normal' }}
                      </q-badge>
                    </q-card-section>

                    <q-card-section
                      v-if="emContingencia && sEmpresa.item?.contingenciadata"
                      class="q-pt-none"
                    >
                      <div class="text-caption text-grey-7">Desde</div>
                      <div class="text-body2 q-mb-sm">
                        {{ formataTimestamp(sEmpresa.item.contingenciadata) }}
                      </div>
                      <div class="text-caption text-grey-7">Justificativa</div>
                      <div class="text-body2">
                        {{ sEmpresa.item.contingenciajustificativa || '-' }}
                      </div>
                    </q-card-section>

                    <q-separator inset />

                    <q-card-section class="q-pt-md">
                      <q-toggle
                        v-model="sEmpresa.item.contingenciaautomatica"
                        label="Contingência automática"
                        :disable="!podeContingencia"
                        @update:model-value="salvarConfContingencia"
                      />
                      <div class="text-caption text-grey-7 q-mb-md">
                        Entra em contingência após 10 comunicações seguidas com a SEFAZ acima da
                        tolerância (ou com erro), e volta ao normal após 20 dentro dela.
                      </div>

                      <q-input
                        v-model.number="sEmpresa.item.contingenciatolerancia"
                        outlined
                        type="number"
                        label="Tolerância (segundos)"
                        :disable="!podeContingencia"
                        :loading="salvandoContingencia"
                        @blur="salvarConfContingencia"
                      />
                    </q-card-section>

                    <q-card-actions v-if="podeContingencia" align="right">
                      <q-btn
                        v-if="!emContingencia"
                        flat
                        color="orange"
                        icon="cloud_off"
                        label="Forçar contingência"
                        @click="entrarContingencia"
                      />
                      <q-btn
                        v-else
                        flat
                        color="primary"
                        icon="cloud_done"
                        label="Voltar ao normal"
                        @click="sairContingencia"
                      />
                    </q-card-actions>
                  </q-card>
                </div>

                <!-- CARD FILIAIS -->
                <div class="col-12">
                  <q-card bordered flat>
                    <q-card-section class="text-grey-9 text-overline row items-center">
                      FILIAIS
                      <q-space />
                      <q-btn
                        flat
                        round
                        dense
                        icon="add"
                        size="sm"
                        color="grey-7"
                        :to="'/empresa/' + sEmpresa.item.codempresa + '/filial/nova'"
                      >
                        <q-tooltip>Nova Filial</q-tooltip>
                      </q-btn>
                    </q-card-section>

                    <q-card-section class="q-pt-none">
                      <q-input
                        v-model="filtroFilial"
                        outlined
                        dense
                        clearable
                        label="Buscar filial"
                        @keyup.enter="buscarFiliais"
                        @clear="buscarFiliais"
                      >
                        <template v-slot:prepend>
                          <q-icon name="search" />
                        </template>
                      </q-input>
                    </q-card-section>

                    <q-inner-loading :showing="sEmpresa.loadingFiliais">
                      <q-spinner-gears size="30px" color="primary" />
                    </q-inner-loading>

                    <div
                      v-if="!sEmpresa.loadingFiliais && sEmpresa.filiais.length === 0"
                      class="text-grey text-center q-pa-md"
                    >
                      Nenhuma filial cadastrada
                    </div>

                    <q-list v-if="!sEmpresa.loadingFiliais">
                      <template v-for="(filial, index) in sEmpresa.filiais" :key="filial.codfilial">
                        <q-separator v-if="index > 0" inset />
                        <q-item clickable :to="'/filial/' + filial.codfilial">
                          <q-item-section avatar>
                            <q-icon color="primary" name="store" size="xs" />
                          </q-item-section>
                          <q-item-section>
                            <q-item-label class="text-caption text-bold">
                              {{ filial.filial }}
                            </q-item-label>
                            <q-item-label caption>
                              {{ formataCodigo(filial.codfilial) }}
                            </q-item-label>
                            <q-item-label caption class="ellipsis">
                              {{ filial.Pessoa?.fantasia || '-' }}
                            </q-item-label>
                          </q-item-section>
                          <q-item-section side>
                            <q-icon name="chevron_right" color="grey" />
                          </q-item-section>
                        </q-item>
                      </template>
                    </q-list>
                  </q-card>
                </div>
              </div>
            </div>
          </div>
        </div>
      </q-page>
    </template>
  </MGLayout>
</template>

<style scoped>
.container-detalhes {
  max-width: 1280px;
  margin: auto;
}
</style>
