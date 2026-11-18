import { mount } from '@vue/test-utils';
import { describe, expect, it, vi } from 'vitest';
import BaseProgressBar from '@/components/base/BaseProgressBar.vue';
import BaseCheckbox from '@/components/base/BaseCheckbox.vue';
import BaseChip from '@/components/base/BaseChip.vue';
import BaseFilterChips from '@/components/base/BaseFilterChips.vue';

describe('BaseProgressBar.vue', () => {
  it('renders ion-progress-bar with normalized value corresponding to progress percentage', () => {
    const wrapper = mount(BaseProgressBar, {
      props: {
        progress: 75,
      },
    });

    const bar = wrapper.find('ion-progress-bar');
    expect(bar.exists()).toBe(true);
    expect(bar.attributes('style')).toContain('--progress-width: 75%');
  });
});

describe('BaseCheckbox.vue', () => {
  it('emits handleItem on label click', async () => {
    const wrapper = mount(BaseCheckbox, {
      props: {
        label: 'Ler livro',
        isChecked: false,
      },
    });

    const label = wrapper.find('ion-label');
    await label.trigger('click');

    expect(wrapper.emitted('handleItem')).toBeTruthy();
  });
});

describe('BaseChip.vue', () => {
  it('renders label in ion-label', () => {
    const wrapper = mount(BaseChip, {
      props: {
        label: 'Produtividade',
      },
    });

    const chip = wrapper.find('ion-chip');
    expect(chip.exists()).toBe(true);
    expect(wrapper.find('ion-label').text()).toBe('Produtividade');
  });

  it('applies active class when isActive is true', () => {
    const wrapper = mount(BaseChip, {
      props: {
        label: 'Ativo',
        isActive: true,
      },
    });

    expect(wrapper.find('ion-chip').classes()).toContain('active');
  });

  it('emits click event when clicked', async () => {
    const wrapper = mount(BaseChip, {
      props: {
        label: 'Clicável',
      },
    });

    await wrapper.find('ion-chip').trigger('click');
    expect(wrapper.emitted('click')).toBeTruthy();
  });
});

describe('BaseFilterChips.vue', () => {
  const options = [
    { label: 'Todos', value: null },
    { label: 'Dom', value: 0 },
    { label: 'Seg', value: 1 },
    { label: 'Ter', value: 2, disabled: true },
  ];

  it('renders all filter chip items and sets active state', () => {
    const wrapper = mount(BaseFilterChips, {
      props: {
        items: options,
        modelValue: 0,
      },
    });

    const chips = wrapper.findAllComponents(BaseChip);
    expect(chips).toHaveLength(4);
    expect(chips[0].text()).toBe('Todos');
    expect(chips[1].text()).toBe('Dom');
    expect(chips[1].classes()).toContain('active');
    expect(chips[0].classes()).not.toContain('active');
  });

  it('emits update:modelValue and change on item click', async () => {
    const wrapper = mount(BaseFilterChips, {
      props: {
        items: options,
        modelValue: null,
      },
    });

    const chips = wrapper.findAllComponents(BaseChip);
    await chips[2].trigger('click');

    expect(wrapper.emitted('update:modelValue')?.[0]).toEqual([1]);
    expect(wrapper.emitted('change')?.[0]).toEqual([1]);
  });

  it('does not emit events when disabled chip is clicked', async () => {
    const wrapper = mount(BaseFilterChips, {
      props: {
        items: options,
        modelValue: null,
      },
    });

    const chips = wrapper.findAllComponents(BaseChip);
    await chips[3].trigger('click');

    expect(wrapper.emitted('update:modelValue')).toBeFalsy();
    expect(wrapper.emitted('change')).toBeFalsy();
  });

  it('applies wrap class when wrap prop is true', () => {
    const wrapper = mount(BaseFilterChips, {
      props: {
        items: options,
        wrap: true,
      },
    });

    expect(wrapper.find('.filter-chips').classes()).toContain('filter-chips--wrap');
  });

  it('scrolls container to center chip when clicked', async () => {
    const scrollToMock = vi.fn();
    const wrapper = mount(BaseFilterChips, {
      props: {
        items: options,
        modelValue: null,
      },
      attachTo: document.body,
    });

    const container = wrapper.find('.filter-chips').element as HTMLElement;
    container.scrollTo = scrollToMock;
    Object.defineProperty(container, 'offsetWidth', { value: 300, configurable: true });

    const chips = wrapper.findAllComponents(BaseChip);
    const targetElement = chips[2].element as HTMLElement;
    Object.defineProperty(targetElement, 'offsetLeft', { value: 200, configurable: true });
    Object.defineProperty(targetElement, 'offsetWidth', { value: 60, configurable: true });

    await chips[2].trigger('click');

    expect(scrollToMock).toHaveBeenCalledWith({
      left: 80,
      behavior: 'smooth',
    });

    wrapper.unmount();
  });
});
