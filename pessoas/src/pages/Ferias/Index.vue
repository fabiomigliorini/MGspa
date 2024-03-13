<template>
  <MGLayout>
    <template #tituloPagina> Férias </template>
    <template #content v-if="user.verificaPermissaoUsuario('Recursos Humanos')">
      <div class="q-pa-md">
        <q-card bordered>
          <q-table title="Programação de férias" :filter="filter" :rows="ferias" :columns="columns"
            no-data-label="Nenhuma programação de férias encontrada" :separator="separator" emit-value
            :pagination="{ rowsPerPage: 100 }">
            <template v-slot:top-right>
              <q-btn flat color="primary" icon="chevron_left" @click="filtroAno(-1)" />
              {{ ano }}
              <q-btn flat color="primary" icon="chevron_right" @click="filtroAno(+1)" />

              <q-input v-if="show_filter" outlined dense debounce="300" class="q-pa-sm" v-model="filter"
                placeholder="Pesquisar">
                <template v-slot:append>
                  <q-icon name="search" />
                </template>
              </q-input>
              <q-btn class="q-ml-sm" icon="filter_list" @click="show_filter = !show_filter" flat />
            </template>

            <template v-slot:body="ferias">

              <q-tr :props="ferias">
                <q-td key="filial" :props="ferias">
                  {{ ferias.row.filial }}
                </q-td>
                <q-td key="cargo" :props="ferias">
                  {{ ferias.row.cargo }}
                </q-td>

                <q-td key="fantasia" :props="ferias" @click="linkPessoa(ferias.row.codpessoa)" class="cursor-pointer">
                  {{ ferias.row.fantasia }}
                </q-td>

                <q-td :key="this.ano == moment().year() ? 'janeiro' : 'dezembro'"
                  :colspan="this.ano == moment().year() ? '14' : '14'" :props="ferias">
                  <div v-for="f in ferias.row.ferias" v-bind:key="f.codferias">
                    <q-range :model-value="f.range" @update:modelValue="(value) => { alteraRange(value, f) }" :min="1"
                      :max="max" ref="range" drag-only-range :disable="!f.prevista"
                      :color="f.prevista ? 'blue' : 'green'" />


                  </div>
                </q-td>
                <q-td key="data" :props="ferias">
                  <div v-for="feriasS in ferias.row.ferias" v-bind:key="feriasS.codferias">
                    {{ moment(feriasS.gozoinicio).format('ddd, D/MMM') }} <br>
                    a
                    {{ moment(feriasS.gozofim).format('D/MMM/YYYY') }}
                  </div>
                </q-td>
              </q-tr>
            </template>
          </q-table>
        </q-card>
      </div>

      <div class="text-right q-pr-md">
        <q-btn label="Salvar" @click="submit()" color="primary" />
      </div>
    </template>
    <!-- Template Não Autorizado -->
    <template #content v-else>
      <nao-autorizado></nao-autorizado>
    </template>
  </MGLayout>
</template>

<script>
import { ref, defineAsyncComponent } from "vue";
import moment from "moment";
import { pessoaStore } from "src/stores/pessoa";
import { formataDocumetos } from "src/stores/formataDocumentos";
import { useQuasar } from "quasar";
import { guardaToken } from "src/stores";
import { useRoute, useRouter } from "vue-router";
import { colaboradorStore } from "src/stores/colaborador";

