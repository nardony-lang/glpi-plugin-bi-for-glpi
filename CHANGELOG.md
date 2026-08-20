# Changelog

Este arquivo registra as alterações relevantes do BI for GLPI. O projeto segue
versionamento semântico para separar versões estáveis, correções e novas evoluções.

## [Não publicado]

## [0.5.0] - 2026-08-20

### Adicionado

- editor visual de dashboards com ordenação e dimensionamento dos componentes;
- Gauge configurável com mínimo, máximo, meta, unidade, faixas e cores;
- formatação de indicadores numéricos com casas decimais, prefixo, sufixo,
  meta e cores condicionais;
- configurações de orientação, cores, valores e unidade para gráficos de barras;
- configurações de escala, pontos, área, suavização e unidade para gráficos de linha;
- gráfico de rosca configurável com legenda, centro, rótulos e percentuais;
- renderização local de gráficos com Apache ECharts 6.1.0 e fallback Canvas;
- tabelas analíticas com regras por coluna, duração, status, barra de progresso
  e minigráficos de linha e barras;
- exportação de tabelas para PNG e PDF, sem envio de dados a serviços externos;
- documentação das cores de status e roadmap de formatação condicional.

### Corrigido

- atualização imediata dos campos ao trocar o tipo de componente;
- contraste dos rótulos no formulário de configuração;
- integração do seletor Select2 com o editor de componentes;
- exportação de tabelas com as funções modernas de cor usadas pelo GLPI 11.

### Compatibilidade

- GLPI 11;
- PHP 8.2 ou superior;
- atualização preservando dashboards, consultas, componentes e permissões.

## [0.4.0] - 2026-08-19

- filtros de entidade e período por dashboard;
- variáveis SQL seguras para entidade e datas;
- catálogo inicial de indicadores de requisições de serviço;
- execução parametrizada no Laboratório SQL;
- melhorias responsivas e preservação das configurações existentes.

[Não publicado]: https://github.com/nardony-lang/biforglpi/compare/v0.5.0...HEAD
[0.5.0]: https://github.com/nardony-lang/biforglpi/compare/v0.4.0...v0.5.0
[0.4.0]: https://github.com/nardony-lang/biforglpi/releases/tag/v0.4.0
