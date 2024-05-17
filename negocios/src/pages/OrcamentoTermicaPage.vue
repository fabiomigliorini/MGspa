<script setup>
import { onMounted } from "vue";
import { useRoute } from "vue-router";
import { negocioStore } from "src/stores/negocio";
import { formataCnpjCpf } from "../utils/formatador.js";
import { produtoStore } from "src/stores/produto";
import BarCode from "components/BarCode.vue";
import moment from "moment/min/moment-with-locales";
moment.locale("pt-br");

const route = useRoute();
const sNegocio = negocioStore();
const sProduto = produtoStore();

onMounted(() => {
  const ret = sNegocio.carregarPeloUuid(route.params.uuid);
});
</script>
<template>
  <template v-if="sNegocio.negocio">
    <div class="cabecalho">
      Orçamento
      <div id="logoOrcamento">
        <img src="MGPapelaria.jpg" />
      </div>
    </div>
    <div class="conteudo">
      <table class="relatorio">
        <tr>
          <td>
            <span class="row">
              <template v-if="sNegocio.negocio.codnegocio">
                #{{ String(sNegocio.negocio.codnegocio).padStart(8, "0") }}
              </template>
              <template v-else>
                {{ sNegocio.negocio.uuid }}
              </template>
              -
              {{ sNegocio.negocio.estoquelocal }}
            </span>
            <span class="row" v-if="sNegocio.negocio.fantasiavendedor">
              Vendedor: {{ sNegocio.negocio.fantasiavendedor }}
            </span>
            {{
              moment(sNegocio.negocio.lancamento).format("DD/MM/YYYY HH:mm:SS")
            }}
          </td>
        </tr>
      </table>
      <hr />
      <table class="relatorio">
        <tr>
          <td class="text-bold" style="font-size: 3em">
            {{ sNegocio.negocio.fantasia }}
          </td>
        </tr>
        <tr>
          <td v-if="sNegocio.negocio.Pessoa.codpessoa != 1">
            <span class="row">
              #{{ String(sNegocio.negocio.codpessoa).padStart(8, "0") }} |
              {{
                formataCnpjCpf(
                  sNegocio.negocio.Pessoa.cnpj,
                  sNegocio.negocio.Pessoa.fisica
                )
              }}
            </span>
            <div class="row">
              <span v-if="sNegocio.negocio.Pessoa.telefone1">
                {{ sNegocio.negocio.Pessoa.telefone1 }}
              </span>
              <span v-if="sNegocio.negocio.Pessoa.telefone2">
                | {{ sNegocio.negocio.Pessoa.telefone2 }}
              </span>
              <span v-if="sNegocio.negocio.Pessoa.telefone3">
                | {{ sNegocio.negocio.Pessoa.telefone3 }}
              </span>
            </div>
            <span v-if="sNegocio.negocio.Pessoa.endereco">
              {{ sNegocio.negocio.Pessoa.endereco }},
            </span>
            <span v-if="sNegocio.negocio.Pessoa.numero"
              >{{ sNegocio.negocio.Pessoa.numero }} -
            </span>
            <span v-if="sNegocio.negocio.Pessoa.complemento"
              >{{ sNegocio.negocio.Pessoa.complemento }} -
            </span>
            <span v-if="sNegocio.negocio.Pessoa.bairro">
              {{ sNegocio.negocio.Pessoa.bairro }} -
            </span>
            {{ sNegocio.negocio.Pessoa.cidade }}/{{
              sNegocio.negocio.Pessoa.uf
            }}
          </td>
        </tr>
      </table>

      <table class="relatorio">
        <thead class="negativo">
          <tr>
            <th class="produto">Produtos</th>
          </tr>
        </thead>
        <tbody class="zebrada">
          <template
            v-for="produto in sNegocio.itensAtivos"
            v-bind:key="produto.uuid"
          >
            <tr>
              <td class="codigo">
                {{ produto.barras }} |

                {{ produto.produto }}
              </td>
            </tr>
            <tr>
              <td class="valor text-right">
                {{
                  new Intl.NumberFormat("pt-BR", {
                    style: "decimal",
                    minimumFractionDigits: 3,
                    maximumFractionDigits: 3,
                  }).format(produto.quantidade)
                }}
                de R$
                {{
                  new Intl.NumberFormat("pt-BR", {
                    style: "decimal",
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2,
                  }).format(produto.valorunitario)
                }}
                = R$
                <b>
                  {{
                    new Intl.NumberFormat("pt-BR", {
                      style: "decimal",
                      minimumFractionDigits: 2,
                      maximumFractionDigits: 2,
                    }).format(produto.valortotal)
                  }}</b
                >
              </td>
            </tr>
          </template>
        </tbody>
        <tbody class="totais">
          <tr
            v-if="sNegocio.negocio.valorprodutos != sNegocio.negocio.valortotal"
          >
            <td class="subtotal text-right">
              Produtos R$
              {{
                new Intl.NumberFormat("pt-BR", {
                  style: "decimal",
                  minimumFractionDigits: 2,
                  maximumFractionDigits: 2,
                }).format(sNegocio.negocio.valorprodutos)
              }}
            </td>
          </tr>

          <tr v-if="sNegocio.negocio.valordesconto">
            <td class="text-right">
              Desconto R$
              {{
                new Intl.NumberFormat("pt-BR", {
                  style: "decimal",
                  minimumFractionDigits: 2,
                  maximumFractionDigits: 2,
                }).format(sNegocio.negocio.valordesconto)
              }}
            </td>
          </tr>

          <tr v-if="sNegocio.negocio.valorfrete">
            <td class="text-right">
              Frete R$
              {{
                new Intl.NumberFormat("pt-BR", {
                  style: "decimal",
                  minimumFractionDigits: 2,
                  maximumFractionDigits: 2,
                }).format(sNegocio.negocio.valorfrete)
              }}
            </td>
          </tr>

          <tr v-if="sNegocio.negocio.valorseguro">
            <td class="text-right">
              Seguro R$
              {{
                new Intl.NumberFormat("pt-BR", {
                  style: "decimal",
                  minimumFractionDigits: 2,
                  maximumFractionDigits: 2,
                }).format(sNegocio.negocio.valorseguro)
              }}
            </td>
          </tr>

          <tr v-if="sNegocio.negocio.valoroutras">
            <td class="text-right">
              Outras R$
              {{
                new Intl.NumberFormat("pt-BR", {
                  style: "decimal",
                  minimumFractionDigits: 2,
                  maximumFractionDigits: 2,
                }).format(sNegocio.negocio.valoroutras)
              }}
            </td>
          </tr>

          <tr>
            <td class="text-right">
              Total R$
              {{
                new Intl.NumberFormat("pt-BR", {
                  style: "decimal",
                  minimumFractionDigits: 2,
                  maximumFractionDigits: 2,
                }).format(sNegocio.negocio.valortotal)
              }}
            </td>
          </tr>
        </tbody>
        <tbody v-if="sNegocio.negocio.pagamentos.length > 0" class="pagamentos">
          <tr>
            <td><b>Forma de Pagamento:</b></td>
          </tr>
          <tr
            v-for="formapagamento in sNegocio.negocio.pagamentos"
            v-bind:key="formapagamento.uuid"
          >
            <td>
              R$
              {{
                new Intl.NumberFormat("pt-BR", {
                  style: "decimal",
                  minimumFractionDigits: 2,
                  maximumFractionDigits: 2,
                }).format(formapagamento.valorpagamento)
              }}
              -
              {{ formapagamento.formapagamento }}
            </td>
          </tr>
        </tbody>
        <tbody v-if="sNegocio.negocio.observacoes" class="observacoes">
          <tr>
            <td style="">
              <b>Observações:</b> <br />
              {{ sNegocio.negocio.observacoes }}
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- <hr class="q-mb-md" /> -->
    <template v-if="sNegocio.negocio.sincronizado">
      <div class="text-center text-h5 text-bold q-pt-md">
        Negocio #{{ String(sNegocio.negocio.codnegocio).padStart(8, "0") }}
      </div>
      <div class="barcode">
        <BarCode
          :value="'NEG' + String(sNegocio.negocio.codnegocio).padStart(8, '0')"
          :format="'code128'"
          display-value="false"
          :width="2"
          :height="70"
          class="flex flex-center"
        />
      </div>
    </template>
    <template v-else>
      <div class="text-center text-h5 text-bold">
        Orçamento {{ sNegocio.negocio.uuid.substring(0, 8) }}
      </div>
      <div
        class="barcode text-center"
        style="display: flex; justify-content: center"
      >
        <BarCode
          :value="'ORC' + sNegocio.negocio.uuid.substring(0, 8)"
          :format="'code128'"
          display-value="false"
          :width="2"
          :height="70"
          class="flex flex-center"
        />
      </div>
    </template>
    <div class="text-center text-h5 text-bold">
      R$
      {{
        new Intl.NumberFormat("pt-BR", {
          style: "decimal",
          minimumFractionDigits: 2,
          maximumFractionDigits: 2,
        }).format(sNegocio.negocio.valortotal)
      }}
    </div>
  </template>
