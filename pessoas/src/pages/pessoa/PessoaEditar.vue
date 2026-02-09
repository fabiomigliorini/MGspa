<template>
  <div class="q-pa-md q-gutter-sm" style="text-align: left; padding-top: 20px">
    <q-btn
      :to="{ name: 'pessoa' }"
      color="grey"
      icon="list"
      label="Listagem"
      dense
      flat
    />
    <q-btn
      :to="{ name: 'pessoanova' }"
      color="grey"
      icon="add"
      label="Nova"
      dense
      flat
    />
    <q-btn
      :to="{ name: 'pessoaView' }"
      color="grey"
      icon="visibility"
      label="Detalhes"
      dense
      flat
    />
  </div>
  <q-separator></q-separator>

  <div class="q-pa-md q-gutter-sm" style="padding-top: 20px">
    <div class="text-h6 text-grey-8">Editar Pessoa</div>
  </div>

  <q-separator></q-separator>
  <q-form @submit.prevent="onSubmit" class="q-gutter-md">
    <div class="q-pa-md q-gutter-sm">
      <q-input
        filled
        v-model="form.fantasia"
        label="Nome Fantasia"
        dense
        :rules="[
          (val) => (val && val.length > 0) || 'Nome fantasia obrigatório',
        ]"
        required
      />
      <q-input
        filled
        v-model="form.pessoa"
        label="Razão Social"
        dense
        :rules="[
          (val) => (val && val.length > 0) || 'Razão Social obrigatório',
        ]"
        required
      />
      <q-input filled v-model="form.contato" label="Contato" dense />

      <q-select
        filled
        v-model="form.codcidade"
        use-input
        input-debounce="0"
        label="Cidade"
        :options="options"
        options-label="label"
        options-value="value"
        map-options
        emit-value
        clearable
        @filter="filtrocidade"
        behavior="menu"
      >
        <template v-slot:no-option>
          <q-item>
            <q-item-section class="text-grey">
              Nenhum resultado encontrado.
            </q-item-section>
          </q-item>
        </template>
      </q-select>

      <q-btn-toggle
        v-model="form.fisica"
        class="my-custom-toggle"
        no-caps
        rounded
        unelevated
        toggle-color="primary"
        color="white"
        text-color="primary"
        :options="[
          { label: 'Pessoa Física', value: true },
          { label: 'Pessoa Jurídica', value: false },
        ]"
      />

      <q-input
        filled
        v-model="form.cnpj"
        label="CNPJ/CPF"
        v-if="form.fisica == true"
        mask="###.###.###-##"
        unmasked-value
        dense
      />
      <q-input
        filled
        v-model="form.cnpj"
        label="CNPJ/CPF"
        v-else
        mask="##.###.###/####-##"
        unmasked-value
        dense
      />
      <q-input filled v-model="form.ie" label="Inscrição Estadual" dense />
      <q-input filled v-model="form.rntrc" label="RNTRC" dense />
      <q-select
        filled
        v-model="form.tipotransportador"
        label="Tipo Transportador"
      />
      <!-- :options="[{label:'ETC - Empresa', value:'etc'}, {label:'TAC - Autônomo', value:'tac'}, {label:'CTC - Cooperativa', value:'ctc'}]" -->

      <div
        v-if="form.fisica == true"
        class="q-pa-md q-gutter-sm"
        style="padding-top: 20px"
      >
        <div class="text-h6 text-grey-8">Dados Pessoa Física</div>
      </div>
      <q-input
        v-if="form.fisica == true"
        v-model="form.rg"
        filled
        label="RG"
        dense
      />
      <q-input
        v-if="form.fisica == true"
        v-model="form.sexo"
        filled
        label="Sexo"
        dense
      />
      <q-input
        v-if="form.fisica == true"
        v-model="form.estadocivil"
        filled
        label="Estado Civil"
        dense
      />
      <q-input
        v-if="form.fisica == true"
        v-model="form.conjuge"
        filled
        label="Conjuge"
        dense
      />

      <q-item-label>Cliente</q-item-label>
      <q-btn-toggle
        v-model="form.cliente"
        class="my-custom-toggle"
        no-caps
        rounded
        unelevated
        toggle-color="primary"
        color="white"
        text-color="primary"
        :options="[
          { label: 'Sim', value: true },
          { label: 'Não', value: false },
        ]"
      />

      <q-input
        filled
        v-model="form.cep"
        label="CEP"
        mask="#####-###"
        @change="BuscaCep()"
        unmasked-value
        dense
        reactive-rules
        :rules="[
          (val) =>
            (val && val.length > 7 && ceperror === false) || 'CEP inválido',
        ]"
        required
      />
      <q-input
        filled
        v-model="form.endereco"
        label="Endereço"
        dense
        :rules="[(val) => (val && val.length > 0) || 'Endereço obrigatório']"
        required
      />
      <q-input
        filled
        v-model="form.numero"
        label="Número"
        dense
        :rules="[(val) => (val && val.length > 0) || 'Número obrigatório']"
        required
      />
      <q-input filled v-model="form.complemento" label="Complemento" dense />
      <q-input
        filled
        v-model="form.bairro"
        label="Bairro"
        dense
        :rules="[(val) => (val && val.length > 0) || 'Bairro obrigatório']"
        required
      />

      <q-item-label>Cobrança no Mesmo Endereço</q-item-label>
      <q-btn-toggle
        v-model="form.cobrancaendereco"
        class="my-custom-toggle"
        label="Cobrança no Mesmo Endereço"
        no-caps
        rounded
        unelevated
        toggle-color="primary"
        color="white"
        text-color="primary"
        :options="[
          { label: 'Sim', value: true },
          { label: 'Não', value: false },
        ]"
      />
    </div>
    <div
      v-if="form.cobrancaendereco == false"
      class="q-pa-md q-gutter-sm"
      style="padding-top: 20px"
    >
      <div class="text-h6 text-grey-8">Endereço de Cobrança</div>
    </div>
    <div class="q-pa-md q-gutter-sm" padding>
      <q-input
        v-if="form.cobrancaendereco == false"
        v-model="form.cepcobranca"
        filled
        label="CEP"
        mask="#####-###"
        unmasked-value
        dense
      />
      <q-input
        v-if="form.cobrancaendereco == false"
        filled
        v-model="form.enderecocobranca"
        label="Endereço"
        dense
      />
      <q-input
        v-if="form.cobrancaendereco == false"
        filled
        v-model="form.numerocobranca"
        label="Número"
        dense
      />
      <q-input
        v-if="form.cobrancaendereco == false"
        filled
        v-model="form.complementocobranca"
        label="Complemento"
        dense
      />
      <q-input
        v-if="form.cobrancaendereco == false"
        filled
        v-model="form.bairrocobranca"
        label="Bairro"
        dense
      />

      <q-select
        v-if="form.cobrancaendereco == false"
        filled
        v-model="form.codcidadecobranca"
        use-input
        input-debounce="0"
        label="Cidade"
        :options="options"
        options-label="label"
        options-value="value"
        map-options
        emit-value
        clearable
        @filter="filtrocidade"
        behavior="menu"
      />

      <q-input
        filled
        v-model="form.telefone1"
        mask="(##)# ####-####"
        label="Telefone"
        dense
      />
      <q-input
        filled
        v-model="form.telefone2"
        mask="(##)# ####-####"
        label="Telefone 2"
        dense
      />
      <q-input
        filled
        v-model="form.telefone3"
        mask="(##)# ####-####"
        label="Telefone 3"
        dense
      />
      <q-input filled v-model="form.email" label="Email" dense />
      <q-input filled v-model="form.emailnfe" label="Email para NFe" dense />
      <q-input
        filled
        v-model="form.emailcobranca"
        label="Email para Cobranca"
        dense
      />
      <q-input
        filled
        v-model="form.observacoes"
        label="Observações"
        type="textarea"
        dense
      />
      <q-item-label>Fornecedor</q-item-label>
      <q-btn-toggle
        class="my-custom-toggle"
        v-model="form.fornecedor"
        no-caps
        rounded
        unelevated
        toggle-color="primary"
        color="white"
        text-color="primary"
        :options="[
          { label: 'Sim', value: true },
          { label: 'Não', value: false },
        ]"
      />
      <q-item-label>Vendedor</q-item-label>
      <q-btn-toggle
        v-model="form.vendedor"
        class="my-custom-toggle"
        no-caps
        rounded
        unelevated
        toggle-color="primary"
        color="white"
        text-color="primary"
        :options="[
          { label: 'Sim', value: true },
          { label: 'Não', value: false },
        ]"
      />

      <q-input
        filled
        v-model="form.inativo"
        mask="##-##-####"
        label="Inativo Desde"
      >
        <template v-slot:append>
          <q-icon name="event" class="cursor-pointer">
            <q-popup-proxy
              cover
              transition-show="scale"
              transition-hide="scale"
            >
              <q-date v-model="form.inativo" :locale="brasil" mask="DD-MM-YYYY">
                <div class="row items-center justify-end">
                  <q-btn v-close-popup label="Fechar" color="primary" flat />
                </div>
              </q-date>
            </q-popup-proxy>
          </q-icon>
        </template>
      </q-input>
    </div>

    <q-separator></q-separator>
    <div class="q-pa-md q-gutter-sm">
      <q-btn
        :to="{ name: 'pessoaView', params: { id: route.params.id } }"
        color="red"
        label="Cancelar"
        dense
        flat
      />
      <q-btn label="Salvar" type="submit" color="primary" />
    </div>
  </q-form>
  <q-separator></q-separator>
