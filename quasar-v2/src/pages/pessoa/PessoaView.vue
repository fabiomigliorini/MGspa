<template>
    <MGLayout>
        <template #content>
            <q-page class="q-pa-sm bg-white">
                <div class="row">
                    <div class="col-lg-4 q-pl-xs col-md-8 col-sm-12 col-xs-12">
                        <!-- IMPORTA CARD DETALHES PESSOA -->
                        <!-- <card-detalhes-pessoa>
                        </card-detalhes-pessoa> -->
                    </div>
                    <div class="col-lg-4 col-md-4 col-sm-12 col-xs-12">
                        <!-- TELEFONE/EMAIL/ENDEREÇOS -->
                        <item-telefone>
                        </item-telefone>

                        <!-- <item-email>
                        </item-email> -->

                        <!-- <item-endereco>
                        </item-endereco> -->

                    </div>
                </div>
            </q-page>
        </template>
    </MGLayout>
</template>
  
<script>
import { defineComponent, defineAsyncComponent, onMounted } from 'vue'
import { useQuasar } from "quasar"
import { useRoute } from 'vue-router'
import { pessoaStore } from 'stores/pessoa'


export default defineComponent({
    name: "PessoaView",
    components: {
        // CardDetalhesPessoa: defineAsyncComponent(() => import('components/pessoa/CardDetalhesPessoa.vue')),
        ItemTelefone: defineAsyncComponent(() => import('components/pessoa/ItemTelefone.vue')),
        // ItemEmail: defineAsyncComponent(() => import('components/pessoa/ItemEmail.vue')),
        // ItemEndereco: defineAsyncComponent(() => import('components/pessoa/ItemEndereco.vue')),
        MGLayout: defineAsyncComponent(() => import('layouts/MGLayout.vue'))
    },

    setup() {

        const $q = useQuasar()
        const route = useRoute()
        const sPessoa = pessoaStore()

        onMounted(() => {
            //   $q.loading.show({})
            const get = sPessoa.get(route.params.id)
            //    $q.loading.hide()
        })

        return {
            sPessoa,
        }
    },
})
</script>
  
<style scoped></style>
  