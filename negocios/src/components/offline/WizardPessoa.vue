<script setup>
import { ref, defineProps } from 'vue';
import { LoadingBar, Notify, debounce, Dialog } from "quasar";
import { db } from "boot/db";
import { formataCnpjCpf, primeiraLetraMaiuscula } from "src/utils/formatador.js";
import { isCpfValido, isCnpjValido, isEmailValido, isTelefoneValido } from 'src/utils/validador.js';
import SelectCidade from '../selects/SelectCidade.vue';
import InputFiltered from '../InputFiltered.vue';
import { sincronizacaoStore } from 'src/stores/sincronizacao';
import { negocioStore } from 'src/stores/negocio';
import { api } from 'src/boot/axios';
import axios from 'axios';
import emitter from "src/utils/emitter";

const sSinc = sincronizacaoStore();
const sNegocio = negocioStore();
const dialog = ref(false);
const step = ref(3)
const stepper = ref(null);
const opcoes = ref([]);
const tiposTelefone = ref([
  {
    label: 'Celular',
    value: 2,
  },
  {
    label: 'Fixo',
    value: 1,
  },
  {
    label: 'Outro',
    value: 9,
  },
]);
const cnpj = ref(null);
const consultando = ref(false);
const formPessoa = ref(null);
const inputTelefone = ref(null);
const pessoa = ref(null)
const consultaSefaz = ref(null);

const props = defineProps({
  modelValue: {
    type: Number,
  },
  somenteAtivos: {
    type: Boolean,
    default: true,
  },
  somenteVendedores: {
    type: Boolean,
    default: false,
  },
});

const abrir = () => {
  cnpj.value = null;
  step.value = 1;
  opcoes.value = [];
  dialog.value = true;
}

emitter.on('informarPessoa', () => {
  abrir();
})

const selecionar = (codpessoa, cpf) => {
  sNegocio.informarPessoa(
    codpessoa,
    cpf
  );
  dialog.value = false;
}

const confirmar = (codpessoa, cpf) => {
  if (sNegocio.negocio.codpessoa != 1 || sNegocio.negocio.cpf != null) {
    Dialog.create({
      title: "Alterar Pessoa",
      message: "Tem certeza que você deseja alterar a pessoa do negócio?",
      cancel: true,
    }).onOk(() => {
      selecionar(codpessoa, cpf)
    });
  } else {
    selecionar(codpessoa, cpf);
  }
}

const pesquisa = debounce(async () => {
  // verifica se tem texto de busca
  const texto = cnpj.value.trim();
  if (texto.length < 3) {
    return;
  }

  // sinaliza pro usuario que está pesquisando
  LoadingBar.start();
  consultando.value = true;

  // monta array de palavras pra buscas
  const numeric = texto.replace(/\D/g, "");
  const alpha = texto.replace(/[^\w\s]/gi, "");
  var palavras = [];
  if (!/\s/g.test(texto) && numeric == alpha && numeric.length > 3) {
    palavras = [numeric];
  } else {
    palavras = texto.split(" ");
  }

  // Busca Pessoas baseados na primeira palavra de pesquisa
  var colPessoas = await db.pessoa
    .where("buscaArr")
    .startsWithIgnoreCase(palavras[0]);

  if (props.somenteAtivos) {
    colPessoas.and((p) => p.inativo == null);
  }

  if (props.somenteVendedores) {
    colPessoas.and((p) => p.vendedor == true);
  }

  // se estiver buscando por mais de uma palavra
  if (palavras.length > 1) {
    // monta expressoes regulares
    var regexes = [];
    for (let i = 1; i < palavras.length; i++) {
      regexes.push(new RegExp(".*" + palavras[i] + ".*", "i"));
    }

    // percorre todos registros filtrando pelas expressoes regulares
    const iMax = regexes.length;
    colPessoas = await colPessoas.and(function (pessoa) {
      for (let i = 0; i < iMax; i++) {
        if (!regexes[i].test(pessoa.busca)) {
          return false;
        }
      }
      return true;
    });
  }
  var arrPessoas = await colPessoas.toArray();
  arrPessoas = arrPessoas.sort((a, b) => {
    if (a.fantasia > b.fantasia) {
      return 1;
    } else {
      return -1;
    }
  });
  // esconde barra
  opcoes.value = arrPessoas.slice(0, 20);
  LoadingBar.stop();
  consultando.value = false;
}, 1000);

