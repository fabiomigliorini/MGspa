---
id: TASK-100
title: 'Redesenhar recebimento do negocio: wizard por teclado (setas alteravam valor)'
status: In Progress
assignee:
  - '@fabio'
created_date: '2026-09-16 14:58'
updated_date: '2026-09-18 20:58'
labels:
  - negocios
dependencies: []
priority: medium
type: bug
ordinal: 99000
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
Ao adicionar um pagamento num negocio (negocios/src/components/offline/Pagamento*.vue), o autofocus cai no campo valor (MgInputValor). O MgInputValor incrementa/decrementa com ArrowUp/ArrowDown (components/MgInputValor.vue ~L222-230). Como o campo tem estilo diferente e nao parece um q-input, o usuario nao percebe que o foco esta nele, usa as setas achando que navega entre campos/opcoes e altera o valor sem querer.

Origem: relato de uso (2026-09-16).

Caminhos possiveis (decidir antes de implementar):
- desligar o incremento por setas no MgInputValor por prop (ex.: nas telas de pagamento) ou por padrao;
- deixar claro visualmente que o campo esta focado/editavel;
- rever para onde vai o autofocus nos dialogs de pagamento.
<!-- SECTION:DESCRIPTION:END -->

## Implementation Notes

<!-- SECTION:NOTES:BEGIN -->
Escopo ampliado (2026-09-16): redesenho da tela de pagamentos. Plano em /home/usuario/.claude/plans/agora-quero-pensar-em-calm-scott.md. Resumo: wizard Receber (F6-F9 abrem, passo 1 valor em texto + Insert edita, passo 2 forma por numero, passos por forma com setas/Enter/Esc), pagamento dividido reinicia wizard com saldo, dialogs especialistas PIX/PagarMe/Saurus mantidos + auto-consulta, cartao manual grava serialmaquineta (lista filtravel + cache), serial fisico no tblsauruspinpad, cheque (TASK-43).

Bug 1 (pix.js:140 findIndex com = em vez de ===, sobrescreve pixCob errada com 2+ cobrancas): corrigir dentro do refactor.

Bug 2 (criarPixCob/criarPagarMePedido sem return false: erro da API abre dialog de detalhes quebrado): corrigir dentro do refactor.

Bug 3 (Prazo abaixo do minimo: lista de planos vazia, salvar fecha o dialog e quebra em parc.valorjuros sem lancar nada): corrigir dentro do refactor — formas indisponiveis aparecem desabilitadas com o motivo.

Bug 4 (Vale: :rules com operador virgula, regra maior-que-zero ignorada) e Bug 5 (codigo do vale formatado com separador de milhar): corrigir dentro do refactor — codigo vira q-input texto; valor usa regras do shell.

Bugs 6-7 (fecharDialogs incompleto; F7 Saurus consulta store PagarMe) e todos os demais pequenos entram no refactor sem discussao (decisao do usuario): parcelas stale no cartao; excluir pagamento sem confirmacao/lock; QR via api.qrserver.com (gerar local); rotas saurus/pedido/pendentes inexistentes (remover chamadas ou criar rotas); Saurus sem PdvService::autoriza e codpessoa em codusuariocriacao (PdvController@criarSaurusPedido); forma-pagamento sem filtro de inativo; checagem morta $nfp->prazo no fechar. Backend maiores viraram tasks proprias: webhooks atras de auth:api, PixWebhookService vazio, cancelar nao estorna integracoes.

Fase 1 implementada (aguardando teste): ReceberDialog.vue (passos 1 valor / 2 forma) + receber/ListaOpcoes.vue; Dinheiro completo; demais formas abrem os dialogs antigos com o valor escolhido (transicao). Store: adicionarPagamento(objeto) + comLock, estado receber{valor,forma}, dialog.receber. IndexPage: F6-F9 -> receber(), fecharDialogs completo. TotalNegocio: botao unico Receber, exclusao com confirmacao. PagamentoDinheiro.vue removido.

Implementacao completa (aguardando teste de tarde): wizard com todas as formas (Dinheiro, Cartao c/ maquininha ou manual + serial, PIX QR/chave, Prazo, Vale, Cheque); dialogs antigos Pagamento*.vue removidos; especialistas PixCobDialog/PagarMePedidoDialog/SaurusPedidoDialog com consulta automatica 3s (composables/useConsultaAutomatica.js); QR gerado local (lib qrcode); utils/parcelamento.js unico. Backend: DDL api/database/negocio_receber.sql (rodado no dev, FALTA PROD), serialmaquineta copiado nos integrados, PATCH v1/pdv/saurus/pinpad/{cod}/serial, criarSaurusPedido com PdvService::autoriza, forma-pagamento filtra inativo, checagem morta $nfp->prazo corrigida. Prod: adicionar CODFORMAPAGAMENTO_CHEQUE=1020 no .env do negocios.

F8/botão Receber bloqueia abertura do wizard em venda >= R$ 1.000 sem CPF/CNPJ (mesma regra do PdvNegocioService::fechar): só avisa (pede F10), não abre o WizardPessoa sozinho. Getter faltaIdentificarCliente no store negocio.

Ajuste (2026-09-18): removido o pedido do serial do pinpad Saurus no 1º uso (etapa 'serial' do FormaCartao, definirSerialSaurus, rota PATCH v1/pdv/saurus/pinpad/{cod}/serial e PdvController::serialPinPadSaurus). Serial é cadastrado pelo TI no cadastro de maquinetas Saurus; sem serial o pedido é enviado igual e serialmaquineta fica null. Também: Pagamento na Entrega virou opção 4 do passo 1 (saiu do Prazo); Prazo e Cheque bloqueados sem cliente no passo 1.
<!-- SECTION:NOTES:END -->
