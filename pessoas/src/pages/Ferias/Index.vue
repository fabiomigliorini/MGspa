
<template>
    <MGLayout>

        <template #tituloPagina>
            Férias
        </template>

        <template #content>

            <q-card bordered>
                <q-list>
                    <q-item-label header>
                        Programação Férias
                    </q-item-label>
                </q-list>
            </q-card>

            <q-card bordered class="q-pa-md">
                <q-markup-table separator="vertical">
                    <thead>
                        <q-tr>
                            <q-th class="text-left">Cargo</q-th>
                            <q-th class="text-left">Nome</q-th>

                            <q-th class="text-left">Janeiro</q-th>
                            <q-th class="text-right">Fevereiro</q-th>
                            <q-th class="text-right">Março</q-th>
                            <q-th class="text-right">Abril</q-th>
                            <q-th class="text-right">Maio</q-th>
                            <q-th class="text-right">Junho</q-th>
                            <q-th class="text-right">Julho</q-th>
                            <q-th class="text-right">Agosto</q-th>
                            <q-th class="text-right">Setembro</q-th>
                            <q-th class="text-right">Outubro</q-th>
                            <q-th class="text-right">Novembro</q-th>
                            <q-th class="text-right">Dezembro</q-th>
                        </q-tr>
                    </thead>
                </q-markup-table>
                
                <div class="row">
                    <q-list>
                        <q-item>
                            <div class="col-md-6">
                                <q-item-label>Entregador</q-item-label>
                            </div>
                            <div class="col-md-6">
                                <q-item-label>Allan Henrique</q-item-label>
                            </div>
                        </q-item>
                    </q-list>

                    <div class="col-md-10">
                        <q-range v-model="modelRange.range" drag-only-range :min="0" :max="366" />
                        {{ modelRange.range }}
                    </div>
                    <!-- <div class="col-sm-1">
                    <q-item-label>Entregador</q-item-label>
                    </div> -->
                    <!-- <div class="">
                    <q-item-label>Allan Henrique</q-item-label>
                    </div> -->
                </div>
                <q-separator />
                <div class="row">
                    <q-list>
                        <q-item>
                            <div class="col-md-6">
                                <q-item-label>Entregador</q-item-label>
                            </div>
                            <div class="col-md-6">
                                <q-item-label>Allan Henrique</q-item-label>
                            </div>
                        </q-item>
                    </q-list>

                    <div class="col-md-10">
                        <q-range v-model="modelRange.range2" drag-only-range :min="0" :max="366" />
                        {{ modelRange.range2 }}
                    </div>
                    <!-- <div class="col-sm-1">
                    <q-item-label>Entregador</q-item-label>
                    </div> -->
                    <!-- <div class="">
                    <q-item-label>Allan Henrique</q-item-label>
                    </div> -->


                </div>

            </q-card>
        </template>

    </MGLayout>
</template>
  
<script>
import { ref, defineAsyncComponent } from 'vue'
import moment from 'moment'

export default {
    components: {
        MGLayout: defineAsyncComponent(() => import('layouts/MGLayout.vue')),
    },

    methods: {

        rangesMeses() {

            var gozoinicio = '2024-03-15'
            var gozofinal = moment('2024-04-14')
            var descobreMes = moment(gozoinicio).month()

            var diasGozo = gozofinal.diff(gozoinicio, 'days')
            console.log(diasGozo)

            console.log(descobreMes)

            if (descobreMes == 2) { // Mes de março
                this.modelRange.min = 74 
                this.modelRange.max = 104 
            }
        }
    },

    setup() {

        const modelRange = ref({
            range: {min: 74, max: 104},
            range2: { min: 60, max: 90 }
        })

        return {
            modelRange,
        }
    },
    mounted() {
        this.rangesMeses()
    }
}
</script>