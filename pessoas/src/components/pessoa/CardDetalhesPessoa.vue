<template>
  <!-- DIALOG EDITAR DETALHES -->
  <q-dialog v-model="DialogDetalhes">
    <q-card style="width: 800px; max-width: 80vw;">
      <q-form @submit="salvarDetalhes()">
        <q-card-section class="row q-col-gutter-md">

          <!-- CNPJ OU CPF -->
          <q-input :class="modelPessoa.fisica ? 'col-md-3 col-sm-6 col-xs-12' : 'col-md-4 col-sm-6 col-xs-12'" outlined
            v-model="modelPessoa.cnpj" :label="modelPessoa.fisica ? 'CPF' : 'CNPJ'"
            :mask="modelPessoa.fisica ? '###.###.###-##' : '##.###.###/####-##'" unmasked-value disable />

          <!-- INSCRICAO ESTADUAL -->
          <input-ie :class="modelPessoa.fisica ? 'col-md-3 col-sm-6 col-xs-12' : 'col-md-4 col-sm-6 col-xs-12'"
            v-model="modelPessoa.ie" label="Inscrição Estadual" disable />

          <!-- NASCIMENTO -->
          <q-input :class="modelPessoa.fisica ? 'col-md-3 col-sm-6 col-xs-12' : 'col-md-4 col-sm-6 col-xs-12'" outlined
            v-model="modelPessoa.nascimento" mask="##/##/####" :label="modelPessoa.fisica ? 'Nascimento' : 'Fundação'">
            <template v-slot:append>
              <q-icon name="event" class="cursor-pointer">
                <q-popup-proxy cover transition-show="scale" transition-hide="scale">
                  <q-date v-model="modelPessoa.nascimento" :locale="brasil" mask="DD/MM/YYYY">
                    <div class="row items-center justify-end">
                      <q-btn v-close-popup label="Fechar" color="primary" flat />
                    </div>
                  </q-date>
                </q-popup-proxy>
              </q-icon>
            </template>
          </q-input>

          <!-- RG -->
          <q-input class="col-md-3 col-sm-6 col-xs-12" outlined v-model="modelPessoa.rg"
            v-if="modelPessoa.fisica == true" label="RG" unmasked-value />

          <!-- FANTASIA -->
          <input-filtered outlined v-model="modelPessoa.fantasia" label="Fantasia" :rules="[
            val => val && val.length > 0 || 'Nome Fantasia é Obrigatório'
          ]" autofocus class="col-md-4 col-sm-6 col-xs-12" />

          <!-- RAZAO SOCIAL -->
          <input-filtered outlined v-model="modelPessoa.pessoa" label="Razão Social" :rules="[
            val => val && val.length > 0 || 'Razão Social é Obrigatório'
          ]" class="col-md-4 col-sm-6 col-xs-12" />

          <!-- GRUPO ECONOMICO -->
          <select-grupo-economico class="col-md-4 col-sm-6 col-xs-12" v-model="modelPessoa.codgrupoeconomico"
            label="Grupo Econômico" :permite-adicionar="true" />

          <template v-if="modelPessoa.fisica">

            <!-- CIDADE NASCIMENTO -->
            <select-cidade class="col-md-4 col-sm-6 col-xs-12" v-model="modelPessoa.codcidadenascimento"
              :cidadeEditar="options" label="Nascido em" />

            <!-- PAI -->
            <input-filtered class="col-md-4 col-sm-6 col-xs-12" outlined v-model="modelPessoa.pai"
              label="Nome do Pai" />

            <!-- MAE -->
            <input-filtered class="col-md-4 col-sm-6 col-xs-12" outlined v-model="modelPessoa.mae"
              label="Nome da Mãe" />


            <!-- TITULO -->
            <q-input class="col-md-4 col-sm-6 col-xs-12" outlined v-model="modelPessoa.tituloeleitor"
              mask="####.####.####" label="Título de Eleitor" unmasked-value />

            <!-- ZONA -->
            <q-input class="col-md-2 col-sm-3 col-xs-6" outlined v-model="modelPessoa.titulozona" label="Zona"
              mask="###" unmasked-value />

            <!-- SECAO -->
            <q-input class="col-md-2 col-sm-3 col-xs-6" outlined v-model="modelPessoa.titulosecao" label="Seção"
              mask="####" unmasked-value />

            <!-- PIS -->
            <q-input class="col-md-4 col-sm-4 col-xs-12" outlined v-model="modelPessoa.pispasep" label="PIS/PASEP"
              mask="###.#####.##-#" unmasked-value />

            <!-- CARTEIRA TRABALHO -->
            <q-input class="col-md-4 col-sm-3 col-xs-12" outlined v-model="modelPessoa.ctps" label="CTPS"
              inputmode="numeric" mask="#######" unmasked-value />

            <!-- SERIE -->
            <q-input class="col-md-2 col-sm-2 col-xs-6" outlined v-model="modelPessoa.seriectps" label="Série"
              mask="####" inputmode="numeric" unmasked-value />

            <!-- UF -->
            <select-estado class="col-md-2 col-sm-3 col-xs-6" v-model="modelPessoa.codestadoctps"
              label="UF"></select-estado>

            <!-- EMISSAO -->
            <q-input class="col-md-4 col-sm-6 col-xs-12" outlined v-model="modelPessoa.emissaoctps" mask="##/##/####"
              label="Emissão CTPS">
              <template v-slot:append>
                <q-icon name="event" class="cursor-pointer">
                  <q-popup-proxy cover transition-show="scale" transition-hide="scale">
                    <q-date v-model="modelPessoa.emissaoctps" :locale="brasil" mask="DD/MM/YYYY">
                      <div class="row items-center justify-end">
                        <q-btn v-close-popup label="Fechar" color="primary" flat />
                      </div>
                    </q-date>
                  </q-popup-proxy>
                </q-icon>
              </template>
            </q-input>

          </template>

          <!-- RNTRC -->
          <q-input class="col-md-4 col-sm-6 col-xs-12" outlined v-model="modelPessoa.rntrc" label="RNTRC"
            mask="#########" unmasked-value />

          <!-- TIPO TRANSPORTADOR -->
          <q-select class="col-md-8 col-sm-12 col-xs-12" outlined v-model="modelPessoa.tipotransportador"
            label="Tipo Transportador" :options="[
              { label: 'Nenhum', value: 0 },
              { label: 'ETC - Empresa', value: 1 },
              { label: 'TAC - Autônomo', value: 2 },
              { label: 'CTC - Cooperativa', value: 3 }]" map-options emit-value clearable />

          <!-- OBSERVACOES -->
          <q-input outlined borderless autogrow v-model="modelPessoa.observacoes" label="Observações" type="textarea"
            class=" col-12" />

          <!-- TOGGLES -->
          <div class="col-12">
            <q-toggle class="" outlined v-model="modelPessoa.cliente" label="Cliente" /> &nbsp;
            <q-toggle class="" outlined v-model="modelPessoa.fornecedor" label="Fornecedor" /> &nbsp;
            <q-toggle class="" outlined v-model="modelPessoa.vendedor" label="Vendedor" />
          </div>

        </q-card-section>
        <q-card-actions align="right">
          <q-btn flat label="Cancelar" color="primary" v-close-popup />
          <q-btn flat label="Salvar" color="primary" type="submit" />
        </q-card-actions>
      </q-form>
    </q-card>
  </q-dialog>

  <!-- DIALOG MERCOS -->
  <q-dialog v-model="DialogMercos">
    <q-card style="width: 300px">
      <q-form @submit="salvarMercos">
        <q-card-section>
          <q-select outlined v-model="mercosTransferir.mercosid" label="Mercos ID"
            :rules="[val => val > 1 || 'Obrigatório']" :options="sPessoa.item.mercosId" />

          <select-pessoa autofocus outlined v-model="mercosTransferir.codpessoanova" label="Transferir para Pessoa"
            somente-ativos :rules="[val => val > 1 || 'Obrigatório']" />

        </q-card-section>
        <q-card-actions align="right">
          <q-btn flat label="salvar" color="primary" type="submit" />
          <q-btn flat label="fechar" color="primary" v-close-popup />
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
            <span class="text-h4 text-weight-bold" :class="sPessoa.item.inativo ? 'text-strike text-red-14' : null">{{
              sPessoa.item.fantasia }}</span>
            <q-btn flat round icon="edit" @click="editarDetalhes()" v-if="user.verificaPermissaoUsuario('Publico')" />
            <q-btn flat round icon="delete" @click="removerPessoa(sPessoa.item.codpessoa, sPessoa.item.pessoa)"
              v-if="user.verificaPermissaoUsuario('Publico')" />

            <q-btn v-if="user.verificaPermissaoUsuario('Publico') && !sPessoa.item.inativo" flat round icon="pause"
              @click="inativar(sPessoa.item.codpessoa)">
              <q-tooltip transition-show="scale" transition-hide="scale">
                Inativar
              </q-tooltip>
            </q-btn>

            <q-btn v-if="user.verificaPermissaoUsuario('Publico') && sPessoa.item.inativo" flat round icon="play_arrow"
              @click="ativar(sPessoa.item.codpessoa)">
              <q-tooltip transition-show="scale" transition-hide="scale">
                Ativar
              </q-tooltip>
            </q-btn>

            <q-btn flat icon="info">
              <q-tooltip transition-show="scale" transition-hide="scale">
                <q-item-label class="row">Criado por {{ sPessoa.item.usuariocriacao }} em {{
                  Documentos.formataData(sPessoa.item.criacao)
                }}</q-item-label>
                <q-item-label class="row">Alterado por {{ sPessoa.item.usuarioalteracao }} em {{
                  Documentos.formataData(sPessoa.item.alteracao) }}</q-item-label>
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
              <q-avatar icon="badge" color="grey-2" text-color="blue" />
            </q-item-section>
            <q-item-section top>
              <q-item-label>
                #{{ String(sPessoa.item.codpessoa).padStart(8, '0') }}
                <span v-if="sPessoa.item.mercosId.length > 0">
                  <template v-for="mid in sPessoa.item.mercosId" :key="mid">
                    /
                    <q-btn dense flat color="primary" :label="mid"
                      :href="'https://app.mercos.com/354041/clientes/' + mid" target="_blank" />
                  </template>
                  <q-btn dense flat icon="manage_accounts" color="primary" @click="abrirDialogMercos">
                    <q-tooltip class="bg-indigo" :offset="[10, 10]">
                      Transferir MercosID {{ sPessoa.item.mercosId }} para outro cadastro!
                    </q-tooltip>
                  </q-btn>
                </span>
              </q-item-label>
              <q-item-label caption>
                Pessoa
                <span v-if="sPessoa.item.mercosId.length > 0"> / Mercos Id</span>
              </q-item-label>
            </q-item-section>
          </q-item>
          <q-separator inset />

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
                {{ Documentos.verificaIdade(sPessoa.item.nascimento) }} anos de Idade
                <template v-if="sPessoa.item.codcidadenascimento">
                  , nascido em {{ sPessoa.item.cidadenascimento }} / {{ sPessoa.item.ufnascimento }}
                </template>
              </q-item-label>
              <q-item-label caption class="text-grey-8">
                {{ moment(sPessoa.item.nascimento).format("dddd, D [de] MMMM [de] YYYY") }}</q-item-label>
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
          <q-separator inset />

          <q-item v-if="sPessoa.item.ctps">
            <q-item-section avatar top>
              <q-avatar icon="feed" color="grey-2" text-color="blue" />
            </q-item-section>
            <q-item-section top>
              <q-item-label style="white-space: pre-line">
                {{ sPessoa.item.seriectps }} / {{ sPessoa.item.ufctpsS }} / {{ sPessoa.item.ctps }} / {{
                  Documentos.formataDatasemHr(sPessoa.item.emissaoctps) }}

              </q-item-label>
              <q-item-label caption>Série / Uf / Ctps / Emissão</q-item-label>
            </q-item-section>
          </q-item>
          <q-separator inset />

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
                <!-- <br>
                <q-item-label>{{ sPessoa.item.pai }}</q-item-label>
                <q-item-label caption>Nome do Pai</q-item-label> -->
              </q-item-section>
            </q-item>
            <q-separator inset />
          </template>


          <template v-if="sPessoa.item.pai || sPessoa.item.mae">
            <q-item class="col-6" style="text-decoration: none;">
              <q-item-section avatar top>
                <q-avatar icon="people" color="grey-2" text-color="blue" />
              </q-item-section>
              <q-item-section top>
                <q-item-label lines="2">
                  {{ sPessoa.item.pai }}

                  <template v-if="sPessoa.item.pai && sPessoa.item.mae">
                    e
                  </template>
                  {{ sPessoa.item.mae }}
                </q-item-label>
                <q-item-label caption>
                  Filiação
                </q-item-label>
              </q-item-section>
            </q-item>
            <q-separator inset />
          </template>

          <template v-if="sPessoa.item.pispasep">
            <q-item class="col-6" style="text-decoration: none;">
              <q-item-section avatar top>
                <q-avatar icon="123" color="grey-2" text-color="blue" />
              </q-item-section>
              <q-item-section top>
                <q-item-label lines="2">
                  {{ Documentos.formataPisPasep(sPessoa.item.pispasep) }}
                </q-item-label>
                <q-item-label caption>
                  PIS/PASEP
                </q-item-label>
              </q-item-section>
            </q-item>
            <q-separator inset />
          </template>


          <template v-if="sPessoa.item.tituloeleitor">
            <q-item class="col-6" style="text-decoration: none;">
              <q-item-section avatar top>
                <q-avatar icon="123" color="grey-2" text-color="blue" />
              </q-item-section>
              <q-item-section top>
                <q-item-label lines="2">
                  {{ Documentos.formataTitulo(sPessoa.item.tituloeleitor) }} / {{ sPessoa.item.titulozona }} / {{
                    sPessoa.item.titulosecao }}
                </q-item-label>
                <q-item-label caption>
                  Titulo Eleitor / Zona / Seção
                </q-item-label>
              </q-item-section>
            </q-item>
            <q-separator inset />
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
    SelectCidade: defineAsyncComponent(() => import('components/pessoa/SelectCidade.vue')),
    SelectEstado: defineAsyncComponent(() => import('components/pessoa/SelectEstado.vue')),
    InputIe: defineAsyncComponent(() => import('components/pessoa/InputIe.vue')),
    InputFiltered: defineAsyncComponent(() => import('components/InputFiltered.vue')),
    SelectPessoa: defineAsyncComponent(() => import('components/select/SelectPessoa.vue')),
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
          color: 'negative',
          textColor: 'white',
          icon: 'error',
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
          color: 'negative',
          textColor: 'white',
          icon: 'error',
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

    async editarDetalhes() {
      this.DialogDetalhes = true
      this.modelPessoa = {
        cnpj: (this.sPessoa.item.fisica) ? String(this.sPessoa.item.cnpj).padStart(11, '0') : String(this.sPessoa.item.cnpj).padStart(14, '0'),
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
        nascimento: this.sPessoa.item.nascimento ? moment(this.sPessoa.item.nascimento).format('DD-MM-YYYY') : null,
        pai: this.sPessoa.item.pai,
        mae: this.sPessoa.item.mae,
        codcidadenascimento: this.sPessoa.item.codcidadenascimento,
        pispasep: this.sPessoa.item.pispasep,
        tituloeleitor: this.sPessoa.item.tituloeleitor,
        titulozona: this.sPessoa.item.titulozona,
        titulosecao: this.sPessoa.item.titulosecao,
        ctps: this.sPessoa.item.ctps,
        seriectps: this.sPessoa.item.seriectps,
        emissaoctps: this.sPessoa.item.emissaoctps ? moment(this.sPessoa.item.emissaoctps).format('DD-MM-YYYY') : null,
        codestadoctps: this.sPessoa.item.codestadoctps
      }

      const ret = await this.sPessoa.consultaCidade(this.sPessoa.item.codcidadenascimento)
      this.options = [ret.data[0]]

    },

    async abrirDialogMercos() {
      this.mercosTransferir.codpessoanova = null;
      if (this.sPessoa.item.mercosId.length > 0) {
        this.mercosTransferir.mercosid = this.sPessoa.item.mercosId[0];
      } else {
        this.mercosTransferir.mercosid = null;
      }
      this.DialogMercos = true;
    },

    salvarMercos(evt) {
      if (evt) {
        evt.preventDefault();
      }
      this.$q.dialog({
        title: 'Confirma',
        message: 'Tem certeza que deseja confirmar a transferência do Mercos ID?',
        cancel: true,
      }).onOk(async () => {
        try {
          const ret = await this.sPessoa.transferirMercosId(this.sPessoa.item.codpessoa,
            this.mercosTransferir.mercosid,
            this.mercosTransferir.codpessoanova
          )
          this.sPessoa.item = ret.data.data
          this.$q.notify({
            color: 'green-5',
            textColor: 'white',
            icon: 'done',
            message: 'MercosID Transferido'
          });
          this.DialogMercos = false
        } catch (error) {
          console.log(error);
          this.$q.notify({
            color: 'red-5',
            textColor: 'white',
            icon: 'error',
            message: 'Falha ao transferir MercosID!'
          })
        }
      })

    },

    async salvarDetalhes() {

      const editar = { ...this.modelPessoa }

      if (editar.nascimento) {
        editar.nascimento = this.Documentos.dataFormatoSql(editar.nascimento)
      }

      if (editar.emissaoctps) {
        editar.emissaoctps = this.Documentos.dataFormatoSql(editar.emissaoctps)
      }

      try {
        const ret = await this.sPessoa.clienteSalvar(this.sPessoa.item.codpessoa, editar)
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
        if (error.response.data.errors && error.response.data.errors.cnpj) {
          this.$q.notify({
            color: 'red-5',
            textColor: 'white',
            icon: 'error',
            message: error.response.data.errors.cnpj
          })
        } else if (error.response.data.errors && error.response.data.errors.ie) {
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
            message: error.response.data.message
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
    const options = ref([])
    const mercosTransferir = ref({
      mercosid: null,
      codpessoanova: null,
    });

    return {
      formapagamento: ref({}),
      sPessoa,
      route,
      Documentos,
      user,
      options,
      moment,
      DialogMercos: ref(false),
      mercosTransferir,
      DialogDetalhes: ref(false),
      modelPessoa: ref([]),
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
