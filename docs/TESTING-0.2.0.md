# Homologação do BI for GLPI 0.2.0

## Atualização

1. Faça backup do banco e da pasta atual do plugin.
2. Substitua a pasta `plugins/biforglpi` pela nova versão.
3. Em **Configuração > Plugins**, execute a atualização e ative o plugin.
4. Atualize o navegador sem cache.

## Permissões

Em **Administração > Perfis > BI for GLPI**, confirme os direitos:

- Visualizar dashboards;
- Visualizar/Gerenciar consultas salvas;
- Executar consultas SQL.

O perfil que executa a atualização recebe inicialmente os novos direitos. Os demais
perfis permanecem sem acesso até serem configurados.

## Indicador de chamados em atendimento

1. Acesse **Plugins > BI for GLPI > Consultas salvas**.
2. Crie uma consulta chamada `Chamados em atendimento`.
3. Escolha **Indicador numérico**, mantenha-a ativa e salve:

```sql
SELECT COUNT(*) AS total
FROM glpi_tickets
WHERE status IN (2, 3)
  AND is_deleted = 0
```

4. Abra o **Dashboard** e confirme o cartão com o total.

## Consulta em tabela

Crie uma segunda consulta como **Tabela**:

```sql
SELECT id, name, date, date_mod, status
FROM glpi_tickets
WHERE status IN (2, 3)
  AND is_deleted = 0
ORDER BY date_mod DESC
```

Confirme que ela aparece no dashboard e abre preenchida no Laboratório SQL.

## Segurança

- tente salvar um `DELETE` e confirme o bloqueio;
- teste um perfil somente com acesso ao dashboard;
- confirme que um perfil sem direitos do BI for GLPI não vê o menu;
- valide que consultas pesadas continuam limitadas a 10 segundos.