const nova = async (fisica) => {
  cnpj.value = cnpj.value.replace(/\D/g, '');
  pessoa.value = {
    cnpj: cnpj.value,
    fisica: fisica,
    ie: null,
    pessoa: null,
    fantasia: null,
    enderecos: [{
      endereco: null,
      numero: null,
      municipio: null,
      uf: null,
      codcidade: null,
      bairro: null,
      complemento: null,
      cep: null
    }],
    emails: [
      null
    ],
    telefones: [{
      tipo: 2,
      numero: null
    }]
  };
  consultarDocumento();
  step.value = 2;
}

const consultarDocumento = async () => {
  LoadingBar.start();
  consultando.value = true;
  try {
    //TODO: Trazer Nome pelo PIX
    let url = 'api/v1/pessoa/verifica-ie-sefaz';
    let params = {
      codfilial: 101,
      cpf: null,
      cnpj: null,
    }
    if (pessoa.value.fisica) {
      params.cpf = pessoa.value.cnpj;
    } else {
      params.cnpj = pessoa.value.cnpj;
    }
    const ret = await api.get(url, { params: params });
    consultaSefaz.value = ret.data;
    if (consultaSefaz.value.retSefaz) {
      consultaSefaz.value.retSefaz.forEach((ie) => {
        if (!Array.isArray(ie.ender)) {
          ie.ender = [ie.ender];
        }
      })
    }
    parseConsultaPix();
    parseReceitaWs();
  } catch (error) {
    console.log(error);
    Notify.create({
      type: "negative",
      message: "Falha ao consultar API da SEFAZ/ReceitaWS!",
      timeout: 3000, // 3 segundos
      actions: [{ icon: "close", color: "white" }],
    });
  } finally {
    consultando.value = false;
    LoadingBar.stop();
  }
}

const parseConsultaPix = () => {
  if (consultaSefaz.value.retPix == null) {
    return;
  }
  if (consultaSefaz.value.retPix.nome == null) {
    return;
  }
  pessoa.value.fantasia = primeiraLetraMaiuscula(consultaSefaz.value.retPix.nome);
  pessoa.value.pessoa = pessoa.value.fantasia;
}

const parseReceitaWs = async () => {
  if (consultaSefaz.value.retReceita == null) {
    return;
  }
  if (consultaSefaz.value.retReceita.status != 'OK') {
    return;
  }
  const ret = consultaSefaz.value.retReceita;
  pessoa.value.fantasia = primeiraLetraMaiuscula(ret.fantasia);
  pessoa.value.pessoa = primeiraLetraMaiuscula(ret.nome);
  pessoa.value.enderecos[0] = {
    endereco: primeiraLetraMaiuscula(ret.logradouro),
    numero: ret.numero,
    municipio: ret.municipio,
    uf: ret.uf,
    codcidade: await procurarCidade(ret.municipio, ret.uf),
    bairro: primeiraLetraMaiuscula(ret.bairro),
    complemento: primeiraLetraMaiuscula(ret.complemento),
    cep: ret.cep
  };
  pessoa.value.emails = [ret.email];
  pessoa.value.telefones = [{
    tipo: 1,
    numero: ret.telefone
  }];
}

const procurarCidade = async (cidade, uf) => {
  try {
    const ret = await api.get('api/v1/select/cidade?cidade=' + cidade + ' ' + uf);
    if (ret.data.length == 1) {
      return ret.data[0].value;
    } else {
      Notify.create({
        type: "negative",
        message: "Localizada mais de uma cidade para '" + cidade + "/" + uf + "'! Informe manualmente!",
        timeout: 3000, // 3 segundos
        actions: [{ icon: "close", color: "white" }],
      });
    }
    return null;

  } catch (error) {
    console.log(error);
    Notify.create({
      type: "negative",
      message: "Falha ao consultar código da cidade!",
      timeout: 3000, // 3 segundos
      actions: [{ icon: "close", color: "white" }],
    });
    return null;

  }

}

