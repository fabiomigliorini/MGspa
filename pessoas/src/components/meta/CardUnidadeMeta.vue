<script setup>
import { ref, computed } from "vue";
import { useQuasar } from "quasar";
import { metaStore } from "src/stores/meta";
import SelectPessoa from "src/components/select/SelectPessoa.vue";

const props = defineProps({
  unidade: { type: Object, required: true },
  codmeta: { type: [Number, String], required: true },
  podeEditar: { type: Boolean, default: false },
  periodoinicial: { type: String, default: null },
  periodofinal: { type: String, default: null },
});

const $q = useQuasar();
const sMeta = metaStore();

const dataISO = (val) => (val ? val.substring(0, 10) : null);

// --- DIALOGS ---

const dialogUnidade = ref(false);
const modelUnidade = ref({});

const dialogPessoa = ref(false);
const isNovaPessoa = ref(false);
const modelPessoa = ref({});
const codunidadePessoa = ref(null);
const idPessoaEditando = ref(null);

const dialogFixo = ref(false);
const isNovoFixo = ref(false);
const modelFixo = ref({});
const idPessoaFixo = ref(null);
const idFixoEditando = ref(null);

// --- FORMATTERS ---

const formataMoeda = (valor) => {
  return new Intl.NumberFormat("pt-BR", {
    style: "currency",
    currency: "BRL",
  }).format(parseFloat(valor) || 0);
};

const formataPercentual = (valor) => {
  return (
    new Intl.NumberFormat("pt-BR", {
      style: "decimal",
      minimumFractionDigits: 1,
      maximumFractionDigits: 1,
    }).format(parseFloat(valor) || 0) + "%"
  );
};

const corProgresso = (percentual) => {
  if (!percentual) return "grey";
  if (percentual >= 100) return "green";
  if (percentual >= 70) return "orange";
  return "red";
};

const corPosicao = (posicao) => {
  if (posicao === 1) return "amber-8";
  if (posicao === 2) return "grey-6";
  if (posicao === 3) return "brown-5";
  return "grey-4";
};

const siglaFuncao = (pessoa) => {
  if (pessoa.percentualsubgerente) return "S";
  if (pessoa.percentualxerox) return "X";
  if (pessoa.percentualcaixa) return "C";
  if (pessoa.percentualvenda) return "V";
  return null;
};

const mesesAbrev = [
  "jan", "fev", "mar", "abr", "mai", "jun",
  "jul", "ago", "set", "out", "nov", "dez",
];

const formataPeriodoPessoa = (di, df) => {
  if (!di || !df) return "";
  const partesDi = String(di).match(/^(\d{4})-(\d{2})-(\d{2})/);
  const partesDf = String(df).match(/^(\d{4})-(\d{2})-(\d{2})/);
  if (!partesDi || !partesDf) return `${di} a ${df}`;

  const diaI = parseInt(partesDi[3]);
  const mesI = parseInt(partesDi[2]) - 1;
  const anoI = parseInt(partesDi[1]);
  const diaF = parseInt(partesDf[3]);
  const mesF = parseInt(partesDf[2]) - 1;
  const anoF = parseInt(partesDf[1]);

  const dateI = new Date(anoI, mesI, diaI);
  const dateF = new Date(anoF, mesF, diaF);
  const dias = Math.round((dateF - dateI) / 86400000) + 1;

  const inicio = mesI === mesF && anoI === anoF
    ? `${diaI}`
    : `${diaI}/${mesesAbrev[mesI]}`;
  const fim = `${diaF}/${mesesAbrev[mesF]}`;

  return `de ${inicio} a ${fim} (${dias} dias)`;
};

const extrairErro = (error, fallback) => {
  const data = error.response?.data;
  if (!data) return fallback;
  if (data.errors) {
    const primeiro = Object.values(data.errors).flat()[0];
    if (primeiro) return primeiro;
  }
  return data.mensagem || data.message || fallback;
};

const tiposFixo = [
  { label: "Premio Meta", value: "PREMIO_META" },
  { label: "Alimentacao", value: "ALIMENTACAO" },
  { label: "Vale Transporte", value: "VALE_TRANSPORTE" },
  { label: "Outro", value: "OUTRO" },
];

