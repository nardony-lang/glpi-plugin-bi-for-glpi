# Homologação da versão 0.4.0

## Atualização

1. Atualize a branch de homologação no diretório `plugins/biforglpi`.
2. Acesse **Configuração > Plugins** e execute a atualização do BI for GLPI.
3. Confirme que a versão exibida é `0.4.0` e que o módulo permanece em **Ferramentas**.

## Filtros do dashboard

1. Em **Meus dashboards**, edite um dashboard.
2. Ative **Filtro de entidade** e **Filtro de período** e salve.
3. Abra o dashboard e confirme que somente entidades permitidas ao usuário aparecem.
4. Troque o período, aplique e confirme que os componentes são atualizados.
5. Tente informar manualmente na URL uma entidade sem acesso e confirme que ela não é usada.

## Catálogo de indicadores

1. Abra **Catálogo**.
2. Adicione cada modelo a um dashboard editável.
3. Confirme a criação da consulta salva e do componente correspondente.
4. Em homologação sem dados, confirme que cartões e gráficos exibem estado vazio sem erro.
5. Para o indicador de ANS sem SLA configurado, confirme a mensagem **Sem dados**.

## Laboratório SQL

1. Abra uma consulta do catálogo em **Consultas salvas > Executar**.
2. Confirme que as variáveis `{{entity_id}}` e de período continuam visíveis no editor.
3. Selecione entidade e datas e execute a consulta.
4. Confirme que o resultado respeita os filtros e mantém limite e tempo de execução.
5. Confirme que uma entidade fora das permissões do usuário não pode ser executada.

## Regressão

1. Teste acesso por perfil e por grupo com direitos gerais e acesso ao dashboard.
2. Confirme que usuários somente leitura não veem ações de edição.
3. Confirme que dashboards antigos abrem normalmente com os filtros desativados.
4. Teste um indicador numérico, uma tabela e gráficos de barra, linha e rosca.