const parseIe = (ret) => {
  if (ret.xNome) {
    pessoa.value.pessoa = primeiraLetraMaiuscula(ret.xNome);
  }
  if (ret.xFant) {
    pessoa.value.fantasia = primeiraLetraMaiuscula(ret.xFant);
  }
  if (ret.IE) {
    pessoa.value.ie = ret.IE;
  }
  if (ret.IEAtual) {
    pessoa.value.ie = ret.IEAtual;
  }
  if (ret.CNPJ) {
    pessoa.value.cnpj = String(ret.CNPJ).padStart(14, '0');
  }
  if (ret.CPF) {
    pessoa.value.cnpj = String(ret.CPF).padStart(11, '0');
  }
  if (ret.ender) {
    pessoa.value.enderecos = [];
    ret.ender.forEach(async (end) => {
      let novo = {
        endereco: primeiraLetraMaiuscula(end.xLgr),
        numero: end.nro,
        municipio: end.xMun,
        uf: ret.UF,
        codcidade: null,
        bairro: primeiraLetraMaiuscula(end.xBairro),
        complemento: primeiraLetraMaiuscula(end.xCpl),
        cep: end.CEP
      };
      if (end.xMun) {
        novo.codcidade = await procurarCidade(end.xMun, ret.UF);
      }
      pessoa.value.enderecos.push(novo);
    })

  }
  step.value = 3;

}

const outraIe = () => {
  pessoa.value.ie = null;
  step.value = 3;
}

const novoEmail = () => {
  if (pessoa.value.emails.includes(null)) {
    return;
  }
  pessoa.value.emails.push(null);
}

const removerEmail = (i) => {
  if (pessoa.value.emails.length <= 1) {
    return;
  }
  pessoa.value.emails.splice(i, 1);
}

const novoTelefone = () => {
  if (pessoa.value.telefones.some(t => t.numero == null)) {
    return;
  }
  pessoa.value.telefones.push({
    tipo: 2,
    numero: null
  });
}

const removerTelefone = (i) => {
  if (pessoa.value.telefones.length <= 1) {
    return;
  }
  pessoa.value.telefones.splice(i, 1);
}

const novoEndereco = () => {
  if (pessoa.value.enderecos.some(t => t.endereco == null)) {
    return;
  }
  pessoa.value.enderecos.push({
    endereco: null,
    numero: null,
    municipio: null,
    uf: null,
    bairro: null,
    complemento: null,
    cep: null
  });
}

const removerEndereco = (i) => {
  if (pessoa.value.enderecos.length <= 1) {
    return;
  }
  pessoa.value.enderecos.splice(i, 1);
}

const mascaraTelefone = (tipo) => {
  switch (tipo) {
    case 1:
      return '(##) ####-####';
    case 2:
      return '(##) #-####-####';
    default:
      return '';
  }
}

const consultarCep = async (i) => {

  const cep = pessoa.value.enderecos[i].cep.replace(/\D/g, '');
  if (cep.length != 8) {
    return;
  }

  try {
    const ax = axios.create();
    const ret = await ax.get('https://viacep.com.br/ws/' + cep + '/json/');
    if (ret.data.erro) {
      Notify.create({
        type: "negative",
        message: "CEP não localizado!",
        timeout: 3000, // 3 segundos
        actions: [{ icon: "close", color: "white" }],
      });
      return;
    }
    const end = {
      endereco: primeiraLetraMaiuscula(ret.data.logradouro),
      complemento: primeiraLetraMaiuscula(ret.data.complemento),
      bairro: primeiraLetraMaiuscula(ret.data.bairro),
      municipio: primeiraLetraMaiuscula(ret.data.localidade),
      uf: primeiraLetraMaiuscula(ret.data.uf),
      codcidade: await procurarCidade(ret.data.localidade, ret.data.uf),
      cep: pessoa.value.enderecos[i].cep,
    }
    pessoa.value.enderecos[i] = end;
  } catch (error) {
    console.log(error);
    Notify.create({
      type: "negative",
      message: "Falha ao consultar CEP!",
      timeout: 3000, // 3 segundos
      actions: [{ icon: "close", color: "white" }],
    });
  }

}

