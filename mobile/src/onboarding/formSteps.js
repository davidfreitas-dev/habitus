export const formSteps = [
  {
    attachTo: { element: '#onboarding-recurrence' },
    content: {
      title: 'Dias de recorrência',
      description: 'Selecione os dias da semana em que deseja praticar este hábito. Você pode escolher quantos quiser!'
    },
    options: {
      popper: { placement: 'top' }
    }
  },
  {
    attachTo: { element: '#onboarding-reminder' },
    content: {
      title: 'Ative lembretes',
      description: 'Ative um lembrete para não esquecer do seu hábito. Você receberá uma notificação no horário escolhido.'
    },
    options: {
      popper: { placement: 'top' }
    }
  }
];
