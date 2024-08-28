<template>
  <MGLayout back-button>
    <template #tituloPagina>
      <span class="q-pl-sm">Pessoa - Nova</span>
    </template>

    <template #botaoVoltar>
      <q-btn flat dense round :to="{ name: 'pessoa' }" icon="arrow_back" aria-label="Voltar">
      </q-btn>
    </template>

    <template #content>
      <div id="q-app" style="min-height: 100vh">
        <div class="row flex flex-center">
          <div class="q-pa-lg col-xs-12 col-sm-8 col-md-6 col-lg-4 col-xl-3">
            <q-stepper vertical v-model="step" color="primary" animated header-nav>
              <!-- TIPO -->
              <q-step :name="0" title="Tipo" :done="step > 0">
                <p>
                  Selecione o Tipo de pessoa, Física para CPF ou Jurídica para
                  CNPJ.
                </p>

                <q-option-group :options="[
                  { label: 'Fisica', value: true },
                  { label: 'Jurídica', value: false },
                ]" type="radio" v-model="model.fisica" @update:model-value="step = step + 1" />
              </q-step>

              <!-- CNPJ -->
              <q-step :name="1" title="CNPJ/CPF" :done="step > 1">
                <p>Informe o número do documento.</p>

                <q-input autofocus outlined v-model="model.cnpj" label="CNPJ" v-if="model.fisica == false"
                  mask="##.###.###/####-##" unmasked-value required :rules="[validaObrigatorio, validaCpfCnpj]"
                  @update:model-value="continuaSeCnpjValido()" style="max-width: 200px" inputmode="numeric" />
                <q-input autofocus outlined v-model="model.cnpj" v-if="model.fisica == true" label="CPF"
                  mask="###.###.###-##" unmasked-value required :rules="[validaObrigatorio, validaCpfCnpj]"
                  @update:model-value="continuaSeCnpjValido()" style="max-width: 200px" inputmode="numeric" />
              </q-step>

              <!-- CADASTROS DUPLICADOS -->
              <q-step :name="2" title="Duplicidade" :done="step > 2">
                <template v-if="cadastrosEncontrados.length > 0">
                  <q-banner rounded inline-actions class="text-white bg-red q-mb-md">
                    Já existe cadastro com esse mesmo CNPJ/CPF. Verifique com
                    atenção para evitar duplicidade de cadastro!
                  </q-banner>

                  <div class="row q-col-gutter-md">
                    <div class="col-12" v-for="listagempessoas in cadastrosEncontrados"
                      v-bind:key="listagempessoas.codpessoa">
                      <!-- CARD AQUI -->
                      <card-pessoas :listagempessoas="listagempessoas"></card-pessoas>
                    </div>
                  </div>
                </template>
                <template v-else>
                  Não existe nenhum cadastro com esse mesmo CNPJ/CPF, você pode
                  prosseguir sem medo de duplicar o cadastro!
                </template>
              </q-step>

              <!-- INSCRICAO SEFAZ -->
              <q-step :name="3" title="Inscrição" :done="step > 3">
                <p>
                  Essas sãos as incrições estaduais que a SEFAZ retornou como
                  válidas. Por favor selecione uma ou clique em sem inscrição
                  para não vincular o cadastro à nenhuma delas.
                </p>

                <q-list style="max-width: 400px">
                  <q-item tag="label" v-ripple>
                    <q-item-section avatar>
                      <q-radio v-model="model.ie" :val="null" @update:model-value="step += 1" />
                    </q-item-section>
                    <q-item-section>
                      <q-item-label>Sem inscrição</q-item-label>
                    </q-item-section>
                  </q-item>

                  <q-item tag="label" v-ripple v-for="ie in sefazCadastro.filter((item) => {
                    return item.cSit == 1;
                  })" v-bind:key="ie.IE">
                    <q-item-section avatar>
                      <q-radio v-model="model.ie" :val="ie.IE" @update:model-value="step += 1" />
                    </q-item-section>
                    <q-item-section>
                      <q-item-label overline>
                        {{ formataIe(ie.UF, ie.IE) }}
                      </q-item-label>
                      <q-item-label v-if="ie.xFant">
                        {{ ie.xFant }}
                      </q-item-label>
                      <q-item-label v-if="ie.ender" caption>
                        {{ ie.ender.xLgr }}, {{ ie.ender.nro }} -
                        <template v-if="ie.ender.xCpl">
                          {{ ie.ender.xCpl }} -
                        </template>
                        {{ ie.ender.xBairro }} - {{ ie.ender.xMun }}/{{ ie.UF }}
                      </q-item-label>
                    </q-item-section>
                  </q-item>

                  <q-item tag="label" v-ripple>
                    <q-item-section avatar>
                      <q-radio v-model="model.ie" val="OUTRA" />
                    </q-item-section>
                    <q-item-section>
                      <q-item-label>
                        <div class="row">
                          <div class="col-6">
                            <q-input outlined v-model="model.ieoutra" label="Outra" unmasked-value
                              @update:model-value="model.ie = 'OUTRA'" />
                          </div>

                          <div class="col-5 q-pl-md">
                            <select-estado label="UF" v-model="model.uf"></select-estado>

                          </div>
                        </div>
                      </q-item-label>
                    </q-item-section>
                  </q-item>
                </q-list>
              </q-step>

              <!-- CONCLUIR -->
              <q-step :name="4" title="Finalizar " :done="step > 4">
                <input-filtered outlined v-model="model.fantasia" label="Fantasia" :rules="[
                  (val) =>
                    (val && val.length >= 3) || 'Nome Fantasia deve ter no mínimo 3 letras!',
                ]" autofocus style="max-width: 350px" maxlength="50" @focus="fantasiaFocus" @update:model-value="preencherRazaoSeVazia" />

                <input-filtered outlined v-model="model.pessoa" label="Razão Social" :rules="[
                  (val) =>
                    (val && val.length >= 5) || 'Razão Social deve coonter no mínimo 5 letras!',
                ]" style="max-width: 550px" maxlength="100" />
              </q-step>

              <template v-slot:navigation>
                <q-stepper-navigation>
                  <q-btn v-if="step != 4" @click="step = step + 1" color="primary" label="Continuar" />
                  <q-btn v-else @click="salvar()" color="primary" label="Salvar" />
                  <q-btn v-if="step > 0" flat color="primary" @click="step = step - 1" label="Voltar" class="q-ml-sm" />
                </q-stepper-navigation>
              </template>
            </q-stepper>
          </div>
        </div>
      </div>
    </template>
  </MGLayout>