const salvar = async (e) => {

  if (e) {
    e.preventDefault();
  }

  // verifica se tem pelo menos 1 telefone
  if ((typeof pessoa.value.telefones === 'undefined') || pessoa.value.telefones.length == 0) {
    pessoa.value.telefones = [];
    novoTelefone();
    return;
  }

  // verifica se tem pelo menos 1 email
  if ((typeof pessoa.value.emails === 'undefined') || pessoa.value.emails.length == 0) {
    pessoa.value.emails = [];
    novoEmail();
    return;
  }

  // verifica se tem pelo menos 1 endereco
  if ((typeof pessoa.value.enderecos === 'undefined') || pessoa.value.enderecos.length == 0) {
    pessoa.value.enderecos = [];
    novoEndereco();
    return;
  }

  // verifica se o form está sem erro
  if (!await formPessoa.value.validate()) {
    return;
  }

  // cria e seleciona a nova pessoa
  Dialog.create({
    title: "Salvar",
    message: "Tem certeza que você deseja salvar a nova pessoa?",
    cancel: true,
  }).onOk(async () => {
    const codpessoa = await sSinc.postPessoa(pessoa.value);
    if (codpessoa) {
      selecionar(codpessoa, null);
    }
  });

}

</script>
<template>
  <q-dialog v-model="dialog">

    <q-stepper v-model="step" ref="stepper" color="primary" animated style="width: 800px; max-width: 80vw;">

      <q-step :name="1" title="Documento" icon="settings" :done="step > 1" style="height: 700px; max-height: 80vh;">
        <div class="row">
          <q-input outlined autofocus label="Pesquisa" v-model="cnpj" class="col-12" @update:model-value="pesquisa()"
            :rules="[val => (!!val && val.length > 3) || 'Digite pelo menos 3 letras']">

            <template v-slot:append>
              <q-icon name="search" />
            </template>
          </q-input>

          <div class="col-12 text-center" style="margin-top: 25vh;" v-if="consultando">
            <q-spinner color="primary" size="200px" v-if="consultando" />
          </div>
          <q-list separator class="col-12" v-else>
            <template v-for="p in opcoes" :key="p.codpessoa">
              <q-item clickable v-ripple @click="confirmar(p.codpessoa, null)">
                <q-item-section avatar>
                  <q-icon name="person" v-if="p.fisica" />
                  <q-icon name="warehouse" v-else />
                </q-item-section>
                <q-item-section>
                  <q-item-label class="text-h6">{{ p.fantasia }}</q-item-label>
                  <q-item-label v-if="p.cnpj">
                    <b>{{ formataCnpjCpf(p.cnpj, p.fisica) }}</b>
                  </q-item-label>
                  <q-item-label v-if="p.ie">
                    {{ p.ie }}
                  </q-item-label>
                  <q-item-label caption>
                    {{ p.pessoa }}
                  </q-item-label>
                  <!-- {{ p.codpessoa }} -->

                </q-item-section>
                <q-item-section side>
                  <q-item-label v-if="p.cidade">
                    {{ p.cidade }} / {{ p.uf }}
                  </q-item-label>
                  <q-item-label v-if="p.bairro">
                    {{ p.bairro }}
                  </q-item-label>
                  <q-item-label caption v-if="p.endereco">
                    {{ p.endereco }}, {{ p.numero }}
                  </q-item-label>
                  <q-item-label v-if="p.complemento">
                    - {{ p.complemento }}
                  </q-item-label>
                </q-item-section>

              </q-item>
            </template>
          </q-list>
        </div>
      </q-step>

      <q-step :name="2" title="Inscrição" icon="create_new_folder" :done="step > 2"
        style="height: 700px; max-height: 80vh;">
        <div class="col-12 text-center" style="margin-top: 25vh;" v-if="consultando">
          <q-spinner color="primary" size="200px" v-if="consultando" />
        </div>
        <q-list separator v-else>
          <template v-for="(ie, i) in consultaSefaz.retSefaz" :key="i">
            <q-item clickable v-ripple @click="parseIe(ie)">
              <q-item-section avatar>
                <q-avatar color="secondary" text-color="white">
                  {{ i + 1 }}
                </q-avatar>
              </q-item-section>
              <q-item-section>
                <q-item-label class="text-h6">
                  {{ ie.IE }}/{{ ie.UF }}
                </q-item-label>
                <q-item-label v-if="ie.xFant">
                  {{ ie.xFant }}
                </q-item-label>
                <q-item-label caption v-if="ie.xNome">
                  {{ ie.xNome }}
                </q-item-label>
              </q-item-section>
              <q-item-section side v-if="ie.ender">
                <q-item-label>
                  {{ ie.ender[0].xMun }}/{{ ie.UF }}
                </q-item-label>
                <q-item-label v-if="ie.ender[0].xBairro">
                  {{ ie.ender[0].xBairro }}
                </q-item-label>
                <q-item-label caption v-if="ie.ender[0].xLgr">
                  {{ ie.ender[0].xLgr }}, {{ ie.ender[0].nro }}
                </q-item-label>
                <q-item-label caption v-if="ie.ender[0].xCpl">
                  {{ ie.ender[0].xCpl }}
                </q-item-label>
              </q-item-section>
            </q-item>
          </template>
          <q-item clickable v-ripple @click="outraIe()">
            <q-item-section avatar>
              <q-avatar color="negative" text-color="white">
                S
              </q-avatar>
            </q-item-section>
            <q-item-section>
              <q-item-label class="text-h6">
                Sem Inscrição | Outra Inscrição
              </q-item-label>
              <q-item-label caption>
                Pessoa <b>NÃO TEM INSCRIÇÃO</b> estadual
              </q-item-label>
              <q-item-label caption>
                <b>OU</b> deseja <b>INFORMAR MANUALMENTE</b>.
              </q-item-label>
            </q-item-section>
          </q-item>
        </q-list>
      </q-step>

      <q-step :name="3" title="Nome" icon="create_new_folder" :done="step > 2" style="height: 700px; max-height: 80vh;">
        <q-form ref="formPessoa" @submit="salvar">

          <div class="row q-col-gutter-md q-mb-md">
            <q-input class="col-md-3 col-sm-6 col-xs-12" outlined v-model="pessoa.cnpj" label="CPF"
              mask="###.###.###-##" :rules="[val => !!val || 'Obrigatório']" v-if="pessoa.fisica" />
            <q-input class="col-md-3 col-sm-6 col-xs-12" outlined v-model="pessoa.cnpj" label="CNPJ"
              mask="##.###.###/####-##" :rules="[val => !!val || 'Obrigatório', val => isCnpjValido(val) || 'Inválido']"
              v-else />
            <q-input class="col-md-3 col-sm-6 col-xs-12" outlined v-model="pessoa.ie" label="Inscrição Estadual"
              :rules="[val => val.length <= 20 || 'Muito longo, reduza!']" counter maxlength="20" />
          </div>
          <div class="row q-col-gutter-md q-mb-md">
            <input-filtered class="col-6" outlined v-model="pessoa.fantasia" label="Fantasia"
              :rules="[val => !!val || 'Obrigatório', val => val.length > 3 || 'Muito Curto', val => val.length <= 50 || 'Muito longo, abrevie!']"
              counter maxlength="50" autofocus />
            <input-filtered class="col-6" outlined v-model="pessoa.pessoa" label="Razão Social"
              :rules="[val => !!val || 'Obrigatório', val => val.length > 3 || 'Muito Curto', val => val.length <= 100 || 'Muito longo, abrevie!']"
              counter maxlength="100" />
          </div>
          <div class="row q-col-gutter-md q-mb-md">
            <template v-for="(e, i) in pessoa.emails" :key="i">
              <q-input class="col-md-6 col-sm-12 col-xs-12" outlined v-model="pessoa.emails[i]" label="Email"
                :rules="[val => !!val || 'Obrigatório', val => isEmailValido(val) || 'Inválido', val => val.length <= 100 || 'Muito longo!']"
                counter maxlength="100">
                <template v-slot:append>
                  <q-btn flat round dense icon="add" @click="novoEmail()" tabindex="-1" />
                  <q-btn flat round dense icon="remove" @click="removerEmail(i)" v-if="pessoa.emails.length > 1"
                    tabindex="-1" />
                </template>
              </q-input>
            </template>
          </div>
          <div class="row q-col-gutter-md q-mb-md">
            <template v-for="(e, i) in pessoa.telefones" :key="i">
              <q-input class="col-md-6 col-sm-12 col-xs-12" outlined v-model="pessoa.telefones[i].numero"
                label="Telefone" ref="inputTelefone"
                :rules="[val => !!val || 'Obrigatório', val => isTelefoneValido(val, pessoa.telefones[i].tipo) || 'Inválido', val => val.length <= 20 || 'Muito longo!']"
                counter maxlength="20" :mask="mascaraTelefone(pessoa.telefones[i].tipo)">
                <template v-slot:before>
                  <q-select outlined v-model="pessoa.telefones[i].tipo" :options="tiposTelefone" label="Tipo" emit-value
                    map-options style="width: 90px" />
                </template>
                <template v-slot:append>
                  <q-btn flat round dense icon="add" @click="novoTelefone()" tabindex="-1" />
                  <q-btn flat round dense icon="remove" @click="removerTelefone(i)" v-if="pessoa.telefones.length > 1"
                    tabindex="-1" />
                </template>
              </q-input>
            </template>
          </div>

          <template v-for="(e, i) in pessoa.enderecos" :key="i">
            <div class="row q-col-gutter-md q-mb-md">
              <q-input class="col-md-3 col-sm-3 col-xs-12" outlined v-model="pessoa.enderecos[i].cep" label="CEP"
                :rules="[val => !!val || 'Obrigatório']" mask="##.###-###" @update:model-value="consultarCep(i)">
              </q-input>
              <input-filtered class="col-md-6 col-sm-6 col-xs-8" outlined v-model="pessoa.enderecos[i].endereco"
                label="Endereço" :rules="[val => !!val || 'Obrigatório', val => val.length <= 100 || 'Muito longo!']"
                counter maxlength="100">
                <template v-slot:append>
                  <q-btn flat round dense icon="add" @click="novoEndereco()" tabindex="-1" />
                  <q-btn flat round dense icon="remove" @click="removerEndereco(i)" v-if="pessoa.enderecos.length > 1"
                    tabindex="-1" />
                </template>
              </input-filtered>
              <q-input class="col-md-3 col-sm-3 col-xs-4" outlined v-model="pessoa.enderecos[i].numero" label="Número"
                :rules="[val => !!val || 'Obrigatório', val => val.length <= 10 || 'Muito longo!']" counter
                maxlength="10">
              </q-input>
              <input-filtered class="col-md-3 col-sm-3 col-xs-12" outlined v-model="pessoa.enderecos[i].bairro"
                label="Bairro" :rules="[val => !!val || 'Obrigatório', val => val.length <= 50 || 'Muito longo!']"
                counter maxlength="50">
              </input-filtered>
              <input-filtered class="col-md-3 col-sm-3 col-xs-12" outlined v-model="pessoa.enderecos[i].complemento"
                label="Complemento" :rules="[val => val.length <= 50 || 'Muito longo!']" counter maxlength="50">
              </input-filtered>
              <select-cidade outlined v-model="pessoa.enderecos[i].codcidade" class="col-md-6 col-sm-6 col-xs-12"
                label="Cidade" :rules="[val => !!val || 'Obrigatório']" />
            </div>
          </template>
          <q-btn color="primary" label="Salvar" type="submit" v-if="step == 3" />

        </q-form>

      </q-step>

      <template v-slot:navigation>
        <q-stepper-navigation class="q-gutter-sm text-right">
          <q-btn color="accent" v-if="step == 1 && isCpfValido(cnpj)" label="Informar CPF Sem Cadastro"
            @click="confirmar(1, cnpj)" />
          <q-btn color="secondary" v-if="step == 1 && isCnpjValido(cnpj)" label="Cadastrar Pessoa Jurídica"
            @click="nova(false)" />
          <q-btn color="secondary" v-if="step == 1 && isCpfValido(cnpj)" label="Cadastrar Pessoa Física"
            @click="nova(true)" />
          <q-btn color="accent" v-if="step == 1 && !isCnpjValido(cnpj) && !isCpfValido(cnpj)"
            label="Não identificar pessoa" @click="confirmar(1, null)" />
          <q-btn v-if="step > 1" flat color="primary" @click="$refs.stepper.previous()" label="Voltar"
            class="q-ml-sm" />
          <q-btn color="negative" label="Fechar" @click="dialog = false" />
        </q-stepper-navigation>
      </template>

    </q-stepper>
  </q-dialog>
</template>
