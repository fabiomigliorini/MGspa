<script setup>
import { ref, computed } from 'vue'
import { conflitosComanda } from '../../utils/comanda.js'

const props = defineProps({
  modelValue: {
    type: Boolean,
    default: false,
  },
  negocio: {
    type: Object,
    default: () => ({}),
  },
  comanda: {
    type: Object,
    default: () => ({}),
  },
})

const emit = defineEmits(['update:modelValue', 'confirmar', 'cancelar'])

const codpessoa = ref(null)
const codpessoavendedor = ref(null)
const confirmado = ref(false)

const dialog = computed({
  get() {
    return props.modelValue
  },
  set(val) {
    emit('update:modelValue', val)
  },
})

const conflitos = computed(() => {
  return conflitosComanda(props.negocio, props.comanda)
})

// deixa marcado o que veio da comanda, que é a informação levantada no salão
const inicializar = () => {
  confirmado.value = false
  codpessoa.value = props.comanda.codpessoa
  codpessoavendedor.value = props.comanda.codpessoavendedor
}

const confirmar = () => {
  const escolhas = {}
  if (conflitos.value.pessoa) {
    escolhas.codpessoa = codpessoa.value
  }
  if (conflitos.value.vendedor) {
    escolhas.codpessoavendedor = codpessoavendedor.value
  }
  confirmado.value = true
  emit('confirmar', escolhas)
  dialog.value = false
}

const aoEsconder = () => {
  if (!confirmado.value) {
    emit('cancelar')
  }
}
</script>

<template>
  <q-dialog v-model="dialog" @before-show="inicializar()" @hide="aoEsconder()">
    <q-card flat style="width: 400px; max-width: 90vw">
      <q-card-section>
        <div class="text-h6">Comanda #{{ comanda.codnegocio }}</div>
        <div class="text-caption text-grey-8">
          Estas informações estão diferentes nos dois negócios. Escolha o que deve valer depois de
          unificar.
        </div>
      </q-card-section>

      <q-card-section v-if="conflitos.pessoa" class="q-pt-none q-mb-md">
        <div class="text-subtitle2 q-mb-sm">Cliente</div>
        <q-list bordered separator>
          <q-item clickable @click="codpessoa = negocio.codpessoa">
            <q-item-section side>
              <q-radio v-model="codpessoa" :val="negocio.codpessoa" />
            </q-item-section>
            <q-item-section>
              <q-item-label>{{ negocio.fantasia }}</q-item-label>
              <q-item-label caption>Negócio #{{ negocio.codnegocio }}</q-item-label>
            </q-item-section>
          </q-item>
          <q-item clickable @click="codpessoa = comanda.codpessoa">
            <q-item-section side>
              <q-radio v-model="codpessoa" :val="comanda.codpessoa" />
            </q-item-section>
            <q-item-section>
              <q-item-label>{{ comanda.fantasia }}</q-item-label>
              <q-item-label caption>Comanda #{{ comanda.codnegocio }}</q-item-label>
            </q-item-section>
          </q-item>
        </q-list>
      </q-card-section>

      <q-card-section v-if="conflitos.vendedor" class="q-pt-none q-mb-md">
        <div class="text-subtitle2 q-mb-sm">Vendedor</div>
        <q-list bordered separator>
          <q-item clickable @click="codpessoavendedor = negocio.codpessoavendedor">
            <q-item-section side>
              <q-radio v-model="codpessoavendedor" :val="negocio.codpessoavendedor" />
            </q-item-section>
            <q-item-section>
              <q-item-label>{{ negocio.fantasiavendedor }}</q-item-label>
              <q-item-label caption>Negócio #{{ negocio.codnegocio }}</q-item-label>
            </q-item-section>
          </q-item>
          <q-item clickable @click="codpessoavendedor = comanda.codpessoavendedor">
            <q-item-section side>
              <q-radio v-model="codpessoavendedor" :val="comanda.codpessoavendedor" />
            </q-item-section>
            <q-item-section>
              <q-item-label>{{ comanda.fantasiavendedor }}</q-item-label>
              <q-item-label caption>Comanda #{{ comanda.codnegocio }}</q-item-label>
            </q-item-section>
          </q-item>
        </q-list>
      </q-card-section>

      <q-card-actions align="right">
        <q-btn flat label="Cancelar" color="grey-8" v-close-popup />
        <q-btn flat label="Unificar" color="primary" @click="confirmar()" />
      </q-card-actions>
    </q-card>
  </q-dialog>
</template>
