# Homologação da versão 0.3.0

## 1. Atualização

1. Faça backup do banco e do diretório `biforglpi`.
2. Atualize os arquivos e execute a atualização do plugin no GLPI.
3. Confirme que o **Dashboard principal** foi criado e manteve as consultas ativas da 0.2.0.
4. Em **Administração > Perfis > BI for GLPI**, confirme os direitos de visualizar e administrar dashboards.

## 2. Múltiplos dashboards

1. Abra **Meus dashboards** e crie um painel chamado `Gestão de Requisições`.
2. Ative o modo de demonstração.
3. Adicione componentes dos tipos número, tabela, barras, linha e rosca.
4. Use dados de exemplo como `[{"mes":"Jan","total":18},{"mes":"Fev","total":24}]`.
5. Confirme a ordem, a largura e a adaptação em telas menores.

## 3. Permissões

1. Autorize um perfil apenas para visualização e confirme que ele abre o painel, mas não o configura.
2. Autorize um grupo com edição e confirme que um membro consegue alterar componentes.
3. Use um perfil sem autorização e confirme que o dashboard não aparece na lista.
4. Confirme que um administrador de dashboards consegue configurar todos os painéis.

## 4. Dados reais

1. Desative o modo de demonstração.
2. Confirme que indicador sem linhas mostra **Sem dados**, e não zero.
3. Confirme que erros SQL aparecem de forma genérica, sem expor detalhes do banco.
4. Valide que as consultas continuam limitadas a 500 linhas e 10 segundos.

## 5. Regressão

1. Execute uma consulta no Laboratório SQL.
2. Crie, edite e abra uma consulta salva.
3. Confirme que uma consulta vinculada a um dashboard não pode ser excluída antes da remoção do componente.
