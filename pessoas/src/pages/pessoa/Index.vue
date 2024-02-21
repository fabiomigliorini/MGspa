
<template>
  <MGLayout drawer>

    <template #tituloPagina>
      Pessoas
    </template>

    <template #content>

      <q-infinite-scroll @load="scrollInfinito" :disable="loading">
        <div class="row q-pa-md q-col-gutter-md">
          <div class="col-md-4 col-sm-6 col-xs-12 col-lg-3 col-xl-2" v-for="listagempessoas in sPessoa.arrPessoas"
            v-bind:key="listagempessoas.codpessoa">

            <!-- CARD AQUI -->
            <card-pessoas :listagempessoas="listagempessoas"></card-pessoas>
          </div>
        </div>
      </q-infinite-scroll>

      <q-page-sticky position="bottom-right" :offset="[18, 18]" v-if="user.verificaPermissaoUsuario('Publico')">
        <q-fab icon="add" direction="up" color="accent">
          <q-fab-action :to="{ name: 'pessoanova' }" color="primary" icon="person_add"
            label="Nova" />
          <!-- <q-fab-action @click="dialogimportar = true" icon="import_contacts" color="primary" label="Importar" /> -->
        </q-fab>
      </q-page-sticky>

      <!--  ABRE A JANELA PARA IMPORTAÇÃO DE CADASTRO SEFAZ -->
      <q-dialog v-model="dialogimportar">
        <q-card>
          <q-form @submit="ImportarSefaz">
            <div class="q-pa-md">
              <q-card-section>
                <div class="text-h6">Importar Pessoa</div>
                <div class="text-caption">Importar cadastro da Receita Federal ou Sintegra</div>
              </q-card-section>
              <q-card-section>
                <div class="q-gutter-md">
                  <q-input label="CNPJ" mask="##.###.###/####-##" outlined v-model="importarsefazmodel.cnpj" autofocus
                    unmasked-value />
                  <q-input label="CPF" mask="###.###.###-##" outlined v-model="importarsefazmodel.cpf" unmasked-value />
                  <q-input label="IE" v-model="importarsefazmodel.ie" outlined />
                  <q-select label="UF" v-model="importarsefazmodel.uf" :options="estados" autofocus outlined />

                  <SelectFilial v-model="importarsefazmodel.codfilial"></SelectFilial>
                  <!-- <q-select label="Filial" v-model="importarsefazmodel.codfilial" autofocus outlined /> -->
                </div>
              </q-card-section>
              <q-card-actions align="right" class="text-primary">
                <q-btn flat label="Cancelar" v-close-popup />
                <q-btn flat label="Importar" type="submit" />
              </q-card-actions>
            </div>
          </q-form>
        </q-card>
      </q-dialog>

      <!-- Dialog cadastro nova pessoa -->
      <q-dialog v-model="dialogNovaPessoa">
        <q-card>
          <q-form @submit="novaPessoa()">
            <q-card-section>
              <div class="row">

                <div class="col-12">
                  <q-input outlined v-model="novaPessoaModel.fantasia" label="Fantasia" :rules="[
                    val => val && val.length > 0 || 'Nome Fantasia é Obrigatório'
                  ]" autofocus />
                </div>

                <div class="col-12">
                  <q-input outlined v-model="novaPessoaModel.pessoa" label="Razão Social" :rules="[
                    val => val && val.length > 0 || 'Razão Social é Obrigatório'
                  ]" />
                </div>

                <div class="col-12">
                  <q-toggle outlined v-model="novaPessoaModel.fisica" label="Pessoa Física" />

                </div>


                <div class="col-6 q-pr-md">
                  <q-input outlined v-model="novaPessoaModel.cnpj" @change="buscaCnpj()" label="Cnpj"
                    v-if="novaPessoaModel.fisica == false" mask="##.###.###/####-##" unmasked-value required />
                  <q-input outlined v-model="novaPessoaModel.cnpj" @change="buscaCpf()"
                    v-if="novaPessoaModel.fisica == true" label="CPF" mask="###.###.###-##" unmasked-value required />
                </div>
                <div class="col-3 q-pr-md">
                  <q-input label="Insc Estadual" v-model="novaPessoaModel.ie" outlined unmasked-value />
                </div>
                <div class="col-3">
                  <q-select label="UF" v-model="novaPessoaModel.uf" :options="estados" option-label="value"
                    option-value="value" emit-value outlined map-options />
                </div>
                <div class="col-6 q-pt-md q-pr-md">
                  <q-input outlined v-model="novaPessoaModel.rg" v-if="novaPessoaModel.fisica == true" label="RG"
                    unmasked-value />
                </div>


                <div class="col-6 q-pt-md">
                  <q-input outlined v-model="novaPessoaModel.nascimento" mask="##/##/####" label="Nascimento / Fundação">
                    <template v-slot:append>
                      <q-icon name="event" class="cursor-pointer">
                        <q-popup-proxy cover transition-show="scale" transition-hide="scale">
                          <q-date v-model="novaPessoaModel.nascimento" :locale="brasil" mask="DD/MM/YYYY">
                            <div class="row items-center justify-end">
                              <q-btn v-close-popup label="Fechar" color="primary" flat />
                            </div>
                          </q-date>
                        </q-popup-proxy>
                      </q-icon>
                    </template>
                  </q-input>
                </div>

                <div class="col-6 q-pt-md q-pr-md">
                  <select-cidade v-model="novaPessoaModel.codcidadenascimento" label="Cidade Nascimento">

                  </select-cidade>
                </div>

                <div class="col-6 q-pt-md">
                  <q-select outlined v-model="novaPessoaModel.tipotransportador" label="Tipo Transportador" :options="[
                    { label: 'Nenhum', value: 0 },
                    { label: 'ETC - Empresa', value: 1 },
                    { label: 'TAC - Autônomo', value: 2 },
                    { label: 'CTC - Cooperativa', value: 3 }]" map-options emit-value clearable />
                </div>

                <div class="col-6 q-pt-md q-pr-md">
                  <q-select outlined v-model="novaPessoaModel.notafiscal" label="Nota Fiscal" :options="[
                    { label: 'Tratamento Padrão', value: 0 },
                    { label: 'Sempre', value: 1 },
                    { label: 'Somente Fechamento', value: 2 },
                    { label: 'Nunca', value: 9 }]" map-options emit-value clearable :rules="[
                      val => val >= 0 && val != null || 'Nota Fiscal Obrigátorio'
                    ]" />
                </div>

                <div class="col-6 q-pt-md">
                  <q-input outlined v-model="novaPessoaModel.rntrc" label="RNTRC" mask="#########" unmasked-value />
                </div>

                <div class="col-6 q-pr-md">
                  <q-input outlined v-model="novaPessoaModel.pai" label="Nome do Pai" />
                </div>
                <div class="col-6">
                  <q-input outlined v-model="novaPessoaModel.mae" label="Nome da Mãe" />
                </div>


                <div class="col-6 q-pt-md q-pr-md">
                  <q-input outlined v-model="novaPessoaModel.tituloeleitor" mask="####.####.####" label="Titulo Eleitor"
                    unmasked-value />
                </div>
                <div class="col-3 q-pt-md q-pr-md">
                  <q-input outlined v-model="novaPessoaModel.titulozona" label="Titulo Zona" mask="###" unmasked-value />
                </div>
                <div class="col-3 q-pt-md">
                  <q-input outlined v-model="novaPessoaModel.titulosecao" label="Titulo Seção" mask="####"
                    unmasked-value />
                </div>

                <div class="col-3 q-pt-md q-pr-md">
                  <q-input outlined v-model="novaPessoaModel.ctps" label="CTPS" inputmode="numeric" mask="#######"
                    unmasked-value />
                </div>

                <div class="col-2 q-pt-md q-pr-md">
                  <q-input outlined v-model="novaPessoaModel.seriectps" label="Série" mask="####" inputmode="numeric"
                    unmasked-value />
                </div>

                <div class="col-3 q-pt-md q-pr-md">
                  <select-estado v-model="novaPessoaModel.codestadoctps" label="UF"></select-estado>

                </div>

                <div class="col-4 q-pt-md">
                  <q-input outlined v-model="novaPessoaModel.emissaoctps" mask="##/##/####" label="Emissão CTPS">
                    <template v-slot:append>
                      <q-icon name="event" class="cursor-pointer">
                        <q-popup-proxy cover transition-show="scale" transition-hide="scale">
                          <q-date v-model="novaPessoaModel.emissaoctps" :locale="brasil" mask="DD/MM/YYYY">
                            <div class="row items-center justify-end">
                              <q-btn v-close-popup label="Fechar" color="primary" flat />
                            </div>
                          </q-date>
                        </q-popup-proxy>
                      </q-icon>
                    </template>
                  </q-input>
                </div>


                <div class="col-4 q-pt-md">
                  <q-input outlined v-model="novaPessoaModel.pispasep" label="PIS/PASEP" mask="###.#####.##-#"
                    unmasked-value />
                </div>

                <div class="col-12">
                  <q-input outlined borderless autogrow v-model="novaPessoaModel.observacoes" label="Observações"
                    type="textarea" class="q-mb-md q-pt-md" />
                </div>


                <q-toggle outlined v-model="novaPessoaModel.cliente" label="Cliente" />
                <q-toggle class="" outlined v-model="novaPessoaModel.fornecedor" label="Fornecedor" /> &nbsp;
                <q-toggle class="" outlined v-model="novaPessoaModel.vendedor" label="Vendedor" />

              </div>
            </q-card-section>
            <div class="col-6 q-pt-none">
              <q-card-actions align="right" class="text-primary">
                <q-btn flat label="Cancelar" v-close-popup />
                <q-btn flat label="Salvar" type="submit" />
              </q-card-actions>
            </div>
          </q-form>
        </q-card>
      </q-dialog>
    </template>

    <!-- Menu Drawer personalizado filtro -->
    <template #drawer>
      <div class="q-pa-none q-pt-sm">
        <q-card flat>
          <q-list>
            <q-item-label header>
              Filtro Pessoa
              <q-btn icon="replay" @click="buscarPessoas()" flat round no-caps />
            </q-item-label>
          </q-list>
        </q-card>
        <q-form @change="buscarPessoas()">
          <div class="q-pa-md q-gutter-md">
            <q-input outlined v-model="sPessoa.filtroPesquisa.codpessoa" label="#" ref="codpessoa" type="number" />
            <q-input outlined v-model="sPessoa.filtroPesquisa.pessoa" ref="pessoa" label="Pessoa" autofocus
              unmasked-value />
            <q-input outlined v-model="sPessoa.filtroPesquisa.cnpj" ref="cnpj" label="Cnpj/Cpf" unmasked-value />
            <q-input outlined v-model="sPessoa.filtroPesquisa.email" ref="email" label="Email" />
            <q-input outlined v-model="sPessoa.filtroPesquisa.fone" ref="fone" label="Fone" type="number" />
            <SelectGrupoEconomico v-model="sPessoa.filtroPesquisa.codgrupoeconomico" label="Grupo Econômico">
            </SelectGrupoEconomico>
            <SelectCidade v-model="sPessoa.filtroPesquisa.codcidade" label="Cidade">
            </SelectCidade>
            <q-select outlined v-model="sPessoa.filtroPesquisa.inativo" label="Ativo / Inativo" :options="[
              { label: 'Ativos', value: 'A' },
              { label: 'Inativos', value: 'I' }]" map-options emit-value clearable />

            <SelectFormaPagamento v-model="sPessoa.filtroPesquisa.codformapagamento" label="Forma Pagamento">
            </SelectFormaPagamento>
            <SelectGrupoCliente v-model="sPessoa.filtroPesquisa.codgrupocliente" label="Grupo Cliente">
            </SelectGrupoCliente>
          </div>
        </q-form>
      </div>
    </template>
  </MGLayout>
