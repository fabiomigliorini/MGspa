<template v-if="user.verificaPermissaoUsuario('Recursos Humanos')">
    <div class="row q-col-gutter-md">
        <div v-for="colaboradorCargo in colaboradorCargos.ColaboradorCargo"
            v-bind:key="colaboradorCargo.codcolaboradorcargo" class="col-xs-12 col-sm-6 col-md-4 col-lg-4 col-xl-3">
            <q-card bordered>

                <q-item-label header>
                    {{ colaboradorCargo.Cargo }}
                    <q-btn flat round icon="edit" @click="editarColaboradorCargo(colaboradorCargo.codcolaboradorcargo, colaboradorCargo.codcolaborador,
                        colaboradorCargo.codcargo, colaboradorCargo.codfilial, colaboradorCargo.inicio, colaboradorCargo.fim,
                        colaboradorCargo.comissaoloja, colaboradorCargo.comissaovenda, colaboradorCargo.comissaoxerox, colaboradorCargo.gratificacao,
                        colaboradorCargo.observacoes)" />
                    <q-btn flat round icon="delete"
                        @click="deletarColaboradorCargo(colaboradorCargo.codcolaboradorcargo)" />
                </q-item-label>

                <q-separator inset />
                <q-item>
                    <q-item-section avatar>
                        <q-icon name="corporate_fare" color="blue"></q-icon>
                    </q-item-section>
                    <q-item-section>
                        <q-item-label v-if="colaboradorCargo.inicio">
                            {{ colaboradorCargo.Filial }}
                        </q-item-label>
                        <q-item-label caption v-if="!colaboradorCargo.fim">
                            {{ moment(colaboradorCargo.inicio).format('DD/MMM/YYYY') }} a
                            ({{ Documentos.formataFromNow(colaboradorCargo.inicio) }})
                        </q-item-label>
                        <q-item-label caption v-else>
                            {{ moment(colaboradorCargo.inicio).format('DD/MMM') }} a
                            {{ moment(colaboradorCargo.fim).format('DD/MMM/YYYY') }}
                        </q-item-label>


                        <!-- <q-item-label caption v-if="!colaboradorCargo.fim">
                            {{ moment(colaboradorCargo.inicio).format('DD/MMM/YYYY') }} a
                            ({{ Documentos.formataFromNow(colaboradorCargo.inicio) }})
                        </q-item-label> -->

                    </q-item-section>
                </q-item>

                <q-separator inset />

                <q-item :to="'/cargo/' + colaboradorCargo.codcargo" clickable>
                    <q-item-section avatar>
                        <q-icon name="engineering" color="blue"></q-icon>
                    </q-item-section>
                    <q-item-section>
                        <q-item-label v-if="colaboradorCargo.inicio">
                            {{ colaboradorCargo.Cargo }}
                        </q-item-label>
                        <!-- <q-item-label caption>
                           CAPTION
                       </q-item-label> -->
                    </q-item-section>
                </q-item>
                <q-separator inset />
                <q-item
                    v-if="colaboradorCargo.comissaoloja || colaboradorCargo.comissaovenda || colaboradorCargo.comissaoxerox">
                    <q-item-section avatar>
                        <q-icon name="money" color="blue"></q-icon>
                    </q-item-section>
                    <q-item-section>
                        <q-item-label>
                            <span> Loja: {{ colaboradorCargo.comissaoloja }}% </span>
                            <span>Venda: {{ colaboradorCargo.comissaovenda }}% </span>
                            <span>Xerox: {{ colaboradorCargo.comissaoxerox }}% </span>
                        </q-item-label>
                        <q-item-label caption>
                            Comissão
                        </q-item-label>
                    </q-item-section>
                </q-item>

                <template v-if="colaboradorCargo.gratificacao">
                    <q-separator inset />
                    <q-item>
                        <q-item-section avatar>
                            <q-icon name="payments" color="blue"></q-icon>
                        </q-item-section>
                        <q-item-section>
                            <q-item-label>
                                {{ new Intl.NumberFormat('pt-BR', {
                                    style: 'currency', currency: 'BRL'
                                }).format(colaboradorCargo.gratificacao) }}
                            </q-item-label>
                            <q-item-label caption>
                                Gratificação
                            </q-item-label>
                        </q-item-section>
                    </q-item>
                </template>

                <template v-if="colaboradorCargo.observacoes">
                    <q-separator inset />
                    <q-item>
                        <q-item-section avatar>
                            <q-icon name="comment" color="blue"></q-icon>
                        </q-item-section>
                        <q-item-section>
                            <q-item-label>
                                {{ colaboradorCargo.observacoes }}
                            </q-item-label>
                            <q-item-label caption>
                                Observações
                            </q-item-label>
                        </q-item-section>
                    </q-item>
                </template>
            </q-card>
        </div>
    </div>

    <!-- Dialog editar Colaborador Cargo -->
    <q-dialog v-model="dialogEditarColaboradorCargo">
        <q-card style="min-width: 350px">
            <q-form @submit="salvarColaboradorCargo()">
                <q-card-section>
                    <div class="text-h6">Editar Colaborador Cargo</div>
                </q-card-section>
                <q-card-section>

                    <select-cargo v-model="modelnovoColaboradorCargo.codcargo" reactive-rules :rules="[
                        val => val !== null && val !== '' && val !== undefined || 'Cargo Obrigatório',
                    ]"></select-cargo>

                    <select-filial v-model="modelnovoColaboradorCargo.codfilial" reactive-rules :rules="[
                        val => val !== null && val !== '' && val !== undefined || 'Filial obrigatório'
                    ]">

                    </select-filial>

                    <div class="row">
                        <div class="col-6">
                            <q-input outlined v-model="modelnovoColaboradorCargo.inicio" mask="##/##/####" label="Início"
                                :rules="[validaDataValida, validaInicio, validaObrigatorio]">

                                <template v-slot:append>
                                    <q-icon name="event" class="cursor-pointer">
                                        <q-popup-proxy cover transition-show="scale" transition-hide="scale">
                                            <q-date v-model="modelnovoColaboradorCargo.inicio" :locale="brasil"
                                                mask="DD/MM/YYYY">
                                                <div class="row items-center justify-end">
                                                    <q-btn v-close-popup label="Fechar" color="primary" flat />
                                                </div>
                                            </q-date>
                                        </q-popup-proxy>
                                    </q-icon>
                                </template>
                            </q-input>
                        </div>
                        <div class="col-6">
                            <q-input outlined v-model="modelnovoColaboradorCargo.fim" class="q-pl-md" mask="##/##/####"
                                label="Fim" :rules="[validaDataValida, validaFim]">

                                <template v-slot:append>
                                    <q-icon name="event" class="cursor-pointer">
                                        <q-popup-proxy cover transition-show="scale" transition-hide="scale">
                                            <q-date v-model="modelnovoColaboradorCargo.fim" :locale="brasil"
                                                mask="DD/MM/YYYY">
                                                <div class="row items-center justify-end">
                                                    <q-btn v-close-popup label="Fechar" color="primary" flat />
                                                </div>
                                            </q-date>
                                        </q-popup-proxy>
                                    </q-icon>
                                </template>
                            </q-input>
                        </div>
                        <div class="col-6">
                            <q-input outlined v-model="modelnovoColaboradorCargo.comissaoloja" label="Comissão Loja">
                                <template v-slot:append>
                                    %
                                </template>

                            </q-input>

                        </div>
                        <div class="col-6">
                            <q-input outlined v-model="modelnovoColaboradorCargo.comissaovenda" label="Comissão Venda"
                                class="q-pl-md">
                                <template v-slot:append>
                                    %
                                </template>
                            </q-input>
                        </div>
                        <div class="col-6">
                            <q-input outlined v-model="modelnovoColaboradorCargo.comissaoxerox" label="Comissão Xerox"
                                class="q-pt-md">
                                <template v-slot:append>
                                    %
                                </template>
                            </q-input>
                        </div>
                        <div class="col-6">
                            <q-input outlined v-model="modelnovoColaboradorCargo.gratificacao" label="Gratificação"
                                class="q-pl-md q-pt-md">
                                <template v-slot:prepend>
                                    R$
                                </template>
                            </q-input>
                        </div>
                    </div>

                    <q-input outlined autogrow bordeless v-model="modelnovoColaboradorCargo.observacoes" class="q-pt-md"
                        label="Observações" type="textarea" />

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
import { defineComponent, defineAsyncComponent, computed } from 'vue'
import { useQuasar, debounce } from "quasar"
import { ref } from 'vue'
import { useRoute } from 'vue-router'
import { pessoaStore } from 'stores/pessoa'
import { guardaToken } from 'src/stores'
import { formataDocumetos } from 'src/stores/formataDocumentos'
import moment from 'moment';
import 'moment/min/locales';
moment.locale("pt-br")

