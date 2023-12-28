<template>
    <MGLayout drawer>
      <template #tituloPagina>
        Transferências Nota Fiscais
      </template>
        <template #content>
          <q-page class="q-pa-sm" >
          <tabela-notas-transf :date=modeldate></tabela-notas-transf>
          <tabela-notas-emitir></tabela-notas-emitir>
          <tabela-notas-nao-autorizadas></tabela-notas-nao-autorizadas>
          <tabela-notas-emitidas :date=modeldate></tabela-notas-emitidas>
          <tabela-notas-lancadas :date=modeldate></tabela-notas-lancadas>
          <!-- <slot v-bind="data" name="drawerfiltronotas" > </slot> -->
        </q-page>
      </template>
       <!-- Menu Drawer personalizado filtro -->
      <template #drawer>
        <q-list>
          <q-item class='q-pa-md'>
            <q-item-section>
              <div class="q-py-md">
                <!-- <q-date v-model="modeldate"
                  :locale="brasil" mask="YYYY-MM-DD"/> -->
                  <q-input filled v-model="modeldate" mask="##-##-####"  label="Filtro data">
                  <template v-slot:append>
                    <q-icon name="event" class="cursor-pointer">
                      <q-popup-proxy cover transition-show="scale" transition-hide="scale">
                        <q-date v-model="modeldate" :locale="brasil" mask="DD-MM-YYYY">
                          <div class="row items-center justify-end">
                            <q-btn v-close-popup label="Fechar" color="primary" flat />
                          </div>
                        </q-date>
                      </q-popup-proxy>
                    </q-icon>
                  </template>
                </q-input>
              </div>
            </q-item-section>
          </q-item>
        </q-list>
        <q-separator></q-separator>
        
        <!-- <div class="col-sm-4 col-lg-6 q-py-md text-center">
          <q-btn color="primary" class="text-center" label="Aplicar filtro" @click="filtrodate"/>
        </div> -->
        <q-separator></q-separator>
      
      </template>      
    </MGLayout>
  </template>
  <script>
  import {defineComponent, defineAsyncComponent, ref} from 'vue'
  import TabelaNotasTransf from 'components/notafiscal/TabelaNotasTransf.vue'
  import  moment  from 'moment'
  
  
  export default defineComponent({
    name: "Tabela",
    components: {
      TabelaNotasTransf,
      TabelaNotasEmitir: defineAsyncComponent(() => import('components/notafiscal/TabelaNotasEmitir.vue')),
      TabelaNotasNaoAutorizadas: defineAsyncComponent(() => import('components/notafiscal/TabelaNotasNaoAutorizadas.vue')),
      TabelaNotasEmitidas: defineAsyncComponent(() => import('components/notafiscal/TabelaNotasEmitidas.vue')),
      TabelaNotasLancadas: defineAsyncComponent(() => import('components/notafiscal/TabelaNotasLancadas.vue')),
       MGLayout: defineAsyncComponent(() => import('layouts/MGLayout.vue'))
    },

    setup(){
      const datemodel = moment().subtract(2, 'months').format('DD-MM-YYYY')
      const modeldate = ref(datemodel)
      const loading = ref(true)
      
      return {
     //Traduzindo o calendario para PT-BR
      brasil: {
        days: 'Domingo_Segunda_Terça_Quarta_Quinta_Sexta_Sábado'.split('_'),
        daysShort: 'Dom_Seg_Ter_Qua_Qui_Sex_Sáb'.split('_'),
        months: 'Janeiro_Fevereiro_Março_Abril_Maio_Junho_Julho_Agosto_Setembro_Outubro_Novembro_Dezembro'.split('_'),
        monthsShort: 'Jan_Fev_Mar_Abr_Mai_Jun_Jul_Ago_Set_Out_Nov_Dez'.split('_'),
        firstDayOfWeek: 1,
        format24h: true,
        pluralDay: 'dias'
      },
      loading,
      modeldate,
    }
    }
  })
  </script>
  <style>
  </style>
  