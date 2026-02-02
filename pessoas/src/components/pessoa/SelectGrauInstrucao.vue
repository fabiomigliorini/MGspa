<script setup>
import { ref, onMounted, computed } from 'vue'
import { grauInstrucaoStore } from 'stores/grau-instrucao'
import { useQuasar } from 'quasar';

const $q = useQuasar();
const store = grauInstrucaoStore();

const props = defineProps({
    modelValue: {
        required: false,
    },
    label: {
        type: String,
        default: 'Grau de Instrução'
    },
    status: {
        type: String,
        default: 'ativos', // ativos, inativos, todos
        validator: (value) => ['ativos', 'inativos', 'todos'].includes(value)
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

const buscarRegistros = async () => {
    try {
        // Só busca do backend se não houver dados em cache ou se o filtro mudou
        if (store.grausInstrucao.length === 0 || store.filtro.status !== props.status) {
            store.filtro = {
                grauinstrucao: null,
                status: props.status
            };
            await store.index();
        }
        opcoes.value = store.grausInstrucao;
    } catch (error) {
        $q.notify({
            color: 'red-5',
            textColor: 'white',
            icon: 'error',
            message: error.message || 'Erro ao buscar graus de instrução'
        })
    }
}

onMounted(async () => {
    // Se já tem dados em cache, usa direto
    if (store.grausInstrucao.length > 0) {
        opcoes.value = store.grausInstrucao;
    } else {
        await buscarRegistros();
    }
})

</script>

<template>
    <q-select outlined :options="opcoes" option-label="grauinstrucao"
        option-value="codgrauinstrucao" map-options emit-value clearable v-model="codigo"
        :label="label">
        <template v-slot:no-option>
            <q-item>
                <q-item-section class="text-grey">
                    Nenhum grau de instrução encontrado
                </q-item-section>
            </q-item>
        </template>
    </q-select>
</template>
