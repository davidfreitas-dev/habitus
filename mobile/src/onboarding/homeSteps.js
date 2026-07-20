export const homeSteps = [
  {
    attachTo: { element: '#onboarding-summary-grid' },
    content: {
      title: 'Seu mapa de hábitos',
      description: 'Cada quadrado representa um dia, e o último é o dia de hoje. Quanto mais verde, mais hábitos você completou — toque em qualquer quadrado para ver os hábitos daquele dia.'
    },
    options: {
      popper: { placement: 'top' }
    }
  },
  {
    attachTo: { element: '#onboarding-btn-new' },
    content: {
      title: 'Crie seu primeiro hábito',
      description: 'Toque aqui para criar o seu primeiro hábito e começar a acompanhar seu progresso diário.'
    },
    options: {
      popper: { placement: 'bottom' }
    }
  }
];