<template>
    <q-card>
        <q-item-label header>
            Top 10 Produtos
        </q-item-label>

        <div class="row">
            <div class="q-pl-md col-md-6">
                <select-pessoas label="Filtrar pessoa" v-model="filtroPessoa.codpessoa"></select-pessoas>
            </div>

            <div class="col-md-6 q-pl-md q-pr-md">
                <q-select outlined v-model="filtroPessoa.date" :options="[
                    'Este ano', '1 Ano', '2 Anos', 'Tudo']" label="Data" dense />
            </div>
        </div>
        <canvas id="graficoTopProdutos" height="200" width="200">
        </canvas>
    </q-card>
</template>
  
<script>
import { defineComponent, defineAsyncComponent, watch } from 'vue'
import { ref } from 'vue'
import Chart from 'chart.js/auto'
import { GrupoEconomicoStore } from 'src/stores/GrupoEconomico';
import { formataDocumetos } from 'src/stores/formataDocumentos';
import moment from 'moment'

export default defineComponent({
    name: "GraficoTop10Produtos",

    components: {
        SelectPessoas: defineAsyncComponent(() => import('components/pessoa/SelectPessoas.vue')),

    },

    methods: {

        async montaGrafico() {
            let valortotal = []
            let label = []
            this.produtos = []

            if (this.filtroPessoa.date === '1 Ano') { 
                let desde = moment().subtract(1, 'year').format('YYYY-MM-DD')
                this.filtroPessoa.desde = desde
            }

            const ret = await this.sGrupoEconomico.getTopProdutos(this.$route.params.id, this.filtroPessoa);

            ret.data.forEach(valores => {
                valortotal.push(valores.valortotal)
                label.push(valores.produto)
                this.produtos.push(valores.produto)
            });


            const Cores = [
                'rgb(255, 99, 132)',
                'rgb(255, 159, 64)',
                'rgb(255, 205, 86)',
                'rgb(75, 192, 192)',
                'rgb(54, 162, 235)',
                'rgb(153, 102, 255)',
                'rgb(201, 203, 207)',
                'rgb(150, 75, 0)',
                'rgb(50, 205, 50)',
                'rgb(127, 0, 255)']

            const data = {
                labels: this.produtos,
                datasets: [
                    {
                        label: 'Valor Total',
                        data: null,
                        backgroundColor: Cores,
                    },
                ]
            };

            const config = {
                type: 'doughnut',
                data: data,
                options: {
                    responsive: true,
                    plugins: {
                        legend: {
                            position: 'top',
                        },
                        title: {
                            display: true,
                            // text: 'Chart.js Line Chart'
                        }
                    }
                },
            };

            data.datasets[0].data = valortotal
            this.graficoTopProdutos = new Chart(document.getElementById('graficoTopProdutos'), config)
        },

        atualizaGrafico() {
            this.graficoTopProdutos.destroy()
            this.montaGrafico()
        },

        filtroDesde() {
            if (this.filtroPessoa.date === 'Este ano') {
                var anoAtual = moment().year();
                var inicio = new Date("1/1/" + anoAtual);
                var primeiroDia = moment(inicio.valueOf()).format('YYYY-MM-DD');
                this.filtroPessoa.desde = primeiroDia
                this.atualizaGrafico()
            }
            if (this.filtroPessoa.date === '1 Ano') {
                let desde = moment().subtract(1, 'year').format('YYYY-MM-DD')
                this.filtroPessoa.desde = desde
                this.atualizaGrafico()
            }
            if (this.filtroPessoa.date === '2 Anos') {
                let desde = moment().subtract(2, 'year').format('YYYY-MM-DD')
                this.filtroPessoa.desde = desde
                this.atualizaGrafico()
            }
            if (this.filtroPessoa.date === 'Tudo') {
                this.filtroPessoa.desde = null
                this.atualizaGrafico()
            }
        }
    },

    data() {

        watch(
            () => this.filtroPessoa.codpessoa,
            () => this.atualizaGrafico(),
            { deep: true }
        );

        watch(
            () => this.filtroPessoa.date,
            () => this.filtroDesde(),
            { deep: true }
        );

        return {
            model: null,
        }
    },

    async mounted() {
        this.montaGrafico()
    },

    setup() {

        const sGrupoEconomico = GrupoEconomicoStore()
        const filtroPessoa = ref({
            date: '1 Ano'
        })
        const graficoTopProdutos = ref('')
        const produtos = ref([])
        const Documentos = formataDocumetos()

       

        return {
            sGrupoEconomico,
            filtroPessoa,
            graficoTopProdutos,
            produtos,
            Documentos
        }
    },

})
</script>
  