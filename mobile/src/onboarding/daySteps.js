export const daySteps = [
  {
    attachTo: { element: '#onboarding-progress-bar' },
    content: {
      title: 'Acompanhe seu progresso',
      description: 'Esta barra mostra o progresso dos seus hábitos para este dia. Complete todos para atingir 100%!'
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
    attachTo: { element: '#onboarding-habit-checkbox' },
    content: {
      title: 'Marque como concluído',
      description: 'Toque no checkbox para marcar um hábito como concluído. Simples assim!'
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
