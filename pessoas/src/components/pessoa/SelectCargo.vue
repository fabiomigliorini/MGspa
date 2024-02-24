<script setup>

import { ref, onMounted, computed } from 'vue'
import { pessoaStore } from 'stores/pessoa'
import { useQuasar } from 'quasar';

const $q = useQuasar();
const sPessoa = pessoaStore();

const props = defineProps({

    modelValue: {
        // type: Number,
        required: true,
    },

    permiteAdicionar: {
        type: Boolean,
        default: false,
    }
})

const emit = defineEmits(['update:modelValue'])

const codigo = computed({
    get() {
        return props.modelValue
    },
    set(value) {
        emit('update:modelValue', value);
    }
});


onMounted(async () => {
    const ret = await sPessoa.selectCargo()
    opcoes.value = ret.data.data
})

const opcoes = ref([]);

const filterFn = async (val, update) => {
    if (val === '') {
        update(async () => {
            const ret = await sPessoa.selectCargo()
            opcoes.value = ret.data.data
        })
        return
    }

    update(() => {
        const needle = val.toLowerCase()
        opcoes.value = opcoes.value.filter(v => v.cargo.toLowerCase().indexOf(needle) > -1)
    })
}

const adicionarRegistro = async (nome, done) => {

    if (!props.permiteAdicionar) {
        return;
    }
    $q.dialog({
        title: 'Deseja criar um novo cargo?',
        cancel: true,
    }).onOk(async () => {

        try {
            const ret = await sPessoa.novoCargo({ cargo: nome });
            opcoes.value = [ret.data.data];
            codigo.value = ret.data.data.codcargo;
            done();
        } catch (error) {
            $q.notify({
                color: 'red-5',
                textColor: 'white',
                icon: 'error',
                message: error.message
            })
        }
    });
}

</script>

<template>
    <q-select outlined label="Cargo" :options="opcoes" map-options emit-value option-label="cargo" use-input
        input-debounce="5" v-model="codigo" option-value="codcargo" clearable @new-value="adicionarRegistro" @filter="filterFn" />
</template>
  