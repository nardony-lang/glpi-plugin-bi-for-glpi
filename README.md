# BI for GLPI

Plugin inicial de Business Intelligence para GLPI 11. A primeira entrega inclui um
**Laboratório SQL** administrativo, com consultas somente leitura e visualização dos
resultados em tabela.

## Compatibilidade

- GLPI `>= 11.0.0` e `< 12.0.0`
- PHP `>= 8.2`
- MySQL 8 ou MariaDB suportado pela instalação do GLPI

## Instalação

O nome do diretório é parte da identidade do plugin e deve ser `biforglpi`:

```bash
cd /caminho/do/glpi/plugins
git clone https://github.com/nardony-lang/glpi-plugin-bi-for-glpi.git biforglpi
```

Depois, no GLPI, acesse **Configuração > Plugins**, instale e ative **BI for GLPI**.
O Laboratório SQL ficará disponível no menu **Plugins** para perfis com direito de
leitura em Configuração.

## Laboratório SQL

O laboratório aceita uma única instrução iniciada por:

- `SELECT`
- `WITH` (CTE cujo resultado seja uma seleção)
- `EXPLAIN SELECT` ou `EXPLAIN WITH`

Proteções incluídas:

- acesso restrito a usuários autenticados com direito administrativo `config/READ`;
- proteção CSRF do GLPI 11 para a chamada AJAX;
- rejeição de múltiplas instruções, comentários SQL e comandos de escrita/DDL;
- bloqueio de cláusulas e funções com efeitos colaterais ou acesso a arquivos;
- limite configurável de 1 a 500 linhas, com padrão de 100;
- medição do tempo de execução e indicação de resultado truncado;
- renderização de células com `textContent`, sem interpretar HTML retornado pelo banco.

> A validação do plugin é uma camada de defesa da aplicação. Em produção, aplique
> também o princípio do menor privilégio no banco e, para análises pesadas, prefira
> uma réplica de leitura. As consultas são executadas pela conexão configurada no GLPI.

Exemplo:

```sql
SELECT id, name, date_mod
FROM glpi_computers
ORDER BY date_mod DESC
```

## Estrutura

```text
ajax/       endpoint de execução
css/        estilos do laboratório
front/      página do plugin
install/    rotinas de instalação
js/         interação e tabela de resultados
src/        classes do domínio
hook.php    hooks de instalação e remoção
setup.php   metadados e inicialização do plugin
```

## Desenvolvimento

Antes de enviar alterações, valide a sintaxe de todos os arquivos PHP:

```bash
find . -name '*.php' -print0 | xargs -0 -n1 php -l
```

## Licença

GPL-3.0-or-later. Consulte [LICENSE](LICENSE).
