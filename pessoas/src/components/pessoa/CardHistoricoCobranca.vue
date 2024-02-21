<template>
    <q-infinite-scroll @load="scrollHistorico" :disable="loading">
        <q-card bordered>
            <q-list>
                <q-item-label header>
                    Histórico de Cobrança
                    <q-btn flat round icon="add" v-if="user.verificaPermissaoUsuario('Publico')" @click="dialogEditarHistorico = true, modelCobrancaHistorico = {},
                        cobrancaNova = true" />
                </q-item-label>
                <div v-for="historico in HistoricosCobranca" v-bind:key="historico.codcobrancahistorico">
                    <q-separator inset />
                    <q-item>
                        <q-item-section avatar>
                            <q-avatar>
                                <q-icon name="comment" color="blue" />
                            </q-avatar>
                            <!-- <q-item-label caption>#0000{{ historico.codcobrancahistorico }}</q-item-label> -->
                        </q-item-section>

                        <q-item-section>
                            <q-item-label>
                                {{ historico.historico }}
                            </q-item-label>
                            <q-item-label caption>
                                {{ historico.usuariocriacao }}
                                {{ Documentos.formataFromNow(historico.criacao) }}
                                <q-tooltip>
                                    {{ Documentos.formataData(historico.criacao) }}
                                </q-tooltip>
                            </q-item-label>
                        </q-item-section>

                        <q-item-section side top class="gt-xs">
                            
                        </q-item-section>

                        <q-btn-dropdown flat auto-close v-if="user.verificaPermissaoUsuario('Publico')">
                            <q-btn flat round icon="edit"
                                @click="editarHistorico(historico.codcobrancahistorico, historico.historico)" />
                            <q-btn flat round icon="delete" @click="deletarHistorico(historico.codcobrancahistorico)" />

                        </q-btn-dropdown>
                    </q-item>
                </div>
                <q-separator inset="item" />
            </q-list>
        </q-card>
    </q-infinite-scroll>

    <!-- Dialog Editar Histórico -->
    <q-dialog v-model="dialogEditarHistorico">
        <q-card style="min-width: 350px">
            <q-form @submit="cobrancaNova == true ? novaCobranca() : salvarHistorico()">
                <q-card-section>
                    <div v-if="cobrancaNova == false" class="text-h6">Editar Histórico de cobrança</div>
                    <div v-else class="text-h6">Novo Histórico de cobrança</div>

                </q-card-section>
                <q-card-section class="">
                    <q-input outlined v-model="modelCobrancaHistorico.historico" autofocus label="Histórico" :rules="[
                        val => val && val.length > 0 || 'Histórico obrigatório'
                    ]" class="" />
                </q-card-section>

                <q-card-actions align="right" class="text-primary">
                    <q-btn flat label="Cancelar" v-close-popup />
                    <q-btn flat label="Salvar" type="submit" />
                </q-card-actions>
            </q-form>
        </q-card>
    </q-dialog>
</template>
  
<script>
import { defineComponent, defineAsyncComponent } from 'vue'
import { useQuasar, debounce } from "quasar"
import { ref } from 'vue'
import { useRoute } from 'vue-router'
import { pessoaStore } from 'stores/pessoa'
import { guardaToken } from 'src/stores'
import { formataDocumetos } from 'src/stores/formataDocumentos'
import moment from 'moment'

export default defineComponent({
    name: "CardHistoricoCobranca",

    methods: {

        async novaCobranca() {

            const ret = await this.sPessoa.novoHistoricoCobranca(this.route.params.id, this.modelCobrancaHistorico.historico)
            if (ret.data.data) {
                this.$q.notify({
                    color: 'green-5',
                    textColor: 'white',
                    icon: 'done',
                    message: 'Histórico criado!'
                })
                this.buscarCobrancas()
                this.dialogEditarHistorico = false
            } else {
                this.$q.notify({
                    color: 'red-5',
                    textColor: 'white',
                    icon: 'warning',
                    message: 'Erro, tente novamente'
                })
            }
        },

        editarHistorico(codcobrancahistorico, historico) {
            this.cobrancaNova = false
            this.dialogEditarHistorico = true
            this.modelCobrancaHistorico = {
                historico: historico,
                codcobrancahistorico: codcobrancahistorico
            }
        },

        deletarHistorico(codcobrancahistorico) {
            this.$q.dialog({
                title: 'Excluir Histórico',
                message: 'Tem certeza que deseja excluir esse histórico de cobrança?',
                cancel: true,
            }).onOk(async () => {
                try {
                    await this.sPessoa.deletaCobrancaHistorico(this.route.params.id, codcobrancahistorico)
                    this.$q.notify({
                        color: 'green-5',
                        textColor: 'white',
                        icon: 'done',
                        message: 'Histórico excluido!'
                    })
                    this.buscarCobrancas()
                } catch (error) {
                    this.$q.notify({
                        color: 'red-5',
                        textColor: 'white',
                        icon: 'error',
                        message: error.response.data.message
                    })
                }
            })
        },

        async salvarHistorico() {
            try {
                const ret = await this.sPessoa.salvarHistoricoCobranca(this.route.params.id, this.modelCobrancaHistorico.codcobrancahistorico, this.modelCobrancaHistorico)
                if (ret.data) {
                    this.$q.notify({
                        color: 'green-5',
                        textColor: 'white',
                        icon: 'done',
                        message: 'Histórico alterado'
                    })
                    this.dialogEditarHistorico = false
                    const i = this.HistoricosCobranca.findIndex(item => item.codcobrancahistorico === this.modelCobrancaHistorico.codcobrancahistorico)
                    this.HistoricosCobranca[i] = ret.data.data
                }
            } catch (error) {
                this.$q.notify({
                    color: 'red-5',
                    textColor: 'white',
                    icon: 'error',
                    message: error.response.data.message
                })
            }

        },

    },

    setup() {

        const $q = useQuasar()
        const sPessoa = pessoaStore()
        const dialogEditarHistorico = ref(false)
        const modelCobrancaHistorico = ref([])
        const loading = ref(true)
        const route = useRoute()
        const cobrancaNova = ref(false)
        const user = guardaToken()
        const Documentos = formataDocumetos()
        const HistoricosCobranca = ref([])
        const Paginas = ref({
            page: 1
        })

        const buscarCobrancas = debounce(async () => {
            try {
                Paginas.value.page = 1;
                const ret = await sPessoa.getCobrancaHistorico(route.params.id, Paginas.value)
                HistoricosCobranca.value = ret.data.data;
                loading.value = false
                $q.loadingBar.stop()
                if (ret.data.data.length == 0) {
                    loading.value = true
                }
            } catch (error) {
                $q.loadingBar.stop()
            }
        }, 500)

        return {
            sPessoa,
            HistoricosCobranca,
            Documentos,
            route,
            Paginas,
            user,
            loading,
            cobrancaNova,
            dialogEditarHistorico,
            buscarCobrancas,
            modelCobrancaHistorico,
            async scrollHistorico(index, done) {
                loading.value = true;
                $q.loadingBar.start()
                Paginas.value.page++
                const ret = await sPessoa.getCobrancaHistorico(route.params.id, Paginas.value)
                HistoricosCobranca.value.push(...ret.data.data)
                loading.value = false

                if (ret.data.data.length == 0) {
                    loading.value = true
                }
                $q.loadingBar.stop()
                done();
            },
        }
    },
    async mounted() {
        this.buscarCobrancas()
    }
})
</script>
  
<style scoped></style>
  