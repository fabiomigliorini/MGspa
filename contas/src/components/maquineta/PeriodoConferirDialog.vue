<script setup>
// Conferir o período da maquineta com o borderô (TASK-188 M9.8), em passos: sem foto ainda, o
// primeiro passo é fotografar o relatório da maquininha (a foto já fica no período, como na coluna
// ao lado; é opcional, dá para seguir sem); depois, a quantidade e o total do papel (todo relatório
// tem os dois; no convênio, conta e soma os comprovantes). O aberto termina na hora do Conferir (o
// próximo cartão já cai no seguinte); bateu (quantidade e total no centavo), conferido; senão,
// pendente. Os campos abrem com o digitado da última vez.
import { ref, computed, watch } from 'vue'
import { storeToRefs } from 'pinia'
import MgInput from '@components/MgInput.vue'
import MgInputValor from '@components/MgInputValor.vue'
import MgSlim from '@components/MgSlim.vue'
import { useMaquinetaPeriodoStore } from 'src/stores/maquinetaPeriodoStore'

const aberto = defineModel({ type: Boolean, default: false })

const store = useMaquinetaPeriodoStore()
const { periodo, salvando } = storeToRefs(store)

const situacao = computed(() => periodo.value?.situacao)
const obrigatorio = (v) => (v !== null && v !== '') || 'Obrigatório'

// ==== wizard ====

const passo = ref(1)
// com foto, o passo 1 não aparece; depois de anexar, voltar dos números fecha
const primeiro = ref(1)
const conferencia = ref({ quantidade: null, total: null, observacoes: null })

watch(aberto, (v) => {
  if (!v) return
  const p = periodo.value
  conferencia.value = {
    quantidade: p.quantidadeinformada ?? null,
    total: p.totalinformado ?? null,
    observacoes: p.observacoes ?? null,
  }
  primeiro.value = p.fotos?.length ? 2 : 1
  passo.value = primeiro.value
})

async function anexarFoto(base64) {
  if (!(await store.anexarFoto(base64))) return
  primeiro.value = 2
  passo.value = 2
}

function voltar() {
  if (passo.value === primeiro.value) {
    aberto.value = false
    return
  }
  passo.value--
}

async function conferir() {
  const { quantidade, total, observacoes } = conferencia.value
  if (await store.conferir({ quantidade, total, observacoes: observacoes || null })) {
    aberto.value = false
  }
}
</script>

<template>
  <q-dialog v-model="aberto">
    <q-card flat style="width: 400px; max-width: 95vw">
      <q-form @submit.prevent="conferir">
        <q-card-section class="text-grey-9 text-overline">CONFERIR COM O BORDERÔ</q-card-section>
        <q-separator inset />

        <!-- PASSO 1: A FOTO -->
        <template v-if="passo === 1">
          <q-card-section class="text-caption text-grey-7 q-pb-none">
            Fotografe o relatório da maquininha. A foto fica no período.
          </q-card-section>
          <q-card-section class="relative-position">
            <MgSlim label="Toque para fotografar o borderô" @imagem="anexarFoto" />
            <q-inner-loading :showing="salvando" color="primary" />
          </q-card-section>
        </template>

        <!-- PASSO 2: QUANTIDADE E TOTAL -->
        <template v-else>
          <q-card-section class="text-caption text-grey-7 q-pb-none">
            A quantidade e o total do relatório da maquininha.
            <template v-if="situacao === 'aberto'">
              O período termina agora: o próximo cartão já cai no seguinte.
            </template>
          </q-card-section>
          <q-card-section>
            <div class="row q-col-gutter-md">
              <div class="col-5">
                <MgInputValor
                  v-model="conferencia.quantidade"
                  label="Quantidade"
                  :decimals="0"
                  :grouping="false"
                  :min="0"
                  autofocus
                  :rules="[obrigatorio]"
                />
              </div>
              <div class="col-7">
                <MgInputValor v-model="conferencia.total" label="Total" :rules="[obrigatorio]" />
              </div>
              <div class="col-12">
                <MgInput
                  v-model="conferencia.observacoes"
                  label="Observações"
                  type="textarea"
                  autogrow
                  rows="2"
                  maxlength="500"
                />
              </div>
            </div>
          </q-card-section>
        </template>

        <q-separator inset />
        <q-card-actions align="right">
          <q-btn
            flat
            :label="passo === primeiro ? 'Cancelar' : 'Voltar'"
            color="grey-8"
            tabindex="-1"
            @click="voltar"
          />
          <q-btn
            v-if="passo === 1"
            flat
            label="Seguir sem foto"
            color="primary"
            :disable="salvando"
            @click="passo = 2"
          />
          <q-btn v-else flat label="Conferir" color="primary" type="submit" :loading="salvando" />
        </q-card-actions>
      </q-form>
    </q-card>
  </q-dialog>
</template>
