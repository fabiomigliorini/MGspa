# MGspa

## Commits — sempre esperar autorização

**Nunca commitar sem o OK explícito de quem pediu.** Ao terminar uma alteração:

1. Deixe o trabalho pronto na árvore de trabalho — **sem** `git add`, **sem** `git commit`.
2. Diga o que mudou e **como testar**: a tela, a URL, o comando a rodar.
3. Espere a validação. Só commite depois de um "pode commitar" claro.

Vale para **qualquer** alteração, inclusive as que parecem triviais, as de documentação e as
do próprio `backlog/`. "Terminei a implementação" não é autorização para commitar — quem
testa é quem autoriza.

Se a validação apontar problema, corrija e volte ao passo 2: a correção também não vai
commitada sozinha.

## Gestão de tarefas — Backlog.md

As tarefas do projeto vivem em `backlog/` como arquivos markdown, versionadas junto com o
código. **Não usar GitHub Issues, Trello nem arquivos `todo`** — foram abandonados justamente
por virarem um segundo lugar pra manter.

Nada precisa ser instalado. O wrapper `./backlog.sh` roda a ferramenta num container
descartável (`docker run --rm node:22-slim`), porque os containers do projeto são Alpine/musl
e o binário do Backlog.md é glibc.

    ./backlog.sh task list -s "To Do"
    ./backlog.sh task list --priority high --plain
    ./backlog.sh board
    ./backlog.sh task view TASK-42

Atalho sugerido no seu shell: `alias backlog=/opt/www/MGspa/backlog.sh`

### Regras de trabalho

**0. O Backlog.md é o controle de tarefas do projeto — use sempre, do começo ao fim.**
Antes de começar qualquer trabalho, procure no backlog se já existe task para aquilo; se
existir, trabalhe nela em vez de abrir outra. Durante o trabalho, mantenha o estado da task
em dia: a task é a fonte de verdade do que está sendo feito, não a conversa do chat. Ao
concluir, feche-a. Nenhum trabalho de projeto deve acontecer fora de uma task.

    ./backlog.sh search "banco"
    ./backlog.sh task list -s "To Do"
    ./backlog.sh task list -s "In Progress"

**1. Toda pendência descoberta vira task, na hora.** Bug encontrado, TODO que ia virar
comentário no código, ideia que surgiu no meio de outra coisa — criar a task, não deixar no
chat nem comentada no fonte.

    ./backlog.sh task create "Título curto" --type bug -l negocios -d "contexto e origem"

**Type** = a natureza do trabalho: `bug` ou `feature` (o Backlog.md também aceita
`enhancement`, `task`, `chore`, `docs`, `spike`). **Label** = só o app de origem —
`pessoas`, `notas`, `negocios`, `contas`, `estoque`, `agro`, `api`, `components`.
Não repita a natureza como label; para filtrar use `--type`:

    ./backlog.sh task list --type bug --plain
    ./backlog.sh task list -l negocios --plain

**Prioridade é obrigatória — toda task nasce com uma.** São quatro níveis, definidos por
**impacto**, nunca pela natureza do trabalho (essa já é o `type`). Bug não é automaticamente
urgente e feature pode ser: o que decide é o quanto dói.

- `Critical` — gente parada, dinheiro saindo errado ou dado exposto **agora**. Larga o que
  está fazendo. Exige justificar o porquê na descrição. Se tiver mais de 2 ou 3 no backlog,
  alguma não é Critical.
- `High` — dói toda semana, tem workaround manual, ou tem prazo externo (fiscal/legal).
- `Medium` — melhora real de rotina, sem workaround doendo.
- `Low` — quando sobrar tempo; não morre se ficar um ano. **É o default**: na dúvida, entra
  `Low`. Errar pra baixo é barato de promover; `high` inflado não ordena nada.

    ./backlog.sh task list --sort priority --plain
    ./backlog.sh task list --priority critical --plain

Nunca atribuir responsável a outra pessoa; cada um pega a sua.

**2. Ao começar uma task:** marcar em andamento e atribuir a si.

    ./backlog.sh task edit TASK-42 -s "In Progress" -a @fabio

**3. Ao terminar:** marcar concluída.

    ./backlog.sh task edit TASK-42 -s Done

**4. Commits de correção citam o id da task**, seguindo a convenção do repo:

    [FIX] TASK-42 Corrige rateio de xerox quando não fecha 100%

O arquivo `.md` da task entra no mesmo commit do código — e o commit só acontece depois da
autorização, conforme a seção **Commits** acima.
