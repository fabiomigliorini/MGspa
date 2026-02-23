<script setup>
import { ref, computed } from "vue";
import { useQuasar } from "quasar";
import { useRoute } from "vue-router";
import { rhStore } from "src/stores/rh";
import { guardaToken } from "src/stores";

const $q = useQuasar();
const route = useRoute();
const sRh = rhStore();
const user = guardaToken();

const podeEditar = computed(
  () => user.verificaPermissaoUsuario("Recursos Humanos")
);

const dash = computed(() => sRh.dashboard || {});
const periodo = computed(() => dash.value.periodo || {});
const unidades = computed(() => dash.value.unidades || []);
const alertas = computed(() => dash.value.alertas || []);

const grupos = computed(() => {
  const mapa = {};
  for (const u of unidades.value) {
    const key = u.codempresa || 0;
    if (!mapa[key]) {
      mapa[key] = { codempresa: u.codempresa, empresa: u.empresa || "Sem empresa", unidades: [] };
    }
    mapa[key].unidades.push(u);
  }
  const result = Object.values(mapa);
  for (const g of result) {
    g.unidades.sort((a, b) => {
      const fa = a.codfilial || 0;
      const fb = b.codfilial || 0;
      if (fa !== fb) return fa - fb;
      return (a.descricao || "").localeCompare(b.descricao || "");
    });
  }
  return result;
});

const somaGrupo = (grupo) => {
  const r = { vendas: 0, totalsalario: 0, totaladicional: 0, totalencargos: 0, totalvariaveis: 0, total: 0 };
  for (const u of grupo.unidades) {
    r.vendas += parseFloat(u.vendas) || 0;
    r.totalsalario += parseFloat(u.totalsalario) || 0;
    r.totaladicional += parseFloat(u.totaladicional) || 0;
    r.totalencargos += parseFloat(u.totalencargos) || 0;
    r.totalvariaveis += parseFloat(u.totalvariaveis) || 0;
    r.total += parseFloat(u.total) || 0;
  }
  return r;
};

const totalVendas = computed(() =>
  unidades.value.reduce((s, u) => s + (parseFloat(u.vendas) || 0), 0)
);

const formataMoeda = (valor) => {
  return new Intl.NumberFormat("pt-BR", {
    style: "currency",
    currency: "BRL",
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
  }).format(parseFloat(valor) || 0);
};

