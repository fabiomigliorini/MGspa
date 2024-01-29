<template v-if="user.verificaPermissaoUsuario('Recursos Humanos')">
    <div class="row q-col-gutter-md">
        <div v-for="ferias in feriasS" v-bind:key="ferias.codferias" class="col-xs-12 col-sm-6 col-md-4 col-lg-4 col-xl-3">
            <q-card bordered :class="ferias.prevista == true ? 'bg-orange' : null">
                <q-item>
                    <q-item-label header>
                        {{ Documentos.formataMes(ferias.gozoinicio) }}
                        <q-btn flat round icon="edit"
                            @click="editarFerias(ferias.codferias, ferias.codcolaborador,
                                ferias.aquisitivoinicio, ferias.aquisitivofim, ferias.gozoinicio, ferias.gozofim, ferias.diasgozo,
                                ferias.diasabono, ferias.diasdescontados, ferias.dias, ferias.observacoes, ferias.prevista)" />
                        <q-btn flat round icon="delete" @click="deletarFerias(ferias.codferias)" />
                    </q-item-label>
                </q-item>
                <q-separator inset />

                <q-item>
                    <q-item-section avatar>
                        <q-icon name="celebration" color="blue"></q-icon>
                    </q-item-section>
                    <q-item-section>
                        <q-item-label v-if="ferias.diasgozo">
                            {{ moment(ferias.gozoinicio).format('DD/MMM') }} a
                            {{ moment(ferias.gozofim).format('DD/MMM/YYYY') }}
                        </q-item-label>
                        <q-item-label caption>
                            {{ ferias.diasgozo }} Dias Gozo
                            <span v-if="ferias.diasabono">
                                / {{ ferias.diasabono }} de Abono
                            </span>
                            <span v-if="ferias.diasdescontados">
                                / {{ ferias.diasdescontados }} Descontados
                            </span>
                            <span v-if="ferias.dias != ferias.diasgozo">
                                = {{ ferias.dias }} Total
                            </span>
                        </q-item-label>
                    </q-item-section>
                </q-item>

                <q-separator inset />


                <q-item>
                    <q-item-section avatar>
                        <q-icon name="event" color="blue"></q-icon>
                    </q-item-section>
                    <q-item-section>
                        <q-item-label >
                            {{ moment(ferias.aquisitivoinicio).format('DD/MMM') }} a
                            {{ moment(ferias.aquisitivofim).format('DD/MMM/YYYY') }}
                        </q-item-label>
                        <q-item-label caption>
                            Período Aquisitivo
                        </q-item-label>
                    </q-item-section>
                </q-item>

                <q-separator inset />


                <q-item v-if="ferias.observacoes">
                    <q-item-section avatar>
                        <q-icon name="comment" color="blue"></q-icon>
                    </q-item-section>
                    <q-item-section>
                        <q-item-label >
                            {{ ferias.observacoes }}
                        </q-item-label>
                        <q-item-label caption>
                            Observações
                        </q-item-label>
                    </q-item-section>
                </q-item>
            </q-card>
        </div>
    </div>

    <!-- Dialog novo Colaborador Ferias -->
    <q-dialog v-model="dialogNovoColaboradorFerias">
        <q-card style="min-width: 350px">
            <q-form @submit="salvarColaboradorFerias()">
                <q-card-section>
                    <div class="text-h6">Editar Férias</div>
                </q-card-section>
                <q-card-section>

                    <div class="row">
                        <div class="col-6">
                            <q-input outlined v-model="modelnovoColaboradorFerias.aquisitivoinicio" mask="##/##/####"
                                label="Aquisitivo Início" :rules="[
                                    val => val && val.length > 0 || 'Aquisitivo Início obrigatório'
                                ]" class="q-pr-md">

                                <template v-slot:append>
                                    <q-icon name="event" class="cursor-pointer">
                                        <q-popup-proxy cover transition-show="scale" transition-hide="scale">
                                            <q-date v-model="modelnovoColaboradorFerias.aquisitivoinicio" :locale="brasil"
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
                            <q-input outlined v-model="modelnovoColaboradorFerias.aquisitivofim" mask="##/##/####"
                                label="Aquisitivo Fim" :rules="[
                                    val => val && val.length > 0 || 'Aquisitivo Fim obrigatório'
                                ]">

                                <template v-slot:append>
                                    <q-icon name="event" class="cursor-pointer">
                                        <q-popup-proxy cover transition-show="scale" transition-hide="scale">
                                            <q-date v-model="modelnovoColaboradorFerias.aquisitivofim" :locale="brasil"
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
                            <q-input outlined v-model="modelnovoColaboradorFerias.gozoinicio" mask="##/##/####"
                                label="Gozo Início" :rules="[
                                    val => val && val.length > 0 || 'Gozo Início obrigatório'
                                ]" class="q-pr-md">

                                <template v-slot:append>
                                    <q-icon name="event" class="cursor-pointer">
                                        <q-popup-proxy cover transition-show="scale" transition-hide="scale">
                                            <q-date v-model="modelnovoColaboradorFerias.gozoinicio" :locale="brasil"
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
                            <q-input outlined v-model="modelnovoColaboradorFerias.gozofim" mask="##/##/####"
                                label="Gozo Fim" :rules="[
                                    val => val && val.length > 0 || 'Gozo Fim obrigatório'
                                ]">

                                <template v-slot:append>
                                    <q-icon name="event" class="cursor-pointer">
                                        <q-popup-proxy cover transition-show="scale" transition-hide="scale">
                                            <q-date v-model="modelnovoColaboradorFerias.gozofim" :locale="brasil"
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

                        <div class="col-12">
                            <q-select outlined v-model="modelnovoColaboradorFerias.prevista" map-options emit-value
                                :options="[
                                    { label: 'Sim', value: true },
                                    { label: 'Não', value: false }
                                ]" label="Prevista" />
                        </div>


                        <div class="col-6">
                            <q-input outlined v-model="modelnovoColaboradorFerias.diasgozo" label="Dias Gozo" :rules="[
                                val => val !== null && val !== '' && val !== undefined || 'Dias Gozo Obrigatório'
                            ]" class="q-pr-md q-pt-md" />
                        </div>
                        <div class="col-6">
                            <q-input outlined v-model="modelnovoColaboradorFerias.diasabono" :rules="[
                                val => val !== null && val !== '' && val !== undefined || 'Dias Abono obrigatório'
                            ]" label="Dias Abono" class="q-pt-md" />
                        </div>
                        <div class="col-6">
                            <q-input outlined v-model="modelnovoColaboradorFerias.diasdescontados" label="Dias Descontados"
                                class="q-pr-md" />
                        </div>
                        <div class="col-6">
                            <q-input outlined v-model="modelnovoColaboradorFerias.dias" label="Dias" />

                        </div>
                    </div>

                    <q-input outlined autogrow bordeless v-model="modelnovoColaboradorFerias.observacoes"
                        label="Observações" type="textarea" class="q-pt-md" />

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
import moment from 'moment';
import 'moment/min/locales';
moment.locale("pt-br")


