export const homeSteps = [
  {
    attachTo: { element: '#onboarding-current-day' },
    content: {
      title: 'Seu mapa de hábitos',
      description: 'Cada quadro representa um dia do ano. Este último quadro com a borda diferenciada sempre marca o dia de hoje. Quanto mais verde, mais hábitos você completou — toque em qualquer quadrado para ver os detalhes daquele dia.'
    },
    options: {
      popper: { 
        placement: 'top',
        modifiers: [
          { name: 'offset', options: { offset: [0, 12] } }
        ]
      }
    }
  },
  {
    attachTo: { element: '#onboarding-btn-new' },
    content: {
      title: 'Crie seu primeiro hábito',
      description: 'Toque aqui para criar o seu primeiro hábito e começar a acompanhar seu progresso diário.'
    },
    options: {
      popper: { 
        placement: 'bottom',
        modifiers: [
          { name: 'offset', options: { offset: [0, 12] } }
        ]
      }
    }
  }
];