export default {
  components: {
    MGLayout: defineAsyncComponent(() => import("layouts/MGLayout.vue")),
    NaoAutorizado: defineAsyncComponent(() =>
      import("components/NaoAutorizado.vue")
    ),
  },

  methods: {
    alteraRange(range, ferias) {
      ferias.range = range;
      ferias.diagozoinicio = range.min;
      ferias.diagozofim = range.max;
      const gozoinicio = moment().startOf('year').add(range.min - 1, 'days');
      const gozofim = moment().startOf('year').add(range.max - 1, 'days');
      ferias.gozoinicio = gozoinicio.format('YYYY-MM-DD');
      ferias.gozofim = gozofim.format('YYYY-MM-DD');
    },

    async filtroAno(anoFiltro) {
      var anoAtual = moment().year();

      if (anoFiltro == -1) {
        this.ano = this.ano - 1;
      }

      if (anoFiltro == 1) {
        this.ano = (parseInt(this.ano) + parseInt(1));
      }


      try {
        const ret = await this.atualizaAno(this.ano);
        if (ret.data.length == 0) {
          this.$q.notify({
            color: "red-5",
            textColor: "white",
            icon: "error",
            message: "Nenhuma programação de férias encontrada!",
          });
        }
      } catch (error) {
        console.log(error);
      }
    },

    async submit() {
      let putFerias = []

      this.ferias.forEach(el => {
        if (el.ferias.length > 0) {
          putFerias.push(el.ferias)
        }
      });

      this.$q.dialog({
        title: 'Salvar Férias',
        message: 'Tem certeza que deseja salvar todas as férias ?',
        cancel: true,
        persistent: true
      }).onOk(async () => {
        try {
          const ret = await this.sColaborador.atualizaTodasFerias(putFerias)
          if (ret.data) {
            this.$q.notify({
              color: 'green-5',
              textColor: 'white',
              icon: 'done',
              message: 'Salvo!'
            })
          }
        } catch (error) {
          console.log(error)
          this.$q.notify({
            color: 'green-5',
            textColor: 'white',
            icon: 'done',
            message: error.response.data.message
          })
        }
      })
    },

    async atualizaAno(ano) {
      if (ano == null) {
        ano = this.route.params.ano;
      }
      const ret = await this.sPessoa.programacaoFerias(ano);
      ret.data.forEach((colab) => {
        colab.ferias.forEach((ferias) => {
          ferias.range = {
            min: ferias.diagozoinicio,
            max: ferias.diagozofim,
          };

          if (ferias.diagozoinicio > 335 && ferias.diagozofim < 31) {
            ferias.range.max = parseInt(397) - parseInt(31) + parseInt(ferias.diagozofim)
          }

          if (this.ano !== moment().year() && ferias.diagozoinicio > 335 && ferias.diagozofim < 31) {
            ferias.range.min = parseInt(397) - parseInt(62) - parseInt(ferias.diagozoinicio)
            ferias.range.max = parseInt(397) - parseInt(ferias.range.max)
          }
        });
      });
      this.ferias = ret.data;
      this.router.push("/ferias/" + ano);
      this.ano = ano;
      return ret;
    },

    linkPessoa(codpessoa) {

      var a = document.createElement('a');
      a.target = "_blank";
      a.href = "/#/pessoa/" + codpessoa
      a.click();
    },

  },

  setup(props, ctx) {
    const modelRange = ref({
      min: 0,
      max: 0,
    });

    const sPessoa = pessoaStore();
    const sColaborador = colaboradorStore()
    const ferias = ref([]);
    const Documentos = formataDocumetos();
    const $q = useQuasar();
    const max = ref([]);
    const ano = ref([]);
    const user = guardaToken();
    const filter = ref("");
    const show_filter = ref(true);
    const separator = ref("cell");
    const range = ref(null);
    const route = useRoute();
    const router = useRouter();



    const columns = [
      {
        name: "filial",
        label: "Filial",
        field: "filial",
        align: "top-left",
      },
      {
        name: "cargo",
        label: "Cargo",
        field: "cargo",
        align: "top-left",
      },
      {
        name: "fantasia",
        label: "Nome",
        field: "fantasia",
        align: "top-left",
      },

      {
        name: "dezembro",
        label: "Dez",
        field: "dezembro",
        align: "top-left",
      },

      {
        name: "janeiro",
        label: "Jan",
        field: "janeiro",
        align: "top-left",
      },
      {
        name: "Fevereiro",
        label: "Fev",
        field: "fevereiro",
        align: "top-left",
      },
      {
        name: "Março",
        label: "Mar",
        field: "marco",
        align: "top-left",
      },
      {
        name: "Abril",
        label: "Abr",
        field: "abril",
        align: "top-left",
      },
      {
        name: "Maio",
        label: "Mai",
        field: "maio",
        align: "top-left",
      },
      {
        name: "Junho",
        label: "Jun",
        field: "junho",
        align: "top-left",
      },
      {
        name: "Julho",
        label: "Jul",
        field: "julho",
        align: "top-left",
      },
      {
        name: "Agosto",
        label: "Ago",
        field: "agosto",
        align: "top-left",
      },
      {
        name: "Setembro",
        label: "Set",
        field: "setembro",
        align: "top-left",
      },
      {
        name: "Outubro",
        label: "Out",
        field: "outubro",
        align: "top-left",
      },
      {
        name: "Novembro",
        label: "Nov",
        field: "novembro",
        align: "top-left",
      },
      {
        name: "Dezembro",
        label: "Dez",
        field: "dezembro",
        align: "top-left",
      },
      {
        name: "Janeiro",
        label: "Jan",
        field: "janeiro",
        align: "top-left",
      },
      {
        name: "data",
        label: "Data",
        field: "data",
        align: "top-left",
      },
    ];

    return {
      moment,
      modelRange,
      sPessoa,
      ferias,
      Documentos,
      max,
      ano,
      user,
      filter,
      show_filter,
      separator,
      columns,
      sColaborador,
      range,
      route,
      router,
    };
  },
  async mounted() {

    var year = moment().year()
    this.ano = year;
    this.atualizaAno();
    var bissexto = moment([year]).isLeapYear();
    if (bissexto) {
      this.max = 397; // dias totais do ano todo + o mes de janeiro
    } else {
      this.max = 396;
    }

  },
};
</script>
