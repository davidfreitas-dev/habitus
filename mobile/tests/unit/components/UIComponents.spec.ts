import { mount } from '@vue/test-utils';
import { describe, expect, it } from 'vitest';
import ProgressBar from '@/components/ui/Progressbar.vue';
import Checkbox from '@/components/ui/Checkbox.vue';

describe('ProgressBar.vue', () => {
  it('renders dynamic width corresponding to progress percentage', () => {
    const wrapper = mount(ProgressBar, {
      props: {
        progress: 75,
      },
    });

    const bar = wrapper.find('.progress');
    expect(bar.attributes('style')).toContain('width: 75%;');
  });
});

describe('Checkbox.vue', () => {
  it('emits handleItem on label click', async () => {
    const wrapper = mount(Checkbox, {
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