</template>

<script>
import { ref, onMounted, defineAsyncComponent } from "vue";
import { api } from "boot/axios";
import { useQuasar } from "quasar";
import { Notify } from "quasar";
import { useRouter } from "vue-router";
import { useRoute } from "vue-router";
import { guardaToken } from "src/stores";

export default {
  //   components:{ MGLayout: defineAsyncComponent(() => import('layouts/MGLayout.vue'))},
  setup() {
    const $q = useQuasar();
    const router = useRouter();
    const route = useRoute();
    const OptionsCidade = ref([]);
    const options = ref(OptionsCidade);
    const ceperror = ref(false);

    // Mostra as primeiras 100 cidades da API
    const SelectCidade = async () => {
      try {
        const { data } = await api.get("v1/select/cidade");
        OptionsCidade.value = data;
      } catch (error) {
        console.log(error);
      }
    };

    onMounted(() => {
      SelectCidade();
      ListPessoaEditar();
    });

    // Carrega os dados da pessoa no form
    const ListPessoaEditar = async () => {
      try {
        const { data } = await api.get("v1/pessoa/" + route.params.id);
        form.value = data.data;
      } catch (error) {
        console.log(error);
      }
    };

    const form = ref({
      pessoa: "",
      fantasia: "",
      fisica: false,
      cliente: true,
      fornecedor: false,
      consumidor: true,
      creditobloqueado: false,
      vendedor: false,
      cobrancaendereco: true,
      notafiscal: "1",
      codusuarioalteracao: "",
    });

    // Busca CEP e preenche o formulario endereço e bairro
    const BuscaCep = async () => {
      setTimeout(async () => {
        if (form.value.cep.length > 7) {
          const { data } = await api.get(
            "https://viacep.com.br/ws/" + form.value.cep + "/json/"
          );

          if (data.logradouro) {
            ceperror.value = false;
            form.value.endereco = data.logradouro;
            form.value.bairro = data.bairro;
            return false;
          }
          if (data.erro == true) {
            ceperror.value = true;
            return true;
          }
          return;
        } else return;
      }, 1000);
    };

    // Envio do formulario de alteração para a API
    const onSubmit = async () => {
      const auth = guardaToken();
      const codusuario = await auth.verificaToken(); //Pega o código do usuario para enviar junto no formulario
      form.value.codusuarioalteracao = codusuario.codusuario;

      try {
        if (form.value.cobrancaendereco == true) {
          form.value.cepcobranca = form.value.cep;
          form.value.enderecocobranca = form.value.endereco;
          form.value.numerocobranca = form.value.numero;
          form.value.bairrocobranca = form.value.bairro;
          form.value.codcidadecobranca = form.value.codcidade;
          form.value.complementocobranca = form.value.complemento;
        }

        const { data } = await api.put(
          "v1/pessoa/" + route.params.id,
          form.value
        );

        if (data.data) {
          $q.notify({
            color: "green-4",
            textColor: "white",
            icon: "done",
            message: "Pessoa Alterada com sucesso",
          });
          router.push("/pessoa/view/" + data.data.codpessoa);
        }
      } catch (error) {
        // console.log(error)
        $q.notify({
          color: "red-5",
          textColor: "white",
          icon: "warning",
          message: "Erro ao alterar pessoa, tente novamente.",
        });
      }
    };
    return {
      form,
      onSubmit,
      SelectCidade,
      BuscaCep,
      ListPessoaEditar,
      route,
      ceperror,
      OptionsCidade,
      brasil: {
        days: "Domingo_Segunda_Terça_Quarta_Quinta_Sexta_Sábado".split("_"),
        daysShort: "Dom_Seg_Ter_Qua_Qui_Sex_Sáb".split("_"),
        months:
          "Janeiro_Fevereiro_Março_Abril_Maio_Junho_Julho_Agosto_Setembro_Outubro_Novembro_Dezembro".split(
            "_"
          ),
        monthsShort: "Jan_Fev_Mar_Abr_Mai_Jun_Jul_Ago_Set_Out_Nov_Dez".split(
          "_"
        ),
        firstDayOfWeek: 1,
        format24h: true,
        pluralDay: "dias",
      },
      options,
      filtrocidade(val, update) {
        if (val === "") {
          update(() => {
            options.value = OptionsCidade.value;
          });
          return;
        }
        update(async () => {
          const needle = val.toLowerCase();
          try {
            const { data } = await api.get("v1/select/cidade?cidade=" + needle);
            options.value = data;
            return;
          } catch (error) {
            console.log(error);
          }
        });
      },
    };
  },
};
</script>
