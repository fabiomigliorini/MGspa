<script setup>
import { onMounted } from 'vue';
import { useRoute } from "vue-router";
import { negocioStore } from 'src/stores/negocio';
import moment from "moment/min/moment-with-locales";
moment.locale("pt-br");
import { formataCnpjCpf } from "../utils/formatador.js";
import { produtoStore } from 'src/stores/produto';

const route = useRoute();
const sNegocio = negocioStore();
const sProduto = produtoStore();

onMounted(() => {
    const ret = sNegocio.carregarPeloUuid(route.params.uuid);
});

</script>
<template>

    <template v-if="sNegocio.negocio">
        <div class='cabecalho'>
            Orçamento
            <div id="logoOrcamento">
                <img src="MGPapelaria.jpg">
            </div>
        </div>
        <div class='conteudo'>
            <table class="relatorio">
                <tr>
                    <td class="local text-bold">Local</td>
                    <td>{{ sNegocio.negocio.estoquelocal }}</td>
                    <td class="text-bold text-right">Negócio</td>
                    <td>#{{ String(sNegocio.negocio.codnegocio).padStart(8, '0') }}</td>
                </tr>
                <tr>
                    <td class="text-bold  text-right">Vendedor</td>
                    <td>{{ sNegocio.negocio.fantasiavendedor }}</td>
                    <td class="text-bold text-right">Data</td>
                    <td>{{ moment(sNegocio.negocio.lancamento).format('DD/MM/YYYY HH:mm:SS') }}</td>
                </tr>
            </table>
            <div class="negocio"></div>
            <table class="relatorio">
                <tr>
                    <td class="text-bold">Cliente</td>
                    <td class="text-bold" style="font-size: 2.5em;">
                        {{ sNegocio.negocio.fantasia }}
                    </td>
                </tr>
                <tr>
                    <td>Cnpj / CPF</td>
                    <td v-if="sNegocio.negocio.Pessoa.cnpj">{{ formataCnpjCpf(sNegocio.negocio.Pessoa.cnpj) }}</td>
                </tr>
                <tr>
                    <td>Endereço:</td>
                    <td><span v-if="sNegocio.negocio.Pessoa.endereco">
                            {{ sNegocio.negocio.Pessoa.endereco
                            }},</span>

                        <span v-if="sNegocio.negocio.Pessoa.numero">{{ sNegocio.negocio.Pessoa.numero }},</span>
                        <span v-if="sNegocio.negocio.Pessoa.bairro">
                            {{
        sNegocio.negocio.Pessoa.bairro }},</span>

                        {{ sNegocio.negocio.Pessoa.cidade }} - {{ sNegocio.negocio.Pessoa.uf }}
                    </td>
                </tr>
            </table>

            <table class="relatorio">
                <thead class="negativo">
                    <tr>
                        <th class="img">
                        </th>
                        <th class="codigo">Código</th>
                        <th class="produto">Descrição</th>
                        <th class="unidademedida">UM</th>
                        <th class="quantidade">Quantidade</th>
                        <th class="valor">Preço</th>
                        <th class="valor">Total</th>
                    </tr>
                </thead>
                <tbody class="zebrada">

                    <tr v-for="produto in sNegocio.itensAtivos" v-bind:key="produto.codproduto">
                        <td class="img">
                            <img :src="sProduto.urlImagem(produto.codimagem)" style="width: 100%; border-radius: 50%;">
                        </td>
                        <td class="codigo">{{ produto.barras }}</td>
                        <td class="produto">{{ produto.produto }}
                        </td>
                        <td class="unidademedida">UN</td>
                        <td class="quantidade">{{ produto.quantidade }}</td>
                        <td class="valor text-right">
                            {{
        new Intl.NumberFormat("pt-BR", {
            style: "decimal",
            minimumFractionDigits: 2,
            maximumFractionDigits: 2,
        }).format(produto.valorunitario)
    }}
                        </td>
                        <td class="valor text-right">
                            {{ new Intl.NumberFormat("pt-BR", {
        style: "decimal",
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    }).format(produto.valortotal) }}
                        </td>
                    </tr>

                    <tr>
                        <td colspan="6" class="subtotal text-right">SubTotal</td>
                        <td class="subtotal text-right"> {{ new Intl.NumberFormat("pt-BR", {
        style: "decimal",
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    }).format(sNegocio.negocio.valorprodutos) }}</td>
                    </tr>

                    <tr v-if="sNegocio.negocio.valordesconto">
                        <td colspan="6" class="text-right">Desconto</td>
                        <td class="text-right"> {{ new Intl.NumberFormat("pt-BR", {
        style: "decimal",
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    }).format(sNegocio.negocio.valordesconto) }}</td>
                    </tr>


                    <tr v-if="sNegocio.negocio.valorfrete">
                        <td colspan="6" class="text-right">Frete</td>
                        <td class="text-right"> {{ new Intl.NumberFormat("pt-BR", {
        style: "decimal",
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    }).format(sNegocio.negocio.valorfrete) }}</td>
                    </tr>


                    <tr v-if="sNegocio.negocio.valorseguro">
                        <td colspan="6" class="text-right">Seguro</td>
                        <td class="text-right"> {{ new Intl.NumberFormat("pt-BR", {
        style: "decimal",
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    }).format(sNegocio.negocio.valorseguro) }}</td>
                    </tr>


                    <tr v-if="sNegocio.negocio.valoroutras">
                        <td colspan="6" class="text-right">Outras</td>
                        <td class="text-right"> {{ new Intl.NumberFormat("pt-BR", {
        style: "decimal",
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    }).format(sNegocio.negocio.valoroutras) }}</td>
                    </tr>

                    <tr>
                        <td colspan="6" class="text-right">Total: </td>
                        <td class="text-right"> {{ new Intl.NumberFormat("pt-BR", {
                            style: "decimal",
                            minimumFractionDigits: 2,
                            maximumFractionDigits: 2,
                            }).format(sNegocio.negocio.valortotal) }}</td>

                    </tr>
                </tbody>

            </table>
        </div>
        <div class="pagamento">
            <div><b>Forma de Pagamento:</b>
                <span class="text-right" v-for="formapagamento in sNegocio.negocio.pagamentos"
                    v-bind:key="formapagamento.codformapagamento">
                    <br>{{ new Intl.NumberFormat("pt-BR", {
                    style: "decimal",
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2,
                    }).format(formapagamento.valorpagamento) }} &nbsp;
                    {{ formapagamento.formapagamento }}
                </span>

            </div>
            <br><br><br><br><br>

            <div class="final text-center">
                <div>Migliorini & Migliorini Ltda</div>
                <div>Cnpj: 04.576.775/0002-41</div>
                <div>Fone: (66) 3515-0101</div>
            </div>

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
    }

    @page {
        margin: 1cm;
    }

}

