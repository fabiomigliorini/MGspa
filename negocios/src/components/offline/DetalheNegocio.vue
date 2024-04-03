<script setup>
import { ref } from "vue";
import { negocioStore } from "stores/negocio";
import { Dialog, Notify } from "quasar";
import { formataCpf } from "../../utils/formatador.js";
import { formataCnpjCpf } from "../../utils/formatador.js";
import moment from "moment/min/moment-with-locales";
import SelectPessoa from "components/selects/SelectPessoa.vue";
import SelectNaturezaOperacao from "components/selects/SelectNaturezaOperacao.vue";
import SelectEstoqueLocal from "components/selects/SelectEstoqueLocal.vue";
import { db } from "boot/db";
import { LoadingBar } from "quasar";
moment.locale("pt-br");

const sNegocio = negocioStore();

const edicaoPessoa = ref({
  codestoquelocal: null,
  codnaturezaoperacao: null,
  codpessoa: null,
  cpf: null,
  observacoes: null,
});

const vendedores = ref([]);
const filtroVendedor = ref(null);
const vendedoresFiltrados = ref([]);
const dialogPessoa = ref(false);
const dialogVendedor = ref(false);

function validarCPF(cpf) {
  if (!cpf) {
    return true;
  }
  cpf = cpf.replace(/[^\d]+/g, "");
  if (cpf == "") {
    return true;
  }
  // Elimina CPFs invalidos conhecidos
  if (
    cpf.length != 11 ||
    cpf == "00000000000" ||
    cpf == "11111111111" ||
    cpf == "22222222222" ||
    cpf == "33333333333" ||
    cpf == "44444444444" ||
    cpf == "55555555555" ||
    cpf == "66666666666" ||
    cpf == "77777777777" ||
    cpf == "88888888888" ||
    cpf == "99999999999"
  ) {
    return "CPF Inválido";
  }
  // Valida 1o digito
  var add = 0;
  for (var i = 0; i < 9; i++) {
    add += parseInt(cpf.charAt(i)) * (10 - i);
  }
  var rev = 11 - (add % 11);
  if (rev == 10 || rev == 11) {
    rev = 0;
  }
  if (rev != parseInt(cpf.charAt(9))) {
    return "CPF Inválido";
  }
  // Valida 2o digito
  add = 0;
  for (i = 0; i < 10; i++) {
    add += parseInt(cpf.charAt(i)) * (11 - i);
  }
  rev = 11 - (add % 11);
  if (rev == 10 || rev == 11) {
    rev = 0;
  }
  if (rev != parseInt(cpf.charAt(10))) {
    return "CPF Inválido";
  }
  return true;
}

const editarPessoa = () => {
  edicaoPessoa.value.codestoquelocal = sNegocio.negocio.codestoquelocal;
  edicaoPessoa.value.codnaturezaoperacao = sNegocio.negocio.codnaturezaoperacao;
  edicaoPessoa.value.codpessoa = sNegocio.negocio.codpessoa;
  edicaoPessoa.value.cpf = sNegocio.negocio.cpf;
  edicaoPessoa.value.observacoes = sNegocio.negocio.observacoes;
  dialogPessoa.value = true;
};

const buscarListagemVendedores = async () => {
  LoadingBar.start();
  vendedores.value = await db.pessoa
    .filter((pessoa) => {
      if (!pessoa.vendedor) {
        return false;
      }
      if (pessoa.inativo) {
        return false;
      }
      return true;
    })
    .sortBy("fantasia");
  LoadingBar.stop();
};

const filtrarVendedor = async () => {
  if (!filtroVendedor.value) {
    vendedoresFiltrados.value = vendedores.value;
    return;
  }
  const filtro = filtroVendedor.value.toUpperCase();
  vendedoresFiltrados.value = vendedores.value.filter((v) => {
    return v.fantasia.toUpperCase().includes(filtro);
  });
};

const editarVendedor = async () => {
  if (vendedores.value.length == 0) {
    await buscarListagemVendedores();
  }
  filtroVendedor.value = null;
  await filtrarVendedor();
  dialogVendedor.value = true;
};

const salvarPessoa = async () => {
  Dialog.create({
    title: "Salvar",
    message: "Tem certeza que você deseja salvar?",
    cancel: true,
  }).onOk(() => {
    sNegocio.informarPessoa(
      edicaoPessoa.value.codestoquelocal,
      edicaoPessoa.value.codnaturezaoperacao,
      edicaoPessoa.value.codpessoa,
      edicaoPessoa.value.cpf,
      edicaoPessoa.value.observacoes
    );
    dialogPessoa.value = false;
  });
};