export default defineComponent({
    name: "CardFerias",

    methods: {

        async deletarFerias(codferias) {
            this.$q.dialog({
                title: 'Excluir Férias',
                message: 'Tem certeza que deseja excluir essa Férias?',
                cancel: true,
            }).onOk(async () => {
                try {
                    const ret = await this.sPessoa.deletarFerias(codferias)
                    this.$q.notify({
                        color: 'green-5',
                        textColor: 'white',
                        icon: 'done',
                        message: 'Férias excluida!'
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

        editarFerias(codferias, codcolaborador, aquisitivoinicio, aquisitivofim, gozoinicio, gozofim, diasgozo,
            diasabono, diasdescontados, dias, observacoes, prevista) {
            this.dialogNovoColaboradorFerias = true
            this.modelnovoColaboradorFerias = {
                codferias: codferias, codcolaborador: codcolaborador, aquisitivoinicio:
                    aquisitivoinicio !== null ? this.Documentos.formataDatasemHr(aquisitivoinicio) : null,
                aquisitivofim: aquisitivofim !== null ? this.Documentos.formataDatasemHr(aquisitivofim) : null,
                gozoinicio: gozoinicio !== null ? this.Documentos.formataDatasemHr(gozoinicio) : null,
                gozofim: gozofim !== null ? this.Documentos.formataDatasemHr(gozofim) : null, diasgozo: diasgozo,
                diasabono: diasabono, diasdescontados: diasdescontados, dias: dias, observacoes: observacoes,
                prevista: prevista
            }

        },

        async salvarColaboradorFerias() {

            if (this.modelnovoColaboradorFerias.aquisitivoinicio) {
                this.modelnovoColaboradorFerias.aquisitivoinicio = this.Documentos.dataFormatoSql(this.modelnovoColaboradorFerias.aquisitivoinicio)
            }

            if (this.modelnovoColaboradorFerias.aquisitivofim) {
                this.modelnovoColaboradorFerias.aquisitivofim = this.Documentos.dataFormatoSql(this.modelnovoColaboradorFerias.aquisitivofim)
            }

            if (this.modelnovoColaboradorFerias.gozoinicio) {
                this.modelnovoColaboradorFerias.gozoinicio = this.Documentos.dataFormatoSql(this.modelnovoColaboradorFerias.gozoinicio)
            }

            if (this.modelnovoColaboradorFerias.gozofim) {
                this.modelnovoColaboradorFerias.gozofim = this.Documentos.dataFormatoSql(this.modelnovoColaboradorFerias.gozofim)
            }

            try {
                const ret = await this.sPessoa.salvarColaboradorFerias(this.modelnovoColaboradorFerias)
                if (ret.data.data) {
                    this.$q.notify({
                        color: 'green-5',
                        textColor: 'white',
                        icon: 'done',
                        message: 'Férias Alterada!'
                    })
                    this.dialogNovoColaboradorFerias = false
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
        }
    },

    props: ['feriasS'],
    emits: ['feriasS'],

    setup(props, ctx) {

        const $q = useQuasar()
        const sPessoa = pessoaStore()
        const route = useRoute()
        const modelnovoColaboradorFerias = ref({})

        const dialogNovoColaboradorFerias = ref(false)
        const user = guardaToken()
        const Documentos = formataDocumetos()


        const emit = async (ret) => {
            return ctx.emit('update:feriasS', ret)
        }

        const colaboradores = ref([])

        return {
            moment,
            sPessoa,
            Documentos,
            route,
            emit,
            dialogNovoColaboradorFerias,
            user,
            colaboradores,
            modelnovoColaboradorFerias,
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
  