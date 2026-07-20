import { LocalNotifications } from '@capacitor/local-notifications';

const generateNotificationId = (habitId, weekday) => {
  // weekday is 0-6 (0=Sun, 1=Mon, ..., 6=Sat)
  // We use a formula to avoid collisions: habitId * 7 + weekday
  // This ensures unique IDs as long as habitId is unique.
  return Number(habitId) * 7 + Number(weekday);
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

const getRandomItem = (array) => array[Math.floor(Math.random() * array.length)];

export const NotificationService = {
  async ensureNotificationChannel() {
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

  async scheduleHabitNotifications(habit) {
    await this.cancelHabitNotifications(habit.id);

    if (!habit.reminder_time || !habit.week_days || habit.week_days.length === 0) {
      return;
    }

    await this.ensureNotificationChannel();

    const [hour, minute] = habit.reminder_time.split(':').map(Number);
    const notifications = [];

    // habit.week_days from API is [0, 1, 2, 3, 4, 5, 6] (0=Sunday, ..., 6=Saturday)
    for (const weekDay of habit.week_days) {
      const notificationId = generateNotificationId(habit.id, weekDay);
      const title = getRandomItem(titles);
      const body = getRandomItem(messages).replace('{title}', habit.title);
      
      // Capacitor weekday is 1-7 (1=Sunday, 2=Monday, ..., 7=Saturday)
      const capacitorWeekday = Number(weekDay) + 1;

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
        largeIcon: 'ic_stat_habitus',
        smallIcon: 'ic_stat_habitus',
        sound: 'default',
      });
    }

    if (notifications.length > 0) {
      await LocalNotifications.schedule({ notifications });
    }
  },

  async cancelHabitNotifications(habitId) {
    if (!habitId) return;
    
    const notificationsToCancel = [];
    for (let i = 0; i <= 6; i++) {
      notificationsToCancel.push({ id: generateNotificationId(habitId, i) });
    }
    
    try {
      await LocalNotifications.cancel({ notifications: notificationsToCancel });
    } catch (error) {
      console.error('Erro ao cancelar notificações:', error);
    }
  },

  async rescheduleAllNotifications(habits) {
    try {
      const pending = await LocalNotifications.getPending();
      if (pending.notifications.length > 0) {
        await LocalNotifications.cancel(pending);
      }

      for (const habit of habits) {
        await this.scheduleHabitNotifications(habit);
      }
    } catch (error) {
      console.error('Erro ao reagendar notificações:', error);
    }
  }
};

