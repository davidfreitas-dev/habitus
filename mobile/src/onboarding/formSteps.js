export const formSteps = [
  {
    attachTo: { element: '#onboarding-habit-name' },
    content: {
      title: 'Nomeie seu hábito',
      description: 'Dica: Seja específico e prefira usar verbos de ação para criar um compromisso mais claro, como "Beber 2L de água", "Ler 10 páginas" ou "Correr 5km".'
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
    attachTo: { element: '#onboarding-recurrence' },
    content: {
      title: 'Dias de recorrência',
      description: 'Escolha quando praticar. O hábito reaparecerá automaticamente na sua lista toda semana nos dias selecionados, criando uma rotina.'
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
    attachTo: { element: '#onboarding-reminder' },
    content: {
      title: 'Ative lembretes',
      description: 'Ative um lembrete e defina um horário para não esquecer do seu hábito. Você receberá uma notificação no horário escolhido.'
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
