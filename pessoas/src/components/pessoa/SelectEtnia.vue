<script setup>
import { ref, onMounted, computed } from 'vue'
import { etniaStore } from 'stores/etnia'
import { useQuasar } from 'quasar';

const $q = useQuasar();
const store = etniaStore();

const props = defineProps({
    modelValue: {
        required: false,
    },
    label: {
        type: String,
        default: 'Etnia'
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
        if (store.etnias.length === 0 || store.filtro.status !== props.status) {
            store.filtro = {
                etnia: null,
                status: props.status
            };
            await store.index();
        }
        opcoes.value = store.etnias;
    } catch (error) {
        $q.notify({
            color: 'red-5',
            textColor: 'white',
            icon: 'error',
            message: error.message || 'Erro ao buscar etnias'
        })
    }
}

onMounted(async () => {
    // Se já tem dados em cache, usa direto
    if (store.etnias.length > 0) {
        opcoes.value = store.etnias;
    } else {
        await buscarRegistros();
    }
})

</script>

<template>
    <q-select outlined :options="opcoes" option-label="etnia"
        option-value="codetnia" map-options emit-value clearable v-model="codigo"
        :label="label">
        <template v-slot:no-option>
            <q-item>
                <q-item-section class="text-grey">
                    Nenhuma etnia encontrada
                </q-item-section>
            </q-item>
        </template>
    </q-select>
</template>
