# Homologação da versão 0.5.0

## Atualização inicial

1. Atualize a branch de homologação no diretório `plugins/biforglpi`.
2. Execute a atualização em **Configuração > Plugins**.
3. Confirme a versão `0.5.0-rc1` e a preservação dos dashboards existentes.

## Gauge

1. Edite um dashboard e adicione um componente.
2. Selecione **Gauge (velocímetro)** e uma consulta que retorne um valor numérico.
3. Configure mínimo `0`, máximo `100`, meta `95`, atenção `80`, sucesso `95` e unidade `%`.
4. Salve e confirme as três faixas, o ponteiro, o valor central e a marca de meta.
5. Altere cores, limites e unidade e confirme a atualização.
6. Informe faixas fora de ordem e confirme que o salvamento é bloqueado com uma mensagem clara.

## Catálogo e demonstração

1. Adicione **Percentual solucionado dentro do ANS** pelo Catálogo.
2. Confirme que o componente criado usa Gauge.
3. Em um dashboard de demonstração, confirme o valor de exemplo e a renderização responsiva.
4. Em dados reais sem SLA, confirme o estado **Sem valor numérico para gerar o Gauge**.

## Editor visual

1. Abra **Meus dashboards > Configurar** em um dashboard com três ou mais componentes.
2. Arraste os componentes para uma nova ordem e clique em **Salvar layout**.
3. Recarregue a página e confirme que a ordem foi preservada no editor e no dashboard.
4. Use os botões para cima e para baixo e confirme a nova ordem.
5. Troque as larguras entre `3/12`, `4/12`, `6/12`, `8/12` e `12/12` e salve.
6. Duplique um componente e confirme que consulta, visualização, largura, dados de demonstração e configuração do Gauge foram copiados.
7. Exclua a cópia e confirme a mensagem de sucesso.
8. Em tela pequena, confirme que os componentes ocupam a largura completa e os botões continuam utilizáveis.

## Formatação de indicadores numéricos

1. Edite um componente do tipo **Indicador numérico**.
2. Teste o modo automático e depois selecione duas casas decimais.
3. Informe prefixo `R$ `, uma meta e confirme a apresentação no cartão.
4. Troque para sufixo `%` e confirme que ele aparece após o valor.
5. Ative as cores condicionais com atenção `80` e sucesso `95`.
6. Teste valores abaixo de `80`, entre `80` e `94,99` e a partir de `95`.
7. Desative as cores e confirme que o cartão volta à cor padrão.
8. Confirme que indicadores textuais, como tempo em `HH:MM:SS`, continuam funcionando no modo automático.
9. Confirme que todos os rótulos dos campos numéricos e do Gauge permanecem legíveis no tema utilizado pelo GLPI.
10. Antes de salvar, alterne entre **Indicador numérico**, **Barras**, **Linha**, **Gauge** e outro tipo; confirme que somente o bloco de configuração correspondente aparece imediatamente.

## Gráfico de barras

1. Crie um componente de barras com quatro ou mais categorias e ative os dados de demonstração.
2. Confirme a grade, a escala numérica e os valores sobre as barras.
3. Troque a cor principal e depois ative **Usar uma cor por barra**.
4. Teste casas decimais e unidade `%`.
5. Mude para orientação horizontal e use nomes longos de grupos; confirme que os rótulos permanecem legíveis.
6. Desative valores e grade e confirme que o gráfico fica mais limpo sem perder os rótulos.
7. Teste as larguras `4/12`, `6/12` e `12/12`.

## Gráfico de linha

1. Crie uma série mensal com pelo menos seis períodos.
2. Confirme a grade, a escala, os pontos e a evolução temporal.
3. Altere a cor, as casas decimais e a unidade.
4. Ative e desative separadamente valores, pontos, preenchimento da área e suavização.
5. Confirme que rótulos em séries maiores são espaçados automaticamente, sem sobreposição excessiva.
6. Teste as larguras `4/12`, `6/12` e `12/12`.

## Regressão

1. Confirme indicadores numéricos, tabelas, barras, linhas e roscas.
2. Teste filtros de entidade e período.
3. Teste acesso somente leitura por perfil e grupo.
4. Abra e execute consultas parametrizadas no Laboratório SQL.