const informarVendedor = async (codpessoavendedor) => {
  if (!sNegocio.negocio.codpessoavendedor) {
    sNegocio.informarVendedor(codpessoavendedor);
    dialogVendedor.value = false;
    return;
  }
  Dialog.create({
    title: "Salvar",
    message: "Tem certeza que você deseja alterar o vendedor?",
    cancel: true,
  }).onOk(() => {
    sNegocio.informarVendedor(codpessoavendedor);
    dialogVendedor.value = false;
  });
};

const sincronizar = async () => {
  if (await sNegocio.sincronizar(sNegocio.negocio.uuid)) {
    Notify.create({
      type: "positive",
      message: "Negócio Sincronizado com o Servidor!",
    });
  }
};

const recarregarDaApi = () => {
  Dialog.create({
    title: "Recarregar",
    message:
      "Tem certeza que você deseja recarregar os dados do servidor? Você poderá perder as informações alteradas deste negócio!",
    cancel: true,
    options: {
      type: "toggle",
      model: [],
      isValid: (val) => val[0] == "OK",
      items: [
        {
          label: "Sim, pode apagar o que eu alterei!",
          value: "OK",
          color: "negative",
        },
      ],
    },
  }).onOk(async () => {
    if (await sNegocio.recarregarDaApi(sNegocio.negocio.uuid)) {
      sNegocio.atualizarListagem();
      Notify.create({
        type: "positive",
        message: "Dados Recarregados do Servidor!",
      });
    }
  });
};