// --- LISTA UNIFICADA (ranking + config) ---

const colaboradores = computed(() => {
  const pessoas = props.unidade.pessoas || [];
  const ranking = props.unidade.rankingprovisorio || [];
  const codpessoasConfig = new Set(pessoas.map((p) => p.codpessoa));

  const merged = pessoas.map((p) => {
    const rank = ranking.find((r) => r.codpessoa === p.codpessoa) || {};
    return {
      ...p,
      posicao: rank.posicao || null,
      totalvendas: rank.totalvendas || 0,
      cargo: rank.cargo || null,
      somenteRanking: false,
    };
  });

  ranking.forEach((r) => {
    if (!codpessoasConfig.has(r.codpessoa)) {
      merged.push({
        codpessoa: r.codpessoa,
        pessoa: r.pessoa,
        cargo: r.cargo,
        posicao: r.posicao,
        totalvendas: r.totalvendas || 0,
        somenteRanking: true,
      });
    }
  });

  return merged.sort((a, b) => {
    if (a.posicao && b.posicao) return a.posicao - b.posicao;
    if (a.posicao) return -1;
    if (b.posicao) return 1;
    return 0;
  });
});

// --- UNIDADE EDIT ---

const abrirEditarUnidade = () => {
  const u = props.unidade;
  modelUnidade.value = {
    valormeta: u.valormeta,
    valormetacaixa: u.valormetacaixa,
    valormetavendedor: u.valormetavendedor,
    valormetaxerox: u.valormetaxerox,
    percentualcomissaovendedor: u.percentualcomissaovendedor,
    percentualcomissaovendedormeta: u.percentualcomissaovendedormeta,
    percentualcomissaosubgerente: u.percentualcomissaosubgerente,
    percentualcomissaosubgerentemeta: u.percentualcomissaosubgerentemeta,
    percentualcomissaoxerox: u.percentualcomissaoxerox,
    premioprimeirovendedor: u.premioprimeirovendedor,
    premiosubgerentemeta: u.premiosubgerentemeta,
    premiometaxerox: u.premiometaxerox,
  };
  dialogUnidade.value = true;
};

const salvarUnidade = async () => {
  dialogUnidade.value = false;
  try {
    await sMeta.atualizarUnidade(
      props.codmeta,
      props.unidade.codunidadenegocio,
      modelUnidade.value
    );
    $q.notify({
      color: "green-5",
      textColor: "white",
      icon: "done",
      message: "Unidade atualizada",
    });
  } catch (error) {
    $q.notify({
      color: "red-5",
      textColor: "white",
      icon: "error",
      message: extrairErro(error, "Erro ao atualizar unidade"),
    });
  }
};

const removerUnidade = () => {
  $q.dialog({
    title: "Remover Unidade",
    message: `Deseja remover ${props.unidade.descricao}? Todas as pessoas e fixos desta unidade serao removidos.`,
    cancel: true,
  }).onOk(async () => {
    try {
      await sMeta.removerUnidade(
        props.codmeta,
        props.unidade.codunidadenegocio
      );
      $q.notify({
        color: "green-5",
        textColor: "white",
        icon: "done",
        message: "Unidade removida",
      });
    } catch (error) {
      $q.notify({
        color: "red-5",
        textColor: "white",
        icon: "error",
        message: extrairErro(error, "Erro ao remover unidade"),
      });
    }
  });
};

// --- PESSOA CRUD ---

const abrirAddPessoa = () => {
  isNovaPessoa.value = true;
  codunidadePessoa.value = props.unidade.codunidadenegocio;
  modelPessoa.value = {
    codpessoa: null,
    datainicial: dataISO(props.periodoinicial),
    datafinal: dataISO(props.periodofinal),
    percentualvenda: null,
    percentualcaixa: null,
    percentualsubgerente: null,
    percentualxerox: null,
  };
  dialogPessoa.value = true;
};

const abrirEditarPessoa = (pessoa) => {
  isNovaPessoa.value = false;
  idPessoaEditando.value = pessoa.codmetaunidadenegociopessoa;
  modelPessoa.value = {
    datainicial: pessoa.datainicial,
    datafinal: pessoa.datafinal,
    percentualvenda: pessoa.percentualvenda,
    percentualcaixa: pessoa.percentualcaixa,
    percentualsubgerente: pessoa.percentualsubgerente,
    percentualxerox: pessoa.percentualxerox,
  };
  dialogPessoa.value = true;
};

