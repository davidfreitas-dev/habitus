import { mount } from '@vue/test-utils';
import { describe, expect, it } from 'vitest';
import { tabbable } from 'tabbable';
import OnboardingStep from '@/components/features/onboarding/OnboardingStep.vue';

describe('OnboardingStep.vue', () => {
  const dummyStep = {
    attachTo: { element: '#test' },
    content: {
      title: 'Título de Teste',
      description: 'Descrição de Teste',
    },
  };

  it('renders title and description properly', () => {
    const wrapper = mount(OnboardingStep, {
      props: {
        step: dummyStep,
        index: 0,
        total: 2,
        isLast: false,
      },
    });

    expect(wrapper.find('.onboarding-step__title').text()).toBe('Título de Teste');
    expect(wrapper.find('.onboarding-step__description').text()).toBe('Descrição de Teste');
    expect(wrapper.find('.onboarding-step__counter').text()).toBe('1 de 2');
    expect(wrapper.find('.onboarding-step__next').text()).toBe('Próximo');
  });

  it('renders "Entendi" on the next button when isLast is true', () => {
    const wrapper = mount(OnboardingStep, {
      props: {
        step: dummyStep,
        index: 1,
        total: 2,
        isLast: true,
      },
    });

    expect(wrapper.find('.onboarding-step__next').text()).toBe('Entendi');
  });

  it('emits skip when clicking skip button', async () => {
    const wrapper = mount(OnboardingStep, {
      props: {
        step: dummyStep,
        index: 0,
        total: 2,
        isLast: false,
      },
    });

    await wrapper.find('.onboarding-step__skip').trigger('click');
    expect(wrapper.emitted('skip')).toBeTruthy();
  });

  it('emits next when clicking next button', async () => {
    const wrapper = mount(OnboardingStep, {
      props: {
        step: dummyStep,
        index: 0,
        total: 2,
        isLast: false,
      },
    });

    await wrapper.find('.onboarding-step__next').trigger('click');
    expect(wrapper.emitted('next')).toBeTruthy();
  });

  it('contains tabbable nodes so focus-trap does not fail', () => {
    const wrapper = mount(OnboardingStep, {
      props: {
        step: dummyStep,
        index: 0,
        total: 2,
        isLast: false,
      },
    });

    const buttons = wrapper.findAll('button');
    expect(buttons.length).toBeGreaterThanOrEqual(2);

    const tabbableNodes = tabbable(wrapper.element as HTMLElement, { displayCheck: 'none' });
    expect(tabbableNodes.length).toBeGreaterThanOrEqual(2);
  });
});
