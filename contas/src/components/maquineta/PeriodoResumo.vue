<script setup>
// Resumo do período da maquineta (TASK-188 M9.8) no formato do relatório da maquininha: modalidade
// (débito, crédito à vista, crédito parcelado) → bandeira, com quantidade e valor, para bater o
// olho com o topo do papel. Embaixo, a conferência: digita a quantidade e o total do papel (todo
// relatório tem os dois; no convênio, conta e soma os comprovantes). O aberto termina na hora do
// Conferir (o próximo cartão já cai no seguinte); bateu (quantidade e o total no centavo),
// conferido; senão, pendente. Venda cancelada no próprio período fica fora, como no papel.
import { ref, computed, watch } from 'vue'
import { storeToRefs } from 'pinia'
import MgInput from '@components/MgInput.vue'
import MgInputValor from '@components/MgInputValor.vue'
import { formataNumero } from '@components/formatters'
import { useMaquinetaPeriodoStore } from 'src/stores/maquinetaPeriodoStore'

const store = useMaquinetaPeriodoStore()
const { periodo, salvando } = storeToRefs(store)

const sistema = computed(() => periodo.value?.sistema)
const naoConferido = computed(() => periodo.value?.situacao !== 'conferido')
const informado = computed(() => periodo.value?.totalinformado != null)

const diferenca = computed(() => ({
  quantidade: (periodo.value.quantidadeinformada ?? 0) - (sistema.value?.quantidade ?? 0),
  total:
    Math.round(((periodo.value.totalinformado ?? 0) - (sistema.value?.total ?? 0)) * 100) / 100,
}))
const cor = (v) => (Math.abs(v) < 0.005 ? 'text-green-8' : 'text-red-8')

// ---- conferir: os campos voltam ao digitado de cada período ----
const form = ref({ quantidade: null, total: null, observacoes: null })
watch(
  () => periodo.value?.codmaquinetalote,
  () => {
    const p = periodo.value
    form.value = {
      quantidade: p?.quantidadeinformada ?? null,
      total: p?.totalinformado ?? null,
      observacoes: p?.observacoes ?? null,
    }
  },
  { immediate: true },
)
const obrigatorio = (v) => (v !== null && v !== '') || 'Obrigatório'

async function conferir() {
  await store.conferir({
    quantidade: form.value.quantidade,
    total: form.value.total,
    observacoes: form.value.observacoes || null,
  })
}
</script>

<template>
  <q-card flat bordered>
    <q-card-section class="text-subtitle1 text-weight-medium q-pb-sm">Resumo</q-card-section>

    <q-card-section class="q-pt-none">
      <q-markup-table flat bordered separator="none" wrap-cells>
        <thead>
          <tr>
            <th class="text-left"></th>
            <th class="text-right" style="width: 64px">Qtd</th>
            <th class="text-right" style="width: 112px">Sistema</th>
          </tr>
        </thead>
        <tbody>
          <template v-for="m in sistema?.modalidades ?? []" :key="m.modalidade">
            <tr>
              <td class="text-weight-medium">{{ m.descricao }}</td>
              <td class="text-right text-weight-medium">{{ m.quantidade }}</td>
              <td class="text-right text-weight-medium">{{ formataNumero(m.valor) }}</td>
            </tr>
            <tr v-for="b in m.bandeiras" :key="b.descricao" class="text-grey-8">
              <td class="q-pl-lg">{{ b.descricao }}</td>
              <td class="text-right">{{ b.quantidade }}</td>
              <td class="text-right">{{ formataNumero(b.valor) }}</td>
            </tr>
          </template>
          <tr v-if="sistema?.cancelamentos?.quantidade" class="text-red-8">
            <td>Cancelamento de outro período</td>
            <td></td>
            <td class="text-right">{{ formataNumero(sistema.cancelamentos.valor) }}</td>
          </tr>
          <tr class="bg-grey-2">
            <td class="text-weight-bold">Total</td>
            <td class="text-right text-weight-bold">{{ sistema?.quantidade ?? 0 }}</td>
            <td class="text-right text-weight-bold">{{ formataNumero(sistema?.total ?? 0) }}</td>
          </tr>
          <template v-if="informado">
            <tr>
              <td>Borderô</td>
              <td class="text-right">{{ periodo.quantidadeinformada }}</td>
              <td class="text-right">{{ formataNumero(periodo.totalinformado) }}</td>
            </tr>
            <tr>
              <td>Diferença</td>
              <td class="text-right text-weight-bold" :class="cor(diferenca.quantidade)">
                {{ diferenca.quantidade }}
              </td>
              <td class="text-right text-weight-bold" :class="cor(diferenca.total)">
                {{ formataNumero(diferenca.total) }}
              </td>
            </tr>
          </template>
        </tbody>
      </q-markup-table>
      <div v-if="sistema?.cancelados" class="text-caption text-grey-7 q-mt-xs">
        {{ sistema.cancelados }}
        {{ sistema.cancelados > 1 ? 'vendas canceladas' : 'venda cancelada' }} no período, fora da
        conta (como no papel).
      </div>
    </q-card-section>

    <!-- conferir com o borderô -->
    <q-card-section v-if="naoConferido" class="q-pt-none">
      <q-form @submit.prevent="conferir">
        <div class="text-caption text-grey-7 q-mb-sm">
          Digite a quantidade e o total do borderô.
          <template v-if="periodo.situacao === 'aberto'">
            O período termina agora: o próximo cartão já cai no seguinte.
          </template>
        </div>
        <div class="row q-col-gutter-md items-start">
          <div class="col-4 col-sm-2">
            <MgInputValor
              v-model="form.quantidade"
              label="Qtd"
              :decimals="0"
              :grouping="false"
              :min="0"
              :rules="[obrigatorio]"
            />
          </div>
          <div class="col-8 col-sm-3">
            <MgInputValor v-model="form.total" label="Total" :rules="[obrigatorio]" />
          </div>
          <div class="col-12 col-sm">
            <MgInput
              v-model="form.observacoes"
              label="Observações"
              type="textarea"
              autogrow
              rows="1"
              maxlength="500"
            />
          </div>
          <div class="col-12 col-sm-auto row justify-end">
            <q-btn flat color="primary" type="submit" label="Conferir" :loading="salvando" />
          </div>
        </div>
      </q-form>
    </q-card-section>
  </q-card>
</template>