const salvarPessoa = async () => {
  dialogPessoa.value = false;
  try {
    if (isNovaPessoa.value) {
      await sMeta.criarPessoa(
        props.codmeta,
        codunidadePessoa.value,
        modelPessoa.value
      );
      $q.notify({
        color: "green-5",
        textColor: "white",
        icon: "done",
        message: "Colaborador adicionado",
      });
    } else {
      await sMeta.atualizarPessoa(
        props.codmeta,
        idPessoaEditando.value,
        modelPessoa.value
      );
      $q.notify({
        color: "green-5",
        textColor: "white",
        icon: "done",
        message: "Colaborador atualizado",
      });
    }
  } catch (error) {
    $q.notify({
      color: "red-5",
      textColor: "white",
      icon: "error",
      message: extrairErro(error, "Erro ao salvar colaborador"),
    });
  }
};

const removerPessoa = (pessoa) => {
  $q.dialog({
    title: "Remover Colaborador",
    message: `Deseja remover ${pessoa.pessoa || "este colaborador"}? Os fixos associados tambem serao removidos.`,
    cancel: true,
  }).onOk(async () => {
    try {
      await sMeta.removerPessoa(
        props.codmeta,
        pessoa.codmetaunidadenegociopessoa
      );
      $q.notify({
        color: "green-5",
        textColor: "white",
        icon: "done",
        message: "Colaborador removido",
      });
    } catch (error) {
      $q.notify({
        color: "red-5",
        textColor: "white",
        icon: "error",
        message: extrairErro(error, "Erro ao remover colaborador"),
      });
    }
  });
};

// --- FIXO CRUD ---

const abrirAddFixo = (pessoa) => {
  isNovoFixo.value = true;
  idPessoaFixo.value = pessoa.codmetaunidadenegociopessoa;
  modelFixo.value = {
    tipo: null,
    valor: null,
    quantidade: null,
    descricao: null,
    datainicial: dataISO(props.periodoinicial),
    datafinal: dataISO(props.periodofinal),
  };
  dialogFixo.value = true;
};

const abrirEditarFixo = (fixo) => {
  isNovoFixo.value = false;
  idFixoEditando.value = fixo.codmetaunidadenegociopessoafixo;
  modelFixo.value = {
    tipo: fixo.tipo,
    valor: fixo.valor,
    quantidade: fixo.quantidade,
    descricao: fixo.descricao,
    datainicial: fixo.datainicial,
    datafinal: fixo.datafinal,
  };
  dialogFixo.value = true;
};

const salvarFixo = async () => {
  dialogFixo.value = false;
  try {
    if (isNovoFixo.value) {
      await sMeta.criarFixo(
        props.codmeta,
        idPessoaFixo.value,
        modelFixo.value
      );
      $q.notify({
        color: "green-5",
        textColor: "white",
        icon: "done",
        message: "Fixo adicionado",
      });
    } else {
      await sMeta.atualizarFixo(
        props.codmeta,
        idFixoEditando.value,
        modelFixo.value
      );
      $q.notify({
        color: "green-5",
        textColor: "white",
        icon: "done",
        message: "Fixo atualizado",
      });
    }
  } catch (error) {
    $q.notify({
      color: "red-5",
      textColor: "white",
      icon: "error",
      message: extrairErro(error, "Erro ao salvar fixo"),
    });
  }
};

const removerFixo = (fixo) => {
  $q.dialog({
    title: "Remover Fixo",
    message: `Deseja remover este fixo (${fixo.tipo})?`,
    cancel: true,
  }).onOk(async () => {
    try {
      await sMeta.removerFixo(
        props.codmeta,
        fixo.codmetaunidadenegociopessoafixo
      );
      $q.notify({
        color: "green-5",
        textColor: "white",
        icon: "done",
        message: "Fixo removido",
      });
    } catch (error) {
      $q.notify({
        color: "red-5",
        textColor: "white",
        icon: "error",
        message: extrairErro(error, "Erro ao remover fixo"),
      });
    }
  });
};
</script>

