<template>
    <MGLayout>
        <template #tituloPagina> Aniversários </template>
        <template #content>

            <div class="row q-pa-md">
                <div class="col-12">
                    <q-card>
                        <q-tabs v-model="tipo" dense class="text-grey" active-color="primary" indicator-color="primary"
                            align="justify" narrow-indicator>
                            <q-tab name="todos" label="Todos" />
                            <q-tab name="colaborador" label="Colaborador" />
                            <q-tab name="cliente" label="Cliente" />
                            <q-tab name="fornecedor" label="Fornecedor" />
                        </q-tabs>

                        <div class="row q-gutter-md q-pa-md flex flex-center">
                            <q-date v-model="date" :options="events" event-color="orange" />
                            <div v-for="aniversario, i in aniversariosDoDia" v-bind:key="i">
                                <div class="col-3">
                                    <q-card>
                                        <q-card-section>
                                            <q-item>
                                                <q-item-section>
                                                    <q-item-label>
                                                        <div class="text-h4 q-mb-md">
                                                            <q-icon name="calendar_month" color="blue" />
                                                            Dia {{ aniversario.dia }}
                                                        </div>
                                                    </q-item-label>
                                                    <q-item-label>
                                                        <q-icon name="celebration" color="blue" />&nbsp; 
                                                        {{
                                                     aniversario.idade }} Anos de {{ aniversario.tipo }}
                                                    </q-item-label>
                                                    <q-item-label>
                                                        <q-icon name="people" color="blue" />&nbsp;
                                                        <q-btn dense flat color="primary" class="ellipsis"
                                                            :label="aniversario.pessoa"
                                                            :href="'/#/pessoa/' + aniversario.codpessoa"
                                                            target="_blank" />
                                                    </q-item-label>
                                                </q-item-section>
                                            </q-item>
                                        </q-card-section>
                                    </q-card>
                                </div>
                            </div>
                        </div>

                        <!-- Tabela aniversarios -->
                        <div class="row q-pa-md">
                            <div class="col-12">
                                <q-table :rows="aniversarios" :columns="columns"
                                    no-data-label="Nenhum aniversário encontrado" :pagination="{ rowsPerPage: 50 }"
                                    emit-value>

                                    <template v-slot:body="aniversarios">

                                        <q-tr :props="aniversarios">
                                    
                                            <q-td key="data" :props="aniversarios">
                                                {{ moment(aniversarios.row.data).format('ddd, D/MMM') }}
                                            </q-td>
                                            <q-td key="pessoa" :props="aniversarios" class="cursor-pointer"
                                                @click="pessoa(aniversarios.row.codpessoa)">
                                                {{ aniversarios.row.pessoa }}
                                            </q-td>
                                            <q-td key="idade" :props="aniversarios">
                                                {{ aniversarios.row.idade }} Anos de idade
                                            </q-td>
                                        </q-tr>
                                    </template>
                                </q-table>
                            </div>
                        </div>
                    </q-card>
                </div>
            </div>
        </template>
    </MGLayout>
</template>

<script>
import { defineComponent, defineAsyncComponent, ref } from 'vue'
import { useRouter } from 'vue-router'
import { pessoaStore } from 'src/stores/pessoa'
import moment from "moment";
import "moment/min/locales";
moment.locale("pt-br");
import Ably from 'ably'

export default defineComponent({
    name: 'Index',
    computed: {
        aniversariosDoDia() {
            const dia = moment(this.date, 'YYYY/MM/DD');
            return this.aniversarios.filter((a) => {
                return (a.dia == dia.date() && a.mes == (dia.month() + 1));
            });
        }
    },
    components: {
        MGLayout: defineAsyncComponent(() => import('layouts/MGLayout.vue')),
    },

    methods: {

        pessoa(codpessoa) {
            var a = document.createElement('a');
            a.target = "_blank";
            a.href = "/#/pessoa/" + codpessoa
            a.click();
        },

        async getAniversarios() {

            const ret = await this.sPessoa.buscaAniversarios(this.tipo)
            this.aniversarios = ret.data

            let dates = []
            let arrAniversarios = []
            var diaAtual = moment()

            this.aniversarios.forEach(el => {

                var date = moment(el.data, 'YYYY-MM-DD')

                var dataAnoAtual = moment(el.data, 'YYYY-MM-DD')
                dataAnoAtual.date(date.date());
                dataAnoAtual.month(date.month());
                dataAnoAtual.year(diaAtual.year());

                var datasFormatadas = moment(dataAnoAtual).format('YYYY/MM/DD')
                el.data = datasFormatadas
                dates.push(datasFormatadas)
                arrAniversarios.push(el)

            });

            this.aniversarios = arrAniversarios
            this.events = dates

        },

        async publishSubscribe() {

            var extras = {
                push: {
                    notification: {
                        title: 'Hello from Ably!',
                        body: 'Teste notificação Push'
                    },
                    data: {
                        foo: 'bar',
                        baz: 'qux'
                    }
                }
            };

            // Connect to Ably with your API key
            const ably = new Ably.Rest.Promise("OnmSpw.hJFWXQ:LufpHd1sCEkx0XtctIww739NwQTsIG3SXLrzslwhMww")

            // ably.connection.once("connected", () => {
            //     console.log("Connected to Ably!")
            // })

            // Create a channel called 'get-started' and register a listener to subscribe to all messages with the name 'first'
            const channel = ably.channels.get("OnmSpw")

            console.log(channel);
            console.log(ably)

            // await channel.subscribe("first", (message) => {
            //     console.log("Message received: " + message.data)
            // });

            // Publish a message with the name 'first' and the contents 'Here is my first message!'
            //    const teste = await channel.publish("first", "Here is my first message!")
            const teste = await channel.publish({ name: 'example', data: 'data', extras: extras }, function (err) {

                if (err) {
                    console.log('erro', err)
                    return
                }

                console.log('push enviado')
            });
            // Close the connection to Ably after a 5 second delay
            // setTimeout(async () => {
            //     ably.connection.close();
            //     await ably.connection.once("closed", function () {
            //         console.log("Closed the connection to Ably.")
            //     });
            // }, 5000);
        }

    },

    watch: {
        tipo: function () {
            this.getAniversarios();
        },
    },

    setup(props, context) {
        const router = useRouter()
        const tipo = ref('todos')
        const sPessoa = pessoaStore()
        const splitterModel = ref(50)
        const events = ['2019/02/01', '2019/02/05', '2019/02/06']
        const date = ref(moment().format('YYYY/MM/DD'))
        const aniversarios = ref([])

        const columns = [
            { name: 'data', label: 'Data', field: 'data', align: 'top-left' },
            { name: 'pessoa', label: 'Pessoa', field: 'pessoa', align: 'top-left', },
            { name: 'idade', label: 'Aniversário', field: 'idade', align: 'top-left' },
        ];

        return {
            splitterModel,
            events,
            tipo,
            sPessoa,
            date,
            aniversarios,
            moment,
            columns
        }

    },
    async mounted() {
        this.getAniversarios()


        // this.publishSubscribe();

    }

})
</script>
