<script setup>
import { computed } from 'vue'
import MgInputValor from '@components/MgInputValor.vue'

const props = defineProps({
  cad: { type: Object, required: true },
})

const cad = computed(() => props.cad)
</script>

<template>
  <div class="row q-col-gutter-md">
    <!-- DESCRIÇÃO -->
    <div class="col-12">
      <q-input
        outlined
        v-model="cad.descricao"
        label="Descrição"
        autofocus
        :rules="[(val) => (val && val.length > 0) || 'Obrigatório']"
      />
    </div>

    <!-- TIPO DE CÁLCULO -->
    <div class="col-12">
      <small class="text-grey">Tipo de cálculo:</small>
      <div class="row items-center">
        <q-radio
          v-model="cad.tipovalor"
          val="F"
          label="Fixo"
          checked-icon="task_alt"
          unchecked-icon="panorama_fish_eye"
        />
        <q-radio
          v-model="cad.tipovalor"
          val="P"
          label="Percentual"
          checked-icon="task_alt"
          unchecked-icon="panorama_fish_eye"
        />
        <q-radio
          v-model="cad.tipovalor"
          val="Q"
          label="Unitário × Quantidade"
          checked-icon="task_alt"
          unchecked-icon="panorama_fish_eye"
        />
      </div>
    </div>

    <!-- VALOR PADRÃO (conforme tipo de cálculo) -->
    <div v-if="cad.tipovalor === 'F'" class="col-12 col-sm-6">
      <MgInputValor v-model="cad.valorpadrao" label="Valor Padrão" prefix="R$" />
    </div>
    <div v-else-if="cad.tipovalor === 'P'" class="col-12 col-sm-6">
      <MgInputValor v-model="cad.valorpadrao" label="Percentual Padrão %" />
    </div>
    <div v-else-if="cad.tipovalor === 'Q'" class="col-12 col-sm-6">
      <MgInputValor v-model="cad.valorunitariopadrao" label="Valor Unitário Padrão" prefix="R$" />
    </div>

    <!-- CONDIÇÃO -->
    <div class="col-12">
      <small class="text-grey">Condição:</small>
      <div class="row items-center">
        <q-radio
          v-model="cad.tipocondicao"
          :val="null"
          label="Nenhuma"
          checked-icon="task_alt"
          unchecked-icon="panorama_fish_eye"
        />
        <q-radio
          v-model="cad.tipocondicao"
          val="M"
          label="Meta Atingida"
          checked-icon="task_alt"
          unchecked-icon="panorama_fish_eye"
        />
        <q-radio
          v-model="cad.tipocondicao"
          val="R"
          label="Ranking (1º lugar)"
          checked-icon="task_alt"
          unchecked-icon="panorama_fish_eye"
        />
      </div>
    </div>

    <!-- RECORRENTE -->
    <div class="col-12">
      <q-toggle v-model="cad.recorrente" label="Recorrente" />
    </div>
  </div>
</template>