<template>
  <!-- DIALOG EDITAR UNIDADE -->
  <q-dialog v-model="dialogUnidade">
    <q-card bordered flat style="width: 600px; max-width: 90vw">
      <q-card-section class="text-grey-9 text-overline">
        EDITAR UNIDADE
      </q-card-section>

      <q-form @submit="salvarUnidade()">
        <q-separator inset />

        <q-card-section>
          <div class="text-subtitle2 text-grey-8 q-mb-sm">Metas</div>
          <div class="row q-col-gutter-md">
            <div class="col-6">
              <q-input
                outlined
                v-model.number="modelUnidade.valormeta"
                label="Meta Geral"
                type="number"
                step="0.01"
                min="0"
                input-class="text-right"
              />
            </div>
            <div class="col-6">
              <q-input
                outlined
                v-model.number="modelUnidade.valormetacaixa"
                label="Meta Caixa"
                type="number"
                step="0.01"
                min="0"
                input-class="text-right"
              />
            </div>
            <div class="col-6">
              <q-input
                outlined
                v-model.number="modelUnidade.valormetavendedor"
                label="Meta Vendedor"
                type="number"
                step="0.01"
                min="0"
                input-class="text-right"
              />
            </div>
            <div class="col-6">
              <q-input
                outlined
                v-model.number="modelUnidade.valormetaxerox"
                label="Meta Xerox"
                type="number"
                step="0.01"
                min="0"
                input-class="text-right"
              />
            </div>
          </div>

          <div class="text-subtitle2 text-grey-8 q-mt-md q-mb-sm">
            Bonificacoes (%)
          </div>
          <div class="row q-col-gutter-md">
            <div class="col-6">
              <q-input
                outlined
                v-model.number="modelUnidade.percentualcomissaovendedor"
                label="Vendedor"
                type="number"
                step="0.01"
                min="0"
                input-class="text-right"
              />
            </div>
            <div class="col-6">
              <q-input
                outlined
                v-model.number="modelUnidade.percentualcomissaovendedormeta"
                label="Vendedor (meta)"
                type="number"
                step="0.01"
                min="0"
                input-class="text-right"
              />
            </div>
            <div class="col-6">
              <q-input
                outlined
                v-model.number="modelUnidade.percentualcomissaosubgerente"
                label="Subgerente"
                type="number"
                step="0.01"
                min="0"
                input-class="text-right"
              />
            </div>
            <div class="col-6">
              <q-input
                outlined
                v-model.number="modelUnidade.percentualcomissaosubgerentemeta"
                label="Subgerente (meta)"
                type="number"
                step="0.01"
                min="0"
                input-class="text-right"
              />
            </div>
            <div class="col-6">
              <q-input
                outlined
                v-model.number="modelUnidade.percentualcomissaoxerox"
                label="Xerox"
                type="number"
                step="0.01"
                min="0"
                input-class="text-right"
              />
            </div>
          </div>

          <div class="text-subtitle2 text-grey-8 q-mt-md q-mb-sm">
            Premios (R$)
          </div>
          <div class="row q-col-gutter-md">
            <div class="col-6">
              <q-input
                outlined
                v-model.number="modelUnidade.premioprimeirovendedor"
                label="1o Vendedor"
                type="number"
                step="0.01"
                min="0"
                input-class="text-right"
              />
            </div>
            <div class="col-6">
              <q-input
                outlined
                v-model.number="modelUnidade.premiosubgerentemeta"
                label="Subgerente Meta"
                type="number"
                step="0.01"
                min="0"
                input-class="text-right"
              />
            </div>
            <div class="col-6">
              <q-input
                outlined
                v-model.number="modelUnidade.premiometaxerox"
                label="Meta Xerox"
                type="number"
                step="0.01"
                min="0"
                input-class="text-right"
              />
            </div>
          </div>
        </q-card-section>

        <q-separator inset />

        <q-card-actions align="right" class="text-primary">
          <q-btn
            flat
            label="Cancelar"
            v-close-popup
            tabindex="-1"
            color="grey-8"
          />
          <q-btn flat label="Salvar" type="submit" />
        </q-card-actions>
      </q-form>
    </q-card>
  </q-dialog>

  <!-- DIALOG PESSOA -->
  <q-dialog v-model="dialogPessoa">
    <q-card bordered flat style="width: 500px; max-width: 90vw">
      <q-card-section class="text-grey-9 text-overline">
        <template v-if="isNovaPessoa">ADICIONAR COLABORADOR</template>
        <template v-else>EDITAR COLABORADOR</template>
      </q-card-section>

      <q-form @submit="salvarPessoa()">
        <q-separator inset />

        <q-card-section>
          <div class="row q-col-gutter-md">
            <div class="col-12" v-if="isNovaPessoa">
              <SelectPessoa
                v-model="modelPessoa.codpessoa"
                outlined
                label="Colaborador"
                :rules="[(v) => !!v || 'Obrigatorio']"
              />
            </div>
            <div class="col-6">
              <q-input
                outlined
                v-model="modelPessoa.datainicial"
                label="Data Inicial"
                type="date"
                :rules="[(v) => !!v || 'Obrigatorio']"
              />
            </div>
            <div class="col-6">
              <q-input
                outlined
                v-model="modelPessoa.datafinal"
                label="Data Final"
                type="date"
                :rules="[(v) => !!v || 'Obrigatorio']"
              />
            </div>
            <div class="col-6">
              <q-input
                outlined
                v-model.number="modelPessoa.percentualvenda"
                label="% Venda"
                type="number"
                step="0.01"
                min="0"
                input-class="text-right"
              />
            </div>
            <div class="col-6">
              <q-input
                outlined
                v-model.number="modelPessoa.percentualcaixa"
                label="% Caixa"
                type="number"
                step="0.01"
                min="0"
                input-class="text-right"
              />
            </div>
            <div class="col-6">
              <q-input
                outlined
                v-model.number="modelPessoa.percentualsubgerente"
                label="% Subgerente"
                type="number"
                step="0.01"
                min="0"
                input-class="text-right"
              />
            </div>
            <div class="col-6">
              <q-input
                outlined
                v-model.number="modelPessoa.percentualxerox"
                label="% Xerox"
                type="number"
                step="0.01"
                min="0"
                input-class="text-right"
              />
            </div>
          </div>
        </q-card-section>

        <q-separator inset />

        <q-card-actions align="right" class="text-primary">
          <q-btn
            flat
            label="Cancelar"
            v-close-popup
            tabindex="-1"
            color="grey-8"
          />
          <q-btn flat label="Salvar" type="submit" />
        </q-card-actions>
      </q-form>
    </q-card>
  </q-dialog>

  <!-- DIALOG FIXO -->
  <q-dialog v-model="dialogFixo">
    <q-card bordered flat style="width: 500px; max-width: 90vw">
      <q-card-section class="text-grey-9 text-overline">
        <template v-if="isNovoFixo">ADICIONAR FIXO</template>
        <template v-else>EDITAR FIXO</template>
      </q-card-section>

      <q-form @submit="salvarFixo()">
        <q-separator inset />

        <q-card-section>
          <div class="row q-col-gutter-md">
            <div class="col-12">
              <q-select
                outlined
                v-model="modelFixo.tipo"
                :options="tiposFixo"
                option-value="value"
                option-label="label"
                emit-value
                map-options
                label="Tipo"
                :rules="[(v) => !!v || 'Obrigatorio']"
              />
            </div>
            <div class="col-6">
              <q-input
                outlined
                v-model.number="modelFixo.valor"
                label="Valor"
                type="number"
                step="0.01"
                min="0"
                input-class="text-right"
              />
            </div>
            <div class="col-6">
              <q-input
                outlined
                v-model.number="modelFixo.quantidade"
                label="Quantidade"
                type="number"
                step="1"
                min="0"
                input-class="text-right"
              />
            </div>
            <div class="col-12">
              <q-input
                outlined
                v-model="modelFixo.descricao"
                label="Descricao"
              />
            </div>
            <div class="col-6">
              <q-input
                outlined
                v-model="modelFixo.datainicial"
                label="Data Inicial"
                type="date"
              />
            </div>
            <div class="col-6">
              <q-input
                outlined
                v-model="modelFixo.datafinal"
                label="Data Final"
                type="date"
              />
            </div>
          </div>
        </q-card-section>

        <q-separator inset />

        <q-card-actions align="right" class="text-primary">
          <q-btn
            flat
            label="Cancelar"
            v-close-popup
            tabindex="-1"
            color="grey-8"
          />
          <q-btn flat label="Salvar" type="submit" />
        </q-card-actions>
      </q-form>
    </q-card>
  </q-dialog>

  <!-- CARD UNIDADE -->
  <q-card bordered flat class="q-mb-md">
    <q-card-section class="text-grey-9 text-overline row items-center">
      <router-link
        :to="{
          name: 'metaUnidadeDashboard',
          params: {
            codmeta: codmeta,
            codunidadenegocio: unidade.codunidadenegocio,
          },
        }"
        class="text-grey-9"
        style="text-decoration: none"
      >
        {{ unidade.descricao }}
      </router-link>
      <q-space />
      <template v-if="podeEditar">
        <q-btn
          flat
          dense
          round
          icon="settings"
          size="sm"
          color="grey-7"
          @click="abrirEditarUnidade()"
        >
          <q-tooltip>Configurar Unidade</q-tooltip>
        </q-btn>
        <q-btn
          flat
          dense
          round
          icon="delete"
          size="sm"
          color="grey-7"
          @click="removerUnidade()"
        >
          <q-tooltip>Remover Unidade</q-tooltip>
        </q-btn>
      </template>
    </q-card-section>

    <q-separator inset />

    <!-- STATS -->
    <q-card-section>
      <div class="row q-col-gutter-md">
        <div class="col-xs-6 col-sm-3">
          <div class="text-caption text-grey">Vendas</div>
          <div class="text-subtitle1">
            {{ formataMoeda(unidade.totalvendas) }}
          </div>
        </div>
        <div class="col-xs-6 col-sm-3">
          <div class="text-caption text-grey">Meta</div>
          <div class="text-subtitle1">
            {{ formataMoeda(unidade.valormeta) }}
          </div>
        </div>
        <div class="col-xs-6 col-sm-3">
          <div class="text-caption text-grey">Atingido</div>
          <div class="text-subtitle1">
            {{ formataPercentual(unidade.percentualatingimento) }}
          </div>
        </div>
        <div class="col-xs-6 col-sm-3">
          <q-linear-progress
            :value="
              Math.min(
                (unidade.percentualatingimento || 0) / 100,
                1
              )
            "
            size="8px"
            stripe
            rounded
            class="q-mt-md"
            :color="corProgresso(unidade.percentualatingimento)"
          />
        </div>
      </div>
    </q-card-section>

    <!-- COLABORADORES -->
    <q-separator />
    <q-card-section
      class="text-grey-7 text-overline row items-center"
    >
      COLABORADORES
      <q-space />
      <q-btn
        v-if="podeEditar"
        flat
        round
        dense
        icon="add"
        size="sm"
        color="primary"
        @click="abrirAddPessoa()"
      >
        <q-tooltip>Adicionar Colaborador</q-tooltip>
      </q-btn>
    </q-card-section>

    <q-list separator v-if="colaboradores.length > 0">
      <template
        v-for="pessoa in colaboradores"
        :key="pessoa.codmetaunidadenegociopessoa || `rank-${pessoa.codpessoa}`"
      >
        <q-item>
          <q-item-section avatar style="min-width: 40px">
            <q-badge
              v-if="pessoa.posicao"
              :color="corPosicao(pessoa.posicao)"
              text-color="white"
              :label="pessoa.posicao"
              rounded
            />
            <q-badge
              v-else-if="siglaFuncao(pessoa)"
              color="grey-4"
              text-color="white"
              :label="siglaFuncao(pessoa)"
              rounded
            />
          </q-item-section>
          <q-item-section>
            <q-item-label>{{
              pessoa.pessoa || pessoa.codpessoa
            }}</q-item-label>
            <q-item-label
              caption
              v-if="pessoa.datainicial && pessoa.datafinal"
            >
              {{ formataPeriodoPessoa(pessoa.datainicial, pessoa.datafinal) }}
            </q-item-label>
            <q-item-label
              caption
              v-if="
                podeEditar && !pessoa.somenteRanking && (
                  pessoa.percentualvenda ||
                  pessoa.percentualcaixa ||
                  pessoa.percentualsubgerente ||
                  pessoa.percentualxerox
                )
              "
            >
              <template v-if="pessoa.percentualvenda"
                >Venda: {{ pessoa.percentualvenda }}%</template
              >
              <template v-if="pessoa.percentualcaixa">
                | Caixa: {{ pessoa.percentualcaixa }}%</template
              >
              <template v-if="pessoa.percentualsubgerente">
                | Subg:
                {{ pessoa.percentualsubgerente }}%</template
              >
              <template v-if="pessoa.percentualxerox">
                | Xerox: {{ pessoa.percentualxerox }}%</template
              >
            </q-item-label>
          </q-item-section>
          <q-item-section side v-if="pessoa.totalvendas">
            <q-item-label>
              {{ formataMoeda(pessoa.totalvendas) }}
            </q-item-label>
          </q-item-section>
          <q-item-section side>
            <q-item-label caption>
              <q-btn
                flat
                dense
                round
                icon="visibility"
                size="sm"
                color="grey-7"
                :to="{
                  name: 'metaDashboardColaborador',
                  params: {
                    codmeta: codmeta,
                    codpessoa: pessoa.codpessoa,
                  },
                }"
              >
                <q-tooltip>Ver Detalhes</q-tooltip>
              </q-btn>
              <template v-if="podeEditar && !pessoa.somenteRanking">
                <q-btn
                  flat
                  dense
                  round
                  icon="edit"
                  size="sm"
                  color="grey-7"
                  @click="abrirEditarPessoa(pessoa)"
                >
                  <q-tooltip>Editar</q-tooltip>
                </q-btn>
                <q-btn
                  flat
                  dense
                  round
                  icon="delete"
                  size="sm"
                  color="grey-7"
                  @click="removerPessoa(pessoa)"
                >
                  <q-tooltip>Remover</q-tooltip>
                </q-btn>
                <q-btn
                  flat
                  round
                  dense
                  icon="add"
                  size="sm"
                  color="primary"
                  @click="abrirAddFixo(pessoa)"
                >
                  <q-tooltip>Adicionar Fixo</q-tooltip>
                </q-btn>
              </template>
            </q-item-label>
          </q-item-section>
        </q-item>

        <!-- FIXOS DA PESSOA -->
        <template v-if="podeEditar && !pessoa.somenteRanking">
          <q-item
            v-for="fixo in pessoa.fixos"
            :key="fixo.codmetaunidadenegociopessoafixo"
            class="q-pl-xl"
          >
            <q-item-section avatar style="min-width: 30px">
              <q-icon
                name="attach_money"
                color="grey-6"
                size="xs"
              />
            </q-item-section>
            <q-item-section>
              <q-item-label caption>
                {{ fixo.tipo }}
                <template v-if="fixo.valor">
                  — {{ formataMoeda(fixo.valor) }}</template
                >
                <template v-if="fixo.quantidade">
                  x {{ fixo.quantidade }}</template
                >
                <template v-if="fixo.descricao">
                  — {{ fixo.descricao }}</template
                >
              </q-item-label>
            </q-item-section>
            <q-item-section side>
              <q-item-label caption>
                <q-btn
                  flat
                  dense
                  round
                  icon="edit"
                  size="sm"
                  color="grey-7"
                  @click="abrirEditarFixo(fixo)"
                >
                  <q-tooltip>Editar Fixo</q-tooltip>
                </q-btn>
                <q-btn
                  flat
                  dense
                  round
                  icon="delete"
                  size="sm"
                  color="grey-7"
                  @click="removerFixo(fixo)"
                >
                  <q-tooltip>Remover Fixo</q-tooltip>
                </q-btn>
              </q-item-label>
            </q-item-section>
          </q-item>
        </template>
      </template>
    </q-list>
    <div
      v-else
      class="q-pa-sm q-pl-md text-caption text-grey"
    >
      Nenhum colaborador cadastrado
    </div>
  </q-card>
</template>
