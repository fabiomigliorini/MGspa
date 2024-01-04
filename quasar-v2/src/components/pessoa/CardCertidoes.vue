<template>
    <q-card bordered>
        <q-list>
            <q-item-label header>
                Certidões
                <q-btn flat round icon="add" v-if="user.verificaPermissaoUsuario('Financeiro')" @click="dialogCertidao = true, modelCertidao = {}, editCertidao = false" />

                <q-radio v-model="filtroCertidaomodel" val="todas" label="Todas" @click="filtroCertidao()" />
                <q-radio v-model="filtroCertidaomodel" val="validas" label="Válidas" @click="filtroCertidao()" />
            </q-item-label>
            <div v-for="certidao in sPessoa.item.PessoaCertidaoS" v-bind:key="certidao.codpessoacertidao">
                <q-separator inset />
                <q-item>
                    <q-item-section avatar>
                        <q-avatar>
                            <q-icon name="card_membership" color="blue" />
                        </q-avatar>
                    </q-item-section>

                    <q-item-section>
                        <q-item-label lines="6">
                            <span class="row">{{ certidao.certidaotipo }}&nbsp;{{ certidao.certidaoemissor }}</span>

                            <span :class="certidao.inativo ? 'text-strike text-red-14' : null"
                                class="text-weight-bold row">{{ certidao.numero }}</span>
                            <span :class="certidao.inativo ? 'text-strike text-red-14' : null">{{ certidao.autenticacao
                            }}</span>
                        </q-item-label>
                    </q-item-section>
                    <q-item-section side top>
                        {{ Documentos.formataDatasemHr(certidao.validade) }}
                    </q-item-section>

                    <q-btn-dropdown flat auto-close dense v-if="user.verificaPermissaoUsuario('Financeiro')">
                        <q-btn flat round icon="edit" @click="editarCertidao(certidao.codpessoacertidao, certidao.codcertidaoemissor,
                            certidao.numero, certidao.autenticacao, certidao.validade, certidao.codcertidaotipo)" />
                        <q-btn flat round icon="delete" @click="deletarCertidao(certidao.codpessoacertidao)" />

                        <q-btn v-if="!certidao.inativo" flat round icon="pause"
                            @click="inativaCertidao(certidao.codpessoacertidao)">
                            <q-tooltip transition-show="scale" transition-hide="scale">
                                Inativar
                            </q-tooltip>
                        </q-btn>

                        <q-btn v-if="certidao.inativo" flat round icon="play_arrow"
                            @click="ativaCertidao(certidao.codpessoacertidao)">
                            <q-tooltip transition-show="scale" transition-hide="scale">
                                Ativar
                            </q-tooltip>
                        </q-btn>
                    </q-btn-dropdown>
                </q-item>
            </div>
            <q-separator inset="item" />
        </q-list>
    </q-card>

    <!-- Dialog Certidões -->
    <q-dialog v-model="dialogCertidao">
        <q-card style="min-width: 350px">
            <q-form @submit="editCertidao == false ? novaCertidao() : salvarCertidao()">
                <q-card-section>
                    <div v-if="editCertidao" class="text-h6">Editar Certidão</div>
                    <div v-else class="text-h6">Nova Certidão</div>
                </q-card-section>
                <q-card-section>
                    <q-input outlined v-model="modelCertidao.numero" mask="####################" autofocus label="Número"
                        :rules="[
                            val => val && val.length > 0 || 'Numero obrigatório'
                        ]" />

                    <q-input outlined v-model="modelCertidao.autenticacao" class="q-mb-md" label="Autenticação" />

                    <q-input outlined v-model="modelCertidao.validade" mask="##/##/####" label="Validade" :rules="[
                        val => val && val.length > 0 || 'Validade obrigatório'
                    ]">

                        <template v-slot:append>
                            <q-icon name="event" class="cursor-pointer">
                                <q-popup-proxy cover transition-show="scale" transition-hide="scale">
                                    <q-date v-model="modelCertidao.validade" :locale="brasil" mask="DD/MM/YYYY">
                                        <div class="row items-center justify-end">
                                            <q-btn v-close-popup label="Fechar" color="primary" flat />
                                        </div>
                                    </q-date>
                                </q-popup-proxy>
                            </q-icon>
                        </template>
                    </q-input>

                    <select-certidao-emissor v-model="modelCertidao.codcertidaoemissor">
                    </select-certidao-emissor>

                    <select-certidao-tipo v-model="modelCertidao.codcertidaotipo" class="q-mt-md"></select-certidao-tipo>

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

