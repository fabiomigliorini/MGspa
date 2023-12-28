<script setup>

import { ref, onMounted, computed } from 'vue'
import { GrupoEconomicoStore } from 'stores/GrupoEconomico'
import { useQuasar } from 'quasar';

const $q = useQuasar();
const sPessoa = GrupoEconomicoStore();

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

const opcoes = ref([]);

const buscarRegistros = (val, update) => {
    update(async () => {
        const busca = val.toLowerCase().trim();
        if (busca.length < 2) {
            return;
        }
        try {
            const ret = await sPessoa.selectGrupoEconomico(busca)
            opcoes.value = ret.data
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

const adicionarRegistro = async (nome, done) => {
    if (!props.permiteAdicionar) {
        return;
    }
    $q.dialog({
        title: 'Deseja criar um novo Grupo Econômico?',
        cancel: true,
    }).onOk(async () => {
        try {
            const ret = await sPessoa.novoGrupoEconomico(nome);
            console.log(ret.data)
            opcoes.value = [ret.data.data];
            codigo.value = ret.data.data.codgrupoeconomico;
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

onMounted(async () => {
    if (!props.modelValue) {
        return
    }
    const ret = await sPessoa.selectGrupoEconomicoPeloCodigo(props.modelValue);
    opcoes.value = ret.data;
})

</script>

<template>
    <q-select outlined use-input input-debounce="5" :options="opcoes" option-label="grupoeconomico"
        option-value="codgrupoeconomico" map-options emit-value @filter="buscarRegistros" clearable v-model="codigo"
        @new-value="adicionarRegistro">
        <template v-slot:no-option>
            <q-item>
                <q-item-section class="text-grey">
                    Digite pelo menos 3 caracteres
                </q-item-section>
            </q-item>
        </template>
    </q-select>
</template>
