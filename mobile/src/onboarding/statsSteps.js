export const statsSteps = [
  {
    attachTo: { element: '#onboarding-period-selector' },
    content: {
      title: 'Filtro de período',
      description: 'Altere entre semana (S), mês (M) ou ano (A) para visualizar seu desempenho em diferentes intervalos. O gráfico abaixo irá se adaptar automaticamente.'
    },
    options: {
      popper: { 
        placement: 'bottom',
        modifiers: [
          { name: 'offset', options: { offset: [0, 12] } }
        ]
      }
    }
  },
  {
    attachTo: { element: '#onboarding-streaks-grid' },
    content: {
      title: 'Suas sequências',
      description: 'Aqui você acompanha sua sequência atual de dias e seu recorde pessoal. Mantenha a consistência!'
    },
    options: {
      popper: { 
        placement: 'top',
        modifiers: [
          { name: 'offset', options: { offset: [0, 12] } }
        ]
      }
    }
  }
];
