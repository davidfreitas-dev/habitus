import { mount } from '@vue/test-utils';
import Heading from '@/components/layout/Heading.vue';
import { describe, expect, test } from 'vitest';

describe('Heading.vue', () => {
  test('renders title properly', () => {
    const wrapper = mount(Heading, {
      props: {
        title: 'Meus Hábitos'
      }
    });
    expect(wrapper.text()).toContain('Meus Hábitos');
  });
});
