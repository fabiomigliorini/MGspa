<template>
    <MGLayout back-button>
        <template #tituloPagina>
            <span class="q-pl-sm">Pessoa - Detalhes</span>
        </template>
        <template #content>
            <q-page class="bg-white ">
                <div class="row q-py-md q-pr-md">
                    <div class="col-lg-4 col-md-8 col-sm-12 col-xs-12 q-pl-md">

                        <!--  DETALHES PESSOA -->
                        <div class="q-pb-md">
                            <card-detalhes-pessoa>
                            </card-detalhes-pessoa>
                        </div>

                        <!-- CLIENTE -->
                        <div class="q-pb-md">
                            <card-cliente>
                            </card-cliente>
                        </div>

                       
                        <div class="q-pb-md">
                <tabela-totais-negocios :totais-negocios="totalNegocioPessoa" v-on:update:totais-negocios="updateTabelaNegocio($event)">
                </tabela-totais-negocios>

                        </div>


                    </div>

                    <div class="col-lg-4 col-md-4 col-sm-12 col-xs-12 q-pl-md">

                        <!-- TELEFONE -->
                        <div class="q-pb-md">
                            <item-telefone>
                            </item-telefone>
                        </div>

                        <!-- EMAIL -->
                        <div class="q-pb-md">
                            <item-email>
                            </item-email>
                        </div>

                        <!-- ENDERECO -->
                        <div class="q-pb-md">
                            <item-endereco>
                            </item-endereco>
                        </div>

                        <!-- Registro Spc -->
                        <div class="q-pb-md">
                            <card-registro-spc></card-registro-spc>
                        </div>

                        <!-- certidoes -->
                        <div class="q-pb-md">
                            <card-certidoes></card-certidoes>
                        </div>

                         <!-- Histórico de cobrança -->
                         <div class="q-pb-md">
                            <card-historico-cobranca>
                            </card-historico-cobranca>
                        </div>
                    </div>
                </div>
                

            </q-page>
        </template>
    </MGLayout>
</template>
  
<script>
import { defineComponent, defineAsyncComponent, onMounted, ref } from 'vue'
import { useQuasar } from "quasar"
import { useRoute } from 'vue-router'
import { pessoaStore } from 'stores/pessoa'
import { GrupoEconomicoStore } from 'src/stores/GrupoEconomico'



export default defineComponent({
    name: "PessoaView",
    components: {
        CardDetalhesPessoa: defineAsyncComponent(() => import('components/pessoa/CardDetalhesPessoa.vue')),
        CardCliente: defineAsyncComponent(() => import('components/pessoa/CardCliente.vue')),
        ItemTelefone: defineAsyncComponent(() => import('components/pessoa/ItemTelefone.vue')),
        ItemEmail: defineAsyncComponent(() => import('components/pessoa/ItemEmail.vue')),
        ItemEndereco: defineAsyncComponent(() => import('components/pessoa/ItemEndereco.vue')),
        CardHistoricoCobranca: defineAsyncComponent(() => import('components/pessoa/CardHistoricoCobranca.vue')),
        CardRegistroSpc: defineAsyncComponent(() => import('components/pessoa/CardRegistroSpc.vue')),
        CardCertidoes: defineAsyncComponent(() => import('components/pessoa/CardCertidoes.vue')),
        TabelaTotaisNegocios: defineAsyncComponent(() => import('components/pessoa/TabelaTotaisNegocios.vue')),
        MGLayout: defineAsyncComponent(() => import('layouts/MGLayout.vue'))
    },

    methods: {
        updateTabelaNegocio(event) {
        this.totalNegocioPessoa = event
        },
    },

    setup() {

        const $q = useQuasar()
        const route = useRoute()
        const sPessoa = pessoaStore()
        const totalNegocioPessoa = ref([])
        const sGrupoEconomico = GrupoEconomicoStore()

        onMounted(async() => {
            //   $q.loading.show({})
            const get = sPessoa.get(route.params.id)
            //    $q.loading.hide()
            const ret = await sPessoa.totaisNegocios(1, {codpessoa: route.params.id})
            totalNegocioPessoa.value = ret.data
        })

        return {
            sPessoa,
            totalNegocioPessoa,
        }
    },
})
</script>
  
<style scoped></style>
  