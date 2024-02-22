<template v-if="user.verificaPermissaoUsuario('Recursos Humanos')">
    <q-card bordered>
        <q-list>
            <q-item-label header>
                Registro de Colaborador
                <q-btn flat round icon="add"
                    @click="dialogNovoColaborador = true, modelNovoColaborador = {}, editColaborador = false" />
            </q-item-label>
        </q-list>
    </q-card>

    <div v-for="colaborador in colaboradores" v-bind:key="colaborador.codcolaborador">
        <q-card bordered v-if="user.verificaPermissaoUsuario('Recursos Humanos')">
            <q-list>
                <q-item>
                    <q-item-label header>
                        <span v-if="colaborador.vinculo == 1">CLT</span>
                        <span v-if="colaborador.vinculo == 2">Menor Aprendiz</span>
                        <span v-if="colaborador.vinculo == 90">Terceirizado</span>
                        <span v-if="colaborador.vinculo == 91">Diarista</span> no
                        {{ colaborador.Filial }}
                        <q-btn flat round icon="edit" @click="editarColaborador(colaborador.codcolaborador, colaborador.codfilial,
                            colaborador.contratacao, colaborador.vinculo, colaborador.experiencia,
                            colaborador.renovacaoexperiencia, colaborador.rescisao, colaborador.numeroponto,
                            colaborador.numerocontabilidade, colaborador.observacoes)" />
                        <q-btn flat round icon="delete" @click="deletarColaborador(colaborador.codcolaborador)" />

                        Cargo
                        <q-btn flat round icon="add"
                            @click="novoColaboradorCargo(colaborador.codcolaborador), modelnovoColaboradorCargo = {}" />

                        Férias
                        <q-btn flat round icon="add"
                            @click="novoColaboradorFerias(colaborador.codcolaborador), dateRange = {}" />

                    </q-item-label>
                </q-item>

                <q-item>
                    <q-item-section avatar>
                        <q-icon name="event" color="blue"></q-icon>
                    </q-item-section>
                    <q-item-section>
                        <q-item-label v-if="!colaborador.rescisao">
                            {{ moment(colaborador.contratacao).format('DD/MMM') }} ({{
                                moment(colaborador.contratacao).fromNow() }})
                        </q-item-label>
                        <q-item-label v-else>
                            {{ moment(colaborador.contratacao).format('DD/MMM') }} a
                            {{ moment(colaborador.rescisao).format('DD/MMM/YYYY') }}
                        </q-item-label>
                        <q-item-label caption>
                            Contratação / Rescisão
                        </q-item-label>
                    </q-item-section>
                </q-item>

                <q-separator inset />
                <q-item v-if="colaborador.experiencia">
                    <q-item-section avatar>
                        <q-icon name="event" color="blue"></q-icon>
                    </q-item-section>
                    <q-item-section>
                        <q-item-label>
                            {{ moment(colaborador.experiencia).format('DD/MMM/YYYY') }} ({{
                                moment(colaborador.experiencia).fromNow() }})
                            / {{ moment(colaborador.renovacaoexperiencia).format('DD/MMM/YYYY') }} ({{
                                moment(colaborador.renovacaoexperiencia).fromNow() }})
                        </q-item-label>
                        <q-item-label caption>
                            Experiência / Renovação
                        </q-item-label>

                    </q-item-section>
                </q-item>

                <template v-if="colaborador.numeroponto || colaborador.numerocontabilidade">
                    <q-separator inset />
                    <q-item>
                        <q-item-section avatar>
                            <q-icon name="timer" color="blue"></q-icon>
                        </q-item-section>
                        <q-item-section>
                            <q-item-label v-if="colaborador.numeroponto || colaborador.numerocontabilidade">
                                <span v-if="colaborador.numeroponto">{{ colaborador.numeroponto }}</span>
                                <span v-if="colaborador.numerocontabilidade"> / {{
                                    colaborador.numerocontabilidade }} </span>
                            </q-item-label>
                            <q-item-label caption v-if="colaborador.numeroponto || colaborador.numerocontabilidade">
                                Ponto / Contabilidade
                            </q-item-label>
                        </q-item-section>
                    </q-item>
                </template>

                <template v-if="colaborador.observacoes">
                    <q-separator inset />
                    <q-item>
                        <q-item-section avatar>
                            <q-icon name="comment" color="blue"></q-icon>
                        </q-item-section>
                        <q-item-section>
                            <q-item-label v-if="colaborador.observacoes" style="white-space: pre-line">
                                {{ colaborador.observacoes }}
                            </q-item-label>
                            <q-item-label caption v-if="colaborador.observacoes">
                                Observações
                            </q-item-label>
                        </q-item-section>
                    </q-item>
                </template>

                <q-item class="q-pl-md">
                    <q-item-section>
                        <card-colaborador-cargo :colaboradorCargos="colaborador"
                            v-on:update:colaboradorCargos="updateColaboradorCargo($event)" />
                    </q-item-section>
                </q-item>

                <q-item class="q-pl-md">
                    <q-item-section>
                        <card-ferias :feriasS="colaborador" v-on:update:feriasS="updateFerias($event)"></card-ferias>
                    </q-item-section>
                </q-item>
            </q-list>
        </q-card>
    </div>

    <!-- Dialog novo Colaborador -->
    <q-dialog v-model="dialogNovoColaborador">
        <q-card style="min-width: 350px">
            <q-form @submit="editColaborador == true ? salvarColaborador() : novoColaborador()">
                <q-card-section>
                    <div v-if="editColaborador" class="text-h6">Editar Colaborador</div>
                    <div v-else class="text-h6">Novo Colaborador</div>
                </q-card-section>
                <q-card-section>
                    <select-filial v-model="modelNovoColaborador.codfilial" :rules="[
                        val => val !== null && val !== '' && val !== undefined || 'Filial Obrigatório',
                    ]">

                    </select-filial>

                    <q-select outlined v-model="modelNovoColaborador.vinculo" label="Vinculo" :options="[
                        { label: 'CLT', value: 1 },
                        { label: 'Menor Aprendiz', value: 2 },
                        { label: 'Terceirizado', value: 90 },
                        { label: 'Diarista', value: 91 }
                    ]" map-options emit-value :rules="[
    val => val !== null && val !== '' && val !== undefined || 'Vinculo Obrigatório',
]" />

                    <div class="row">
                        <div class="col-6">
                            <q-input outlined v-model="modelNovoColaborador.contratacao" class="q-pr-md" mask="##/##/####"
                                label="Contratação" :rules="[validaObrigatorio, validaData, validaContratacao]"
                                @change="preencheExperiencia()">

                                <template v-slot:append>
                                    <q-icon name="event" class="cursor-pointer">
                                        <q-popup-proxy cover transition-show="scale" transition-hide="scale">
                                            <q-date v-model="modelNovoColaborador.contratacao" :locale="brasil"
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
                            <q-input outlined v-model="modelNovoColaborador.experiencia" mask="##/##/####"
                                label="Experiência" :rules="[validaData, validaExperiencia]">

                                <template v-slot:append>
                                    <q-icon name="event" class="cursor-pointer">
                                        <q-popup-proxy cover transition-show="scale" transition-hide="scale">
                                            <q-date v-model="modelNovoColaborador.experiencia" :locale="brasil"
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
                            <q-input outlined v-model="modelNovoColaborador.renovacaoexperiencia" mask="##/##/####"
                                label="Renovação Experiência" :rules="[validaData, validaRenovacaoExperiencia]"
                                class="q-pr-md">

                                <template v-slot:append>
                                    <q-icon name="event" class="cursor-pointer">
                                        <q-popup-proxy cover transition-show="scale" transition-hide="scale">
                                            <q-date v-model="modelNovoColaborador.renovacaoexperiencia" :locale="brasil"
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
                            <q-input outlined v-model="modelNovoColaborador.rescisao" mask="##/##/####" label="Rescisão"
                                :rules="[validaData, validaRescisao]">

                                <template v-slot:append>
                                    <q-icon name="event" class="cursor-pointer">
                                        <q-popup-proxy cover transition-show="scale" transition-hide="scale">
                                            <q-date v-model="modelNovoColaborador.rescisao" :locale="brasil"
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
                            <q-input outlined v-model="modelNovoColaborador.numeroponto" label="Número Ponto"
                                class="q-pr-md" />

                        </div>
                        <div class="col-6">

                            <q-input outlined v-model="modelNovoColaborador.numerocontabilidade"
                                label="Número Contabilidade" />
                        </div>
                    </div>


                    <q-input outlined autogrow bordeless v-model="modelNovoColaborador.observacoes" class="q-pt-md"
                        label="Observações" type="textarea" />

                </q-card-section>

                <q-card-actions align="right" class="text-primary">
                    <q-btn flat label="Cancelar" v-close-popup />
                    <q-btn flat label="Salvar" type="submit" />
                </q-card-actions>
            </q-form>
        </q-card>
    </q-dialog>

    <!-- Dialog novo Colaborador Cargo -->
    <q-dialog v-model="dialogNovoColaboradorCargo">
        <q-card style="min-width: 350px">
            <q-form @submit="novoColaboradorCargo()">
                <q-card-section>
                    <div class="text-h6">Novo Colaborador Cargo</div>
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
                                :rules="[validaData, validaInicio, validaObrigatorio]">

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
                                label="Fim" :rules="[validaData, validaFim]">

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

                    <q-input outlined v-model="modelnovoColaboradorCargo.observacoes" borderless autogrow class="q-pt-md"
                        label="Observações" type="textarea" />

                </q-card-section>

                <q-card-actions align="right" class="text-primary">
                    <q-btn flat label="Cancelar" v-close-popup />
                    <q-btn flat label="Salvar" type="submit" />
                </q-card-actions>
            </q-form>
        </q-card>
    </q-dialog>

    <!-- Dialog novo Colaborador Ferias -->
    <q-dialog v-model="dialogNovoColaboradorFerias">
        <q-card style="min-width: 350px">
            <q-form @submit="getApiNovaFerias()">
                <q-card-section>
                    <div class="text-h6">Nova Férias</div>
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
                            ]" label="Dias Abono" @change="calculaDias()" />
                        </div>
                        <div class="col-6">
                            <q-input outlined v-model="modelnovoColaboradorFerias.diasdescontados" class="q-pr-md"
                                label="Dias Descontados" @change="calculaDias()" />
                        </div>

                        <div class="col-6">
                            <q-input outlined v-model="modelnovoColaboradorFerias.diasgozo" label="Dias Gozo" :rules="[
                                val => val !== null && val !== '' && val !== undefined || 'Dias Gozo Obrigatório'
                            ]" @change="calculaDias()" />
                        </div>

                        <div class="col-6">
                            <q-input outlined v-model="modelnovoColaboradorFerias.aquisitivoinicio" mask="##/##/####"
                                label="Aquisitivo Início" :rules="[validaObrigatorio, validaData, validaAqInicio]"
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
                                label="Aquisitivo Fim" :rules="[validaObrigatorio, validaData, validaAqFim]">

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

                            <q-date v-model="dateRange" label="Periodo Gozo" :locale="brasil" range mask="DD/MM/YYYY"
                                landscape>

                            </q-date>

                            <!-- <q-input outlined
                                :model-value="`${dateRange.from ? dateRange.from : ''} - ${dateRange.to ? dateRange.to : ''}`"
                                label="Periodo de Gozo" :rules="[validaObrigatorio, validaData, validaGozoInicio]"
                                class="q-pr-md" mask="##/##/#### - ##/##/####">


                                <template v-slot:append>
                                    <q-icon name="event" class="cursor-pointer">
                                        <q-popup-proxy cover transition-show="scale" transition-hide="scale">
                                            <q-date v-model="dateRange" :locale="brasil" range mask="DD/MM/YYYY">
                                                <div class="row items-center justify-end">
                                                    <q-btn v-close-popup label="Fechar" color="primary" flat />
                                                </div>
                                            </q-date>
                                        </q-popup-proxy>
                                    </q-icon>
                                </template>
                            </q-input> -->
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
    name: "CardColaborador",

    methods: {

        updateColaboradorCargo(event) {

            if (!event[0]) {
                const i = this.colaboradores.findIndex(item => item.codcolaborador === event.codcolaborador)
                const l = this.colaboradores[i].ColaboradorCargo.findIndex(item => item.codcolaboradorcargo === event.codcolaboradorcargo)
                this.colaboradores[i].ColaboradorCargo[l] = event
            }

            if (event[0]) {
                this.colaboradores = event
            }
        },

        updateFerias(event) {
            if (!event[0]) {
                const i = this.colaboradores.findIndex(item => item.codcolaborador === event.codcolaborador)
                const l = this.colaboradores[i].Ferias.findIndex(item => item.codferias === event.codferias)
                this.colaboradores[i].Ferias[l] = event
            }

            if (event[0]) {
                this.colaboradores = event
            }
        },


        preencheExperiencia() {

            this.modelNovoColaborador.experiencia = moment(this.modelNovoColaborador.contratacao, 'DD/MM/YYYY').add(44, 'days').format('DD/MM/YYYY')
            this.modelNovoColaborador.renovacaoexperiencia = moment(this.modelNovoColaborador.experiencia, 'DD/MM/YYYY').add(44, 'days').format('DD/MM/YYYY')

        },

        async novoColaborador() {

            this.modelNovoColaborador.codpessoa = this.route.params.id

            const colab = { ...this.modelNovoColaborador };

            if (colab.contratacao) {
                colab.contratacao = this.Documentos.dataFormatoSql(colab.contratacao)
            }

            if (colab.experiencia) {
                colab.experiencia = this.Documentos.dataFormatoSql(colab.experiencia)
            }
            if (colab.renovacaoexperiencia) {
                colab.renovacaoexperiencia = this.Documentos.dataFormatoSql(colab.renovacaoexperiencia)
            }
            if (colab.rescisao) {
                colab.rescisao = this.Documentos.dataFormatoSql(colab.rescisao)
            }

            try {
                const ret = await this.sPessoa.novoColaborador(colab)
                if (ret.data.data) {
                    this.$q.notify({
                        color: 'green-5',
                        textColor: 'white',
                        icon: 'done',
                        message: 'Colaborador criado!'
                    })
                    this.dialogNovoColaborador = false
                    this.colaboradores.push(ret.data.data)
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

        async salvarColaborador() {

            const colab = { ...this.modelNovoColaborador };

            if (colab.contratacao) {
                colab.contratacao = this.Documentos.dataFormatoSql(colab.contratacao)
            }

            if (colab.experiencia) {
                colab.experiencia = this.Documentos.dataFormatoSql(colab.experiencia)
            }
            if (colab.renovacaoexperiencia) {
                colab.renovacaoexperiencia = this.Documentos.dataFormatoSql(colab.renovacaoexperiencia)
            }
            if (colab.rescisao) {
                colab.rescisao = this.Documentos.dataFormatoSql(colab.rescisao)
            }

            try {
                const ret = await this.sPessoa.salvarColaborador(colab)

                if (ret.data.data) {
                    this.$q.notify({
                        color: 'green-5',
                        textColor: 'white',
                        icon: 'done',
                        message: 'Colaborador Alterado!'
                    })
                    this.dialogNovoColaborador = false
                    const i = this.colaboradores.findIndex(item => item.codcolaborador === this.modelNovoColaborador.codcolaborador)
                    this.colaboradores[i] = ret.data.data
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

        async novoColaboradorCargo(codcolaborador) {


            if (!this.modelnovoColaboradorCargo.codcolaborador || this.modelnovoColaboradorCargo.codcolaborador == undefined) {
                this.modelnovoColaboradorCargo.codcolaborador = this.codcolaborador
            }

            const colabCargo = { ...this.modelnovoColaboradorCargo }

            if (colabCargo.inicio) {
                colabCargo.inicio = this.Documentos.dataFormatoSql(colabCargo.inicio)
            }

            if (colabCargo.fim) {
                colabCargo.fim = this.Documentos.dataFormatoSql(colabCargo.fim)
            }

            this.dialogNovoColaboradorCargo = true
            this.codcolaborador = codcolaborador


            if (this.modelnovoColaboradorCargo.codcargo) {

                try {
                    const ret = await this.sPessoa.novoColaboradorCargo(colabCargo)
                    if (ret.data.data) {
                        this.$q.notify({
                            color: 'green-5',
                            textColor: 'white',
                            icon: 'done',
                            message: 'Colaborador Cargo criado!'
                        })
                        this.dialogNovoColaboradorCargo = false
                        const i = this.colaboradores.findIndex(item => item.codcolaborador === this.modelnovoColaboradorCargo.codcolaborador)
                        this.colaboradores[i].ColaboradorCargo.unshift(ret.data.data)
                        this.modelnovoColaboradorCargo = {}
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

        async novoColaboradorFerias(codcolaborador) {
            this.dialogNovoColaboradorFerias = true

            if (codcolaborador !== undefined) {
                this.codcolaborador = codcolaborador
            }

            const colaborador = this.colaboradores.find(colaborador => colaborador.codcolaborador === this.codcolaborador)

            if (colaborador.Ferias[0] && colaborador.Ferias[1]) {

                if (colaborador.Ferias[0].dias + colaborador.Ferias[1].dias == 30) {

                    this.modelnovoColaboradorFerias.dias = 30
                    this.modelnovoColaboradorFerias.diasgozo = 30


                } else if (colaborador.Ferias[0].dias + colaborador.Ferias[1].dias < 30) {
                    var dias = (30 - colaborador.Ferias[0].dias)
                    if (dias > 0) {
                        this.modelnovoColaboradorFerias.dias = dias
                        this.modelnovoColaboradorFerias.diasgozo = dias
                    }
                }

                if (colaborador.Ferias[0].dias + colaborador.Ferias[1].dias > 30) {
                    var dias = (30 - colaborador.Ferias[0].dias)
                    if (dias > 0) {
                        this.modelnovoColaboradorFerias.dias = dias
                        this.modelnovoColaboradorFerias.diasgozo = dias
                    }
                }
            }

            if (colaborador.Ferias[0] && colaborador.Ferias[0].dias !== undefined && !colaborador.Ferias[1]) {

                if (colaborador.Ferias[0].dias == 30) {
                    this.modelnovoColaboradorFerias.dias = 30
                    this.modelnovoColaboradorFerias.diasgozo = 30

                } else if (colaborador.Ferias[0].dias < 30) {
                    var dias = (30 - colaborador.Ferias[0].dias)
                    if (dias > 0) {
                        this.modelnovoColaboradorFerias.dias = dias
                        this.modelnovoColaboradorFerias.diasgozo = dias
                    }
                }
            }

            if (colaborador.Ferias[0] && colaborador.Ferias[0].aquisitivofim !== undefined) {

                const sugestaoAqInicio = moment(colaborador.Ferias[0].aquisitivofim).add(1, 'days').format('DD/MM/YYYY')
                const sugestaoAqFim = moment(sugestaoAqInicio, 'DD/MM/YYYY').add(1, 'year').subtract(1, 'days').format('DD/MM/YYYY')
                this.modelnovoColaboradorFerias.aquisitivoinicio = sugestaoAqInicio
                this.modelnovoColaboradorFerias.aquisitivofim = sugestaoAqFim
            }
            if (this.modelnovoColaboradorFerias.dias == null && this.modelnovoColaboradorFerias.diasgozo == null) {
                this.modelnovoColaboradorFerias.dias = '30'
                this.modelnovoColaboradorFerias.diasgozo = '30'
            }


            this.modelnovoColaboradorFerias.diasabono = '0'
            this.modelnovoColaboradorFerias.diasdescontados = '0'

            if (!this.modelnovoColaboradorFerias.codcolaborador && this.codcolaborador !== undefined) {
                this.modelnovoColaboradorFerias.codcolaborador = this.codcolaborador
            }

        },

        async getApiNovaFerias() {

            const colabFerias = { ...this.modelnovoColaboradorFerias }

            if (colabFerias.aquisitivoinicio) {
                colabFerias.aquisitivoinicio = this.Documentos.dataFormatoSql(colabFerias.aquisitivoinicio)
            }

            if (this.dateRange) {
                colabFerias.gozoinicio = this.Documentos.dataFormatoSql(this.dateRange.from)
                colabFerias.gozofim = this.Documentos.dataFormatoSql(this.dateRange.to)
            }

            if (colabFerias.aquisitivofim) {
                colabFerias.aquisitivofim = this.Documentos.dataFormatoSql(colabFerias.aquisitivofim)
            }
            try {
                const ret = await this.sPessoa.novoColaboradorFerias(colabFerias)
                if (ret.data.data) {
                    this.$q.notify({
                        color: 'green-5',
                        textColor: 'white',
                        icon: 'done',
                        message: 'Colaborador Férias criado!'
                    })
                    this.dialogNovoColaboradorFerias = false
                    const i = this.colaboradores.findIndex(item => item.codcolaborador === this.modelnovoColaboradorFerias.codcolaborador)
                    this.colaboradores[i].Ferias.unshift(ret.data.data)
                    this.modelnovoColaboradorFerias = {}
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


        async deletarColaborador(codcolaborador) {
            this.$q.dialog({
                title: 'Excluir Colaborador',
                message: 'Tem certeza que deseja excluir esse colaborador?',
                cancel: true,
            }).onOk(async () => {
                try {
                    const ret = await this.sPessoa.deletarColaborador(codcolaborador)
                    this.$q.notify({
                        color: 'green-5',
                        textColor: 'white',
                        icon: 'done',
                        message: 'Colaborador excluido!'
                    })
                    const getColaborador = await this.sPessoa.getColaborador(this.route.params.id)
                    this.colaboradores = getColaborador.data.data
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



        editarColaborador(codcolaborador, codfilial, contratacao, vinculo, experiencia, renovacaoexperiencia,
            rescisao, numeroponto, numerocontabilidade, observacoes) {
            this.dialogNovoColaborador = true
            this.editColaborador = true

            this.modelNovoColaborador = {
                codcolaborador: codcolaborador, codfilial: codfilial,
                contratacao: contratacao !== null ? this.Documentos.formataDatasemHr(contratacao) : null, vinculo: vinculo,
                experiencia: experiencia !== null ? this.Documentos.formataDatasemHr(experiencia) : null,
                renovacaoexperiencia: renovacaoexperiencia !== null ? this.Documentos.formataDatasemHr(renovacaoexperiencia) : null,
                rescisao: rescisao !== null ? this.Documentos.formataDatasemHr(rescisao) : null,
                numeroponto: numeroponto, numerocontabilidade: numerocontabilidade, observacoes: observacoes
            }
        },

        validaObrigatorio(value) {
            if (!value) {
                return "Preenchimento Obrigatório!";
            }
            return true;
        },

        validaData(value) {
            if (!value) {
                return true;
            }
            const data = moment(value, 'DD/MM/YYYY');
            if (!data.isValid()) {
                return 'Data Inválida!';
            }
            return true;
        },

        validaContratacao(value) {
            const maximo = moment().add(7, 'days');
            const cont = moment(value, 'DD/MM/YYYY');
            if (maximo.isBefore(cont)) {
                return 'Data Muito no Futuro!';
            }


            return true;
        },

        validaExperiencia(value) {
            const minimo = moment(this.modelNovoColaborador.contratacao, 'DD/MM/YYYY');
            const maximo = moment(this.modelNovoColaborador.contratacao, 'DD/MM/YYYY').add(44, 'days');
            const exp = moment(value, 'DD/MM/YYYY');
            if (maximo.isBefore(exp)) {
                return 'Data Muito no Futuro!';
            }
            if (minimo.isAfter(exp)) {
                return 'Experiencia não pode ser anterior á Constratação!';
            }
            return true;
        },

        validaRenovacaoExperiencia(value) {
            const minimo = moment(this.modelNovoColaborador.experiencia, 'DD/MM/YYYY');
            const maximo = moment(this.modelNovoColaborador.experiencia, 'DD/MM/YYYY').add(44, 'days');
            const exp = moment(value, 'DD/MM/YYYY');
            if (maximo.isBefore(exp)) {
                return 'Data Muito no Futuro!';
            }
            if (minimo.isAfter(exp)) {
                return 'Renovação não pode ser anterior a experiência';
            }
            return true;
        },

        validaRescisao(value) {
            if (!value) {
                return true;
            }
            const res = moment(value, 'DD/MM/YYYY');
            const contratacao = moment(this.modelNovoColaborador.contratacao, 'DD/MM/YYYY');
            if (contratacao.isAfter(res)) {
                return 'Rescisão não pode ser anterior à Contratação!';
            }
            if (this.editColaborador == true) {
                const colaborador = this.colaboradores.find(colaborador => colaborador.codcolaborador === this.modelNovoColaborador.codcolaborador)
                let min = contratacao;
                colaborador.ColaboradorCargo.forEach((cc) => {
                    min = moment.max(min, moment(cc.inicio));
                    if (cc.fim) {
                        min = moment.max(min, moment(cc.fim));
                    }
                })
                if (min.isAfter(res)) {
                    return 'Existe cargo com data inicial/final posterior à Recisão!';
                }
            }
            return true;
        },

        validaInicio(value) {
            const inicio = moment(value, 'DD/MM/YYYY');
            const colaborador = this.colaboradores.find(colaborador => colaborador.codcolaborador === this.codcolaborador)
            const contratacao = moment(colaborador.contratacao);

            // const fimCargo = moment(colaborador.ColaboradorCargo)

            const fim = moment(this.modelnovoColaboradorCargo.fim, 'DD/MM/YYYY');

            if (contratacao.isAfter(inicio)) {
                return 'Inicio não pode ser anterior á Contratação!';
            }

            if (colaborador.ColaboradorCargo[0]) {
                const fimCargo = moment(colaborador.ColaboradorCargo[0].fim)
                if (fimCargo.isAfter(inicio)) {
                    return 'Início não pode ser anterior a data final do cargo!'
                }
                if (!colaborador.ColaboradorCargo[0].fim) {
                    return 'O ultimo cargo precisa de uma data final para criar um novo!'
                }
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

        validaAqInicio(value) {

            const AqInicio = moment(value, 'DD/MM/YYYY');
            const colaborador = this.colaboradores.find(colaborador => colaborador.codcolaborador === this.codcolaborador)
            const contratacao = moment(colaborador.contratacao);


            if (contratacao.isAfter(AqInicio)) {
                return 'Aquisitivo início não pode ser anterior a contratação!'
            }

            if (colaborador.Ferias[0] && colaborador.Ferias[0].aquisitivofim !== undefined && moment(colaborador.Ferias[0].aquisitivofim).isAfter(AqInicio)) {
                return 'Aquisitivo início não pode ser anterior ao aquisitivo fim da última férias'
            }


            if (!this.modelnovoColaboradorFerias.aquisitivofim) {
                const Aq1Ano = moment(AqInicio).add(1, 'year').subtract(1, 'days').format('DD/MM/YYYY')
                this.modelnovoColaboradorFerias.aquisitivofim = Aq1Ano
            }

            return true;
        },

        validaAqFim(value) {

            const aqFim = moment(value, 'DD/MM/YYYY');
            const colaborador = this.colaboradores.find(colaborador => colaborador.codcolaborador === this.codcolaborador)
            const rescisao = moment(colaborador.rescisao);

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

            value = value.split('-')

            const gozoInicio = moment(value[0], 'DD/MM/YYYY')
            const gozoFim = moment(value[1], 'DD/MM/YYYY')

            var diasGozo = gozoFim.diff(gozoInicio, 'days')

            this.modelnovoColaboradorFerias.diasgozo = diasGozo
            this.modelnovoColaboradorFerias.diasabono = '0'
            this.modelnovoColaboradorFerias.diasdescontados = '0'
            this.modelnovoColaboradorFerias.dias = diasGozo

            const colaborador = this.colaboradores.find(colaborador => colaborador.codcolaborador === this.codcolaborador)
            const contratacao = moment(colaborador.contratacao);
            if (contratacao.isAfter(gozoInicio)) {
                return 'Gozo Inicio não pode ser anterior a contratação!'
            }

            return true;
        },
        validaGozoFim(value) {

            const gozoFim = moment(value, 'DD/MM/YYYY');
            const colaborador = this.colaboradores.find(colaborador => colaborador.codcolaborador === this.codcolaborador)
            const rescisao = moment(colaborador.rescisao);
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

            const colaborador = this.colaboradores.find(colaborador => colaborador.codcolaborador === this.codcolaborador)

            if (!this.modelnovoColaboradorFerias.diasabono || !this.modelnovoColaboradorFerias.diasdescontados) {
                this.modelnovoColaboradorFerias.diasabono = '0'
                this.modelnovoColaboradorFerias.diasdescontados = '0'
            }

            var calculadias = (this.modelnovoColaboradorFerias.dias -
                this.modelnovoColaboradorFerias.diasabono - this.modelnovoColaboradorFerias.diasdescontados)

            this.modelnovoColaboradorFerias.diasgozo = calculadias

        },
    },

    components: {
        CardColaboradorCargo: defineAsyncComponent(() => import('components/pessoa/CardColaboradorCargo.vue')),
        CardFerias: defineAsyncComponent(() => import('components/pessoa/CardFerias.vue')),
        SelectFilial: defineAsyncComponent(() => import('components/pessoa/SelectFilial.vue')),
        SelectCargo: defineAsyncComponent(() => import('components/pessoa/SelectCargo.vue')),
    },


    setup() {

        const $q = useQuasar()
        const sPessoa = pessoaStore()
        const route = useRoute()
        const modelNovoColaborador = ref({})
        const modelnovoColaboradorCargo = ref({})
        const modelnovoColaboradorFerias = ref({})
        const modelNovoCargo = ref({})
        const user = guardaToken()
        const Documentos = formataDocumetos()
        const editColaborador = ref(false)
        const dialogNovoColaborador = ref(false)
        const dialogNovoColaboradorCargo = ref(false)
        const colaboradores = ref([])
        const dialogNovoColaboradorFerias = ref(false)
        const dialogNovoCargo = ref(false)
        const codcolaborador = ref('')
        const dateRange = ref({ from: '', to: '' })


        const range = debounce(async () => {


            const gozoInicio = moment(dateRange.value.from, 'DD/MM/YYYY')
            const gozoFim = moment(dateRange.value.to, 'DD/MM/YYYY')

            var diasGozo = (gozoFim.diff(gozoInicio, 'days') + 1)

            if (dateRange.value.from && dateRange.value.to) {
                modelnovoColaboradorFerias.value.dias = diasGozo
                modelnovoColaboradorFerias.value.diasgozo = diasGozo
            }

            // try {
            //     const ret = await sPessoa.buscarPessoas();
            //     loading.value = false;
            //     $q.loadingBar.stop()
            //     if (ret.data.data.length == 0) {
            //         return $q.notify({
            //             color: 'red-5',
            //             textColor: 'white',
            //             icon: 'warning',
            //             message: 'Nenhum Registro encontrado'
            //         })
            //     }
            // } catch (error) {
            //     $q.loadingBar.stop()
            // }
        }, 500)

        watch(
            () => dateRange.value,
            () => range(),
            { deep: true }
        );


        return {
            sPessoa,
            Documentos,
            route,
            user,
            dateRange,
            colaboradores,
            editColaborador,
            codcolaborador,
            modelnovoColaboradorCargo,
            dialogNovoColaboradorCargo,
            dialogNovoColaborador,
            dialogNovoColaboradorFerias,
            modelNovoCargo,
            dialogNovoCargo,
            modelnovoColaboradorFerias,
            moment,
            modelNovoColaborador,
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

    async mounted() {
        const ret = await this.sPessoa.getColaborador(this.route.params.id)
        this.colaboradores = ret.data.data

    }
})
</script>
  
<style scoped></style>
  