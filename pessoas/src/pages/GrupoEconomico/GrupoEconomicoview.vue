<template>
    <MGLayout back-button>

        <template #botaoVoltar>
            <q-btn flat dense round  :to="{ name: 'grupoeconomicoindex' }"
            icon="arrow_back" aria-label="Voltar">
            </q-btn>
        </template>
        
        <template #tituloPagina>
            Grupo Econômico
        </template>
        <template #content>
            <q-card bordered class="col-12 q-ma-md">
                <q-item>
                    <q-toolbar class="text-black">
                        <q-avatar color="primary" size="100px" text-color="white" icon="groups"></q-avatar>
                        <q-item class="q-subtitle-1 q-pl-md">
                            <q-item-section>
                                <q-item-label header lines="1" class="text-h4 text-weight-bold"
                                    :class="GrupoEconomico.inativo ? 'text-strike text-red-14' : null">
                                    {{ GrupoEconomico.grupoeconomico }}
                                    <span v-if="GrupoEconomico.inativo" class="row text-caption text-red-14">
                                        Inativo desde: {{ Documentos.formataData(GrupoEconomico.inativo) }}
                                    </span>
                                </q-item-label>
                                <q-item-label caption lines="5">
                                    <span class="text-weight-bold" style="white-space: pre">{{ GrupoEconomico.observacoes
                                    }}</span>
                                </q-item-label>
                                <q-item-label caption>

                                </q-item-label>
                            </q-item-section>
                        </q-item>
                        <q-btn v-if="user.verificaPermissaoUsuario('Financeiro')" round flat icon="edit"
                            @click="editarGrupoEconomico(route.params.id)" />
                        <q-btn v-if="user.verificaPermissaoUsuario('Financeiro')" round flat icon="delete"
                            @click="excluirGrupoEconomico(route.params.id)" />

                        <q-btn v-if="user.verificaPermissaoUsuario('Financeiro') && !GrupoEconomico.inativo" round flat
                            icon="pause" @click="inativarGrupo(route.params.id)">
                            <q-tooltip>
                                Inativar
                            </q-tooltip>
                        </q-btn>

                        <q-btn v-if="user.verificaPermissaoUsuario('Financeiro') && GrupoEconomico.inativo"
                            @click="ativar(route.params.id)" round flat icon="play_arrow">
                            <q-tooltip>
                                Ativar
                            </q-tooltip>
                        </q-btn>

                        <q-btn round flat icon="info">
                            <q-tooltip>
                                <q-item-label class="row">Criado por {{ GrupoEconomico.usuariocriacao }} em {{
                                    Documentos.formataData(GrupoEconomico.criacao)
                                }}</q-item-label>
                                <q-item-label class="row">Alterado por {{ GrupoEconomico.usuarioalteracao }} em {{
                                    Documentos.formataData(GrupoEconomico.alteracao) }}</q-item-label>
                            </q-tooltip>
                        </q-btn>
                    </q-toolbar>
                </q-item>
            </q-card>

            <div class="row">

                <div class="q-pa-md col-md-12 col-lg-12 col-xs-12">
                    <grafico-negocios>

                    </grafico-negocios>
                </div>

                <div class="col-md-3 col-sm-6 col-xs-12 col-lg-3 col-xl-2 q-pr-md q-pb-md q-pl-md"
                    v-for="pessoa in CardPessoasGrupo" v-bind:key="pessoa.codpessoa">
                    <card-pessoas :listagempessoas="pessoa"></card-pessoas>
                </div>

            </div>

            <div class="row">
                <div class="q-pa-md col-md-8 col-xl-8 col-xs-12 q-pt-md">
                    <tabela-totais-negocios :totaisNegocios="totaisNegocios"
                        v-on:update:totais-negocios="updateTabelaNegocio($event)">
                    </tabela-totais-negocios>
                </div>
                <div class="q-pa-md col-md-4 col-lg-4 col-xs-12 q-pt-md">
                    <grafico-top-10-produtos>

                    </grafico-top-10-produtos>
                </div>
            </div>


            <div class="q-pa-md">
                <tabela-titulos-abertos :titulos-abertos="titulosAbertos"
                    v-on:update:titulos-abertos="updateTitulosAbertos($event)">
                </tabela-titulos-abertos>
            </div>

            <div class="q-pa-md">
                <tabela-nfe-terceiro :nfe-terceiro="nfeTerceiro" v-on:update:nfe-terceiro="updateNfeTerceiro($event)">
                </tabela-nfe-terceiro>
            </div>

            <!-- DIALOG EDITAR GRUPO ECONOMICO -->
            <q-dialog v-model="DialogGrupoEconomico">
                <q-card>
                    <q-card-section>
                        <q-form @submit="salvarGrupoEconomico(route.params.id)">
                        <q-input outlined v-model="modelEditarGrupo.grupoeconomico" label="Grupo Econômico"
                            class="q-mb-md" />
                        <q-input outlined v-model="modelEditarGrupo.observacoes" label="Observações" type="textarea"
                            class="q-mb-md" borderless autogrow />
                        </q-form>
                    </q-card-section>
                    <q-card-actions align="right">
                        <q-btn flat label="OK" color="primary" type="submit"
                            v-close-popup />
                        <q-btn flat label="Cancelar" color="secondary" v-close-popup />
                    </q-card-actions>
                </q-card>
            </q-dialog>
        </template>
    </MGLayout>
