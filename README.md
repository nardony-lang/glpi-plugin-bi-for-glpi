# BI for GLPI

Plugin de Business Intelligence para GLPI 11. Inclui um **Laboratório SQL** seguro,
consultas salvas, múltiplos dashboards, gráficos e controle de acesso.

## Compatibilidade

- GLPI `>= 11.0.0` e `< 12.0.0`
- PHP `>= 8.2`
- MySQL 8 ou MariaDB suportado pela instalação do GLPI

## Instalação

O nome do diretório é parte da identidade do plugin e deve ser `biforglpi`:

```bash
cd /caminho/do/glpi/plugins
git clone https://github.com/nardony-lang/biforglpi.git
```

Depois, no GLPI, acesse **Configuração > Plugins**, instale e ative **BI for GLPI**.
O BI for GLPI ficará disponível no menu **Ferramentas** somente para perfis com
algum direito do módulo. O Laboratório SQL será exibido apenas para perfis com o
direito específico **Executar consultas SQL**. Na instalação, esse direito é concedido
apenas ao perfil que instalou o plugin, normalmente o **Super-Admin**. Ele pode ser
administrado posteriormente em **Administração > Perfis > BI for GLPI**.

## Laboratório SQL

O laboratório aceita uma única instrução iniciada por:

- `SELECT`
- `WITH` (CTE cujo resultado seja uma seleção)
- `EXPLAIN SELECT` ou `EXPLAIN WITH`

Proteções incluídas:

- acesso restrito ao direito específico `plugin_biforglpi_sql_lab/READ`;
- proteção CSRF do GLPI 11 para a chamada AJAX;
- rejeição de múltiplas instruções, comentários SQL e comandos de escrita/DDL;
- bloqueio de cláusulas e funções com efeitos colaterais ou acesso a arquivos;
- limite configurável de 1 a 500 linhas, com padrão de 100;
- timeout de 10 segundos imposto pelo MySQL ou MariaDB para cada consulta;
- medição do tempo de execução e indicação de resultado truncado;
- renderização de células com `textContent`, sem interpretar HTML retornado pelo banco.

## Consultas salvas e dashboards

Perfis autorizados podem salvar consultas como:

- **Indicador numérico**: usa o primeiro valor retornado para montar um cartão;
- **Tabela**: fica disponível para abertura no Laboratório SQL.

Exemplo de indicador:

```sql
SELECT COUNT(*) AS total
FROM glpi_tickets
WHERE status IN (2, 3)
  AND is_deleted = 0
```

Cada dashboard armazena seus próprios componentes e pode combinar:

- indicadores numéricos;
- tabelas;
- gráficos de barras, linha e rosca;
- Gauge (velocímetro) com mínimo, máximo, meta, unidade e faixas de cores;
- dados reais das consultas ou dados JSON de demonstração.

O **Editor visual** permite reorganizar componentes por arraste ou pelos botões de
movimento, visualizar e alterar suas larguras, duplicar, excluir e salvar todo o
layout em uma única operação.

O modo demonstração permite desenhar e homologar painéis mesmo sem chamados ou
SLAs cadastrados. Ao desativá-lo, os componentes executam suas consultas mantendo
o limite de linhas, o timeout e as proteções de somente leitura.

Na versão 0.4, cada dashboard pode habilitar filtros de **entidade** e **período**.
As consultas usam variáveis controladas pelo plugin, substituídas com segurança
somente no momento da execução:

- `{{entity_id}}`;
- `{{date_start}}`;
- `{{date_end}}`;
- `{{date_end_exclusive}}`.

O **Catálogo de indicadores** cria consultas e componentes prontos no dashboard.
O catálogo inicial inclui total e tempo médio de requisições solucionadas,
resultados por grupo solucionador e percentual dentro do ANS. As consultas criadas
continuam editáveis e podem ser abertas no Laboratório SQL.

O Gauge utiliza o primeiro valor numérico retornado pela consulta. Sua configuração
permite definir faixas crítica, de atenção e de sucesso, além de uma marca de meta.

Cartões numéricos podem usar formatação automática ou de zero a seis casas
decimais, prefixo, sufixo, meta e cores condicionais. As regras de cor usam duas
faixas configuráveis e consideram valores maiores como melhores.

Gráficos de barras permitem orientação vertical ou horizontal, cor única ou
paleta, valores, grade, casas decimais e unidade. Gráficos de linha permitem
configurar cor, escala, valores, pontos, preenchimento da área e suavização.

Ao atualizar da versão 0.2.0, o plugin cria um **Dashboard principal** e vincula
automaticamente as consultas ativas existentes.

Permissões disponíveis em **Administração > Perfis > BI for GLPI**:

- visualizar dashboards;
- criar e administrar dashboards;
- visualizar ou gerenciar consultas salvas;
- executar consultas no Laboratório SQL.

Além dos direitos gerais do perfil, cada dashboard possui uma lista própria de
acesso. É possível conceder a um **perfil** ou **grupo** o nível “somente visualizar”
ou “visualizar e editar”. Administradores de dashboards podem acessar todos os
painéis para configuração.

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
front/      página do plugin
install/    rotinas de instalação
public/     estilos e JavaScript públicos exigidos pelo GLPI 11
src/        classes do domínio
tests/      testes das regras, assets, permissões e instalação
hook.php    hooks de instalação e remoção
setup.php   metadados e inicialização do plugin
```

## Desenvolvimento

Antes de enviar alterações, valide a sintaxe de todos os arquivos PHP:

```bash
find . -name '*.php' -print0 | xargs -0 -n1 php -l
```

Execute também os testes das regras de segurança e do timeout:

```bash
php tests/run.php
```

O roteiro de homologação da versão em desenvolvimento está em
[`docs/TESTING-0.5.0.md`](docs/TESTING-0.5.0.md).

## Licença

GPL-3.0-or-later. Consulte [LICENSE](LICENSE).

## Autoria

Desenvolvido por **Douglas Nardoni**.