</template>
<style>
/*
To change this license header, choose License Headers in Project Properties.
To change this template file, choose Tools | Templates
and open the template in the editor.
*/
/*
    Created on : 23/07/2016, 10:02:11
    Author     : escmig98
*/

@media print {
  body {
    -webkit-print-color-adjust: exact;
    print-color-adjust: exact;
  }

  @page {
    margin: 0.1cm;
  }
}

@media screen {
  html {
    background: #eeeeee;
  }

  body {
    background: white;

    border: 1px solid grey;
    padding: 0.3cm;

    /* to centre page on screen*/
    margin-left: auto;
    margin-right: auto;
  }
}

@media all {
  .borda table {
    border: 1px solid blue;
    padding: 5px;
  }

  body {
    font-family: sans-serif;
    font-size: 4pt;
  }

  div.cabecalho,
  div.rodape {
    width: 100%;
    border-top: 3px solid black;
    padding: 0.2cm;
    font-size: 15pt;
    font-weight: bold;
    margin: 0cm;
  }

  div.pagamento {
    width: 100%;
    border-top: 4px solid black;
    padding: 0.2cm;
    font-size: 8pt;
    margin: 0cm;
  }

  div.cabecalho {
    /* margin-bottom: 0.5cm; */
    border-bottom: 4px solid black;
  }

  div.rodape {
    margin-top: 0.5cm;
  }

  .pull-right {
    float: right;
  }

  tbody.zebrada tr:nth-child(even) {
    background-color: #eeeeee;
  }

  tbody.totais tr:first-child td {
    border-top: 2px solid black;
  }

  tbody.totais tr:last-child td {
    border-bottom: 2px solid black;
  }

  tbody.pagamentos tr:last-child td {
    border-bottom: 2px solid black;
  }

  tbody.observacoes tr td {
    white-space: pre-line;
    border-bottom: 2px solid black;
  }

  tbody.totais tr td {
    font-size: 2.5em;
    font-weight: bold;
  }

  /*
  tbody.zebrada tr:first-child td {
    border-top: 2px solid black;
  }
  */
  td,
  th {
    text-align: left;
    padding: 0.02cm;
    font-size: 8pt;

    /* vertical-align: top; */
  }

  th {
    border-bottom: 2px solid black;
    text-align: center;
  }

  .negocio {
    border-top: 1px solid black;
    font-weight: bold;
    padding-top: 2px;
    width: 100%;
    padding-bottom: 2px;
  }

  table {
    width: 100%;
    border-spacing: 0.15cm;
  }

  .text-right {
    text-align: right;
  }

  .text-center {
    text-align: center;
  }

  .text-danger {
    color: #a94442;
  }

  .text-success {
    color: #3c763d;
  }

  .text-primary {
    color: #337ab7;
  }

  .text-warning {
    color: #8a6d3b;
  }

  .text-info {
    color: #31708f;
  }

  .text-muted {
    color: #777;
  }

  a {
    text-decoration: none;
  }

  a:link {
    color: rgb(0, 0, 238);
  }

  a:active {
    color: rgb(0, 0, 238);
  }

  a:visited {
    color: rgb(0, 0, 238);
  }

  a:hover {
    color: rgb(0, 0, 238);
  }
}