const formataPercentual = (valor) => {
  if (valor == null) return "—";
  return (
    new Intl.NumberFormat("pt-BR", {
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

const corAlerta = (tipo) => {
  if (tipo === "sem_setor") return "bg-red-1";
  if (tipo === "sem_meta") return "bg-orange-1";
  return "bg-yellow-1";
};

const iconeAlerta = (tipo) => {
  if (tipo === "sem_setor") return "error";
  if (tipo === "sem_meta") return "warning";
  return "info";
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

// --- DIALOG EDITAR META UNIDADE ---

const dialogMeta = ref(false);
const modelMeta = ref({ codindicador: null, meta: null });

const editarMeta = (u) => {
  if (!u.codindicador) return;
  modelMeta.value = { codindicador: u.codindicador, meta: u.meta };
  dialogMeta.value = true;
};

const salvarMeta = async () => {
  dialogMeta.value = false;
  try {
    await sRh.atualizarMeta(modelMeta.value.codindicador, {
      meta: modelMeta.value.meta,
    });
    $q.notify({
      color: "green-5",
      textColor: "white",
      icon: "done",
      message: "Meta atualizada",
    });
    await sRh.getDashboard(route.params.codperiodo);
  } catch (error) {
    $q.notify({
      color: "red-5",
      textColor: "white",
      icon: "error",
      message: extrairErro(error, "Erro ao atualizar meta"),
    });
  }
};
</script>

<template>
  <!-- DIALOG EDITAR META -->
  <q-dialog v-model="dialogMeta">
    <q-card bordered flat style="width: 400px; max-width: 90vw">
      <q-form @submit="salvarMeta()">
        <q-card-section class="text-grey-9 text-overline">
          EDITAR META
        </q-card-section>

        <q-separator inset />

        <q-card-section>
          <q-input
            outlined
            v-model.number="modelMeta.meta"
            label="Meta (R$)"
            type="number"
            step="0.01"
            autofocus
          />
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

  <!-- TABELAS POR EMPRESA -->
  <template v-for="grupo in grupos" :key="grupo.codempresa">
    <q-card bordered flat class="q-mb-md">
      <q-card-section class="text-grey-9 text-overline row items-center">
        {{ grupo.empresa }}
      </q-card-section>

      <q-markup-table flat separator="horizontal" style="table-layout: fixed">
        <colgroup>
          <col style="width: 14%" />
          <col style="width: 10%" />
          <col style="width: 10%" />
          <col style="width: 6%" />
          <col style="width: 10%" />
          <col style="width: 9%" />
          <col style="width: 9%" />
          <col style="width: 9%" />
          <col style="width: 9%" />
          <col style="width: 9%" />
          <col style="width: 5%" />
        </colgroup>
        <thead>
          <tr class="text-left">
            <th>Unidade</th>
            <th class="text-right">Vendas</th>
            <th class="text-right">Meta</th>
            <th class="text-right">Ating.</th>
            <th>Progresso</th>
            <th class="text-right">Salário</th>
            <th class="text-right">Adicional</th>
            <th class="text-right">Encargos</th>
            <th class="text-right">Variáveis</th>
            <th class="text-right">Total</th>
            <th class="text-right">% Vendas</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="u in grupo.unidades" :key="u.codunidadenegocio">
            <td class="text-weight-medium">{{ u.descricao }}</td>
            <td class="text-right">{{ formataMoeda(u.vendas) }}</td>
            <td class="text-right">
              <template v-if="u.meta">{{ formataMoeda(u.meta) }}</template>
              <template v-else>—</template>
              <q-btn
                v-if="podeEditar && u.codindicador && periodo.status === 'A'"
                flat
                dense
                round
                icon="edit"
                size="xs"
                color="grey-7"
                @click="editarMeta(u)"
              >
                <q-tooltip>Editar Meta</q-tooltip>
              </q-btn>
            </td>
            <td class="text-right">
              {{
                u.meta
                  ? formataPercentual((u.vendas / u.meta) * 100)
                  : "—"
              }}
            </td>
            <td>
              <q-linear-progress
                v-if="u.meta"
                :value="Math.min((u.vendas / u.meta) || 0, 1)"
                size="8px"
                stripe
                rounded
                :color="corProgresso(u.meta ? (u.vendas / u.meta) * 100 : 0)"
              />
              <span v-else class="text-grey">—</span>
            </td>
            <td class="text-right">{{ formataMoeda(u.totalsalario) }}</td>
            <td class="text-right">{{ formataMoeda(u.totaladicional) }}</td>
            <td class="text-right">{{ formataMoeda(u.totalencargos) }}</td>
            <td class="text-right">{{ formataMoeda(u.totalvariaveis) }}</td>
            <td class="text-right text-weight-medium">
              {{ formataMoeda(u.total) }}
            </td>
            <td class="text-right text-weight-medium">
              {{ u.vendas ? formataPercentual((u.total / u.vendas) * 100) : "—" }}
            </td>
          </tr>
        </tbody>
        <tfoot v-if="grupo.unidades.length > 1">
          <tr class="text-weight-bold">
            <td>Subtotal</td>
            <td class="text-right">{{ formataMoeda(somaGrupo(grupo).vendas) }}</td>
            <td></td>
            <td></td>
            <td></td>
            <td class="text-right">{{ formataMoeda(somaGrupo(grupo).totalsalario) }}</td>
            <td class="text-right">{{ formataMoeda(somaGrupo(grupo).totaladicional) }}</td>
            <td class="text-right">{{ formataMoeda(somaGrupo(grupo).totalencargos) }}</td>
            <td class="text-right">{{ formataMoeda(somaGrupo(grupo).totalvariaveis) }}</td>
            <td class="text-right">{{ formataMoeda(somaGrupo(grupo).total) }}</td>
            <td class="text-right">
              {{ somaGrupo(grupo).vendas ? formataPercentual((somaGrupo(grupo).total / somaGrupo(grupo).vendas) * 100) : "—" }}
            </td>
          </tr>
        </tfoot>
      </q-markup-table>
    </q-card>
  </template>

  <!-- TOTAL GERAL -->
  <q-card bordered flat class="q-mb-md" v-if="grupos.length > 1">
    <q-markup-table flat separator="horizontal" style="table-layout: fixed">
      <colgroup>
        <col style="width: 14%" />
        <col style="width: 10%" />
        <col style="width: 10%" />
        <col style="width: 6%" />
        <col style="width: 10%" />
        <col style="width: 9%" />
        <col style="width: 9%" />
        <col style="width: 9%" />
        <col style="width: 9%" />
        <col style="width: 9%" />
        <col style="width: 5%" />
      </colgroup>
      <tbody>
        <tr class="text-weight-bold">
          <td>Total Geral</td>
          <td class="text-right">{{ formataMoeda(totalVendas) }}</td>
          <td></td>
          <td></td>
          <td></td>
          <td class="text-right">{{ formataMoeda(dash.totalsalario) }}</td>
          <td class="text-right">{{ formataMoeda(dash.totaladicional) }}</td>
          <td class="text-right">{{ formataMoeda(dash.totalencargos) }}</td>
          <td class="text-right">{{ formataMoeda(dash.totalvariaveis) }}</td>
          <td class="text-right">{{ formataMoeda(dash.total) }}</td>
          <td class="text-right">
            {{ totalVendas ? formataPercentual((dash.total / totalVendas) * 100) : "—" }}
          </td>
        </tr>
      </tbody>
    </q-markup-table>
  </q-card>

  <!-- ALERTAS -->
  <template v-if="alertas.length > 0">
    <q-card
      v-for="(alerta, i) in alertas"
      :key="i"
      bordered
      flat
      class="q-mb-sm"
      :class="corAlerta(alerta.tipo)"
    >
      <q-card-section class="row items-center q-py-sm">
        <q-icon
          :name="iconeAlerta(alerta.tipo)"
          :color="alerta.tipo === 'sem_setor' ? 'red' : 'orange'"
          size="sm"
          class="q-mr-sm"
        />
        <span class="text-body2">
          <router-link
            v-if="alerta.codperiodocolaborador"
            :to="{
              name: 'rhColaboradorDetalhe',
              params: {
                codperiodo: route.params.codperiodo,
                codperiodocolaborador: alerta.codperiodocolaborador,
              },
            }"
            class="text-primary"
          >
            {{ alerta.mensagem }}
          </router-link>
          <template v-else>{{ alerta.mensagem }}</template>
        </span>
      </q-card-section>
    </q-card>
  </template>
</template>
