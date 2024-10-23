<script setup>

import { ref, computed, watch } from 'vue'
import { pessoaStore } from 'stores/pessoa'
import { useQuasar } from 'quasar';

const $q = useQuasar();
const sPessoa = pessoaStore();

const props = defineProps({
    modelSelectCidade: {},
    cidadeEditar: {}
})

const codigoCidadeEditar = computed({
    get() {
        return props.cidadeEditar
    },

    set(value) {
        emit('update:cidadeEditar', value);
    }
});

const codigoCidadeNova = computed({
    get() {
        return props.modelSelectCidade
    },

    set(value) {
        emit('update:modelSelectCidade', value);
    }
});


watch(codigoCidadeEditar, (value) => {
  if(value[0]){
    opcoes.value = [value[0]]
  }
});

watch(codigoCidadeNova, async (value) => {
 if(value) {
    const ret = await sPessoa.consultaCidade(value)
    opcoes.value = [ret.data[0]]
 }
});

const emit = defineEmits(['update:modelSelectCidade', 'update:cidadeEditar'])

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
            if (needle.length > 3) {
                const ret = await sPessoa.consultaCidade(needle)
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
    <q-select outlined :model-value="modelSelectCidade" use-input input-debounce="0" label="Cidade"
        :options="opcoes" option-label="label" option-value="value" map-options emit-value clearable
        @filter="buscarRegistros" behavior="menu">

        <template v-slot:no-option>
            <q-item>
                <q-item-section class="text-grey">
                    Nenhum resultado encontrado.
                </q-item-section>
            </q-item>
        </template>
    </q-select>
</template>