</template>

<script>
import { ref, onMounted, defineAsyncComponent, watch, computed } from 'vue'
import { api } from 'boot/axios'
import { useQuasar } from 'quasar'
import { formataDocumetos } from 'src/stores/formataDocumentos'
import { useRouter } from 'vue-router'
import { guardaToken } from 'src/stores'
import { pessoaStore } from 'src/stores/pessoa'
import { debounce } from 'quasar'


export default {
  components: {
    MGLayout: defineAsyncComponent(() => import('layouts/MGLayout.vue')),
    SelectGrupoEconomico: defineAsyncComponent(() => import('components/pessoa/SelectGrupoEconomico.vue')),
    SelectCidade: defineAsyncComponent(() => import('components/pessoa/SelectCidade.vue')),
    SelectFormaPagamento: defineAsyncComponent(() => import('components/pessoa/SelectFormaPagamento.vue')),
    SelectGrupoCliente: defineAsyncComponent(() => import('components/pessoa/SelectGrupoCliente.vue')),
    SelectFilial: defineAsyncComponent(() => import('components/pessoa/SelectFilial.vue')),
    CardPessoas: defineAsyncComponent(() => import('components/pessoa/CardPessoas.vue')),
    SelectEstado: defineAsyncComponent(() => import('components/pessoa/SelectEstado.vue')),

  },

  methods: {

    async buscaCnpj() {
      var removeZero = this.novaPessoaModel.cnpj.replace(/^0+/, '')


      if (this.novaPessoaModel.cnpj !== '') {
        try {
          const ret = await this.sPessoa.buscaCpfCnpjCadastro({ cnpj: removeZero })
          if (ret.data[0]) {
            this.novaPessoaModel.fantasia = ret.data[0].nome
            this.novaPessoaModel.pessoa = ret.data[0].nome
          }
        } catch (error) {
          this.$q.notify({
            color: 'red-5',
            textColor: 'white',
            icon: 'warning',
            message: error.response.data
          })
        }

      }

    },

    async buscaCpf() {


      var removeZero = this.novaPessoaModel.cnpj.replace(/^0+/, '')

      console.log(this.novaPessoaModel.cnpj)
      if (this.novaPessoaModel.cnpj !== '') {
        try {
          const ret = await this.sPessoa.buscaCpfCnpjCadastro({ cpf: removeZero })
          if (ret.data[0]) {
            this.novaPessoaModel.fantasia = ret.data[0].nome
            this.novaPessoaModel.pessoa = ret.data[0].nome
          }
        } catch (error) {
          this.$q.notify({
            color: 'red-5',
            textColor: 'white',
            icon: 'warning',
            message: error.response.data
          })
        }

      }

    },

    async novaPessoa() {

      if (this.novaPessoaModel.ie && !this.novaPessoaModel.uf) {
        this.$q.notify({
          color: 'red-5',
          textColor: 'white',
          icon: 'warning',
          message: 'UF é obrigatória para validar Inscrição Estadual!'
        })
        return;
      }

      const novaPessoacp = { ...this.novaPessoaModel }

      if (novaPessoacp.nascimento) {
        novaPessoacp.nascimento = this.Documentos.dataFormatoSql(novaPessoacp.nascimento)
      }
      if (novaPessoacp.emissaoctps) {
        novaPessoacp.emissaoctps = this.Documentos.dataFormatoSql(novaPessoacp.emissaoctps)
      }

      try {
        const ret = await this.sPessoa.criarPessoa(novaPessoacp)
        if (ret.data.data) {
          this.$q.notify({
            color: 'green-4',
            textColor: 'white',
            icon: 'done',
            message: 'Pessoa criada!'
          })
          this.router.push('/pessoa/' + ret.data.data.codpessoa)
        }
      } catch (error) {
        if (error.response.data.errors && error.response.data.errors.cnpj) {
          this.$q.notify({
            color: 'red-5',
            textColor: 'white',
            icon: 'warning',
            message: error.response.data.errors.cnpj
          })
        }
        if (error.response.data.errors && error.response.data.errors.ie) {
          this.$q.notify({
            color: 'red-5',
            textColor: 'white',
            icon: 'warning',
            message: error.response.data.errors.ie
          })
        }
        if (!error.response.data.errors && error.response.data.message) {
          this.$q.notify({
            color: 'red-5',
            textColor: 'white',
            icon: 'warning',
            message: error.response.data.message
          })
        }
      }
    },
  },

  setup() {

    const loading = ref(true)
    const $q = useQuasar()
    const router = useRouter()
    const user = guardaToken()
    const Documentos = formataDocumetos()
    const sPessoa = pessoaStore()
    const dialogimportar = ref(false)
    const dialogNovaPessoa = ref(false)
    const filtro = ref([])
    const novaPessoaModel = ref({
      fisica: false,
    })
    const listapessoas = ref([])
    const estados = ref([
      { label: 'Acre', value: 'AC' },
      { label: 'Alagoas', value: 'AL' },
      { label: 'Amapá', value: 'AP' },
      { label: 'Amazonas', value: 'AM' },
      { label: 'Bahia', value: 'BA' },
      { label: 'Espírito Santo', value: 'ES' },
      { label: 'Goiás', value: 'GO' },
      { label: 'Maranhão', value: 'MA' },
      { label: 'Mato Grosso', value: 'MT' },
      { label: 'Mato Grosso do Sul', value: 'MS' },
      { label: 'Minas Gerais', value: 'MG' },
      { label: 'Pará', value: 'PA' },
      { label: 'Paraíba', value: 'PB' },
      { label: 'Paraná', value: 'PR' },
      { label: 'Pernambuco', value: 'PE' },
      { label: 'Piauí', value: 'PI' },
      { label: 'Rio de Janeiro', value: 'RJ' },
      { label: 'Rio Grande do Norte', value: 'RN' },
      { label: 'Rio Grande do Sul', value: 'RS' },
      { label: 'Rondônia', value: 'RO' },
      { label: 'Roraima', value: 'RR' },
      { label: 'Santa Catarina', value: 'SC' },
      { label: 'São Paulo', value: 'SP' },
      { label: 'Sergipe', value: 'SE' },
      { label: 'Tocantins', value: 'TO' },
      { label: 'Distrito Federal', value: 'DF' },
      { label: 'Pará', value: 'PA' }
    ])

    const importarsefazmodel = ref({
      // codfilial: '101'
    })

    // Pega o código da filial pelo usuario logado
    const codfilial = async () => {

      const usuario = user.usuarioLogado
      if (usuario.codfilial) {
        importarsefazmodel.value.codfilial = usuario.codfilial
      }
    }

    const buscarPessoas = debounce(async () => {
      $q.loadingBar.start()
      sPessoa.filtroPesquisa.page = 1;
      try {
        const ret = await sPessoa.buscarPessoas();
        loading.value = false;
        $q.loadingBar.stop()
        if (ret.data.data.length == 0) {
          return $q.notify({
            color: 'red-5',
            textColor: 'white',
            icon: 'warning',
            message: 'Nenhum Registro encontrado'
          })
        }
      } catch (error) {
        $q.loadingBar.stop()
      }
    }, 500)

    watch(
      () => sPessoa.filtroPesquisa.codformapagamento,
      () => buscarPessoas(),
      { deep: true }
    );

    watch(
      () => sPessoa.filtroPesquisa.inativo,
      () => buscarPessoas(),
      { deep: true }
    );

    watch(
      () => sPessoa.filtroPesquisa.codgrupocliente,
      () => buscarPessoas(),
      { deep: true }
    );

    // Importa cadastro da sefaz/receitaws
    const ImportarSefaz = async () => {
      $q.loading.show({
      })
      try {
        const ret = await api.post('v1/pessoa/importar', importarsefazmodel.value)
        if (ret.data.data && ret.data.data.length > 0) {
          $q.notify({
            color: 'green-4',
            textColor: 'white',
            icon: 'done',
            message: ret.data.data.length + ' cadastro(s) importados!'
          })
          sPessoa.arrPessoas = ret.data.data
          dialogimportar.value = false
          $q.loading.hide()
        } else {
          $q.notify({
            color: 'red-4',
            textColor: 'white',
            icon: 'error',
            message: 'Nenhum cadastro para importar!'
          })
          dialogimportar.value = false
          $q.loading.hide()
        }
      } catch (error) {
        $q.notify({
          color: 'red-5',
          textColor: 'white',
          icon: 'warning',
          message: error.response.data.message ?? 'Erro ao importar cadastro'
        })
        $q.loading.hide()
      }
    }

    onMounted(async () => {
      codfilial();
      if (sPessoa.arrPessoas.length == 0) {
        buscarPessoas();
      }
      if (sPessoa.filtroPesquisa.codcidade) {
        const ret = await sPessoa.consultaCidade(sPessoa.filtroPesquisa.codcidade)
        sPessoa.filtroPesquisa.codcidade = ret.data[0]

      }
    })

    return {
      model: ref(null),
      listapessoas,
      codfilial,
      importarsefazmodel,
      filtro,
      Documentos,
      user,
      router,
      estados,
      buscarPessoas,
      sPessoa,
      ImportarSefaz,
      dialogimportar,
      dialogNovaPessoa,
      novaPessoaModel,
      loading,
      async scrollInfinito(index, done) {
        loading.value = true;
        $q.loadingBar.start()
        sPessoa.filtroPesquisa.page++;
        const ret = await sPessoa.buscarPessoas();
        loading.value = false
        $q.loadingBar.stop()
        if (ret.data.data.length == 0) {
          loading.value = true
        }
        await done();
      },
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
}
</script>