import { mount } from '@vue/test-utils';
import { describe, expect, it } from 'vitest';
import HabitDay from '@/components/habits/HabitDay.vue';

describe('HabitDay.vue', () => {
  it('renders habit-day-0 when there are no completed habits', () => {
    const wrapper = mount(HabitDay, {
      props: {
        amountOfHabits: 3,
        amountCompleted: 0,
        isCurrentDay: false,
      },
    });

    expect(wrapper.classes()).toContain('habit-day-0');
    expect(wrapper.classes()).not.toContain('active');
  });

  it('renders intermediate percentage classes correctly', () => {
    const wrapper20 = mount(HabitDay, {
      props: { amountOfHabits: 10, amountCompleted: 1 }, // 10%
    });
    expect(wrapper20.classes()).toContain('habit-day-20');

    const wrapper40 = mount(HabitDay, {
      props: { amountOfHabits: 10, amountCompleted: 3 }, // 30%
    });
    expect(wrapper40.classes()).toContain('habit-day-40');

    const wrapper60 = mount(HabitDay, {
      props: { amountOfHabits: 10, amountCompleted: 5 }, // 50%
    });
    expect(wrapper60.classes()).toContain('habit-day-60');

    const wrapper80 = mount(HabitDay, {
      props: { amountOfHabits: 10, amountCompleted: 7 }, // 70%
    });
    expect(wrapper80.classes()).toContain('habit-day-80');
  });

  it('renders habit-day-100 when all habits are completed and active when isCurrentDay', () => {
    const wrapper = mount(HabitDay, {
      props: {
        amountOfHabits: 4,
        amountCompleted: 4,
        isCurrentDay: true,
      },
    });

    expect(wrapper.classes()).toContain('habit-day-100');
    expect(wrapper.classes()).toContain('active');
  });

  it('emits click when clicked or triggered via keyboard', async () => {
    const wrapper = mount(HabitDay, {
      props: {
        date: '2026-11-17',
        amountOfHabits: 2,
        amountCompleted: 1,
      },
    });

    await wrapper.trigger('click');
    expect(wrapper.emitted('click')).toHaveLength(1);

    await wrapper.trigger('keydown.enter');
    expect(wrapper.emitted('click')).toHaveLength(2);
  });
});
