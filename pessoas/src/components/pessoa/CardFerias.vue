<template v-if="user.verificaPermissaoUsuario('Recursos Humanos')">
    <div class="row q-pl-md">
        <div v-for="ferias in feriasS.Ferias" v-bind:key="ferias.codferias"
            class="">
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
                        <q-item-label>
                            {{ moment(ferias.aquisitivoinicio).format('DD/MMM') }} a
                            {{ moment(ferias.aquisitivofim).format('DD/MMM/YYYY') }}
                        </q-item-label>
                        <q-item-label caption>
                            Período Aquisitivo
                        </q-item-label>
                    </q-item-section>
                </q-item>

                <template v-if="ferias.observacoes">
                    <q-separator inset />
                    <q-item>
                        <q-item-section avatar>
                            <q-icon name="comment" color="blue"></q-icon>
                        </q-item-section>
                        <q-item-section>
                            <q-item-label>
                                {{ ferias.observacoes }}
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

    <!-- Dialog editar Colaborador Ferias -->
    <q-dialog v-model="dialogNovoColaboradorFerias">
        <q-card style="min-width: 350px">
            <q-form @submit="salvarColaboradorFerias()">
                <q-card-section>
                    <div class="text-h6">Editar Férias</div>
                </q-card-section>
                <q-card-section>
                    <div class="row">
                        <div class="col-6">
                            <q-input outlined v-model="modelnovoColaboradorFerias.dias" label="Dias" class="q-pr-md"
                                @change="calculaDias()" />

                        </div>

                        <div class="col-6">
                            <q-input outlined v-model="modelnovoColaboradorFerias.diasabono" :rules="[
                                val => val !== null && val !== '' && val !== undefined || 'Dias Abono obrigatório'
                            ]" label="Dias Abono"  @change="calculaDias()" />
                        </div>
                        <div class="col-6">
                            <q-input outlined v-model="modelnovoColaboradorFerias.diasdescontados" class="q-pr-md" label="Dias Descontados"
                                @change="calculaDias()" />
                        </div>

                        <div class="col-6">
                            <q-input outlined v-model="modelnovoColaboradorFerias.diasgozo" label="Dias Gozo" :rules="[
                                val => val !== null && val !== '' && val !== undefined || 'Dias Gozo Obrigatório'
                            ]" @change="calculaDias()" />
                        </div>

                        <div class="col-6">
                            <q-input outlined v-model="modelnovoColaboradorFerias.aquisitivoinicio" mask="##/##/####"
                                label="Aquisitivo Início" :rules="[validaObrigatorio, validaDataValida, validaAqInicio]"
                                class="q-pr-md">

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
                                label="Aquisitivo Fim" :rules="[validaObrigatorio, validaDataValida, validaAqFim]">

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
                            <q-toggle outlined v-model="modelnovoColaboradorFerias.prevista" label="Prevista" />
                        </div>
                        <div class="col-12">
                            <q-date v-model="dateRange" :locale="brasil" range mask="DD/MM/YYYY" landscape>

                            </q-date>
                        </div>
                       
                    </div>

                    <q-input outlined autogrow bordeless v-model="modelnovoColaboradorFerias.observacoes"
                        label="Observações" class="q-pt-md" type="textarea" />

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
import { defineComponent, defineAsyncComponent, watch } from 'vue'
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

            this.dateRange = { from: this.Documentos.formataDatasemHr(gozoinicio), to: this.Documentos.formataDatasemHr(gozofim) }
        },

        async salvarColaboradorFerias() {

            const colabFerias = { ...this.modelnovoColaboradorFerias };

            if (colabFerias.aquisitivoinicio) {
                colabFerias.aquisitivoinicio = this.Documentos.dataFormatoSql(colabFerias.aquisitivoinicio)
            }

            if (colabFerias.aquisitivofim) {
                colabFerias.aquisitivofim = this.Documentos.dataFormatoSql(colabFerias.aquisitivofim)
            }

            if (this.dateRange) {
                colabFerias.gozoinicio = this.Documentos.dataFormatoSql(this.dateRange.from)
                colabFerias.gozofim = this.Documentos.dataFormatoSql(this.dateRange.to)
            }

            try {
                const ret = await this.sPessoa.salvarColaboradorFerias(colabFerias)
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

        validaAqInicio(value) {

            const AqInicio = moment(value, 'DD/MM/YYYY');
            const contratacao = moment(this.feriasS.contratacao);

            if (contratacao.isAfter(AqInicio)) {
                return 'Aquisitivo início não pode ser anterior a contratação!'
            }

            return true;
        },

        validaAqFim(value) {

            const aqFim = moment(value, 'DD/MM/YYYY');
            const rescisao = moment(this.feriasS.rescisao);
            const inicio = moment(this.modelnovoColaboradorFerias.aquisitivoinicio, 'DD/MM/YYYY')

            if (rescisao.isBefore(aqFim)) {
                return 'Aquisitivo fim tem que ser anterior a rescisão!'
            }

            if (inicio.isAfter(aqFim)) {
                return 'Aquisitivo fim tem que ser depois do inicio!'
            }

            return true;
        },

        validaGozoInicio(value) {

            const contratacao = moment(this.feriasS.contratacao);
            value = value.split('-')

            const gozoInicio = moment(value[0], 'DD/MM/YYYY')
            const gozoFim = moment(value[1], 'DD/MM/YYYY')

            var diasGozo = gozoFim.diff(gozoInicio, 'days')

            // this.modelnovoColaboradorFerias.diasgozo = diasGozo
            // this.modelnovoColaboradorFerias.diasabono = '0'
            // this.modelnovoColaboradorFerias.diasdescontados = '0'
            // this.modelnovoColaboradorFerias.dias = diasGozo

            const colaborador = this.colaboradores.find(colaborador => colaborador.codcolaborador === this.codcolaborador)
            if (contratacao.isAfter(gozoInicio)) {
                return 'Gozo Inicio não pode ser anterior a contratação!'
            }

            return true;
        },
        validaGozoFim(value) {

            const gozoFim = moment(value, 'DD/MM/YYYY');
            const colaborador = this.colaboradores.find(colaborador => colaborador.codcolaborador === this.codcolaborador)

            const rescisao = moment(this.feriasS.rescisao);
            const inicio = moment(this.modelnovoColaboradorFerias.gozoinicio, 'DD/MM/YYYY')

            if (rescisao.isBefore(gozoFim)) {
                return 'Gozo fim tem que ser anterior a rescisão!'
            }

            if (inicio.isAfter(gozoFim)) {
                return 'Gozo fim tem que ser depois do inicio!'
            }

            return true;
        },

        calculaDias() {

            if (!this.modelnovoColaboradorFerias.diasabono || !this.modelnovoColaboradorFerias.diasdescontados) {
                this.modelnovoColaboradorFerias.diasabono = '0'
                this.modelnovoColaboradorFerias.diasdescontados = '0'
            }


            var calculadias = (this.modelnovoColaboradorFerias.dias -
                this.modelnovoColaboradorFerias.diasabono - this.modelnovoColaboradorFerias.diasdescontados)

            this.modelnovoColaboradorFerias.diasgozo = calculadias

        },


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
        const dateRange = ref({ from: '', to: '' })


        const emit = async (ret) => {
            return ctx.emit('update:feriasS', ret)
        }


        const range = debounce(async () => {

            const gozoInicio = moment(dateRange.value.from, 'DD/MM/YYYY')
            const gozoFim = moment(dateRange.value.to, 'DD/MM/YYYY')

            var diasGozo = (gozoFim.diff(gozoInicio, 'days') +1)

            if (dateRange.value.from && dateRange.value.to && diasGozo !== modelnovoColaboradorFerias.value.dias) {
                modelnovoColaboradorFerias.value.dias = diasGozo
                modelnovoColaboradorFerias.value.diasgozo = diasGozo
            }
        }, 500)

        watch(
            () => dateRange.value,
            () => range(),
            { deep: true }
        );



        const colaboradores = ref([])

        return {
            moment,
            sPessoa,
            Documentos,
            route,
            emit,
            dateRange,
            dialogNovoColaboradorFerias,
            user,
            colaboradores,
            modelnovoColaboradorFerias,
            brasil: {
                days: 'Domingo_Segunda_Terça_Quarta_Quinta_Sexta_Sábado'.split('_'),
                daysShort: 'Dom_Seg_Ter_Qua_Qui_Sex_Sáb'.split('_'),
                months: 'Janeiro_Fevereiro_Março_Abril_Maio_Junho_Julho_Agosto_Setembro_Outubro_Novembro_Dezembro'.split('_'),
                monthsShort: 'Jan_Fev_Mar_Abr_Mai_Jun_Jul_Ago_Set_Out_Nov_Dez'.split('_'),
                firstDayOfWeek: 0,
                format24h: true,
                pluralDay: 'dias'
            },
        }
    },
})
</script>
  
<style scoped></style>
  