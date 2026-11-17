# Responsividade, Layouts Adaptativos & Dispositivos Dobráveis

Esta referência consolida as diretrizes de design responsivo e adaptação de layout para diferentes fatores de forma (smartphones convencionais, tablets, telas largas e dispositivos dobráveis/foldables).

---

## 1. Princípios de Layout Adaptativo no Habitus

O Habitus foi concebido como um aplicativo prioritariamente móvel, mas deve se comportar de forma elegante em telas expandidas (tablets como iPads/Galaxy Tabs e dispositivos dobráveis como Samsung Galaxy Z Fold e Google Pixel Fold):

- **Largura Máxima de Leitura**: Conteúdos de lista e formulários nunca devem esticar indefinidamente em telas largas. Use um container centralizado (`max-width: 600px` em smartphones e `max-width: 900px` em tablets).
- **Safe Area Insets**: Sempre respeite as variáveis de Safe Area do Ionic (`ion-safe-area-top`, `ion-safe-area-bottom`) para evitar sobreposição por Dynamic Island, notches ou barras de gestos do sistema operacional.

---

## 2. Padrões CSS para Telas Médias e Grandes (Tablets & Foldables Abertos)

Em telas abertas/expandidas (largura $\ge$ 768px), o grid de hábitos diários deve aproveitar o espaço horizontal:

```css
/* Container base centralizado */
.habits-container {
  width: 100%;
  max-width: 600px;
  margin: 0 auto;
}

/* Telas expandidas (Tablets e Foldables abertos) */
@media (min-width: 768px) {
  .habits-container {
    max-width: 900px;
  }

  .habits-grid-wrapper {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 16px;
  }
}
```

---

## 3. Dispositivos Dobráveis (Foldables & Dual-Screen)

Dispositivos dobráveis possuem estados físicos específicos:
- **Fechado (Cover Screen)**: Funciona como um smartphone convencional estreito.
- **Totalmente Aberto (Unfolded / Flat)**: Funciona como uma tela de tablet com aspecto quase quadrado.
- **Semiaberto (Flex Mode / Tent / Book Posture)**: O dispositivo fica apoiado sobre uma mesa em ângulo de 90° a 135°.

### Regras para Posturas Semiabertas:
1. **Evitar a Área da Dobradiça (*Hinge Area*)**:
   - Elementos interativos críticos (botão de toggle de hábito, botões de ação principal, modais de confirmação) **nunca** devem ficar posicionados exatamente na linha central onde a tela dobra.
2. **Media Queries para Viewport Segments**:
   - Utilize a especificação de CSS para dobras de tela quando necessário dividir a interface:
     - `@media (horizontal-viewport-segments: 2)` (dobra vertical - estilo livro)
     - `@media (vertical-viewport-segments: 2)` (dobra horizontal - estilo concha/laptop)
3. **Divisão Natural em Modo Livro**:
   - Se o usuário estiver no modo semiaberto vertical, a coluna esquerda pode exibir a lista de hábitos do dia e a coluna direita o resumo/estatísticas.