export default defineComponent({
    name: "CardColaboradorCargo",

    methods: {

        async deletarColaboradorCargo(codcolaboradorcargo) {
            this.$q.dialog({
                title: 'Excluir Colaborador Cargo',
                message: 'Tem certeza que deseja excluir esse Cargo?',
                cancel: true,
            }).onOk(async () => {
                try {
                    const ret = await this.sPessoa.deletarColaboradorCargo(codcolaboradorcargo)
                    this.$q.notify({
                        color: 'green-5',
                        textColor: 'white',
                        icon: 'done',
                        message: 'Colaborador Cargo excluido!'
                    })
                    const getColaborador = await this.sPessoa.getColaborador(this.route.params.id)
                    this.colaboradores = getColaborador.data.data
                    this.emit(getColaborador.data.data)
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

        async editarColaboradorCargo(codcolaboradorcargo, codcolaborador, codcargo, codfilial, inicio, fim,
            comissaoloja, comissaovenda, comissaoxerox, gratificacao, observacoes) {
            this.dialogEditarColaboradorCargo = true

            this.modelnovoColaboradorCargo = {
                codcolaboradorcargo: codcolaboradorcargo, codcolaborador: codcolaborador, codcargo: codcargo,
                codfilial: codfilial, inicio: inicio !== null ? this.Documentos.formataDatasemHr(inicio) : null,
                fim: fim !== null ? this.Documentos.formataDatasemHr(fim) : null, comissaoloja: comissaoloja, comissaovenda: comissaovenda,
                comissaoxerox: comissaoxerox, gratificacao: gratificacao, observacoes: observacoes
            }

        },


        async salvarColaboradorCargo() {

            const colabCargo = { ...this.modelnovoColaboradorCargo };

            if (colabCargo.inicio) {
                colabCargo.inicio = this.Documentos.dataFormatoSql(colabCargo.inicio)
            }

            if (colabCargo.fim) {
                colabCargo.fim = this.Documentos.dataFormatoSql(colabCargo.fim)
            }

            try {
                const ret = await this.sPessoa.salvarColaboradorCargo(colabCargo)
                if (ret.data.data) {
                    this.$q.notify({
                        color: 'green-5',
                        textColor: 'white',
                        icon: 'done',
                        message: 'Colaborador Cargo Alterado!'
                    })
                    this.dialogEditarColaboradorCargo = false
                    // this.colaboradores[i] = ret.data.data
                    this.emit(ret.data.data)
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

        validaObrigatorio(value) {
            if (!value) {
                return "Preenchimento Obrigatório!";
            }
            return true;
        },

        validaDataValida(value) {
            if (!value) {
                return true;
            }
            const data = moment(value, 'DD/MM/YYYY');
            if (!data.isValid()) {
                return 'Data Inválida!';
            }
            return true;
        },

        validaInicio(value) {
            const inicio = moment(value, 'DD/MM/YYYY');
            const contratacao = moment(this.colaboradorCargos.contratacao);

            if (contratacao.isAfter(inicio)) {
                return 'Inicio não pode ser anterior á Contratação!';
            }


            return true;

        },

        validaFim(value) {

            const fim = moment(value, 'DD/MM/YYYY');
            const inicio = moment(this.modelnovoColaboradorCargo.inicio, 'DD/MM/YYYY');

            if (inicio.isAfter(fim)) {
                return 'Fim não pode ser anterior ao inicio!'
            }
            return true;

        },

    },

    components: {
        SelectFilial: defineAsyncComponent(() => import('components/pessoa/SelectFilial.vue')),
        SelectCargo: defineAsyncComponent(() => import('components/pessoa/SelectCargo.vue')),
    },

    props: ['colaboradorCargos'],
    emits: ['colaboradorCargo', 'colaboradorCargos'],

    setup(props, ctx) {

        const $q = useQuasar()
        const sPessoa = pessoaStore()
        const route = useRoute()
        const user = guardaToken()
        const Documentos = formataDocumetos()
        const colaboradores = ref([])
        const dialogEditarColaboradorCargo = ref(false)
        const modelnovoColaboradorCargo = ref({})

        const emit = async (ret) => {
            return ctx.emit('update:colaboradorCargos', ret)
        }

        return {
            sPessoa,
            Documentos,
            moment,
            emit,
            dialogEditarColaboradorCargo,
            modelnovoColaboradorCargo,
            route,
            user,
            colaboradores,
            brasil: {
                days: 'Domingo_Segunda_Terça_Quarta_Quinta_Sexta_Sábado'.split('_'),
                daysShort: 'Dom_Seg_Ter_Qua_Qui_Sex_Sáb'.split('_'),
                months: 'Janeiro_Fevereiro_Março_Abril_Maio_Junho_Julho_Agosto_Setembro_Outubro_Novembro_Dezembro'.split('_'),
                monthsShort: 'Jan_Fev_Mar_Abr_Mai_Jun_Jul_Ago_Set_Out_Nov_Dez'.split('_'),
                firstDayOfWeek: 1,
                format24h: true,
                pluralDay: 'dias'
            },
        }
    },
})
</script>
  
<style scoped></style>
  