<template>
  <!-- DIALOG EDITAR DETALHES -->
  <q-dialog v-model="DialogDetalhes">
    <q-card>
      <q-form @submit="salvarDetalhes()">
        <q-card-section>
          <q-input outlined v-model="modelEditarDetalhes.fantasia" label="Fantasia" class="" :rules="[
            val => val && val.length > 0 || 'Nome Fantasia é Obrigatório'
          ]" />

          <q-input outlined v-model="modelEditarDetalhes.pessoa" label="Razão Social" :rules="[
            val => val && val.length > 0 || 'Razão Social é Obrigatório'
          ]" />

          <select-grupo-economico v-model="modelEditarDetalhes.codgrupoeconomico" label="Grupo Econômico" class="q-mb-md"
            :permite-adicionar="true" />

          <div class="row">
            <q-toggle class="" outlined v-model="modelEditarDetalhes.fisica" label="Pessoa Física" />
          </div>

          <div class="row">
            <div class="col-6">
              <q-input outlined v-model="modelEditarDetalhes.cnpj" v-if="modelEditarDetalhes.fisica == false" label="Cnpj"
                mask="##.###.###/####-##" class="q-pr-md" unmasked-value disable/>
              <q-input outlined v-model="modelEditarDetalhes.cnpj" class="q-pr-md"
                v-if="modelEditarDetalhes.fisica == true" label="CPF" mask="###.###.###-##" unmasked-value disable/>
            </div>
            <div class="col-6">
              <input-ie v-model="modelEditarDetalhes.ie" disable>
              </input-ie>
            </div>
          </div>

          <div class="row">
            <div class="col-6">
              <q-input outlined v-model="modelEditarDetalhes.rg" v-if="modelEditarDetalhes.fisica == true" label="RG"
                class="q-pr-md q-mb-md" unmasked-value />
            </div>
            <div class="col-6">
              <q-input outlined v-model="modelEditarDetalhes.nascimento" mask="##-##-####" class="q-mb-md"
                label="Nascimento / Fundação">
                <template v-slot:append>
                  <q-icon name="event" class="cursor-pointer">
                    <q-popup-proxy cover transition-show="scale" transition-hide="scale">
                      <q-date v-model="modelEditarDetalhes.nascimento" :locale="brasil" mask="DD-MM-YYYY">
                        <div class="row items-center justify-end">
                          <q-btn v-close-popup label="Fechar" color="primary" flat />
                        </div>
                      </q-date>
                    </q-popup-proxy>
                  </q-icon>
                </template>
              </q-input>
            </div>
          </div>



          <div class="row">
            <div class="col-6">
              <q-select outlined v-model="modelEditarDetalhes.tipotransportador" class="q-pr-md"
                label="Tipo Transportador" :options="[
                  { label: 'Nenhum', value: 0 },
                  { label: 'ETC - Empresa', value: 1 },
                  { label: 'TAC - Autônomo', value: 2 },
                  { label: 'CTC - Cooperativa', value: 3 }]" map-options emit-value clearable />
            </div>
            <div class="col-6">
              <q-input outlined v-model="modelEditarDetalhes.rntrc" label="RNTRC" class="q-mb-md" mask="#########"
                unmasked-value />
            </div>
          </div>

          <q-input outlined borderless autogrow v-model="modelEditarDetalhes.observacoes" label="Observações"
            type="textarea" class="q-mb-md" />

          <q-toggle class="" outlined v-model="modelEditarDetalhes.cliente" label="Cliente" /> &nbsp;
          <q-toggle class="" outlined v-model="modelEditarDetalhes.fornecedor" label="Fornecedor" /> &nbsp;
          <q-toggle class="" outlined v-model="modelEditarDetalhes.vendedor" label="Vendedor" />
        </q-card-section>
        <q-card-actions align="right">
          <q-btn flat label="Cancelar" color="primary" v-close-popup />
          <q-btn flat label="Salvar" color="primary" type="submit" />
        </q-card-actions>
      </q-form>
    </q-card>
  </q-dialog>

  <q-card bordered>
    <q-list>
      <q-item>
        <q-item-section avatar>
          <q-avatar color="primary" class="q-my-md" size="70px" text-color="white" v-if="sPessoa.item.fantasia">
            {{ primeiraLetra(sPessoa.item.fantasia) }}
          </q-avatar>
        </q-item-section>
        <q-item-label header>
          <q-item-label>
            <span class="text-h4 text-weight-bold" :class="sPessoa.item.inativo ? 'text-strike text-red-14' : null">{{ sPessoa.item.fantasia }}</span>
            <q-btn flat round icon="edit" @click="editarDetalhes()" v-if="user.verificaPermissaoUsuario('Financeiro')" />
            <q-btn flat round icon="delete" @click="removerPessoa(sPessoa.item.codpessoa, sPessoa.item.pessoa)"
              v-if="user.verificaPermissaoUsuario('Financeiro')" />

            <q-btn v-if="user.verificaPermissaoUsuario('Financeiro') && !sPessoa.item.inativo" flat round icon="pause"
              @click="inativar(sPessoa.item.codpessoa)">
              <q-tooltip transition-show="scale" transition-hide="scale">
                Inativar
              </q-tooltip>
            </q-btn>

            <q-btn v-if="user.verificaPermissaoUsuario('Financeiro') && sPessoa.item.inativo" flat round icon="play_arrow"
              @click="ativar(sPessoa.item.codpessoa)">
              <q-tooltip transition-show="scale" transition-hide="scale">
                Ativar
              </q-tooltip>
            </q-btn>

          </q-item-label>
          <q-item-label v-if="sPessoa.item.inativo">
            Inativo 
            {{ Documentos.formataFromNow(sPessoa.item.inativo) }}
          </q-item-label>
          <q-item-label caption>
            {{ sPessoa.item.pessoa }}
          </q-item-label>
        </q-item-label>

      </q-item>
      <q-separator inset />

      <div class="row">
        <div class="col-xs-12 col-sm-6">
          <q-item>
            <q-item-section avatar top>
              <q-avatar icon="fingerprint" color="grey-2" text-color="blue" />
            </q-item-section>
            <q-item-section top>
              <q-item-label>
                {{ sPessoa.item.fisica == true ? Documentos.formataCPF(sPessoa.item.cnpj) :
                  Documentos.formataCNPJ(sPessoa.item.cnpj) }}
                <span v-if="sPessoa.item.ie">/ {{ Documentos.formataIePorSigla(sPessoa.item.ie) }}</span>
                <span v-if="sPessoa.item.rg">/ {{ sPessoa.item.rg }}</span>
              </q-item-label>
              <q-item-label caption>
                <span>Documentos</span>
              </q-item-label>
            </q-item-section>
          </q-item>
          <q-separator inset />

          <q-item v-if="sPessoa.item.nascimento">
            <q-item-section avatar top>
              <q-avatar icon="celebration" color="grey-2" text-color="blue" />
            </q-item-section>
            <q-item-section top>
              <q-item-label>
                  {{ Documentos.verificaIdade(sPessoa.item.nascimento) }} Anos de idade
              </q-item-label>
              <q-item-label caption class="text-grey-8">{{ Documentos.formataDataLonga(sPessoa.item.nascimento) }}</q-item-label>
            </q-item-section>
          </q-item>
          <q-separator inset />

          <q-item v-if="sPessoa.item.observacoes">
            <q-item-section avatar top>
              <q-avatar icon="notes" color="grey-2" text-color="blue" />
            </q-item-section>
            <q-item-section top>
              <q-item-label style="white-space: pre-line">
                {{ sPessoa.item.observacoes }}
                <q-tooltip>
                {{ sPessoa.item.observacoes }}
                </q-tooltip>
              </q-item-label>
              <q-item-label caption class="text-grey-8">Observações</q-item-label>
            </q-item-section>
          </q-item>

        </div>
        <div class="col-xs-12 col-sm-6">
          <template v-if="sPessoa.item.rntrc || sPessoa.item.tipotransportador">
            <q-item class="col-6">
              <q-item-section avatar top>
                <q-avatar icon="local_shipping" color="grey-2" text-color="blue" />
              </q-item-section>
              <q-item-section top>
                <q-item-label lines="2" v-if="sPessoa.item.rntrc">
                  <template v-if="sPessoa.item.tipotransportador">
                    <span v-if="sPessoa.item.tipotransportador == 1">ETC - Empresa</span>
                    <span v-if="sPessoa.item.tipotransportador == 2">TAC - Autônomo</span>
                    <span v-if="sPessoa.item.tipotransportador == 3">CTC - Cooperativa</span>
                    |
                  </template>
                  {{ sPessoa.item.rntrc }}
                </q-item-label>
                <q-item-label caption>
                  RNTRC
                </q-item-label>
              </q-item-section>
            </q-item>
            <q-separator inset />
          </template>

          <template v-if="sPessoa.item.codgrupoeconomico">
            <q-item class="col-6" :to="'/grupoeconomico/' + this.sPessoa.item.codgrupoeconomico"
              style="text-decoration: none;" v-ripple clickable>
              <q-item-section avatar top>
                <q-avatar icon="groups" color="grey-2" text-color="blue" />
              </q-item-section>
              <q-item-section top>
                <q-item-label lines="2">
                  {{ sPessoa.item.GrupoEconomico.grupoeconomico }}
                </q-item-label>
                <q-item-label caption>
                  Grupo Econômico
                </q-item-label>
              </q-item-section>
            </q-item>
          </template>
        </div>
      </div>
    </q-list>
  </q-card>
