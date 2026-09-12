# MGspa

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

Prioridade só quando for evidente: bug, defeito em produção ou problema de segurança nascem
`high`. Fora isso, **deixar sem prioridade** — não chutar. Nunca atribuir responsável a outra
pessoa; cada um pega a sua.

**2. Ao começar uma task:** marcar em andamento e atribuir a si.

    ./backlog.sh task edit TASK-42 -s "In Progress" -a @fabio

**3. Ao terminar:** marcar concluída.

    ./backlog.sh task edit TASK-42 -s Done

**4. Commits de correção citam o id da task**, seguindo a convenção do repo:

    [FIX] TASK-42 Corrige rateio de xerox quando não fecha 100%

O arquivo `.md` da task entra no mesmo commit do código.
