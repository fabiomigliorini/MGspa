<template>
    <q-card class="no-shadow" bordered>
        <!-- <q-card-section>
        <div class="text-h6 text-grey-8">
          Inline Actions
          <q-btn label="Export" class="float-right text-capitalize text-indigo-8 shadow-3" icon="person"/>
        </div>
      </q-card-section>
      <q-separator></q-separator> -->
        <q-card-section class="q-pa-none">
            <q-table :rows="data" :columns="columns" hide-bottom class="no-shadow" :filter="filter">
                <template v-slot:body-cell-Name="props">
                    <q-td :props="props">
                        <q-item style="max-width: 420px">
                            <q-item-section avatar>
                                <q-avatar>
                                    <img :src="props.row.avatar">
                                </q-avatar>
                            </q-item-section>

                            <q-item-section>
                                <q-item-label>{{ props.row.name }}</q-item-label>
                            </q-item-section>
                        </q-item>
                    </q-td>
                </template>
                <template v-slot:top-right>
                    <q-input v-if="show_filter" filled borderless dense debounce="300" v-model="model"
                        placeholder="Pesquisar">
                        <template v-slot:append>
                            <q-icon name="search" />
                        </template>
                    </q-input>
                    <q-btn class="q-ml-sm" icon="filter_list" @click="show_filter = !show_filter" flat />
                </template>

                <template v-slot:body-cell-Action="props">
                    <q-td :props="props">
                        <q-btn icon="edit" size="sm" flat round />
                        <q-btn icon="delete" size="sm" class="q-ml-sm" flat round />
                    </q-td>
                </template>
            </q-table>
        </q-card-section>
    </q-card>
</template>
  
<script>
import { defineComponent, ref } from 'vue'

const data = [
    {
        permissao: 'pessoa.incluir',
        Observacao: 'Pessoa / Criar',
    },
    {
        permissao: 'pessoa.update',
        Observacao: 'Pessoa / Update',
    },
];

const columns = [
    // {name: 'Name', label: '#', field: 'name', sortable: true, align: 'left'},
    { name: 'Permissao', label: 'Permissões', field: 'permissao', sortable: true, align: 'left' },
    { name: 'Project', label: 'Observação', field: 'Observacao', sortable: true, align: 'left' },
    { name: 'Action', label: 'Ações', field: 'Action', sortable: false, align: 'center' }
];

const show_filter = ref(true)
const model = ref([])

export default defineComponent({
    name: "TabelaPermissoes",
    setup() {
        return {
            data,
            filter: ref(''),
            columns,
            show_filter,
            model,
        }
    }
})
</script>
  
<style scoped></style>