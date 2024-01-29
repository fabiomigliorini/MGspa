<template v-if="user.verificaPermissaoUsuario('Recursos Humanos')">
    <q-card bordered>
        <q-list>
            <q-item-label header>
                Registro de Colaborador
                <q-btn flat round icon="add" @click="dialogNovoColaborador = true" />
            </q-item-label>
        </q-list>
    </q-card>

    <div v-for="colaborador in colaboradores" v-bind:key="colaborador.codcolaborador" class="q-pt-md">
        <q-card bordered v-if="user.verificaPermissaoUsuario('Recursos Humanos')">
            <q-list>
                <q-item>
                    <q-item-label header>
                        <span v-if="colaborador.vinculo == 2">Menor Aprendiz</span>
                            <span v-if="colaborador.vinculo == 90">Terceirizado</span>
                            <span v-if="colaborador.vinculo == 91">Diarista</span> no
                        {{colaborador.Filial}}
                        <q-btn flat round icon="edit" @click="editarColaborador(colaborador.codcolaborador, colaborador.codfilial,
                            colaborador.contratacao, colaborador.vinculo, colaborador.experiencia,
                            colaborador.renovacaoexperiencia, colaborador.rescisao, colaborador.numeroponto,
                            colaborador.numerocontabilidade, colaborador.observacoes)" />
                        <q-btn flat round icon="delete" @click="deletarColaborador(colaborador.codcolaborador)" />

                        Cargo
                        <q-btn flat round icon="add" @click="novoColaboradorCargo(colaborador.codcolaborador)" />

                        Férias
                        <q-btn flat round icon="add" @click="novoColaboradorFerias(colaborador.codcolaborador)" />

                    </q-item-label>
                </q-item>

                <q-item>
                    <q-item-section avatar>
                        <q-icon name="event" color="blue"></q-icon>
                    </q-item-section>
                    <q-item-section>
                        <q-item-label v-if="!colaborador.rescisao">
                           {{ moment(colaborador.contratacao).fromNow() }}
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
                <q-separator  inset/>

                <q-item>
                    <q-item-section avatar>
                        <q-icon name="event" color="blue"></q-icon>
                    </q-item-section>
                    <q-item-section>
                        <q-item-label>
                          {{ moment(colaborador.experiencia).format('DD/MMM/YYYY') }}  
                            / {{ moment(colaborador.renovacaoexperiencia).format('DD/MMM/YYYY') }}
                        </q-item-label>
                       <q-item-label caption>
                         Experiência / Renovação
                       </q-item-label>

                    </q-item-section>
                </q-item>

               
                <q-separator inset />

                <q-item v-if="colaborador.numeroponto || colaborador.numerocontabilidade">
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

                <!-- <q-item>
                    <q-item-section>

                        <q-item-section class="q-pa-sm">
                            <q-item-label>
                                <q-icon name="corporate_fare" color="blue"></q-icon>&nbsp;
                                {{ colaborador.Filial }} - <span v-if="colaborador.vinculo == 1">CLT</span>
                                <span v-if="colaborador.vinculo == 2">Menor Aprendiz</span>
                                <span v-if="colaborador.vinculo == 90">Terceirizado</span>
                                <span v-if="colaborador.vinculo == 91">Diarista</span> -
                                {{ Documentos.formataFromNow(colaborador.contratacao) }}
                            </q-item-label>

                            <q-item-label v-if="colaborador.numeroponto || colaborador.numerocontabilidade">
                                <q-icon v-if="colaborador.numeroponto" name="timer" color="blue"></q-icon>&nbsp;
                                <span v-if="colaborador.numeroponto">Ponto: {{ colaborador.numeroponto }}</span>
                                <span class="q-pl-md" v-if="colaborador.numerocontabilidade"> <q-icon name="receipt"
                                        color="blue" />&nbsp;Contabilidade: {{
                                            colaborador.numerocontabilidade }} </span>
                            </q-item-label>

                            <q-item-label v-if="colaborador.observacoes" style="white-space: pre-line">
                                <q-icon name="comment" color="blue"></q-icon>&nbsp;
                                {{ colaborador.observacoes }}
                            </q-item-label>
                        </q-item-section>
                        </q-card> -->
                <!-- </q-item-section> -->
                <!-- </q-item> -->

                <q-separator inset/>
                <q-item>
                    <q-item-section>
                        <card-colaborador-cargo :colaboradorCargos="colaborador.ColaboradorCargo"
                            v-on:update:colaboradorCargos="updateColaboradorCargo($event)" />
                    </q-item-section>
                </q-item>

                <q-item>
                    <q-item-section>
                        <card-ferias :feriasS="colaborador.Ferias" v-on:update:feriasS="updateFerias($event)"></card-ferias>
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
                                label="Contratação" :rules="[
                                    val => val && val.length > 0 || 'Contratação obrigatório'
                                ]" @change="preencheDataExp()">

                                <template v-slot:append>
                                    <q-icon name="event" class="cursor-pointer">
                                        <q-popup-proxy cover transition-show="scale" transition-hide="scale">
                                            <q-date v-model="modelNovoColaborador.contratacao" :locale="brasil"
                                                mask="DD/MM/YYYY" @update:model-value="preencheDataExp()">
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
                                label="Experiência" :rules="[
                                    val => val && val.length > 0 || 'Experiência obrigatório'
                                ]">

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
                                label="Renovação Experiência" :rules="[
                                    val => val && val.length > 0 || 'Renovação Experiência obrigatório'
                                ]" class="q-pr-md">

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
                            <q-input outlined v-model="modelNovoColaborador.rescisao" mask="##/##/####" label="Rescisão">

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
                                :rules="[
                                    val => val && val.length > 0 || 'Início obrigatório'
                                ]">

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
                                label="Fim">

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
                            <q-input outlined v-model="modelnovoColaboradorCargo.comissaoloja" label="Comissão Loja" />

                        </div>
                        <div class="col-6">
                            <q-input outlined v-model="modelnovoColaboradorCargo.comissaovenda" label="Comissão Venda"
                                class="q-pl-md" />
                        </div>
                        <div class="col-6">
                            <q-input outlined v-model="modelnovoColaboradorCargo.comissaoxerox" label="Comissão Xerox"
                                class="q-pt-md" />
                        </div>
                        <div class="col-6">
                            <q-input outlined v-model="modelnovoColaboradorCargo.gratificacao" label="Gratificação"
                                class="q-pl-md q-pt-md" />
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
            <q-form @submit="novoColaboradorFerias()">
                <q-card-section>
                    <div class="text-h6">Nova Férias</div>
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
                                val => val && val.length > 0 || 'Dias Gozo obrigatório'
                            ]" class="q-pr-md q-pt-md" />
                        </div>
                        <div class="col-6">
                            <q-input outlined v-model="modelnovoColaboradorFerias.diasabono" :rules="[
                                val => val && val.length > 0 || 'Dias Abono obrigatório'
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

    <!-- Dialog novo Cargo / Editar Cargo -->
    <q-dialog v-model="dialogNovoCargo">
        <q-card style="min-width: 350px">
            <q-form @submit="novoCargo()">
                <q-card-section>
                    <div class="text-h6">Novo Cargo</div>
                </q-card-section>
                <q-card-section>

                    <q-input outlined v-model="modelNovoCargo.cargo" label="Cargo" :rules="[
                        val => val && val.length > 0 || 'Cargo obrigatório'
                    ]" />

                    <q-input outlined v-model="modelNovoCargo.salario" label="Salario" />

                    <q-input outlined v-model="modelNovoCargo.adicional" class="q-pt-md" label="Adicional" />
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
    name: "CardColaborador",

    methods: {
        preencheDataExp() {

            var diasExperiencia = moment(this.modelNovoColaborador.contratacao, "DD/MM/YYYY").add(45, 'days').format('DD/MM/YYYY')
            var renovacaoExperiencia = moment(diasExperiencia, "DD/MM/YYYY").add(45, 'days').format('DD/MM/YYYY')

            this.modelNovoColaborador.experiencia = diasExperiencia
            this.modelNovoColaborador.renovacaoexperiencia = renovacaoExperiencia

        },

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

        async novoColaborador() {
            this.modelNovoColaborador.codpessoa = this.route.params.id

            if (this.modelNovoColaborador.contratacao) {
                this.modelNovoColaborador.contratacao = this.Documentos.dataFormatoSql(this.modelNovoColaborador.contratacao)
            }

            if (this.modelNovoColaborador.experiencia) {
                this.modelNovoColaborador.experiencia = this.Documentos.dataFormatoSql(this.modelNovoColaborador.experiencia)
            }
            if (this.modelNovoColaborador.renovacaoexperiencia) {
                this.modelNovoColaborador.renovacaoexperiencia = this.Documentos.dataFormatoSql(this.modelNovoColaborador.renovacaoexperiencia)
            }
            if (this.modelNovoColaborador.rescisao) {
                this.modelNovoColaborador.rescisao = this.Documentos.dataFormatoSql(this.modelNovoColaborador.rescisao)
            }

            try {
                const ret = await this.sPessoa.novoColaborador(this.modelNovoColaborador)
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

            if (this.modelNovoColaborador.contratacao) {
                this.modelNovoColaborador.contratacao = this.Documentos.dataFormatoSql(this.modelNovoColaborador.contratacao)
            }

            if (this.modelNovoColaborador.experiencia) {
                this.modelNovoColaborador.experiencia = this.Documentos.dataFormatoSql(this.modelNovoColaborador.experiencia)
            }
            if (this.modelNovoColaborador.renovacaoexperiencia) {
                this.modelNovoColaborador.renovacaoexperiencia = this.Documentos.dataFormatoSql(this.modelNovoColaborador.renovacaoexperiencia)
            }
            if (this.modelNovoColaborador.rescisao) {
                this.modelNovoColaborador.rescisao = this.Documentos.dataFormatoSql(this.modelNovoColaborador.rescisao)
            }

            try {
                const ret = await this.sPessoa.salvarColaborador(this.modelNovoColaborador)

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
            if (!this.modelnovoColaboradorCargo.codcolaborador) {
                this.modelnovoColaboradorCargo.codcolaborador = codcolaborador
            }

            if (this.modelnovoColaboradorCargo.inicio) {
                this.modelnovoColaboradorCargo.inicio = this.Documentos.dataFormatoSql(this.modelnovoColaboradorCargo.inicio)
            }

            if (this.modelnovoColaboradorCargo.fim) {
                this.modelnovoColaboradorCargo.fim = this.Documentos.dataFormatoSql(this.modelnovoColaboradorCargo.fim)
            }

            this.dialogNovoColaboradorCargo = true

            if (this.modelnovoColaboradorCargo.codcargo) {

                try {
                    const ret = await this.sPessoa.novoColaboradorCargo(this.modelnovoColaboradorCargo)
                    if (ret.data.data) {
                        this.$q.notify({
                            color: 'green-5',
                            textColor: 'white',
                            icon: 'done',
                            message: 'Colaborador Cargo criado!'
                        })
                        this.dialogNovoColaboradorCargo = false
                        const i = this.colaboradores.findIndex(item => item.codcolaborador === this.modelnovoColaboradorCargo.codcolaborador)
                        this.colaboradores[i].ColaboradorCargo.push(ret.data.data)
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
            if (!this.modelnovoColaboradorFerias.codcolaborador) {
                this.modelnovoColaboradorFerias.codcolaborador = codcolaborador
            }
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

            if (this.modelnovoColaboradorFerias.aquisitivoinicio) {

                try {
                    const ret = await this.sPessoa.novoColaboradorFerias(this.modelnovoColaboradorFerias)
                    if (ret.data.data) {
                        this.$q.notify({
                            color: 'green-5',
                            textColor: 'white',
                            icon: 'done',
                            message: 'Colaborador Férias criado!'
                        })
                        this.dialogNovoColaboradorFerias = false
                        const i = this.colaboradores.findIndex(item => item.codcolaborador === this.modelnovoColaboradorFerias.codcolaborador)
                        this.colaboradores[i].Ferias.push(ret.data.data)
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

        async novoCargo() {
            this.dialogNovoCargo = true
            if (this.modelNovoCargo.cargo) {
                try {
                    const ret = await this.sPessoa.novoCargo(this.modelNovoCargo)
                    if (ret.data.data) {
                        this.$q.notify({
                            color: 'green-5',
                            textColor: 'white',
                            icon: 'done',
                            message: 'Cargo criado!'
                        })
                        this.dialogNovoCargo = false
                        this.modelNovoCargo = {}
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
        }
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
        return {
            sPessoa,
            Documentos,
            route,
            user,
            colaboradores,
            editColaborador,
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
                firstDayOfWeek: 1,
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
  