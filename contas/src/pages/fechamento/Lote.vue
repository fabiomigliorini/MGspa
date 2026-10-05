<script setup>
// Lote da maquineta = o borderô (M9 doc-3). Aberto: o gerente digita crédito e débito do borderô
// às cegas, fotografa o borderô e confere; só depois vê o sistema e a diferença. Os lançamentos
// se corrigem com o lote aberto (maquineta errada, crédito/débito, venda depois do borderô para o
// lote seguinte, registro indevido).
import { ref, computed, onMounted, onBeforeUnmount, watch } from 'vue'
import { useRoute } from 'vue-router'
import { useQuasar } from 'quasar'
import { api } from 'src/services/api'
import { blobUrlFromApi } from '@components/blobUrlFromApi'
import { formataNumero, formataTimestamp } from '@components/formatters'
import MgInput from '@components/MgInput.vue'
import MgInputValor from '@components/MgInputValor.vue'
import MgSlim from '@components/MgSlim.vue'
import MgInfoCriacao from '@components/MgInfoCriacao.vue'
import { useConferenciaStore } from 'src/stores/conferenciaStore'
import ListaLancamentos from 'src/components/conferencia/ListaLancamentos.vue'
import CorrecaoPagamentoDialog from 'src/components/conferencia/CorrecaoPagamentoDialog.vue'

const route = useRoute()
const $q = useQuasar()
const store = useConferenciaStore()

const id = computed(() => Number(route.params.id))
const lote = computed(() => store.lote)

const form = ref({ creditoinformado: null, debitoinformado: null, observacoes: '' })
const verLancamentos = ref(false)

const diferenca = (informado, sistema) =>
  Math.round(((informado ?? 0) - (sistema ?? 0)) * 100) / 100
const corDiferenca = (v) => (Math.abs(v) < 0.005 ? 'text-green-8' : 'text-red-8')

async function carregar() {
  await store.carregarLote(id.value)
  form.value = { creditoinformado: null, debitoinformado: null, observacoes: '' }
  verLancamentos.value = false
  carregarFotos()
}

async function conferir() {
  await store.fecharLote(id.value, {
    creditoinformado: form.value.creditoinformado ?? 0,
    debitoinformado: form.value.debitoinformado ?? 0,
    observacoes: form.value.observacoes || null,
  })
}

function reabrir() {
  $q.dialog({
    title: 'Reabrir',
    message: 'Reabrir o lote para corrigir os lançamentos? A conferência é desfeita.',
    cancel: { label: 'Cancelar', color: 'grey-8', flat: true },
    ok: { label: 'Reabrir', color: 'primary', flat: true },
  }).onOk(() => store.reabrirLote(id.value))
}

// ---- foto do borderô ----
const fotos = ref([])
async function carregarFotos() {
  fotos.value.forEach((f) => URL.revokeObjectURL(f.url))
  fotos.value = []
  for (const arquivo of lote.value?.fotos ?? []) {
    try {
      const url = await blobUrlFromApi(api, `v1/conferencia/lote/${id.value}/foto/${arquivo}`, null)
      fotos.value.push({ arquivo, url })
    } catch {
      // foto que não abre não impede a conferência
    }
  }
}
async function anexarFoto(base64) {
  if (await store.enviarFotoLote(id.value, base64)) carregarFotos()
}

// ---- correções ----
const dialogCorrecao = ref(false)
const corrigindo = ref(null)
const corrigir = (l) => {
  corrigindo.value = l
  dialogCorrecao.value = true
}
const indevido = (l) => {
  $q.dialog({
    title: 'Registro indevido',
    message: `Cancelar o lançamento de ${formataNumero(l.total)}? Ele sai do lote como se nunca tivesse entrado (não é cancelamento na maquineta).`,
    prompt: {
      model: '',
      type: 'text',
      label: 'Justificativa',
      isValid: (v) => v.trim().length >= 5,
    },
    cancel: { label: 'Voltar', color: 'grey-8', flat: true },
    ok: { label: 'Cancelar lançamento', color: 'red-5', flat: true },
  }).onOk(async (justificativa) => {
    if (await store.indevido(l.codpagamento, justificativa)) carregar()
  })
}

onMounted(carregar)
watch(id, carregar)
onBeforeUnmount(() => fotos.value.forEach((f) => URL.revokeObjectURL(f.url)))
</script>