@media print {
  @page {
    size: 8cm 30cm;
  }
}

@media screen {
  body {
    width: 8cm;
  }
}

/*table.relatorio {table-layout:fixed; width:20cm;}*/
/*Setting the table width is important!*/
/*table.relatorio td {overflow:hidden;}*/
/*Hide text outside the cell.*/

/* especifico do relatorio */
.codigo {
  width: 0.3cm;
  /* vertical-align: middle; */
}

.img {
  width: 0.1cm;
}

.produto {
  width: 0.3cm;
  /* vertical-align: middle; */
}

.preco {
  width: 0.3cm;
  /* vertical-align: middle; */
}

.unidademedida {
  width: 0.3cm;
  /* vertical-align: middle; */
}

.variacao {
  width: 2cm;
}

.referencia {
  width: 1.5cm;
  /* vertical-align: middle; */
}

.data {
  width: 1.1cm;
}

.quantidade {
  width: 0.3cm;
  text-align: right;
  /* vertical-align: middle; */
}

.valor {
  width: 0.1cm;
  /* vertical-align: middle; */
}

.local {
  width: 0.7cm;
  /* vertical-align: middle; */
}

.prateleira {
  width: 1.4cm;
}

.saldo {
  width: 0.8cm;
}

.dias {
  width: 0.8cm;
}

.medio {
  width: 1cm;
}

.minimo {
  width: 0.5cm;
}

.maximo {
  width: 0.5cm;
}

.bimestre {
  width: 0.5cm;
}

.semestre {
  width: 0.5cm;
}

.ano {
  width: 0.5cm;
}

.quinzena {
  width: 0.5cm;
}

#logoOrcamento {
  text-align: right;
  margin-top: -30px;
}

#logoOrcamento img {
  width: 100px;
}

hr {
  border: 0.8px solid black;
}
</style>
