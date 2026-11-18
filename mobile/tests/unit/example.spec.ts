import { mount } from '@vue/test-utils';
import AppHeading from '@/components/app/AppHeading.vue';
import { describe, expect, test } from 'vitest';

describe('AppHeading.vue', () => {
  test('renders title properly', () => {
    const wrapper = mount(AppHeading, {
      props: {
        title: 'Meus Hábitos'
      }
    });
    expect(wrapper.text()).toContain('Meus Hábitos');
  });
});
