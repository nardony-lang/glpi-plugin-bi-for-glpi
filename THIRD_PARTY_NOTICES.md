# Componentes de terceiros

## Apache ECharts

- Versão: 6.1.0
- Projeto: https://echarts.apache.org/
- Código-fonte: https://github.com/apache/echarts
- Licença: Apache License 2.0

A distribuição utilizada está em `public/vendor/echarts/`. Os textos integrais
de licença e aviso acompanham a biblioteca em `LICENSE.txt` e `NOTICE.txt`.

O Apache ECharts é carregado localmente pelo plugin; nenhuma CDN é utilizada.

## html2canvas

- Versão: 1.4.1
- Projeto: https://html2canvas.hertzen.com/
- Código-fonte: https://github.com/niklasvh/html2canvas
- Licença: MIT

A distribuição utilizada está em `public/vendor/html2canvas/`, acompanhada do
texto integral da licença. Ela é usada somente no navegador para capturar uma
tabela autorizada como imagem, sem enviar dados a serviços externos.

## jsPDF

- Versão: 4.2.1
- Projeto e código-fonte: https://github.com/parallax/jsPDF
- Licença: MIT

A distribuição utilizada está em `public/vendor/jspdf/`, acompanhada do texto
integral da licença. O plugin gera o PDF a partir da imagem já renderizada da
tabela e não utiliza os métodos de interpretação de HTML da biblioteca.
