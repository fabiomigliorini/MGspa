<script setup>
import { ref } from "vue";
import { negocioStore } from "stores/negocio";
import { sincronizacaoStore } from "stores/sincronizacao";
import { Dialog, Notify } from "quasar";
import { formataCpf } from "../../utils/formatador.js";
import { formataCnpjCpf } from "../../utils/formatador.js";
import SelectNaturezaOperacao from "components/selects/SelectNaturezaOperacao.vue";
import SelectEstoqueLocal from "components/selects/SelectEstoqueLocal.vue";
import WizardPessoa from "./WizardPessoa.vue";
import { db } from "boot/db";
import { LoadingBar } from "quasar";
import { iconeNegocio, corIconeNegocio } from "src/utils/iconeNegocio.js";
import emitter from "src/utils/emitter";
import moment from "moment/min/moment-with-locales";
moment.locale("pt-br");

const sNegocio = negocioStore();
const sSinc = sincronizacaoStore();

const edicaoNatureza = ref({
  codestoquelocal: null,
  codnaturezaoperacao: null,
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

const editarNatureza = () => {
  edicaoNatureza.value.codestoquelocal = sNegocio.negocio.codestoquelocal;
  edicaoNatureza.value.codnaturezaoperacao = sNegocio.negocio.codnaturezaoperacao;
  edicaoNatureza.value.observacoes = sNegocio.negocio.observacoes;
  dialogPessoa.value = true;
};

const editarPessoa = () => {
  emitter.emit('informarPessoa');
}

const urlPessoa = (codpessoa) => {
  return process.env.PESSOAS_URL + "/pessoa/" + codpessoa;
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

const salvarNatureza = async () => {
  Dialog.create({
    title: "Salvar",
    message: "Tem certeza que você deseja salvar?",
    cancel: true,
  }).onOk(() => {
    sNegocio.informarNatureza(
      edicaoNatureza.value.codestoquelocal,
      edicaoNatureza.value.codnaturezaoperacao,
      edicaoNatureza.value.observacoes
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
      timeout: 1000, // 1 segundo
      actions: [{ icon: "close", color: "white" }],
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
        timeout: 1000, // 1 segundo
        actions: [{ icon: "close", color: "white" }],
      });
    }
  });
};

const apropriar = () => {
  Dialog.create({
    title: "Apropriar",
    message:
      "Tem certeza que você deseja se apropriar desse negócio? Será impossível continuar editando este negócio no computador onde ele está vinculado atualmente!",
    cancel: true,
  }).onOk(async () => {
    if (await sNegocio.apropriar(sNegocio.negocio.codnegocio)) {
      sNegocio.atualizarListagem();
      emitter.emit('negocioAlterado');
      Notify.create({
        type: "positive",
        message: "Negocio !",
        timeout: 1000, // 1 segundo
        actions: [{ icon: "close", color: "white" }],
      });
    }
  });
}

</script>
<template>
  <!-- Editar Pessoa -->
  <q-dialog v-model="dialogPessoa">
    <q-card style="width: 500px; max-width: 80vw">
      <q-form ref="formItem" @submit="salvarNatureza()">
        <q-card-section>
          <div class="row q-gutter-md q-pr-md">
            <div class="col-12">
              <select-estoque-local outlined v-model="edicaoNatureza.codestoquelocal" label="Local de Estoque"
                :rules="[(val) => !!val || 'Preenchimento Obrigatório']" />
            </div>
            <div class="col-12">
              <select-natureza-operacao outlined v-model="edicaoNatureza.codnaturezaoperacao"
                label="Natureza de Operacao" :rules="[(val) => !!val || 'Preenchimento Obrigatório']" />
            </div>
            <div class="col-12">
              <q-input autofocus outlined autogrow v-model.number="edicaoNatureza.observacoes" label="Observações" />
            </div>
          </div>
        </q-card-section>
        <q-card-actions align="right">
          <q-btn flat label="Cancelar" color="primary" @click="dialogPessoa = false" tabindex="-1" />
          <q-btn type="submit" flat label="Salvar" color="primary" />
        </q-card-actions>
      </q-form>
    </q-card>
  </q-dialog>

  <!-- Editar Vendedor -->
  <q-dialog v-model="dialogVendedor" full-height>
    <q-card style="width: 500px">
      <q-form ref="formItem">
        <q-card-section>
          <q-input outlined autofocus label="Vendedor" v-model="filtroVendedor"
            @update:model-value="filtrarVendedor()" />
        </q-card-section>
        <q-card-section>
          <q-list>
            <q-item clickable v-ripple @click="informarVendedor(null)">
              <q-item-section avatar>
                <q-avatar color="negative" icon="close" text-color="white"></q-avatar>
              </q-item-section>
              <q-item-section> Sem Vendedor </q-item-section>
            </q-item>
            <q-item v-for="vendedor in vendedoresFiltrados" :key="vendedor.codpessoa" clickable v-ripple
              @click="informarVendedor(vendedor.codpessoa)">
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
    <q-item clickable @click="editarNatureza()">
      <q-item-section avatar top>
        <q-avatar icon="store" color="secondary" text-color="white" />
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
    <q-item clickable v-ripple @click="editarNatureza()">
      <q-item-section avatar top>
        <q-avatar :icon="iconeNegocio(sNegocio.negocio)" :color="corIconeNegocio(sNegocio.negocio)"
          text-color="white" />
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


    <!-- OBSERVACOES -->
    <q-item clickable v-ripple @click="editarNatureza()">
      <q-item-section avatar top>
        <q-avatar icon="notes" :color="(sNegocio.negocio.observacoes) ? 'secondary' : 'grey'" text-color="white" />
      </q-item-section>

      <q-item-section>
        <q-item-label lines="1" style="white-space: pre-line">
          <template v-if="sNegocio.negocio.observacoes">
            {{ sNegocio.negocio.observacoes }}
          </template>
          <template v-else>
            Clique aqui para adicionar
          </template>
        </q-item-label>
        <q-item-label caption>Observações</q-item-label>
      </q-item-section>
    </q-item>

    <q-separator spaced />

    <!-- PESSOA -->
    <wizard-pessoa />
    <q-item clickable v-ripple @click="editarPessoa()">
      <q-item-section avatar top>
        <q-avatar icon="person"
          :color="(sNegocio.negocio.codpessoa != 1 || sNegocio.negocio.cpf) ? 'secondary' : 'grey'"
          text-color="white" />
      </q-item-section>

      <q-item-section>
        <q-item-label lines="1">
          {{ sNegocio.negocio.fantasia }}
        </q-item-label>
        <q-item-label caption v-if="sNegocio.negocio.cpf">
          {{ formataCpf(sNegocio.negocio.cpf) }}
        </q-item-label>
        <template v-if="sNegocio.negocio.codpessoa != 1 && sNegocio.negocio.Pessoa">
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
        <template v-else>
          <q-item-label caption>
            Clique ou F10 para informar
          </q-item-label>
        </template>
      </q-item-section>

      <q-item-section side v-if="sNegocio.negocio.codpessoa == 1 && !sNegocio.negocio.cpf">
        <q-icon name="info" color="warning" />
      </q-item-section>
    </q-item>

    <q-item clickable v-ripple :href="urlPessoa(sNegocio.negocio.codpessoa)" target="_blank"
      v-if="sNegocio.negocio.codpessoa != 1">
      <q-item-section avatar top>
        <q-avatar icon="attach_money" color="secondary" text-color="white" />
      </q-item-section>

      <q-item-section>
        <template v-if="sNegocio.negocio.Pessoa != undefined && sNegocio.negocio.Pessoa.codformapagamento">
          <q-item-label lines="1">
            {{ sNegocio.negocio.Pessoa.formapagamento }}
          </q-item-label>
          <q-item-label caption>Forma de Pagamento Padrão</q-item-label>
        </template>
        <template v-else>
          <q-item-label lines="2">
            Sem forma de pagamento definida
          </q-item-label>
        </template>
      </q-item-section>
      <q-item-section side>
        <q-avatar icon="launch" color="secondary" text-color="white" :to="urlPessoa(sNegocio.negocio.codpessoa)" />
      </q-item-section>
    </q-item>

    <template v-if="sNegocio.negocio.Pessoa != undefined">
      <q-item v-if="sNegocio.negocio.Pessoa.mensagemvenda">
        <q-item-section>
          <q-banner inline-actions rounded class="bg-orange-8 text-white" style="white-space: pre-line">
            {{ sNegocio.negocio.Pessoa.mensagemvenda }}
          </q-banner>
        </q-item-section>
      </q-item>
    </template>

    <q-separator spaced />

    <!-- VENDEDOR -->
    <q-item clickable v-ripple @click="editarVendedor()">
      <q-item-section avatar top>
        <q-avatar icon="escalator_warning" :color="(sNegocio.negocio.codpessoavendedor) ? 'secondary' : 'grey'"
          text-color="white" />
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
        <q-btn @click="sincronizar()" round :color="sNegocio.negocio.sincronizado == true ? 'secondary' : 'accent'
          " icon="file_upload" />
      </q-item-section>

      <q-item-section>
        <q-item-label lines="1" v-if="sNegocio.negocio.codnegocio">
          #{{ String(sNegocio.negocio.codnegocio).padStart(8, "0") }}
        </q-item-label>
        <q-item-label lines="1" v-else> Não Integrado </q-item-label>
        <q-item-label caption>{{ sNegocio.negocio.uuid }}</q-item-label>
      </q-item-section>

      <q-item-section side>
        <q-btn @click="recarregarDaApi()" round color="negative" icon="file_download" />
      </q-item-section>
    </q-item>

    <q-separator spaced />

    <!-- PDV/Usuario -->
    <q-item>
      <q-item-section avatar top>
        <q-avatar icon="mdi-monitor-account" color="secondary" text-color="white" />
      </q-item-section>

      <q-item-section>
        <q-item-label lines="1" v-if="sNegocio.negocio.codusuario">
          {{ sNegocio.negocio.usuario }}
        </q-item-label>
        <template v-if="sNegocio.negocio.codpdv">
          <template v-if="sNegocio.negocio.Pdv">
            <q-item-label lines="1" v-if="sNegocio.negocio.Pdv.apelido">
              {{ sNegocio.negocio.Pdv.apelido }}
            </q-item-label>
            <q-item-label caption>
              {{ sNegocio.negocio.Pdv.uuid }}
            </q-item-label>
            <q-item-label caption>
              {{ sNegocio.negocio.Pdv.ip }}
            </q-item-label>
          </template>
          <q-item-label lines="1" v-else>
            #{{ String(sNegocio.negocio.codpdv).padStart(8, "0") }}
          </q-item-label>
        </template>
      </q-item-section>

      <q-item-section top side
        v-if="sNegocio.negocio.codnegociostatus == 1 && sNegocio.negocio.codpdv != sSinc.pdv.codpdv && sNegocio.negocio.sincronizado == true">
        <q-btn @click="apropriar()" round color="negative" icon="mdi-transit-transfer" />
      </q-item-section>
    </q-item>
  </q-list>
</template>
