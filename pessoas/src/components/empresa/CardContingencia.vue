<script setup>
import { ref, computed, watch } from 'vue'
import { useQuasar } from 'quasar'
import MgInputData from '@components/MgInputData.vue'
import { empresaStore } from 'src/stores/empresa'
import { useAuth } from 'src/composables/useAuth'

const sEmpresa = empresaStore()
const $q = useQuasar()

const { temPermissao } = useAuth()
const podeContingencia = computed(() => temPermissao('Gerente'))
const salvandoContingencia = ref(false)

const opcoesModoemissao = [
  { label: 'Normal', value: 1 },
  { label: 'Offline', value: 9 },
]

// Minimo do xJust da SEFAZ, mesma regra do EmpresaContingenciaRequest no backend.
const JUSTIFICATIVA_MINIMA = 15

const emContingencia = computed(() => sEmpresa.item?.modoemissaonfce == 9)

// O select nao pode ser mutado otimisticamente: se o usuario cancelar o dialogo
// o valor precisa voltar ao que a store diz. Por isso um ref espelhando a store.
const modoEmissao = ref(sEmpresa.item?.modoemissaonfce ?? 1)

watch(
  () => sEmpresa.item?.modoemissaonfce,
  (val) => {
    modoEmissao.value = val ?? 1
  },
  { immediate: true },
)

const ressincronizar = () => {
  modoEmissao.value = sEmpresa.item?.modoemissaonfce ?? 1
}

const salvarConfContingencia = async () => {
  salvandoContingencia.value = true
  try {
    await sEmpresa.atualizarEmpresa(sEmpresa.item.codempresa, {
      contingenciadata: sEmpresa.item.contingenciadata,
      contingenciajustificativa: sEmpresa.item.contingenciajustificativa,
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

// Grava so quando a data realmente muda: o MgInputData emite uma unica vez,
// depois da mascara completa, e nao a cada tecla.
const salvarDataContingencia = (valor) => {
  sEmpresa.item.contingenciadata = valor
  salvarConfContingencia()
}

const entrarContingencia = () => {
  $q.dialog({
    title: 'Entrar em contingência',
    message:
      'Toda NFC-e passará a ser emitida off-line, sem passar pela SEFAZ, e transmitida ' +
      'depois pelo robô (prazo legal de 24h). Confirma?',
    cancel: { label: 'Cancelar', flat: true },
    ok: { label: 'Entrar em contingência', flat: true, color: 'orange' },
  })
    .onOk(async () => {
      try {
        await sEmpresa.entrarContingencia(
          sEmpresa.item.codempresa,
          sEmpresa.item.contingenciajustificativa,
        )
        $q.notify({
          color: 'orange',
          icon: 'cloud_off',
          message: 'Empresa em contingência off-line',
        })
      } catch (error) {
        $q.notify({
          color: 'red-5',
          icon: 'error',
          message: error.response?.data?.message || 'Erro ao entrar em contingência',
        })
      } finally {
        ressincronizar()
      }
    })
    .onCancel(ressincronizar)
}

const sairContingencia = () => {
  $q.dialog({
    title: 'Voltar ao modo normal',
    message:
      'A NFC-e volta a ser transmitida direto para a SEFAZ. As notas já emitidas ' +
      'off-line continuam pendentes e serão transmitidas pelo robô.',
    cancel: { label: 'Cancelar', flat: true },
    ok: { label: 'Voltar ao normal', flat: true, color: 'primary' },
  })
    .onOk(async () => {
      try {
        await sEmpresa.sairContingencia(sEmpresa.item.codempresa)
        $q.notify({ color: 'green-5', icon: 'cloud_done', message: 'Emissão normal restabelecida' })
      } catch (error) {
        $q.notify({
          color: 'red-5',
          icon: 'error',
          message: error.response?.data?.message || 'Erro ao sair da contingência',
        })
      } finally {
        ressincronizar()
      }
    })
    .onCancel(ressincronizar)
}

const trocarModoEmissao = (valor) => {
  if (valor == sEmpresa.item?.modoemissaonfce) {
    return
  }
  if (valor != 9) {
    sairContingencia()
    return
  }
  // A justificativa vai no proprio campo, entao ela precisa estar preenchida
  // antes de trocar o modo — senao a SEFAZ rejeita o xJust.
  const justificativa = sEmpresa.item?.contingenciajustificativa || ''
  if (justificativa.length < JUSTIFICATIVA_MINIMA) {
    $q.notify({
      color: 'red-5',
      icon: 'error',
      message: `Preencha a justificativa com no mínimo ${JUSTIFICATIVA_MINIMA} caracteres.`,
    })
    ressincronizar()
    return
  }
  entrarContingencia()
}
</script>

<template>
  <div class="q-gutter-sm">
    <div class="text-grey-9 text-overline row items-center">
      CONTINGÊNCIA NFC-e
      <q-space />
      <q-badge :color="emContingencia ? 'orange' : 'secondary'">
        <q-icon :name="emContingencia ? 'cloud_off' : 'cloud_done'" size="xs" class="q-mr-xs" />
        {{ emContingencia ? 'Off-line' : 'Normal' }}
      </q-badge>
    </div>

    <q-select
      outlined
      v-model="modoEmissao"
      :options="opcoesModoemissao"
      label="Modo Emissão NFC-e"
      emit-value
      map-options
      :disable="!podeContingencia"
      class="q-pa-none"
      @update:model-value="trocarModoEmissao"
    />

    <MgInputData
      :model-value="sEmpresa.item.contingenciadata"
      type="timestamp"
      label="Data de Contingência"
      input-class="mg-data-alinhada"
      :readonly="!podeContingencia"
      @update:model-value="salvarDataContingencia"
    />

    <q-input
      outlined
      v-model="sEmpresa.item.contingenciajustificativa"
      label="Justificativa de Contingência"
      type="textarea"
      rows="3"
      :readonly="!podeContingencia"
      class="q-pa-none"
      @blur="salvarConfContingencia"
    />

    <q-toggle
      v-model="sEmpresa.item.contingenciaautomatica"
      label="Contingência automática"
      :disable="!podeContingencia"
      @update:model-value="salvarConfContingencia"
    />
    <div class="text-caption text-grey-7">
      Entra em contingência após 10 comunicações seguidas com a SEFAZ acima da tolerância (ou com
      erro), e volta ao normal após 20 dentro dela.
    </div>

    <q-input
      v-model.number="sEmpresa.item.contingenciatolerancia"
      outlined
      type="number"
      label="Tolerância (segundos)"
      :disable="!podeContingencia"
      :loading="salvandoContingencia"
      class="q-pa-none"
      @blur="salvarConfContingencia"
    />
  </div>
</template>

<style scoped>
/* MgInputData centraliza o texto por padrao; aqui ele precisa alinhar
   com os demais campos da tela. */
:deep(input.mg-data-alinhada) {
  text-align: left;
}
</style>
