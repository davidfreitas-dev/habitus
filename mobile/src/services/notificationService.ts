import { LocalNotifications, type LocalNotificationSchema } from '@capacitor/local-notifications';
import { Capacitor } from '@capacitor/core';
import type { Habit } from '@/types';

const generateNotificationId = (
  habitId: number | string,
  weekday: number | string,
  hour: number,
  minute: number
): number => {
  // Deterministic 32-bit signed int without collisions for (habitId, weekday 0..6, minuteOfDay 0..1439)
  const safeHabitId = Number(habitId) % 20000;
  const minuteOfDay = hour * 60 + minute;
  return safeHabitId * 100000 + Number(weekday) * 1440 + minuteOfDay;
};

const titles = [
  'Ei, psiu! ✨',
  'Bora focar? 💪',
  'Hora do show! 🚀',
  'Olha só quem chegou... 👀',
  'Momento Habitus! 🌿'
];

const messages = [
  'Que tal dar aquele check em "{title}" agora?',
  'O seu eu do futuro vai te agradecer por fazer "{title}"!',
  'Nada de preguiça! Vamos de "{title}"? 🔥',
  'Passando para te lembrar do seu compromisso: "{title}".',
  'Um passo de cada vez! Hora de "{title}".'
];

const CHANNEL_ID = 'habits_reminders_high';

const getRandomItem = <T>(array: T[]): T => array[Math.floor(Math.random() * array.length)];

export const notificationService = {
  async ensureNotificationChannel() {
    if (!Capacitor.isNativePlatform()) return;
    try {
      await LocalNotifications.createChannel({
        id: CHANNEL_ID,
        name: 'Lembretes de Hábitos',
        description: 'Notificações importantes para lembrar de realizar seus hábitos.',
        importance: 5, // IMPORTANCE_MAX (Android 8.0+): Toca som e aparece na tela
        visibility: 1, // VISIBILITY_PUBLIC (Mostra conteúdo na lock screen)
        vibration: true,
        sound: 'default',
        lights: true,
        lightColor: '#a3e635'
      });
    } catch (error) {
      console.error('Erro ao criar canal de notificações:', error);
    }
  },

  async scheduleHabitNotifications(habit: Pick<Habit, 'id' | 'title' | 'week_days' | 'reminders'>) {
    if (!Capacitor.isNativePlatform()) return;

    await this.cancelHabitNotifications(habit.id);

    if (!habit.reminders || habit.reminders.length === 0 || !habit.week_days || habit.week_days.length === 0) {
      return;
    }

    await this.ensureNotificationChannel();

    const activeWeekDays = new Set(habit.week_days.map(Number));
    const notifications: LocalNotificationSchema[] = [];

    // habit.week_days from API is [0, 1, 2, 3, 4, 5, 6] (0=Sunday, ..., 6=Saturday)
    for (const reminder of habit.reminders) {
      const weekDay = Number(reminder.week_day);
      if (!activeWeekDays.has(weekDay)) continue;

      const [hour, minute] = reminder.time.split(':').map(Number);
      if (Number.isNaN(hour) || Number.isNaN(minute)) continue;

      const notificationId = generateNotificationId(habit.id, weekDay, hour, minute);
      const title = getRandomItem(titles);
      const body = getRandomItem(messages).replace('{title}', habit.title);

      // Capacitor weekday is 1-7 (1=Sunday, 2=Monday, ..., 7=Saturday)
      const capacitorWeekday = weekDay + 1;

      notifications.push({
        id: notificationId,
        title,
        body,
        channelId: CHANNEL_ID,
        schedule: {
          on: {
            weekday: capacitorWeekday,
            hour,
            minute,
          },
          repeats: true,
          allowWhileIdle: true,
        },
        extra: {
          habitId: habit.id,
        },
        largeIcon: 'ic_stat_habitus',
        smallIcon: 'ic_stat_habitus',
        sound: 'default',
      });
    }

    if (notifications.length > 0) {
      await LocalNotifications.schedule({ notifications });
    }
  },

  async cancelHabitNotifications(habitId: number | string | null | undefined) {
    if (!Capacitor.isNativePlatform() || !habitId) return;

    try {
      const pending = await LocalNotifications.getPending();
      const targetId = Number(habitId);
      const notificationsToCancel = pending.notifications
        .filter((n) => Number(n.extra?.habitId) === targetId)
        .map((n) => ({ id: n.id }));

      if (notificationsToCancel.length > 0) {
        await LocalNotifications.cancel({ notifications: notificationsToCancel });
      }
    } catch (error) {
      console.error('Erro ao cancelar notificações:', error);
    }
  },

  async cancelAllNotifications() {
    if (!Capacitor.isNativePlatform()) return;
    try {
      const pending = await LocalNotifications.getPending();
      if (pending.notifications.length > 0) {
        await LocalNotifications.cancel(pending);
      }
    } catch (error) {
      console.error('Erro ao cancelar todas as notificações:', error);
    }
  },

  async rescheduleAllNotifications(habits: Habit[]) {
    if (!Capacitor.isNativePlatform()) return;
    try {
      await this.cancelAllNotifications();

      for (const habit of habits) {
        await this.scheduleHabitNotifications(habit);
      }
    } catch (error) {
      console.error('Erro ao reagendar notificações:', error);
    }
  }
};
