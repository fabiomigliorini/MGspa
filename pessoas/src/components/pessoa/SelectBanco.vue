<script setup>

import { ref, computed, watch } from 'vue'
import { pessoaStore } from 'stores/pessoa'
import { useQuasar } from 'quasar';

const $q = useQuasar();
const sPessoa = pessoaStore();

const props = defineProps({
    modelSelectBanco: {},
    bancoEditar: {}
})

const codigobancoEditar = computed({
    get() {
        return props.bancoEditar
    },

    set(value) {
        emit('update:bancoEditar', value);
    }
});

const codigoContaNova = computed({
    get() {
        return props.modelSelectBanco
    },

    set(value) {
        emit('update:modelSelectBanco', value);
    }
});


watch(codigobancoEditar, (value) => {
  if(value[0]){
    opcoes.value = [value[0]]
  }
});

watch(codigoContaNova, async (value) => {
 if(value) {
    const ret = await sPessoa.selectBanco(value)
    opcoes.value = [ret.data[0]]
 }
});

const emit = defineEmits(['update:modelSelectBanco', 'update:bancoEditar'])

const opcoes = ref([]);

const buscarRegistros = (val, update) => {
    if (val === '') {
        update(() => {
            opcoes.value = []
        })
        return
    }
    update(async () => {
        const needle = val.toLowerCase()
        try {
            if (needle.length > 1) {
                const ret = await sPessoa.selectBanco({banco: needle})
                opcoes.value = ret.data
                return
            }
        } catch (error) {
            console.log(error)
        }
    })

}
</script>

<template>
    <q-select outlined :model-value="modelSelectBanco" use-input input-debounce="0" label="Banco"
        :options="opcoes" option-label="banco" option-value="codbanco" map-options emit-value clearable
        @filter="buscarRegistros" behavior="menu">

        <template v-slot:no-option>
            <q-item>
                <q-item-section class="text-grey">
                    Digite pelo menos 2 caracteres.
                </q-item-section>
            </q-item>
        </template>

        <template v-slot:option="bancos">
                <q-item v-bind="bancos.itemProps">
                  <q-item-section avatar>
                    <q-icon name="account_balance" color="blue" />
                  </q-item-section>
                  <q-item-section>
                    <q-item-label>{{ bancos.opt.banco }}</q-item-label>
                    <q-item-label caption>({{ bancos.opt.codbanco.toString().padStart(3, '0') }})</q-item-label>
                  </q-item-section>
                </q-item>
              </template>

    </q-select>
</template>