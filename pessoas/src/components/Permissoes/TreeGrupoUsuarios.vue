<template>
    <div class="col-md-4  col-xl-8 col-xs-12">
        <q-card class="no-shadow" bordered>
            <q-card-section>
                <div class="text-h6 text-indigo-8">
                    Grupo de Usuários
                    <q-btn round flat icon="add" />
                </div>
            </q-card-section>

            <q-separator></q-separator>
            <q-card-section class="q-pa-none">
            </q-card-section>
            <div class="q-pa-md row q-col-gutter-sm">

                <q-tree class="col-12 col-sm-6" :nodes="simple" node-key="label" tick-strategy="leaf"
                    v-model:selected="selected" v-model:ticked="ticked" v-model:expanded="expanded" />

            </div>
            <q-card-actions align="right" class="text-primary">
                <!-- <q-btn flat label="Cancelar" v-close-popup /> -->
                <q-btn flat label="Salvar" type="submit" />
            </q-card-actions>
        </q-card>
    </div>
</template>


<script>
import { defineComponent, defineAsyncComponent, onMounted } from 'vue'
import { useQuasar } from "quasar"
import { useRoute } from 'vue-router'
import { pessoaStore } from 'stores/pessoa'
import { guardaToken } from 'src/stores'
import { ref } from 'vue'


export default defineComponent({
    name: "TreeGrupoUsuarios",
   

    setup() {

        const $q = useQuasar()
        const route = useRoute()
        const sPessoa = pessoaStore()
        const user = guardaToken()

        const columns = [
            {
                name: 'label',
                required: true,
                label: 'Label',
                align: 'left',
                field: 'label',
                // (optional) tell QHierarchy you want this column sortable
                sortable: true,
                // If you want different sorting icon
                filterable: true
            },
            {
                name: 'Description',
                label: 'Description',
                sortable: true,
                field: 'description',
                align: 'center',
                filterable: false
            },
            {
                name: 'note',
                label: 'Note',
                sortable: true,
                field: 'note',
                align: 'left',
                filterable: false
            }
        ];
        const data = [
            {
                label: "Administrador",
                description: "Grupo de administração",
                note: "id",
                // id: 1,
                children: [
                    {
                        label: "Pessoa",
                        description: "Permissões de pessoas",
                        note: "Node 1.1 note",
                        // id: 2
                    },
                    {
                        label: "Node 1.2",
                        description: "Node 1.2 description",
                        note: "Node 1.2 note",
                        // id: 3,
                        children: [
                            {
                                label: "Node 1.2.1",
                                description: "Node 1.2.1 description",
                                note: "Node 1.2.1 note",
                                // id: 4
                            },
                            {
                                label: "Node 1.2.2",
                                description: "Node 1.2.2 description",
                                note: "Node 1.2.2 note",
                                // id: 5
                            }
                        ],
                    }
                ],
            },
            {
                label: "Node 2",
                description: "Node 2 description",
                note: "Node 2 note",
                // id: 6,
                children: [
                    {
                        label: "Node 2.1",
                        description: "Node 2.1 description",
                        note: "Node 2.1 note",
                        // id: 7,
                        children: [
                            {
                                label: "Node 2.1.1",
                                description: "Node 2.1.1 description",
                                note: "Node 2.1.1 note",
                                // id: 8
                            },
                            {
                                label: "Node 2.1.2",
                                description: "Node 2.1.2 description",
                                note: "Node 2.1.2 note",
                                // id: 9
                            }
                        ],
                    },
                    {
                        label: "Node 2.2",
                        description: "Node 2.2 description",
                        note: "Node 2.2 note",
                        // id: 10
                    }
                ],
            }
        ];

        return {
            sPessoa,
            columns,
            data,
            user,
            selected: ref('Pleasant surroundings'),
            ticked: ref(['Quality ingredients', 'Good table presentation']),
            expanded: ref(['Satisfied customers', 'Good service (disabled node)', 'Pleasant surroundings']),

            simple: [
                {
                    label: 'Administrador',
                    children: [
                        {
                            label: 'Pessoa',
                            children: [
                                { label: 'pessoa.incluir' },
                                { label: 'pessoa.update' },
                                { label: 'pessoa.excluir' },
                            ]
                        },
                        {
                            label: 'Grupo economico',
                            children: [
                                { label: 'grupoeconomico.incluir' },
                                { label: 'grupoeconomico.update' },
                                { label: 'grupoeconomico.excluir' },
                            ]
                        },
                    ],
                },
                {
                    label: 'Financeiro',
                    children: [
                        {
                            label: 'Pessoa',
                            children: [
                                { label: 'pessoa.incluir' },
                                { label: 'pessoa.update' },
                                { label: 'pessoa.excluir' },
                            ]
                        },
                        {
                            label: 'Grupo economico',
                            children: [
                                { label: 'grupoeconomico.incluir' },
                                { label: 'grupoeconomico.update' },
                                { label: 'grupoeconomico.excluir' },
                            ]
                        },
                    ],
                },
                {
                    label: 'Caixa',
                    children: [
                        {
                            label: 'Pessoa',
                            children: [
                                { label: 'pessoa.incluir' },
                                { label: 'pessoa.update' },
                                { label: 'pessoa.excluir' },
                            ]
                        },
                        {
                            label: 'Grupo economico',
                            children: [
                                { label: 'grupoeconomico.incluir' },
                                { label: 'grupoeconomico.update' },
                                { label: 'grupoeconomico.excluir' },
                            ]
                        },
                    ],
                },
            ]
        }
    },
})
</script>

