export const homeSteps = [
  {
    attachTo: { element: '#onboarding-summary-grid' },
    content: {
      title: 'Seu mapa de hábitos',
      description: 'Este é o seu grid de atividades. Cada quadrado representa um dia — quanto mais verde, mais hábitos você completou!'
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