</template>
  
<script>
import { defineComponent, defineAsyncComponent, onMounted } from 'vue'
import { useQuasar } from "quasar"
import { ref } from 'vue'
import { useRoute } from 'vue-router'
import { useRouter } from 'vue-router'
import { formataDocumetos } from 'src/stores/formataDocumentos'
import { GrupoEconomicoStore } from 'src/stores/GrupoEconomico'
import { guardaToken } from 'src/stores'
import { pessoaStore } from 'src/stores/pessoa'

export default defineComponent({
    name: "GrupoEconomico",

    components: {
        MGLayout: defineAsyncComponent(() => import('layouts/MGLayout.vue')),
        CardPessoas: defineAsyncComponent(() => import('components/pessoa/CardPessoas.vue')),
        TabelaTotaisNegocios: defineAsyncComponent(() => import('components/grupoEconomico/TabelaTotaisNegocios.vue')),
        TabelaTitulosAbertos: defineAsyncComponent(() => import('components/grupoEconomico/TabelaTitulosAbertos.vue')),
        TabelaNfeTerceiro: defineAsyncComponent(() => import('components/grupoEconomico/TabelaNfeTerceiro.vue')),
        GraficoNegocios: defineAsyncComponent(() => import('components/grupoEconomico/GraficoNegocios.vue')),
        GraficoTop10Produtos: defineAsyncComponent(() => import('components/grupoEconomico/GraficoTop10Produtos.vue')),


    },

    methods: {

        updateTabelaNegocio(event) {
            this.totaisNegocios = event
        },

        updateTitulosAbertos(event) {
            this.titulosAbertos = event
        },

        updateNfeTerceiro(event) {
            this.nfeTerceiro = event
        },

        async inativarGrupo(codgrupoeconomico) {
            try {
                const ret = await this.sGrupoEconomico.inativarGrupo(codgrupoeconomico)
                if (ret.data) {
                    this.GrupoEconomico = ret.data.data
                    this.$q.notify({
                        color: 'green-5',
                        textColor: 'white',
                        icon: 'done',
                        message: 'Inativado!'
                    })
                }
            } catch (error) {
                this.$q.notify({
                    color: 'red-5',
                    textColor: 'white',
                    icon: 'error',
                    message: error.message
                })
            }
        },

        async ativar(codgrupoeconomico) {
            try {
                const ret = await this.sGrupoEconomico.ativarGrupo(codgrupoeconomico)
                if (ret.data) {
                    this.GrupoEconomico = ret.data.data
                    this.$q.notify({
                        color: 'green-5',
                        textColor: 'white',
                        icon: 'done',
                        message: 'Ativado!'
                    })
                }
            } catch (error) {
                this.$q.notify({
                    color: 'red-5',
                    textColor: 'white',
                    icon: 'error',
                    message: error.message
                })
            }
        },

        removerdoGrupo(pessoa, grupoeconomico, codpessoa, codgrupoeconomico) {

            this.$q.dialog({
                title: 'Excluir pessoa do grupo',
                message: 'Tem certeza que deseja excluir ' + pessoa + ' do grupo economico ' + grupoeconomico + '?',
                cancel: true,
            }).onOk(async () => {
                const ret = await this.sGrupoEconomico.removerdoGrupo(codpessoa, codgrupoeconomico)
                if (ret.data.message == true) {
                    this.$q.notify({
                        color: 'green-5',
                        textColor: 'white',
                        icon: 'done',
                        message: 'Removido'
                    })
                    const get = await this.sGrupoEconomico.getGrupoEconomico(codgrupoeconomico)
                    this.CardPessoasGrupo = get.data.data.PessoasdoGrupo
                }
            })

        },

        editarGrupoEconomico(codgrupoeconomico) {
            this.DialogGrupoEconomico = true
            this.modelEditarGrupo = {
                grupoeconomico: this.GrupoEconomico.grupoeconomico,
                observacoes: this.GrupoEconomico.observacoes
            }
        },

        excluirGrupoEconomico(codgrupoeconomico) {
            this.$q.dialog({
                title: 'Excluir grupo',
                message: 'Tem certeza que deseja excluir esse grupo econômico?',
                cancel: true,
            }).onOk(async () => {
                const ret = await this.sGrupoEconomico.excluirGrupoEconomico(codgrupoeconomico)
                if (ret) {
                    this.$q.notify({
                        color: 'green-5',
                        textColor: 'white',
                        icon: 'done',
                        message: 'Grupo removido'
                    })
                    return this.router.push('/grupoeconomico')
                }
            })
        },

        async salvarGrupoEconomico(codgrupoeconomico) {
            this.DialogGrupoEconomico = false
            try {
                const ret = await this.sGrupoEconomico.salvarGrupoEconomico(codgrupoeconomico, this.modelEditarGrupo)
                if (ret.data.data) {
                    this.$q.notify({
                        color: 'green-5',
                        textColor: 'white',
                        icon: 'done',
                        message: 'Alterado'
                    })
                    this.GrupoEconomico = ret.data.data
                }
            } catch (error) {
                this.$q.notify({
                    color: 'red-5',
                    textColor: 'white',
                    icon: 'error',
                    message: error.message
                })
            }
        },
    },

    setup() {

        const $q = useQuasar()

        const route = useRoute()
        const GrupoEconomico = ref([])
        const router = useRouter()
        const sGrupoEconomico = GrupoEconomicoStore()
        const sPessoa = pessoaStore()
        const Documentos = formataDocumetos()
        const CardPessoasGrupo = ref([])
        const user = guardaToken()
        const totaisNegocios = ref([])
        const titulosAbertos = ref([])
        const nfeTerceiro = ref([])

        const totaisNegociosGrupo = async () => {
            try {
                // const ret = await sPessoa.totaisNegocios(route.params.id)
                // totaisNegocios.value = ret.data

                const retTitulos = await sPessoa.titulosAbertos(route.params.id)
                titulosAbertos.value = retTitulos.data

            } catch (error) {
                $q.notify({
                    color: 'red-5',
                    textColor: 'white',
                    icon: 'error',
                    message: error.response.data.message ?? 'Erro ao carregar nfe Terceiro'
                })
            }

        }

        onMounted(async () => {
            const ret = await sGrupoEconomico.getGrupoEconomico(route.params.id)
            CardPessoasGrupo.value = ret.data.data.PessoasdoGrupo
            GrupoEconomico.value = ret.data.data
            totaisNegociosGrupo()
        })

        return {
            route,
            sGrupoEconomico,
            router,
            user,
            sPessoa,
            titulosAbertos,
            totaisNegocios,
            nfeTerceiro,
            Documentos,
            DialogGrupoEconomico: ref(false),
            modelEditarGrupo: ref([]),
            GrupoEconomico,
            CardPessoasGrupo,
            selectGrupoEconomico(val, update) {
                update(async () => {
                    const nomeGrupo = val.toLowerCase()
                    try {
                        if (nomeGrupo.length > 3) {
                            const ret = await sGrupoEconomico.selectGrupoEconomico(nomeGrupo)
                            GrupoEconomico.value = ret.data.data
                            return
                        }
                    } catch (error) {
                        this.$q.notify({
                            color: 'red-5',
                            textColor: 'white',
                            icon: 'error',
                            message: error.message
                        })
                    }
                })
            },
        }
    },
})
</script>
  
<style scoped></style>
  