<template>
  <q-page>
    <div class="q-pa-md" style="max-width: 1086px; margin: auto">
      <q-btn
        flat
        round
        icon="arrow_back"
        :to="{ name: 'fechamento' }"
        aria-label="Voltar"
        class="q-mb-sm"
      />

      <template v-if="lote">
        <q-card bordered flat class="q-mb-md">
          <q-card-section>
            <div class="row items-center">
              <div class="col">
                <div class="text-h6">{{ lote.maquineta }}</div>
                <div class="text-caption text-grey-7">
                  {{ lote.adquirente }} ·
                  {{ lote.compartilhada ? 'Todas as filiais' : lote.filial }} · lote
                  {{ lote.codmaquinetalote }} desde {{ formataTimestamp(lote.abertura, 2) }}
                </div>
              </div>
              <q-badge
                :color="lote.aberto ? 'amber-8' : 'green-7'"
                :label="lote.aberto ? 'A conferir' : 'Conferido'"
              />
            </div>
          </q-card-section>

          <!-- às cegas: digita o borderô antes de ver o sistema -->
          <q-form v-if="lote.aberto" @submit.prevent="conferir">
            <q-card-section>
              <div class="row q-col-gutter-md">
                <div class="col-6">
                  <MgInputValor
                    v-model="form.creditoinformado"
                    label="Crédito do borderô"
                    autofocus
                  />
                </div>
                <div class="col-6">
                  <MgInputValor v-model="form.debitoinformado" label="Débito do borderô" />
                </div>
                <div class="col-12">
                  <MgInput
                    v-model="form.observacoes"
                    label="Observação"
                    type="textarea"
                    autogrow
                    maxlength="500"
                  />
                </div>
              </div>
            </q-card-section>
            <q-card-actions align="right">
              <q-btn
                unelevated
                color="primary"
                icon="done_all"
                label="Conferir"
                type="submit"
                :loading="store.salvando"
              />
            </q-card-actions>
          </q-form>

          <!-- conferido: borderô × sistema -->
          <q-card-section v-else>
            <q-markup-table flat bordered separator="horizontal">
              <thead>
                <tr>
                  <th class="text-left"></th>
                  <th class="text-right">Borderô</th>
                  <th class="text-right">Sistema</th>
                  <th class="text-right">Diferença</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="c in ['credito', 'debito']" :key="c">
                  <td>{{ c === 'credito' ? 'Crédito' : 'Débito' }}</td>
                  <td class="text-right">{{ formataNumero(lote[`${c}informado`]) }}</td>
                  <td class="text-right">{{ formataNumero(lote[`${c}sistema`]) }}</td>
                  <td
                    class="text-right text-weight-bold"
                    :class="corDiferenca(diferenca(lote[`${c}informado`], lote[`${c}sistema`]))"
                  >
                    {{ formataNumero(diferenca(lote[`${c}informado`], lote[`${c}sistema`])) }}
                  </td>
                </tr>
              </tbody>
            </q-markup-table>
            <div v-if="lote.sistema?.pdvs?.length > 1" class="text-caption text-grey-7 q-mt-sm">
              Por caixa:
              <span v-for="p in lote.sistema.pdvs" :key="p.codpdv ?? 0" class="q-mr-md">
                {{ p.pdv }}: crédito {{ formataNumero(p.credito) }}, débito
                {{ formataNumero(p.debito) }}
              </span>
            </div>
            <div class="text-caption text-grey-7 q-mt-sm">
              Conferido por {{ lote.usuariofechamento }} em
              {{ formataTimestamp(lote.fechamento, 2) }}
              <template v-if="lote.observacoes"> · {{ lote.observacoes }}</template>
            </div>
          </q-card-section>
          <q-card-actions v-if="!lote.aberto" align="right">
            <q-btn flat color="primary" icon="lock_open" label="Reabrir" @click="reabrir" />
          </q-card-actions>
        </q-card>

        <q-card bordered flat class="q-mb-md">
          <q-card-section class="text-subtitle1 q-pb-sm">Foto do borderô</q-card-section>
          <q-card-section class="q-pt-none">
            <div class="row q-col-gutter-sm q-mb-sm">
              <div v-for="f in fotos" :key="f.arquivo" class="col-6 col-sm-3">
                <a :href="f.url" target="_blank">
                  <q-img :src="f.url" :ratio="1" fit="contain" class="rounded-borders" />
                </a>
              </div>
            </div>
            <MgSlim label="Toque para fotografar o borderô" @imagem="anexarFoto" />
          </q-card-section>
        </q-card>

        <q-card bordered flat class="q-mb-md">
          <q-card-section class="row items-center q-pb-sm">
            <div class="text-subtitle1 col">Lançamentos</div>
            <q-btn
              v-if="lote.aberto && !verLancamentos"
              flat
              size="sm"
              color="primary"
              label="Ver"
              @click="verLancamentos = true"
            />
          </q-card-section>
          <q-card-section v-if="lote.aberto && !verLancamentos" class="text-caption text-grey-7">
            Digite o borderô antes: os lançamentos mostram os valores do sistema.
          </q-card-section>
          <q-card-section v-else class="q-pt-none">
            <ListaLancamentos
              :lancamentos="lote.lancamentos"
              :codmaquinetalote="lote.codmaquinetalote"
              :editavel="lote.aberto"
              @corrigir="corrigir"
              @indevido="indevido"
            />
          </q-card-section>
        </q-card>

        <MgInfoCriacao :registro="lote" />
      </template>
    </div>

    <CorrecaoPagamentoDialog
      v-model="dialogCorrecao"
      :lancamento="corrigindo"
      @corrigido="carregar"
    />
  </q-page>
</template>
