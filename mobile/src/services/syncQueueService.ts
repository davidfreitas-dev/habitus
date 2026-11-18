import { Preferences } from '@capacitor/preferences';
import type { SyncQueueItem } from '@/types';

export const SYNC_QUEUE_KEY = 'habitus_pending_sync_queue';

export const syncQueueService = {
  async getQueue(): Promise<SyncQueueItem[]> {
    try {
      const { value } = await Preferences.get({ key: SYNC_QUEUE_KEY });
      if (!value) {
        return [];
      }
      const parsed = JSON.parse(value) as unknown;
      return Array.isArray(parsed) ? (parsed as SyncQueueItem[]) : [];
    } catch (error) {
      console.error('Error reading offline sync queue:', error);
      return [];
    }
  },

  async saveQueue(queue: SyncQueueItem[]): Promise<void> {
    try {
      if (queue.length === 0) {
        await Preferences.remove({ key: SYNC_QUEUE_KEY });
      } else {
        await Preferences.set({
          key: SYNC_QUEUE_KEY,
          value: JSON.stringify(queue),
        });
      }
    } catch (error) {
      console.error('Error saving offline sync queue:', error);
    }
  },

  async enqueueToggle(
    habitId: number | string,
    date: string
  ): Promise<{ enqueued: boolean; queue: SyncQueueItem[] }> {
    const queue = await this.getQueue();
    const existingIndex = queue.findIndex(
      item =>
        item.type === 'TOGGLE_HABIT' &&
        String(item.habitId) === String(habitId) &&
        item.date === date
    );

    // If already in queue, the user toggled it again offline: cancel both out
    if (existingIndex !== -1) {
      queue.splice(existingIndex, 1);
      await this.saveQueue(queue);
      return { enqueued: false, queue };
    }

    const newItem: SyncQueueItem = {
      id: `${Date.now()}_${Math.random().toString(36).slice(2, 9)}`,
      type: 'TOGGLE_HABIT',
      habitId,
      date,
      timestamp: Date.now(),
    };

    queue.push(newItem);
    await this.saveQueue(queue);
    return { enqueued: true, queue };
  },

  async remove(id: string): Promise<SyncQueueItem[]> {
    const queue = await this.getQueue();
    const updated = queue.filter(item => item.id !== id);
    await this.saveQueue(updated);
    return updated;
  },

  async clear(): Promise<void> {
    try {
      await Preferences.remove({ key: SYNC_QUEUE_KEY });
    } catch (error) {
      console.error('Error clearing offline sync queue:', error);
    }
  },

  async getCount(): Promise<number> {
    const queue = await this.getQueue();
    return queue.length;
  },
};