export default defineComponent({
    name: "CardCertidoes",

    methods: {

        filtroCertidao() {
            if (this.filtroCertidaomodel == 'validas') {
                let validas = this.sPessoa.item.PessoaCertidaoS.filter(x => x.validade >= this.Documentos.dataAtual())
                this.sPessoa.item.PessoaCertidaoS = validas
            }
            if (this.filtroCertidaomodel == 'todas') {
                this.sPessoa.get(this.route.params.id)
            }
        },

        async novaCertidao() {
            this.modelCertidao.codpessoa = this.route.params.id
            if (this.modelCertidao.validade) {
             this.modelCertidao.validade = this.Documentos.dataFormatoSql(this.modelCertidao.validade)
            }
            try {
                const ret = await this.sPessoa.novaCertidao(this.modelCertidao)
                if (ret.data.data) {
                    this.$q.notify({
                        color: 'green-5',
                        textColor: 'white',
                        icon: 'done',
                        message: 'Certidão criada!'
                    })
                    this.dialogCertidao = false
                    this.sPessoa.get(this.route.params.id)
                }
            } catch (error) {
                this.$q.notify({
                    color: 'red-5',
                    textColor: 'white',
                    icon: 'error',
                    message: error.response.data.errors.codcertidaoemissor ? 'O campo Emissor é obrigatório' : 'O campo Tipo é obrigatório'
                })
            }
        },

        editarCertidao(codpessoacertidao, codcertidaoemissor, numero, autenticacao, validade, codcertidaotipo) {
            this.editCertidao = true
            this.dialogCertidao = true
            this.modelCertidao = {
                codpessoacertidao: codpessoacertidao, codcertidaoemissor: codcertidaoemissor, numero: numero,
                autenticacao: autenticacao, validade: this.Documentos.formataDataInput(validade), codcertidaotipo: codcertidaotipo
            }
        },

        async salvarCertidao() {
            if (this.modelCertidao.validade) {
             this.modelCertidao.validade = this.Documentos.dataFormatoSql(this.modelCertidao.validade)
            }
            try {
                const ret = await this.sPessoa.salvarEdicaoCertidao(this.modelCertidao.codpessoacertidao, this.modelCertidao)
                if (ret.data.data) {
                    this.$q.notify({
                        color: 'green-5',
                        textColor: 'white',
                        icon: 'done',
                        message: 'Certidão alterada!'
                    })
                    this.editCertidao = false
                    this.dialogCertidao = false
                    console.log(this.sPessoa.item)
                    const i = this.sPessoa.item.PessoaCertidaoS.findIndex(item => item.codpessoacertidao === this.modelCertidao.codpessoacertidao)
                    this.sPessoa.item.PessoaCertidaoS[i] = ret.data.data
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

        async inativaCertidao(codpessoacertidao) {
            try {
                const ret = await this.sPessoa.inativarCertidao(codpessoacertidao)
                if (ret.data.data) {
                    this.$q.notify({
                        color: 'green-5',
                        textColor: 'white',
                        icon: 'done',
                        message: 'Inativado!'
                    })
                    const i = this.sPessoa.item.PessoaCertidaoS.findIndex(item => item.codpessoacertidao === codpessoacertidao)
                    this.sPessoa.item.PessoaCertidaoS[i] = ret.data.data
                }
            } catch (error) {
                this.$q.notify({
                    color: 'red-5',
                    textColor: 'white',
                    icon: 'error',
                    message: error.message
                })
            }
        },

        async ativaCertidao(codpessoacertidao) {
            try {
                const ret = await this.sPessoa.ativarCertidao(codpessoacertidao)
                if (ret.data.data) {
                    this.$q.notify({
                        color: 'green-5',
                        textColor: 'white',
                        icon: 'done',
                        message: 'Ativado!'
                    })
                    const i = this.sPessoa.item.PessoaCertidaoS.findIndex(item => item.codpessoacertidao === codpessoacertidao)
                    this.sPessoa.item.PessoaCertidaoS[i] = ret.data.data
                }
            } catch (error) {
                this.$q.notify({
                    color: 'red-5',
                    textColor: 'white',
                    icon: 'error',
                    message: error.message
                })
            }
        },

        async deletarCertidao(codpessoacertidao) {
            // deletarCertidao
            this.$q.dialog({
                title: 'Excluir Histórico',
                message: 'Tem certeza que deseja excluir essa certidão?',
                cancel: true,
            }).onOk(async () => {
                try {
                    await this.sPessoa.deletarCertidao(codpessoacertidao)
                    this.$q.notify({
                        color: 'green-5',
                        textColor: 'white',
                        icon: 'done',
                        message: 'Certidão excluida!'
                    })
                    this.sPessoa.get(this.route.params.id)
                } catch (error) {
                    this.$q.notify({
                        color: 'red-5',
                        textColor: 'white',
                        icon: 'error',
                        message: error.response.data.message
                    })
                }
            })
        }
    },

    components: {
        SelectCertidaoEmissor: defineAsyncComponent(() => import('components/pessoa/SelectCertidaoEmissor.vue')),
        SelectCertidaoTipo: defineAsyncComponent(() => import('components/pessoa/SelectCertidaoTipo.vue')),
    },


    setup() {

        const $q = useQuasar()
        const sPessoa = pessoaStore()
        const editCertidao = ref(false)
        //   const modelEditar = ref([])
        const loading = ref(true)
        const route = useRoute()
        const dialogCertidao = ref(false)
        const modelCertidao = ref({})
        const filtroCertidaomodel = ref('todas')

        const user = guardaToken()
        const Documentos = formataDocumetos()
        const HistoricosCobranca = ref([])
        const Paginas = ref({
            page: 1
        })

        return {
            sPessoa,
            HistoricosCobranca,
            Documentos,
            filtroCertidaomodel,
            route,
            Paginas,
            user,
            dialogCertidao,
            editCertidao,
            loading,
            modelCertidao,
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
  