</template>

<script>
import { ref, defineAsyncComponent } from "vue";
import { useQuasar } from "quasar";
import { useRouter } from "vue-router";
import { guardaToken } from "src/stores";
import { pessoaStore } from "src/stores/pessoa";
import { isCnpjCpfValido } from "src/utils/validador";
import {
  formataIe,
  primeiraLetraMaiuscula,
  removerAcentos,
} from "src/utils/formatador";

export default {
  components: {
    MGLayout: defineAsyncComponent(() => import("layouts/MGLayout.vue")),
    SelectEstado: defineAsyncComponent(() => import("components/pessoa/SelectEstado.vue")),
    InputFiltered: defineAsyncComponent(() => import('components/InputFiltered.vue')),

    CardPessoas: defineAsyncComponent(() =>
      import("components/pessoa/CardPessoas.vue")
    ),
  },

  methods: {
    fantasiaFocus(evt) {
      if (this.model.pessoa == this.model.fantasia) {
        this.razaoVazia = true;
        return;
      }
      this.razaoVazia = (this.model.pessoa)?false:true;
    },

    preencherRazaoSeVazia(value) {
      if (!this.razaoVazia) {
        return
      }
      this.model.pessoa = value;
    },

    validaObrigatorio(value) {
      if (!value) {
        return "Preenchimento Obrigatório!";
      }
      return true;
    },

    validaCpfCnpj(value) {
      if (!isCnpjCpfValido(value)) {
        return "Número de Documento Inválido!";
      }
      return true;
    },

    async continuaSeCnpjValido() {
      if (!this.model.cnpj) {
        return;
      }
      if (isCnpjCpfValido(this.model.cnpj)) {
        await this.buscarCadastrosCnpj();
        if (this.cadastrosEncontrados.length == 0) {
          this.step = 3;
        } else {
          this.step = 2;
        }
      }
    },

    async buscarCadastrosCnpj() {
      try {
        this.cnpjConsultado = this.model.cnpj;
        const ret = await this.sPessoa.VerificaExisteCnpjCpf({
          cnpj: this.model.cnpj,
        });
        if (ret.data.data) {
          this.cadastrosEncontrados = ret.data.data;
        }
      } catch (error) {
        console.log(error);
      }
      try {
        this.verificaIeSefaz();
      } catch (error) {
        console.log(error);
      }
    },

    async verificaIeSefaz() {
      try {
        var codfilial = this.user.usuarioLogado.codfilial;
        const ret = await this.sPessoa.verificaIeSefaz(
          101,
          this.model.fisica,
          this.model.cnpj
        );
        this.sefazCadastro = ret.data.retSefaz;
        this.receitaWsCadastro = ret.data.retReceita;
      } catch (error) {
        console.log(error);
      }
    },

    async sugerirNome() {
      if (!this.model.fisica && this.receitaWsCadastro.nome) {
        this.model.pessoa = primeiraLetraMaiuscula(
          removerAcentos(this.receitaWsCadastro.nome).substr(0, 50)
        );
        if (this.receitaWsCadastro.fantasia) {
          this.model.fantasia = primeiraLetraMaiuscula(
            removerAcentos(this.receitaWsCadastro.fantasia).substr(0, 50)
          );
        } else {
          this.model.fantasia = this.model.pessoa;
        }
        return;
      }
      if (this.sefazCadastro && this.sefazCadastro.length > 0) {
        var insc = null;
        if (this.model.ie == null || this.model.ie == "Digitada") {
          insc = this.sefazCadastro[0];
        } else {
          insc = this.sefazCadastro.find((item) => {
            return item.IE == this.model.ie;
          });
        }
        if (insc) {
          this.model.pessoa = primeiraLetraMaiuscula(
            removerAcentos(insc.xNome).substr(0, 50)
          );
          if (insc.xFant) {
            this.model.fantasia = primeiraLetraMaiuscula(
              removerAcentos(insc.xFant).substr(0, 50)
            );
          } else {
            this.model.fantasia = this.model.pessoa;
          }
          return;
        }
      }

      const nomePix = await this.sPessoa.descobreNomePeloPix(this.model.cnpj);
      if (nomePix.data.nome) {
        this.model.fantasia = primeiraLetraMaiuscula(
          removerAcentos(nomePix.data.nome).substr(0, 50)
        );
        this.model.pessoa = this.model.fantasia;
      }
    },

    async validaCamposPreenchidos() {

      // verifica fisica/juridica
      if (this.model.fisica == null) {
        this.step = 0;
        return false;
      }

      // verifica CPF/CNPJ
      if (!isCnpjCpfValido(this.model.cnpj)) {
        this.step = 1;
        return false;
      }

      if (this.model.fantasia.length < 3) {
        this.step = 4;
        return false;
      }

      if (this.model.pessoa.length < 5) {
        this.step = 4;
        return false;
      }

      return true;
    },

    async salvar() {

      // verifica se campos estao corretos
      if (!(await this.validaCamposPreenchidos())) {
        this.$q.notify({
          color: "red-5",
          textColor: "white",
          icon: "error",
          message: 'Preencha todos os campos corretamente antes de salvar!',
        });
        return false;
      }

      // monta um objeto com as consultas da sefaz e receitaws
      var consultas = {
        sefaz: null,
        receitaWs: this.receitaWsCadastro
      }

      // se usuario selecionou uma ie da sefaz, adiciona ela no objeto
      if (this.model.ie) {
        consultas.sefaz = this.sefazCadastro.find((el) => { return el.IE == this.model.ie });
      }

      // parametros com o model e as consultas
      const params = Object.assign(
        this.model,
        consultas
      );

      try {
        const ret = await this.sPessoa.criarPessoa(params);
        if (ret.data.data) {
          this.$q.notify({
            color: "green-5",
            textColor: "white",
            icon: "done",
            message: "Cadastro criado!",
          });
          this.router.push("/pessoa/" + ret.data.data.codpessoa);
        }
      } catch (error) {
        console.log(error);

        if (error.response.data.errors.ieoutra && error.response.data.errors.ieoutra[0]) {
          this.$q.notify({
            color: "red-5",
            textColor: "white",
            icon: "error",
            message: error.response.data.errors.ieoutra[0],
          });
        } else {
          this.$q.notify({
            color: "red-5",
            textColor: "white",
            icon: "error",
            message: error.response.data.message,
          });
        }

      }
    },
  },

  watch: {
    step: function (newVal) {
      switch (newVal) {
        case 2:
          if (this.model.cnpj != this.cnpjConsultado) {
            this.buscarCadastrosCnpj();
          }
          break;

        case 3:
          break;

        case 4:
          this.sugerirNome();
          break;

        default:
          break;
      }
    },
  },

  setup() {
    const cnpjConsultado = ref(null);
    const $q = useQuasar();
    const sPessoa = pessoaStore();
    const router = useRouter();
    const step = ref(0);
    const model = ref({
      fisica: null,
      cnpj: null,
      ie: null,
      ieoutra: null,
      uf: null,
      pessoa: null,
      fantasia: null,
      consumidor: true,
      fornecedor: false,
      cliente: true,
    });
    const razaoVazia = ref(false);
    const cadastrosEncontrados = ref([]);
    const sefazCadastro = ref([]);
    const receitaWsCadastro = ref([]);
    const user = guardaToken();

    return {
      step,
      router,
      user,
      model,
      razaoVazia,
      sPessoa,
      sefazCadastro,
      cadastrosEncontrados,
      receitaWsCadastro,
      formataIe,
    };
  },
};
</script>