const negocioStatusIconColor = () => {
  switch (sNegocio.negocio.codnegociostatus) {
    case 2: // Fechado
      return "secondary";

    case 3: // Cancelado
      return "negative";

    default:
      return "grey";
  }
};
</script>
<template>
  <!-- Editar Pessoa -->
  <q-dialog v-model="dialogPessoa">
    <q-card style="width: 500px; max-width: 80vw">
      <q-form ref="formItem" @submit="salvarPessoa()">
        <q-card-section>
          <div class="row q-gutter-md q-pr-md">
            <div class="col-12">
              <select-estoque-local
                outlined
                v-model="edicaoPessoa.codestoquelocal"
                label="Local de Estoque"
                :rules="[(val) => !!val || 'Preenchimento Obrigatório']"
              />
            </div>
            <div class="col-12">
              <select-natureza-operacao
                outlined
                v-model="edicaoPessoa.codnaturezaoperacao"
                label="Natureza de Operacao"
                :rules="[(val) => !!val || 'Preenchimento Obrigatório']"
              />
            </div>
            <div class="col-12">
              <select-pessoa
                ref="selectPessoa"
                outlined
                v-model="edicaoPessoa.codpessoa"
                label="Pessoa"
                :rules="[(val) => !!val || 'Preenchimento Obrigatório']"
                clearable
                @clear="edicaoPessoa.codpessoa = 1"
              >
                <template v-slot:after>
                  X
                  <q-icon
                    name="delete"
                    @click.stop.prevent="model = null"
                    class="cursor-pointer"
                  />
                </template>
              </select-pessoa>
            </div>
            <div class="col-12">
              <q-input
                ref="codpessoa"
                v-if="edicaoPessoa.codpessoa == 1"
                :rules="[validarCPF]"
                outlined
                v-model="edicaoPessoa.cpf"
                label="CPF"
                mask="###.###.###-##"
                unmasked-value
              />
            </div>
            <div class="col-12">
              <q-input
                outlined
                autogrow
                v-model.number="edicaoPessoa.observacoes"
                label="Observações"
              />
            </div>
          </div>
        </q-card-section>
        <q-card-actions align="right">
          <q-btn
            flat
            label="Cancelar"
            color="primary"
            @click="dialogPessoa = false"
            tabindex="-1"
          />
          <q-btn type="submit" flat label="Salvar" color="primary" />
        </q-card-actions>
      </q-form>
    </q-card>
  </q-dialog>

  <!-- Editar Vendedor -->
  <q-dialog v-model="dialogVendedor" full-height>
    <q-card style="width: 500px">
      <q-form ref="formItem" @submit="salvarPessoa()">
        <q-card-section>
          <q-input
            outlined
            autofocus
            label="Vendedor"
            v-model="filtroVendedor"
            @update:model-value="filtrarVendedor()"
          />
        </q-card-section>
        <q-card-section>
          <q-list>
            <q-item clickable v-ripple @click="informarVendedor(null)">
              <q-item-section avatar>
                <q-avatar
                  color="negative"
                  icon="close"
                  text-color="white"
                ></q-avatar>
              </q-item-section>
              <q-item-section> Sem Vendedor </q-item-section>
            </q-item>
            <q-item
              v-for="vendedor in vendedoresFiltrados"
              :key="vendedor.codpessoa"
              clickable
              v-ripple
              @click="informarVendedor(vendedor.codpessoa)"
            >
              <q-item-section avatar>
                <q-avatar color="primary" text-color="white">
                  {{ vendedor.fantasia.charAt(0) }}
                </q-avatar>
              </q-item-section>
              <q-item-section>
                {{ vendedor.fantasia }}
              </q-item-section>
            </q-item>
          </q-list>
        </q-card-section>
      </q-form>
    </q-card>
  </q-dialog>

  <q-list v-if="sNegocio.negocio">
    <!-- <q-item-label header>Pessoa</q-item-label> -->

    <!-- Filial -->
    <q-item clickable @click="editarPessoa()">
      <q-item-section avatar top>
        <q-avatar icon="store" color="grey" text-color="white" />
      </q-item-section>

      <q-item-section>
        <q-item-label lines="1">
          {{ sNegocio.negocio.estoquelocal }}
        </q-item-label>
        <q-item-label caption>
          {{ moment(sNegocio.negocio.lancamento).format("llll") }}
        </q-item-label>
      </q-item-section>
    </q-item>

    <!-- Natureza -->
    <q-item clickable v-ripple @click="editarPessoa()">
      <q-item-section avatar top>
        <q-avatar
          icon="work"
          :color="negocioStatusIconColor()"
          text-color="white"
        />
      </q-item-section>

      <q-item-section>
        <q-item-label lines="1">
          {{ sNegocio.negocio.operacao }} -
          {{ sNegocio.negocio.naturezaoperacao }}
        </q-item-label>
        <q-item-label caption>
          {{ sNegocio.negocio.negociostatus }}
        </q-item-label>
        <q-item-label caption v-if="sNegocio.negocio.justificativa">
          {{ sNegocio.negocio.justificativa }}
        </q-item-label>
      </q-item-section>
    </q-item>

    <!-- PESSOA -->
    <q-item clickable v-ripple @click="editarPessoa()">
      <q-item-section avatar top>
        <q-avatar icon="person" color="grey" text-color="white" />
      </q-item-section>

      <q-item-section>
        <q-item-label lines="1">
          {{ sNegocio.negocio.fantasia }}
        </q-item-label>
        <q-item-label caption v-if="sNegocio.negocio.cpf">
          {{ formataCpf(sNegocio.negocio.cpf) }}
        </q-item-label>
        <template v-if="sNegocio.negocio.Pessoa">
          <q-item-label caption v-if="sNegocio.negocio.Pessoa.cnpj">
            {{
              formataCnpjCpf(
                sNegocio.negocio.Pessoa.cnpj,
                sNegocio.negocio.Pessoa.fisica
              )
            }}
          </q-item-label>
          <q-item-label caption v-if="sNegocio.negocio.Pessoa.endereco">
            {{ sNegocio.negocio.Pessoa.endereco }},
            {{ sNegocio.negocio.Pessoa.numero }} -
            <template v-if="sNegocio.negocio.Pessoa.complemento">
              {{ sNegocio.negocio.Pessoa.complemento }} -
            </template>
            <template v-if="sNegocio.negocio.Pessoa.bairro">
              {{ sNegocio.negocio.Pessoa.bairro }} -
            </template>
            {{ sNegocio.negocio.Pessoa.cidade }} /
            {{ sNegocio.negocio.Pessoa.uf }}
          </q-item-label>
        </template>
      </q-item-section>

      <q-item-section
        side
        v-if="sNegocio.negocio.codpessoa == 1 && !sNegocio.negocio.cpf"
      >
        <q-icon name="info" color="warning" />
      </q-item-section>
    </q-item>

    <template v-if="sNegocio.negocio.Pessoa != undefined">
      <q-item v-if="sNegocio.negocio.Pessoa.mensagemvenda">
        <q-item-section>
          <q-banner
            inline-actions
            rounded
            class="bg-orange-8 text-white"
            style="white-space: pre-line"
          >
            {{ sNegocio.negocio.Pessoa.mensagemvenda }}
          </q-banner>
        </q-item-section>
      </q-item>
    </template>

    <!-- OBSERVACOES -->
    <q-item
      clickable
      v-ripple
      v-if="sNegocio.negocio.observacoes"
      @click="editarPessoa()"
    >
      <q-item-section avatar top>
        <q-avatar icon="notes" color="grey" text-color="white" />
      </q-item-section>

      <q-item-section>
        <q-item-label lines="1" style="white-space: pre-line">
          {{ sNegocio.negocio.observacoes }}
        </q-item-label>
        <q-item-label caption>Observações</q-item-label>
      </q-item-section>
    </q-item>

    <template
      v-if="sNegocio.negocio.Pessoa != undefined && sNegocio.negocio.financeiro"
    >
      <q-item
        clickable
        v-ripple
        v-if="sNegocio.negocio.Pessoa.codformapagamento"
      >
        <q-item-section avatar top>
          <q-avatar icon="attach_money" color="grey" text-color="white" />
        </q-item-section>

        <q-item-section>
          <q-item-label lines="1">
            {{ sNegocio.negocio.Pessoa.formapagamento }}
          </q-item-label>
          <q-item-label caption>Forma de Pagamento Padrão</q-item-label>
        </q-item-section>
      </q-item>
    </template>

    <q-separator spaced />

    <!-- VENDEDOR -->
    <q-item clickable v-ripple @click="editarVendedor()">
      <q-item-section avatar top>
        <q-avatar icon="escalator_warning" color="grey" text-color="white" />
      </q-item-section>

      <q-item-section>
        <q-item-label lines="1" v-if="sNegocio.negocio.codpessoavendedor">
          {{ sNegocio.negocio.fantasiavendedor }}
        </q-item-label>
        <q-item-label lines="1" v-else> Não Informado </q-item-label>
        <q-item-label caption>Vendedor</q-item-label>
      </q-item-section>

      <q-item-section side v-if="!sNegocio.negocio.codpessoavendedor">
        <q-icon name="error" color="warning" />
      </q-item-section>
    </q-item>

    <q-separator spaced />

    <!-- CODIGOS -->
    <q-item>
      <q-item-section avatar top>
        <q-btn
          @click="sincronizar()"
          round
          :color="
            sNegocio.negocio.sincronizado == true ? 'secondary' : 'orange'
          "
          icon="file_upload"
        />
      </q-item-section>

      <q-item-section>
        <q-item-label lines="1" v-if="sNegocio.negocio.codnegocio">
          #{{ String(sNegocio.negocio.codnegocio).padStart(8, "0") }}
        </q-item-label>
        <q-item-label lines="1" v-else> Não Integrado </q-item-label>
        <q-item-label caption>{{ sNegocio.negocio.uuid }}</q-item-label>
      </q-item-section>

      <q-item-section side>
        <q-btn
          @click="recarregarDaApi()"
          round
          color="negative"
          icon="file_download"
        />
      </q-item-section>
    </q-item>

    <q-separator spaced />

    <!-- PDV/Usuario -->
    <q-item>
      <q-item-section avatar top>
        <q-avatar icon="mdi-monitor-account" color="grey" text-color="white" />
      </q-item-section>

      <q-item-section>
        <q-item-label lines="1" v-if="sNegocio.negocio.codusuario">
          {{ sNegocio.negocio.usuario }}
        </q-item-label>
        <template v-if="sNegocio.negocio.codpdv">
          <q-item-label lines="1" v-if="sNegocio.negocio.Pdv.apelido">
            {{ sNegocio.negocio.Pdv.apelido }}
          </q-item-label>
          <q-item-label lines="1" v-else>
            #{{ String(sNegocio.negocio.codpdv).padStart(8, "0") }}
          </q-item-label>
          <q-item-label caption>
            {{ sNegocio.negocio.Pdv.uuid }}
          </q-item-label>
          <q-item-label caption>
            {{ sNegocio.negocio.Pdv.ip }}
          </q-item-label>
        </template>
      </q-item-section>
    </q-item>
  </q-list>
</template>