@media screen {

    html {
        background: #EEEEEE;
    }

    body {

        background: white;

        border: 1px solid grey;
        padding: 1.3cm;

        /* to centre page on screen*/
        margin-left: auto;
        margin-right: auto;
    }
}

@media all {

    .borda table {
        border: 1px solid blue;
        padding: 10px;
    }

    body {
        font-family: sans-serif;
        font-size: 7pt;
    }

    div.cabecalho,
    div.rodape {
        width: 100%;
        border-top: 4px solid black;
        padding: 0.2cm;
        font-size: 20pt;
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
        margin-bottom: 0.5cm;
        border-bottom: 4px solid black;
    }

    div.rodape {
        margin-top: 0.5cm;
    }

    .pull-right {
        float: right;
    }

    tbody.zebrada tr:nth-child(even) {
        background-color: #EEEEEE;
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
        font-size: 7pt;
        vertical-align: top;
    }

    th {
        border-bottom: 1px solid black;
        text-align: center;
    }

    td.subtotal {
        border-top: 1px solid black;
        font-weight: bold;
        padding-top: 2px;
        padding-bottom: 2px;
    }

    .final {
        border-top: 1px solid black;
        font-weight: bold;
        padding-top: 2px;
        width: 50%;
        padding-bottom: 2px;
    }

    .negocio {
        border-top: 1px solid black;
        font-weight: bold;
        padding-top: 2px;
        width: 100%;
        padding-bottom: 2px;
    }


    td.total {
        /* border-top: 1px solid black; */
        /* border-bottom: 1px solid black; */
        padding-top: 2px;
        padding-bottom: 2px;
        font-weight: bold;
    }


    td.total-geral {
        border-top: 2px solid black;
        border-bottom: 2px solid black;
        padding-top: 2px;
        padding-bottom: 2px;
        font-weight: bold;
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
        color: rgb(0, 0, 238)
    }

    a:active {
        color: rgb(0, 0, 238)
    }

    a:visited {
        color: rgb(0, 0, 238)
    }

    a:hover {
        color: rgb(0, 0, 238)
    }

}

@media print {
    @page {
        size: A4 portrait;
    }
}

@media screen {
    body {
        width: 21.0cm;
    }
}

/*table.relatorio {table-layout:fixed; width:20cm;}*/
/*Setting the table width is important!*/
/*table.relatorio td {overflow:hidden;}*/
/*Hide text outside the cell.*/

/* especifico do relatorio */
.codigo {
    width: 1.1cm;
    vertical-align: middle;
}


.img {
    width: 1cm;
}


.produto {
    width: 8cm;
    vertical-align: middle;
}

.preco {
    width: 5cm;
    vertical-align: middle;
}

.unidademedida {
    width: 0.3cm;
    vertical-align: middle;
}

.variacao {
    width: 2cm;
}

.referencia {
    width: 1.5cm;
    vertical-align: middle;
}

.data {
    width: 1.1cm;
}

.quantidade {
    width: 0.5cm;
    text-align: right;
    vertical-align: middle;
}

.valor {
    width: 1cm;
    vertical-align: middle;
}

.local {
    width: 0.7cm;
    vertical-align: middle;
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
    margin-top: -40px;
    margin-bottom: -10px;
}

#logoOrcamento img {
    width: 150px;
}
</style>