<script setup>
import { computed, ref, watch } from 'vue'
import { useQuasar } from 'quasar'
import { storeToRefs } from 'pinia'
import MgInput from '@components/MgInput.vue'
import { formataTimestamp } from '@components/formatters'
import { negocioStore } from 'stores/negocio'
import { entregaStore, RESULTADOS, MOTIVOS } from 'stores/entrega'

// Entrega do negócio fechado (TASK-55): um card com a situação; clicando, o histórico das
// tentativas e o registro de uma nova. Só online.
const $q = useQuasar()
const sNegocio = negocioStore()
const sEntrega = entregaStore()
const { entregas, controla, entregue, form, salvando } = storeToRefs(sEntrega)
const dialog = ref(false)

const ativas = computed(() => entregas.value.filter((e) => !e.inativo))
const ultima = computed(() => ativas.value[ativas.value.length - 1] || null)
const resultado = (valor) => RESULTADOS.find((r) => r.value === valor) || RESULTADOS[0]
const mostrar = computed(() => controla.value || entregas.value.length > 0)

watch(
  () => [
    sNegocio.negocio?.codnegocio,
    sNegocio.negocio?.codnegociostatus,
    sNegocio.negocio?.entrega,
  ],
  ([codnegocio, status]) => {
    if (codnegocio && status == 2) sEntrega.carregarDoNegocio(codnegocio)
  },
  { immediate: true },
)

watch(
  () => form.value.resultado,
  () => (form.value.motivo = null),
)

function abrir() {
  sEntrega.novo()
  dialog.value = true
}

function estornar(e) {
  $q.dialog({
    title: 'Estornar',
    message: `Estornar o registro "${resultado(e.resultado).label}" de ${formataTimestamp(e.data)}?`,
    cancel: { label: 'Voltar', color: 'grey-8', flat: true },
    ok: { label: 'Estornar', color: 'red-5', flat: true },
  }).onOk(() => sEntrega.estornar(e))
}
</script>

<template>
  <div class="col-xs-6 col-sm-4 col-md-4 col-lg-3 col-xl-2" v-if="mostrar">
    <q-card flat bordered class="full-height column no-wrap">
      <q-item clickable v-ripple @click="abrir">
        <q-item-section avatar>
          <q-avatar
            icon="local_shipping"
            :color="entregue ? 'green-7' : 'orange-8'"
            text-color="white"
          />
        </q-item-section>
        <q-item-section>
          <q-item-label class="ellipsis">Entrega</q-item-label>
          <q-item-label caption class="ellipsis">
            {{ entregue ? 'Entregue ' + formataTimestamp(entregue) : 'Pendente' }}
          </q-item-label>
          <q-item-label caption class="ellipsis" v-if="ultima && !entregue">
            {{ resultado(ultima.resultado).label }}
            <template v-if="ultima.motivodescricao"> · {{ ultima.motivodescricao }}</template>
          </q-item-label>
        </q-item-section>
      </q-item>
    </q-card>

    <q-dialog v-model="dialog">
      <q-card flat style="width: 600px; max-width: 95vw">
        <q-card-section class="text-grey-9 text-overline text-uppercase">Entrega</q-card-section>
        <q-list v-if="entregas.length" separator>
          <q-item v-for="e in entregas" :key="e.codnegocioentrega">
            <q-item-section avatar>
              <q-icon :name="resultado(e.resultado).icone" :color="resultado(e.resultado).cor" />
            </q-item-section>
            <q-item-section :class="{ 'text-strike text-grey-6': e.inativo }">
              <q-item-label>
                {{ resultado(e.resultado).label }}
                <template v-if="e.motivodescricao"> · {{ e.motivodescricao }}</template>
              </q-item-label>
              <q-item-label caption>
                {{ formataTimestamp(e.data) }} · {{ e.usuariocriacao }}
                <template v-if="e.recebedor"> · recebeu {{ e.recebedor }}</template>
              </q-item-label>
              <q-item-label caption v-if="e.justificativa">
                Sem código: {{ e.justificativa }}
              </q-item-label>
              <q-item-label caption v-if="e.observacao">{{ e.observacao }}</q-item-label>
            </q-item-section>
            <q-item-section side v-if="!e.inativo">
              <q-btn flat round size="sm" color="grey-7" icon="undo" @click="estornar(e)">
                <q-tooltip>Estornar</q-tooltip>
              </q-btn>
            </q-item-section>
          </q-item>
        </q-list>
        <q-separator v-if="entregas.length" />
        <q-form @submit.prevent="sEntrega.registrar(sNegocio.negocio.codnegocio)">
          <q-card-section>
            <div class="row q-col-gutter-md">
              <div class="col-12">
                <q-option-group v-model="form.resultado" :options="RESULTADOS" type="radio" />
              </div>
              <div class="col-12" v-if="MOTIVOS[form.resultado]">
                <div class="text-caption text-grey-7">Motivo</div>
                <q-option-group
                  v-model="form.motivo"
                  :options="MOTIVOS[form.resultado]"
                  type="radio"
                  inline
                />
              </div>
              <div class="col-12" v-if="form.resultado !== 'N'">
                <MgInput
                  v-model="form.recebedor"
                  label="Quem recebeu"
                  maxlength="100"
                  autofocus
                  :rules="[(v) => !!v || 'Informe quem recebeu']"
                />
              </div>
              <div class="col-12">
                <MgInput v-model="form.observacao" label="Observação" maxlength="500" autogrow />
              </div>
            </div>
          </q-card-section>
          <q-card-actions align="right">
            <q-btn flat label="Fechar" color="grey-8" tabindex="-1" v-close-popup />
            <q-btn
              flat
              label="Registrar"
              color="primary"
              type="submit"
              :loading="salvando"
              :disable="!!MOTIVOS[form.resultado] && !form.motivo"
            />
          </q-card-actions>
        </q-form>
      </q-card>
    </q-dialog>
  </div>
</template>
