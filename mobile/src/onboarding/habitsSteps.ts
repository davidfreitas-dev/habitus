import type { StepEntity } from 'v-onboarding';

export const habitsSteps: StepEntity[] = [
  {
    attachTo: { element: '#onboarding-habits-new' },
    content: {
      title: 'Novo hábito',
      description: 'Toque aqui para cadastrar um novo hábito e definir frequência e lembretes.',
    },
    options: {
      popper: {
        placement: 'bottom',
        modifiers: [
          { name: 'offset', options: { offset: [0, 12] } },
        ],
      },
    },
  },
  {
    attachTo: { element: '#onboarding-habits-search' },
    content: {
      title: 'Buscar hábitos',
      description: 'Encontre rapidamente qualquer hábito pelo nome utilizando o campo de busca.',
    },
    options: {
      popper: {
        placement: 'bottom',
        modifiers: [
          { name: 'offset', options: { offset: [0, 12] } },
        ],
      },
    },
  },
  {
    attachTo: { element: '#onboarding-habits-chips' },
    content: {
      title: 'Filtro por dia',
      description: 'Selecione um dia da semana para visualizar apenas os hábitos programados para aquele dia.',
    },
    options: {
      popper: {
        placement: 'bottom',
        modifiers: [
          { name: 'offset', options: { offset: [0, 12] } },
        ],
      },
    },
  },
  {
    attachTo: { element: '#onboarding-habit-card' },
    content: {
      title: 'Ações por gesto',
      description: 'Deslize para a direita para editar ou para a esquerda para excluir o hábito.',
    },
    options: {
      popper: {
        placement: 'bottom',
        modifiers: [
          { name: 'offset', options: { offset: [0, 12] } },
        ],
      },
    },
  },
];
