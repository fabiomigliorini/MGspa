<script setup>
import { ref, computed } from 'vue'
import { useRouter } from 'vue-router'
import { useQuasar, date } from 'quasar'
import { api } from 'src/services/api'
import { notifySuccess, notifyError } from 'src/utils/notify'
import SelectPortador from 'src/components/select/SelectPortador.vue'
import MgInputData from '@components/MgInputData.vue'
import SeletorTitulosAbertos from 'src/components/SeletorTitulosAbertos.vue'
import { formataNumero } from 'src/utils/formatters.js'

const router = useRouter()
const $q = useQuasar()

const step = ref('titulos')
const saving = ref(false)

const linhas = ref([])
const codpessoaFiltro = ref(null)
const totalLiquido = ref(0)
const operacao = ref('DB')

const finalizar = ref({
  transacao: date.formatDate(new Date(), 'YYYY-MM-DD'),
  codportador: null,
  observacao: '',
})

const podeAvancar = computed(() => linhas.value.length > 0)

function descricaoTitulos() {
  if (linhas.value.length === 0) return 'Nenhum título selecionado'
  return `${linhas.value.length} título(s) — ${formataNumero(totalLiquido.value)} ${operacao.value}`
}

async function salvar() {
  if (!finalizar.value.codportador) {
    notifyError({ message: 'Selecione um portador!' }, 'Selecione um portador')
    return
  }
  $q.dialog({
    title: 'Confirmar',
    message: 'Tem certeza que deseja salvar a liquidação?',
    cancel: true,
  }).onOk(async () => {
    saving.value = true
    try {
      const codpessoa = codpessoaFiltro.value
      if (!codpessoa) throw new Error('Selecione a pessoa nos filtros do passo 1!')
      const payload = {
        codpessoa,
        codportador: finalizar.value.codportador,
        transacao: finalizar.value.transacao,
        observacao: finalizar.value.observacao || null,
        titulos: linhas.value.map((l) => ({
          codtitulo: l.codtitulo,
          saldo: l.saldo,
          multa: l.multa,
          juros: l.juros,
          desconto: l.desconto,
          total: l.total,
        })),
      }
      const { data } = await api.post('v1/liquidacao-titulo', payload)
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
    <div style="max-width: 1200px; margin: auto">
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

      <q-stepper v-model="step" header-nav animated flat bordered>
        <q-step
          name="titulos"
          title="Títulos"
          icon="receipt_long"
          :done="podeAvancar"
        >
          <SeletorTitulosAbertos
            v-model="linhas"
            :codpessoa-inicial="codpessoaFiltro"
            @update:codpessoa="(v) => (codpessoaFiltro = v)"
            @update:total-liquido="(v) => (totalLiquido = v)"
            @update:operacao="(v) => (operacao = v)"
          />
        </q-step>

        <q-step
          name="finalizar"
          title="Finalizar"
          icon="check_circle"
          :disable="!podeAvancar"
        >
          <div class="text-grey-7 q-mb-md">{{ descricaoTitulos() }}</div>

          <q-form @submit.prevent="salvar">
            <div class="row q-col-gutter-md">
              <div class="col-xs-12 col-sm-4">
                <MgInputData
                  v-model="finalizar.transacao"
                  type="date"
                  label="Data Transação"
                  stack-label
                  :rules="[(v) => !!v || 'Obrigatório']"
                  autofocus
                />
              </div>
              <div class="col-xs-12 col-sm-8">
                <SelectPortador
                  v-model="finalizar.codportador"
                  outlined
                  label="Portador"
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
            <div class="q-mt-md text-right">
              <q-btn label="Salvar" type="submit" color="primary" :loading="saving" />
            </div>
          </q-form>
        </q-step>

        <template #navigation>
          <q-stepper-navigation>
            <q-btn
              v-if="step === 'titulos'"
              :disable="!podeAvancar"
              color="primary"
              label="Continuar"
              @click="step = 'finalizar'"
            />
            <q-btn
              v-else
              flat
              color="primary"
              label="Voltar"
              class="q-ml-sm"
              @click="step = 'titulos'"
            />
          </q-stepper-navigation>
        </template>
      </q-stepper>
    </div>
  </q-page>
</template>