</template>

<script>
import { defineComponent, defineAsyncComponent } from 'vue'
import { useQuasar } from "quasar"
import { ref } from 'vue'
import { pessoaStore } from 'stores/pessoa'
import { useRoute } from 'vue-router'
import { formataDocumetos } from 'src/stores/formataDocumentos'
import { guardaToken } from 'stores/index'
import moment from 'moment'


export default defineComponent({
  name: "CardDetalhesPessoa",

  components: {
    SelectGrupoEconomico: defineAsyncComponent(() => import('components/pessoa/SelectGrupoEconomico.vue')),
    InputIe: defineAsyncComponent(() => import('components/pessoa/InputIe.vue')),
  },

  methods: {

    async inativar(codpessoa) {
      try {
        const ret = await this.sPessoa.inativarPessoa(codpessoa)
        if (ret.data) {
          this.sPessoa.item = ret.data.data
          this.$q.notify({
            color: 'green-5',
            textColor: 'white',
            icon: 'done',
            message: 'Inativado!'
          })
        }
      } catch (error) {
        this.$q.notify({
          color: 'green-5',
          textColor: 'white',
          icon: 'done',
          message: error.response.data
        })
      }

    },

    async ativar(codpessoa) {
      try {
        const ret = await this.sPessoa.ativarPessoa(codpessoa)
        if (ret.data) {
          this.sPessoa.item = ret.data.data
          this.$q.notify({
            color: 'green-5',
            textColor: 'white',
            icon: 'done',
            message: 'Ativado!'
          })
        }
      } catch (error) {
        this.$q.notify({
          color: 'green-5',
          textColor: 'white',
          icon: 'done',
          message: error.response.data
        })
      }

    },

    removerPessoa(codpessoa, pessoa) {

      this.$q.dialog({
        title: 'Excluir pessoa',
        message: 'Tem certeza que deseja excluir ' + pessoa + '?',
        cancel: true,
      }).onOk(async () => {
        try {
          const ret = await this.sPessoa.removePessoa(codpessoa)
          if (ret.data.result == true) {
            this.$q.notify({
              color: 'green-5',
              textColor: 'white',
              icon: 'done',
              message: 'Removido'
            })
            this.$router.push('/pessoa')
          }
        } catch (error) {
          this.$q.notify({
            color: 'red-5',
            textColor: 'white',
            icon: 'warning',
            message: error.response.data.message
          })
        }
      })
    },

    editarDetalhes() {
      this.DialogDetalhes = true
      this.modelEditarDetalhes = {
        cnpj: this.Documentos.formataCnpjCpf(this.sPessoa.item.cnpj, this.sPessoa.item.fisica),
        rntrc: this.sPessoa.item.rntrc,
        ie: this.sPessoa.item.ie,
        fantasia: this.sPessoa.item.fantasia,
        pessoa: this.sPessoa.item.pessoa,
        tipotransportador: this.sPessoa.item.tipotransportador,
        fisica: this.sPessoa.item.fisica,
        cliente: this.sPessoa.item.cliente,
        fornecedor: this.sPessoa.item.fornecedor,
        vendedor: this.sPessoa.item.vendedor,
        observacoes: this.sPessoa.item.observacoes,
        codgrupoeconomico: this.sPessoa.item.codgrupoeconomico,
        codcidade: this.sPessoa.item.PessoaEnderecoS.find(item => item.nfe === true)?.codcidade ?? null,
        rg: this.sPessoa.item.rg,
        nascimento: this.sPessoa.item.nascimento ? moment(this.sPessoa.item.nascimento).format('DD-MM-YYYY') : null
      }
    },


    async salvarDetalhes() {
      if (!this.sPessoa.item.PessoaEnderecoS.find(item => item.nfe === true) && this.modelEditarDetalhes.ie) {
        this.$q.notify({
          color: 'red-5',
          textColor: 'white',
          icon: 'error',
          message: 'Cadastre um endereço para verificar a Inscrição Estadual'
        })
        return
      }

      try {
        const ret = await this.sPessoa.clienteSalvar(this.sPessoa.item.codpessoa, this.modelEditarDetalhes)
        this.sPessoa.item = ret.data.data
        if (ret.data.data) {
          this.$q.notify({
            color: 'green-5',
            textColor: 'white',
            icon: 'done',
            message: 'Alterado'
          })
          this.DialogDetalhes = false
        }
      } catch (error) {
        if (error.response.data.errors.cnpj) {
          this.$q.notify({
            color: 'red-5',
            textColor: 'white',
            icon: 'error',
            message: error.response.data.errors.cnpj
          })
        } else if (error.response.data.errors.ie) {
          this.$q.notify({
            color: 'red-5',
            textColor: 'white',
            icon: 'error',
            message: error.response.data.errors.ie
          })
        } else {
          this.$q.notify({
            color: 'red-5',
            textColor: 'white',
            icon: 'error',
            message: error.message
          })
        }
      }
    },

    primeiraLetra(fantasia) {
      if (fantasia.charAt(0) == ' ') {
        return fantasia.charAt(1)
      }
      return fantasia.charAt(0)
    },
  },

  setup() {

    const $q = useQuasar()
    const sPessoa = pessoaStore()
    const route = useRoute()
    const GrupoEconomico = ref([])
    const Documentos = formataDocumetos()
    const user = guardaToken()

    return {
      formapagamento: ref({}),
      sPessoa,
      route,
      Documentos,
      user,
      DialogDetalhes: ref(false),
      modelEditarDetalhes: ref([]),
      GrupoEconomico,
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
