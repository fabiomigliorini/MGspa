<script setup>
import { ref, computed } from 'vue'
import { useRouter } from 'vue-router'
import { useQuasar } from 'quasar'
import { api } from 'src/services/api'
import { notifySuccess, notifyError } from 'src/utils/notify'
import MgSelectPortador from '@components/MgSelectPortador.vue'
import SelectPessoa from '@components/MgSelectPessoa.vue'
import MgInputData from '@components/MgInputData.vue'
import SeletorTitulosAbertos from 'src/components/SeletorTitulosAbertos.vue'
import { formataNumero, formataDataIso } from '@components/formatters'
import { useAuthStore } from 'src/stores/auth'
import { useLiquidacaoTituloStore } from 'src/stores/liquidacaoTituloStore'
import { useSelectCacheStore } from '@components/stores/selectCacheStore'

const router = useRouter()
const $q = useQuasar()
const auth = useAuthStore()
const store = useLiquidacaoTituloStore()
const selectCache = useSelectCacheStore()

const filiaisPortador = computed(() => auth.filiaisRestritas())

const saving = ref(false)
const dialogFinalizar = ref(false)

const titulos = ref([])
const codpessoaFiltro = ref(null)
const totalLiquido = ref(0)
const operacao = ref('DB')

const finalizar = ref({
  transacao: formataDataIso(new Date()),
  codpessoa: null,
  codportador: null,
  observacao: '',
})

const podeAvancar = computed(() => titulos.value.length > 0)

const portadorCacheKey = () =>
  auth.usuario?.codusuario ? `liquidacao-titulo:ultimoPortador:${auth.usuario.codusuario}` : null

async function abrirFinalizar() {
  // Sugere pessoa quando todos os títulos têm a mesma
  const codpessoas = new Set(titulos.value.map((l) => l.codpessoa).filter((v) => v != null))
  finalizar.value.codpessoa = codpessoas.size === 1 ? [...codpessoas][0] : null

  // Sugere último portador do usuário (se disponível na sua filial)
  finalizar.value.codportador = null
  const key = portadorCacheKey()
  const cod = key ? Number(localStorage.getItem(key)) : null
  if (cod) {
    await selectCache.loadList('portador', 'v1/select/portador')
    const filiais = filiaisPortador.value
    const disponivel = (selectCache.entities.portador?.items || []).some((p) => {
      if (Number(p.codportador) !== cod) return false
      if (filiais == null) return true
      return filiais.map(Number).includes(Number(p.codfilial))
    })
    if (disponivel) finalizar.value.codportador = cod
  }

  dialogFinalizar.value = true
}

async function salvar() {
  if (!finalizar.value.codpessoa) {
    notifyError({ message: 'Selecione a pessoa!' }, 'Selecione a pessoa')
    return
  }
  if (!finalizar.value.codportador) {
    notifyError({ message: 'Selecione um portador!' }, 'Selecione um portador')
    return
  }
  $q.dialog({
    title: 'Confirmar',
    message: 'Tem certeza que deseja salvar a liquidação?',
    ok: { label: 'Confirmar', color: 'primary', flat: true },
    cancel: { label: 'Cancelar', color: 'grey-8', flat: true },
  }).onOk(async () => {
    saving.value = true
    try {
      const payload = {
        codpessoa: finalizar.value.codpessoa,
        codportador: finalizar.value.codportador,
        transacao: finalizar.value.transacao,
        observacao: finalizar.value.observacao || null,
        titulos: titulos.value.map((l) => ({
          codtitulo: l.codtitulo,
          saldo: l.saldo,
          multa: l.multa,
          juros: l.juros,
          desconto: l.desconto,
          total: l.total,
        })),
      }
      const { data } = await api.post('v1/liquidacao-titulo', payload)
      store.upsertLocal(data.data)
      const key = portadorCacheKey()
      if (key) localStorage.setItem(key, String(payload.codportador))
      notifySuccess('Liquidação criada')
      router.replace({
        name: 'liquidacao-titulo-detalhe',
        params: { id: data.data.codliquidacaotitulo },
      })
    } catch (e) {
      notifyError(e, 'Erro ao salvar liquidação')
    } finally {
      saving.value = false
    }
  })
}
</script>

<template>
  <q-page class="q-pa-md">
    <div style="max-width: 1086px; margin: auto">
      <q-item class="q-pb-md q-px-none">
        <q-item-section avatar>
          <q-btn
            flat
            dense
            round
            icon="arrow_back"
            :to="{ name: 'liquidacao-titulo' }"
            aria-label="Voltar"
          />
        </q-item-section>
        <q-item-section>
          <div class="text-h5 text-grey-9">Nova Liquidação</div>
        </q-item-section>
      </q-item>

      <div>
        <SeletorTitulosAbertos
          v-model="titulos"
          :codpessoa-inicial="codpessoaFiltro"
          @update:codpessoa="(v) => (codpessoaFiltro = v)"
          @update:total-liquido="(v) => (totalLiquido = v)"
          @update:operacao="(v) => (operacao = v)"
        />
      </div>
    </div>

    <!-- FAB Salvar -->
    <q-page-sticky position="bottom-right" :offset="[18, 18]">
      <q-btn fab icon="save" color="primary" :disable="!podeAvancar" @click="abrirFinalizar">
        <q-tooltip>Finalizar Liquidação</q-tooltip>
      </q-btn>
    </q-page-sticky>

    <!-- Dialog Finalizar -->
    <q-dialog v-model="dialogFinalizar">
      <q-card bordered flat style="width: 500px; max-width: 90vw">
        <q-form @submit.prevent="salvar">
          <q-card-section class="items-center q-pb-none">
            <div class="text-grey-9 text-overline">FINALIZAR</div>
            <div class="text-grey-7 q-mb-md text-caption">
              {{ titulos.length }} títulos selecionados — R$ {{ formataNumero(totalLiquido) }}
              {{ operacao }}
            </div>
          </q-card-section>
          <q-separator inset />
          <q-card-section>
            <div class="row q-col-gutter-md">
              <div class="col-xs-12 col-sm-4">
                <MgInputData
                  v-model="finalizar.transacao"
                  type="date"
                  label="Data Transação"
                  stack-label
                  :rules="[(v) => !!v || 'Obrigatório']"
                />
              </div>
              <div class="col-xs-12 col-sm-8">
                <MgSelectPortador
                  v-model="finalizar.codportador"
                  outlined
                  label="Portador"
                  :rules="[(v) => !!v || 'Obrigatório']"
                  :filiais="filiaisPortador"
                  autofocus
                />
              </div>
              <div class="col-xs-12">
                <SelectPessoa
                  v-model="finalizar.codpessoa"
                  outlined
                  label="Pessoa"
                  :rules="[(v) => !!v || 'Obrigatório']"
                />
              </div>
              <div class="col-12">
                <q-input
                  v-model="finalizar.observacao"
                  outlined
                  type="textarea"
                  label="Observação"
                  maxlength="200"
                  autogrow
                />
              </div>
            </div>
          </q-card-section>
          <q-separator inset />
          <q-card-actions align="right">
            <q-btn flat label="Cancelar" color="grey-8" v-close-popup tabindex="-1" />
            <q-btn flat label="Salvar" type="submit" color="primary" :loading="saving" />
          </q-card-actions>
        </q-form>
      </q-card>
    </q-dialog>
  </q-page>
</template>
